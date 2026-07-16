require('dotenv').config();

const axios = require('axios');
const express = require('express');
const fs = require('fs');
const path = require('path');
const qrcode = require('qrcode-terminal');
const QRCode = require('qrcode-terminal/vendor/QRCode');
const QRErrorCorrectLevel = require('qrcode-terminal/vendor/QRCode/QRErrorCorrectLevel');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.use(express.json());

const port = Number(process.env.PORT || 3099);
const pollIntervalMs = Number(process.env.POLL_INTERVAL_MS || 10000);
const laravelApiUrl = (process.env.LARAVEL_API_URL || 'https://autorcmanager.pt/api').replace(/\/$/, '');
const laravelApiToken = process.env.LARAVEL_API_TOKEN || '';
const startedAt = new Date().toISOString();
let whatsappReady = false;
let whatsappState = 'starting';
let pollingOutgoing = false;
let pollingLeadNotifications = false;

function saveQrSvg(value) {
  const qr = new QRCode(-1, QRErrorCorrectLevel.L);
  qr.addData(value);
  qr.make();
  const quietZone = 4;
  const size = qr.getModuleCount();
  const paths = [];
  for (let row = 0; row < size; row += 1) {
    for (let col = 0; col < size; col += 1) {
      if (qr.isDark(row, col)) paths.push(`M${col + quietZone} ${row + quietZone}h1v1h-1z`);
    }
  }
  const fullSize = size + quietZone * 2;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${fullSize} ${fullSize}" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="white"/><path d="${paths.join('')}" fill="black"/></svg>`;
  fs.writeFileSync(path.join(__dirname, 'logs', 'whatsapp-qr.svg'), svg, 'utf8');
}
const puppeteerHeadless = process.env.PUPPETEER_HEADLESS === undefined
  ? true
  : !['false', '0', 'no'].includes(String(process.env.PUPPETEER_HEADLESS).toLowerCase());
const whatsappUserAgent = process.env.WHATSAPP_USER_AGENT
  || 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';

console.log('AutoRC WhatsApp bridge version: resolve-chatid-20260625-v2');

if (!laravelApiToken) {
  console.warn('LARAVEL_API_TOKEN is empty. Laravel will reject requests protected by node.token.');
}

const api = axios.create({
  baseURL: laravelApiUrl,
  timeout: 30000,
  headers: {
    Authorization: `Bearer ${laravelApiToken}`,
    Accept: 'application/json',
  },
});

const recentlySentByAssistant = new Set();
const assistantSendTargets = new Set();
const processedIncomingMessages = new Set();
const messageAckById = new Map();
const ackWaitMs = Number(process.env.WHATSAPP_ACK_WAIT_MS || 30000);

function rememberAssistantMessage(messageId) {
  if (!messageId) return;

  recentlySentByAssistant.add(messageId);
  setTimeout(() => recentlySentByAssistant.delete(messageId), 5 * 60 * 1000);
}

function rememberAssistantTarget(chatId) {
  if (!chatId) return;

  assistantSendTargets.add(chatId);
  setTimeout(() => assistantSendTargets.delete(chatId), 30 * 1000);
}

function isCustomerChatId(chatId) {
  return typeof chatId === 'string' && (chatId.endsWith('@c.us') || chatId.endsWith('@lid'));
}

function chatIdToPhone(chatId, contact, chat) {
  if (contact && contact.number) return contact.number;
  if (chat && chat.name && /^\+?\d[\d\s().-]+$/.test(chat.name)) {
    return chat.name.replace(/\D/g, '');
  }
  return String(chatId || '').replace(/@(c\.us|lid)$/, '');
}

async function resolveChatIdForPhone(phone) {
  const digits = String(phone || '').replace(/\D/g, '');
  if (!digits) return null;

  if (String(phone).includes('@c.us') || String(phone).includes('@lid')) {
    return String(phone);
  }

  if (digits.length > 12 && !digits.startsWith('351')) {
    return `${digits}@lid`;
  }

  const numberId = await client.getNumberId(digits);
  return numberId && numberId._serialized ? numberId._serialized : null;
}

function rememberIncomingMessage(messageId) {
  if (!messageId) return;

  processedIncomingMessages.add(messageId);
  setTimeout(() => processedIncomingMessages.delete(messageId), 60 * 60 * 1000);
}

function truncateForLog(value, maxLength = 500) {
  if (value === undefined || value === null) return '';

  const text = String(value);
  return text.length > maxLength ? `${text.slice(0, maxLength)}...<truncated>` : text;
}

function safeJsonForLog(value, maxLength = 1500) {
  try {
    return truncateForLog(JSON.stringify(value || {}), maxLength);
  } catch (error) {
    return `unserializable: ${error.message}`;
  }
}

function rememberMessageAck(messageId, ack) {
  if (!messageId) return;

  messageAckById.set(messageId, ack);
  setTimeout(() => messageAckById.delete(messageId), 10 * 60 * 1000);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForConfirmedAck(chatId, sent, waitMs = ackWaitMs) {
  const messageId = sent && sent.id && sent.id._serialized;
  let lastAck = sent && sent.ack !== undefined ? sent.ack : undefined;
  let found = Boolean(messageId);
  let lastError = null;
  const deadline = Date.now() + waitMs;

  if (messageId && messageAckById.has(messageId)) {
    lastAck = messageAckById.get(messageId);
  }

  while (Date.now() < deadline) {
    if (messageId && messageAckById.has(messageId)) {
      lastAck = messageAckById.get(messageId);
      if (Number(lastAck) >= 1) break;
    }

    try {
      const chat = await client.getChatById(chatId);
      const messages = await chat.fetchMessages({ limit: 20 });
      const refreshed = messages.find((item) => item.id && item.id._serialized === messageId);
      if (refreshed) {
        found = true;
        lastAck = refreshed.ack;
        rememberMessageAck(messageId, lastAck);
        if (Number(lastAck) >= 1) break;
      }
    } catch (error) {
      lastError = error;
    }

    await sleep(1000);
  }

  return {
    ack: lastAck,
    confirmed: Number(lastAck) >= 1,
    failed: lastAck !== undefined && Number(lastAck) < 0,
    pending: lastAck === undefined || Number(lastAck) === 0,
    found,
    error: lastError ? lastError.message : null,
  };
}

function unconfirmedAckError(ackResult) {
  if (ackResult.failed) {
    return `WhatsApp rejected the message after sendMessage (ack=${ackResult.ack})`;
  }

  return `WhatsApp did not confirm the message after ${ackWaitMs}ms (ack=${ackResult.ack === undefined ? 'unknown' : ackResult.ack})`;
}

async function refreshWhatsappState() {
  try {
    const state = await client.getState();
    if (state) {
      whatsappState = state;
      whatsappReady = state === 'CONNECTED';
    }
    return state;
  } catch (error) {
    return null;
  }
}

let restartScheduled = false;
function scheduleProcessRestart(reason, delayMs = 5000) {
  if (restartScheduled) return;
  restartScheduled = true;
  console.error(`Scheduling process restart in ${delayMs}ms: ${reason}`);
  setTimeout(() => {
    process.exit(1);
  }, delayMs).unref();
}

const client = new Client({
  authStrategy: new LocalAuth({ clientId: process.env.WHATSAPP_SESSION_NAME || 'autorc-manager' }),
  userAgent: whatsappUserAgent,
  puppeteer: {
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
    headless: puppeteerHeadless,
    timeout: Number(process.env.PUPPETEER_TIMEOUT_MS || 120000),
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
});

const pairingPhone = String(process.env.WHATSAPP_PAIRING_PHONE || '').replace(/\D/g, '');
let pairingCodeRequested = false;

client.on('qr', async (qr) => {
  whatsappState = 'qr';
  if (pairingPhone) {
    if (pairingCodeRequested) return;
    pairingCodeRequested = true;
    try {
      const code = await client.requestPairingCode(pairingPhone);
      whatsappState = 'pairing_code';
      console.log(`WhatsApp pairing code: ${code}`);
    } catch (error) {
      pairingCodeRequested = false;
      console.error('Failed to request WhatsApp pairing code:', error);
    }
    return;
  }
  fs.writeFileSync(path.join(__dirname, 'logs', 'latest-qr.txt'), qr, 'utf8');
  saveQrSvg(qr);
  console.log('Scan this QR code with WhatsApp:');
  qrcode.generate(qr, { small: true });
});

client.on('code_received', (code) => {
  whatsappState = 'pairing_code';
  console.log(`WhatsApp pairing code: ${code}`);
});

client.on('loading_screen', (percent, message) => {
  console.log(`WhatsApp loading ${percent}%: ${message}`);
});

client.on('authenticated', () => {
  whatsappState = 'authenticated';
  console.log('WhatsApp authenticated.');
  setTimeout(refreshWhatsappState, 10000);
});

client.on('auth_failure', (message) => {
  whatsappState = 'auth_failure';
  console.error('WhatsApp authentication failed:', message);
  scheduleProcessRestart('WhatsApp authentication failed');
});

client.on('ready', () => {
  whatsappReady = true;
  whatsappState = 'ready';
  console.log('WhatsApp client ready.');
});

client.on('disconnected', (reason) => {
  whatsappReady = false;
  whatsappState = 'disconnected';
  console.error('WhatsApp client disconnected:', reason);
  scheduleProcessRestart(`WhatsApp client disconnected: ${reason}`);
});

client.on('message_ack', (message, ack) => {
  const messageId = message && message.id ? message.id._serialized : null;
  rememberMessageAck(messageId, ack);
  console.log(`Message ack: id=${messageId || 'unknown'} to=${message ? message.to : 'unknown'} ack=${ack}`);
});

process.on('unhandledRejection', (error) => {
  console.error('Unhandled promise rejection:', error);
  process.exit(1);
});

process.on('uncaughtException', (error) => {
  console.error('Uncaught exception:', error);
  process.exit(1);
});

client.on('message_create', async (message) => {
  console.log(`Message create event: fromMe=${message.fromMe} from=${message.from} to=${message.to} hasBody=${Boolean(message.body && message.body.trim())}`);
  if (!message.fromMe) return;
  if (!message.body || !message.body.trim()) return;
  if (!isCustomerChatId(message.to)) {
    console.log(`Ignoring outgoing message to non-customer chat: ${message.to}`);
    return;
  }

  const messageId = message.id && message.id._serialized;
  const contact = await message.getContact().catch(() => null);
  if (recentlySentByAssistant.has(messageId)) {
    return;
  }

  if (assistantSendTargets.has(message.to)) {
    assistantSendTargets.delete(message.to);
    rememberAssistantMessage(messageId);
    return;
  }

  try {
    await api.post('/whatsapp/human-outgoing-message', {
      channel: 'whatsapp',
      phone: chatIdToPhone(message.to, contact),
      message: message.body,
      message_id: messageId,
      metadata: {
        whatsapp_from: message.from,
        whatsapp_to: message.to,
        timestamp: message.timestamp,
        source: 'whatsapp_from_me',
      },
    });
  } catch (error) {
    const status = error.response && error.response.status;
    if (status !== 404) {
      console.error('Human outgoing message failed:', error.response ? error.response.data : error.message);
    }
  }
});

async function handleIncomingMessage(message, source) {
  if (message.fromMe) {
    console.log(`Ignoring incoming event from own WhatsApp number: ${message.to || message.from}`);
    return;
  }
  if (!message.body || !message.body.trim()) {
    console.log(`Ignoring incoming message without text from ${message.from}`);
    return;
  }
  if (!isCustomerChatId(message.from)) {
    console.log(`Ignoring incoming message from non-customer chat: ${message.from}`);
    return;
  }

  const messageId = message.id && message.id._serialized;
  if (processedIncomingMessages.has(messageId)) {
    return;
  }
  rememberIncomingMessage(messageId);

  try {
    const contact = await message.getContact();
    const chat = await message.getChat().catch(() => null);
    console.log(`Incoming WhatsApp message from ${message.from} (${contact.number || 'no-number'}) via ${source}: ${message.body.slice(0, 80)}`);
    console.log(`Incoming debug: source=${source} from=${message.from} id=${messageId || 'none'} timestamp=${message.timestamp || 'none'} contact_id=${contact && contact.id ? contact.id._serialized : 'none'} contact_number=${contact ? contact.number || 'none' : 'none'} contact_pushname=${contact ? contact.pushname || 'none' : 'none'} contact_name=${contact ? contact.name || 'none' : 'none'} chat_id=${chat && chat.id ? chat.id._serialized : 'none'} chat_name=${chat ? chat.name || 'none' : 'none'} chat_isGroup=${chat ? chat.isGroup : 'unknown'} chat_unreadCount=${chat ? chat.unreadCount : 'unknown'}`);
    const response = await api.post('/whatsapp/incoming-message', {
      channel: 'whatsapp',
      phone: chatIdToPhone(message.from, contact, chat),
      name: contact.pushname || contact.name || null,
      message: message.body,
      message_id: messageId,
      metadata: {
        whatsapp_from: message.from,
        whatsapp_to: message.to,
        timestamp: message.timestamp,
        source,
      },
    });
    console.log(`Laravel incoming response: reply=${Boolean(response.data && response.data.reply)} message_id=${response.data && response.data.message_id ? response.data.message_id : 'none'} status=${response.data && response.data.status ? response.data.status : 'unknown'} human_takeover=${response.data && response.data.human_takeover !== undefined ? response.data.human_takeover : 'unknown'}`);

    if (response.data && response.data.reply) {
      rememberAssistantTarget(message.from);
      console.log(`Preparing AI reply: to=${message.from} method=message.reply preview=${truncateForLog(response.data.reply, 80)}`);
      const sent = await message.reply(response.data.reply);
      console.log(`Sent AI reply to ${message.from}`);
      console.log(`Sent AI reply debug: id=${sent && sent.id ? sent.id._serialized : 'none'} to=${sent ? sent.to || 'none' : 'none'} from=${sent ? sent.from || 'none' : 'none'} timestamp=${sent ? sent.timestamp || 'none' : 'none'} ack=${sent && sent.ack !== undefined ? sent.ack : 'unknown'} deviceType=${sent ? sent.deviceType || 'none' : 'none'} hasMedia=${sent ? sent.hasMedia : 'unknown'} rawData=${safeJsonForLog(sent ? sent.rawData : {})}`);
      const sentChat = await client.getChatById(message.from).catch((error) => {
        console.error(`Sent chat lookup failed for ${message.from}:`, error.message);
        return null;
      });
      if (sentChat) {
        console.log(`Sent chat debug: chat_id=${sentChat.id ? sentChat.id._serialized : 'none'} chat_name=${sentChat.name || 'none'} lastMessage_id=${sentChat.lastMessage && sentChat.lastMessage.id ? sentChat.lastMessage.id._serialized : 'none'} lastMessage_ack=${sentChat.lastMessage && sentChat.lastMessage.ack !== undefined ? sentChat.lastMessage.ack : 'unknown'} lastMessage_body=${truncateForLog(sentChat.lastMessage ? sentChat.lastMessage.body || '' : '', 120)}`);
      }
      rememberAssistantMessage(sent.id && sent.id._serialized);
      if (response.data.message_id) {
        await api.post(`/whatsapp/outgoing-messages/${response.data.message_id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { immediate_reply: true },
        });
      }
    }
  } catch (error) {
    console.error('Incoming message failed:', error.response ? error.response.data : error.message);
  }
}

client.on('message', async (message) => {
  await handleIncomingMessage(message, 'event');
});

async function pollOutgoingMessages() {
  if (!whatsappReady) return;
  if (pollingOutgoing) return;

  pollingOutgoing = true;

  try {
    const response = await api.get('/whatsapp/outgoing-messages', {
      params: { created_after: startedAt },
    });
    const messages = response.data && response.data.data ? response.data.data : [];

    for (const item of messages) {
      if (!item.phone || !item.message) continue;

      const chatId = await resolveChatIdForPhone(item.phone);
      if (!chatId) {
        await api.post(`/whatsapp/outgoing-messages/${item.id}/failed`, {
          error: 'Phone number is not registered on WhatsApp',
          metadata: { polled: true, requested_phone: item.phone },
        });
        console.error(`Failed to send pending message ${item.id}: phone not registered on WhatsApp (${item.phone}); requested_phone=${item.phone}`);
        continue;
      }
      if (!isCustomerChatId(chatId) || chatId === '@c.us') continue;
      try {
        rememberAssistantTarget(chatId);
        console.log(`Sending outgoing message ${item.id} to ${item.phone} via ${chatId}`);
        const sent = await client.sendMessage(chatId, item.message, { linkPreview: false });
        rememberAssistantMessage(sent.id && sent.id._serialized);
        const ackResult = await waitForConfirmedAck(chatId, sent);
        if (!ackResult.confirmed) {
          await api.post(`/whatsapp/outgoing-messages/${item.id}/failed`, {
            error: unconfirmedAckError(ackResult),
            metadata: {
              polled: true,
              target_chat_id: chatId,
              requested_phone: item.phone,
              external_id: sent.id && sent.id._serialized,
              ack: ackResult.ack,
              ack_found: ackResult.found,
              ack_error: ackResult.error,
            },
          });
          console.error(`Failed to confirm pending message ${item.id}: ack=${ackResult.ack === undefined ? 'unknown' : ackResult.ack} found=${ackResult.found} error=${ackResult.error || 'none'}; target_chat_id=${chatId} requested_phone=${item.phone}`);
          continue;
        }
        await api.post(`/whatsapp/outgoing-messages/${item.id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { polled: true, target_chat_id: chatId, requested_phone: item.phone, ack: ackResult.ack },
        });
        console.log(`Marked pending message ${item.id} sent: ack=${ackResult.ack} target_chat_id=${chatId} requested_phone=${item.phone}`);
      } catch (sendError) {
        await api.post(`/whatsapp/outgoing-messages/${item.id}/failed`, {
          error: sendError.message,
          metadata: { polled: true, target_chat_id: chatId, requested_phone: item.phone },
        });
        console.error(`Failed to send pending message ${item.id}: ${sendError.message}; target_chat_id=${chatId} requested_phone=${item.phone}`);
      }
    }
  } catch (error) {
    console.error('Polling outgoing messages failed:', error.response ? error.response.data : error.message);
  } finally {
    pollingOutgoing = false;
  }
}

async function pollUnreadIncomingMessages() {
  if (!whatsappReady) return;

  try {
    const chats = await client.getChats();
    const unreadChats = chats
      .filter((chat) => chat.unreadCount > 0 && chat.lastMessage && !chat.lastMessage.fromMe)
      .slice(0, 20);

    for (const chat of unreadChats) {
      const message = chat.lastMessage;
      if (!message || !message.body || !message.body.trim()) continue;

      await handleIncomingMessage(message, 'unread_poll');
      await chat.sendSeen().catch(() => null);
    }
  } catch (error) {
    console.error('Polling unread incoming messages failed:', error.message);
  }
}

async function pollLeadNotifications() {
  if (!whatsappReady) return;
  if (pollingLeadNotifications) return;

  pollingLeadNotifications = true;

  try {
    const response = await api.get('/whatsapp/lead-notifications');
    const messages = response.data && response.data.data ? response.data.data : [];

    for (const item of messages) {
      if (!item.phone || !item.message) continue;

      const chatId = await resolveChatIdForPhone(item.phone);
      if (!chatId) {
        await api.post(`/whatsapp/lead-notifications/${item.id}/failed`, {
          error: 'Phone number is not registered on WhatsApp',
          metadata: { polled: true, requested_phone: item.phone },
        });
        console.error(`Failed to send lead notification ${item.id}: phone not registered on WhatsApp (${item.phone}); requested_phone=${item.phone}`);
        continue;
      }
      if (!isCustomerChatId(chatId) || chatId === '@c.us') continue;
      try {
        rememberAssistantTarget(chatId);
        console.log(`Sending lead notification ${item.id} to ${item.phone} via ${chatId}`);
        const sent = await client.sendMessage(chatId, item.message, { linkPreview: false });
        rememberAssistantMessage(sent.id && sent.id._serialized);
        const ackResult = await waitForConfirmedAck(chatId, sent);
        if (!ackResult.confirmed) {
          await api.post(`/whatsapp/lead-notifications/${item.id}/failed`, {
            error: unconfirmedAckError(ackResult),
            metadata: {
              polled: true,
              target_chat_id: chatId,
              requested_phone: item.phone,
              external_id: sent.id && sent.id._serialized,
              ack: ackResult.ack,
              ack_found: ackResult.found,
              ack_error: ackResult.error,
            },
          });
          console.error(`Failed to confirm lead notification ${item.id}: ack=${ackResult.ack === undefined ? 'unknown' : ackResult.ack} found=${ackResult.found} error=${ackResult.error || 'none'}; target_chat_id=${chatId} requested_phone=${item.phone}`);
          continue;
        }
        await api.post(`/whatsapp/lead-notifications/${item.id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { polled: true, target_chat_id: chatId, requested_phone: item.phone, ack: ackResult.ack },
        });
        console.log(`Marked lead notification ${item.id} sent: ack=${ackResult.ack} target_chat_id=${chatId} requested_phone=${item.phone}`);
      } catch (sendError) {
        await api.post(`/whatsapp/lead-notifications/${item.id}/failed`, {
          error: sendError.message,
          metadata: { polled: true, target_chat_id: chatId, requested_phone: item.phone },
        });
        console.error(`Failed to send lead notification ${item.id}: ${sendError.message}; target_chat_id=${chatId} requested_phone=${item.phone}`);
      }
    }
  } catch (error) {
    console.error('Polling lead notifications failed:', error.response ? error.response.data : error.message);
  } finally {
    pollingLeadNotifications = false;
  }
}

app.get('/health', async (req, res) => {
  const liveState = await refreshWhatsappState();
  res.json({
    ok: true,
    laravelApiUrl,
    whatsappReady,
    whatsappState,
    liveState,
    startedAt,
  });
});

app.get('/debug/chats', async (req, res) => {
  if (!whatsappReady) {
    res.status(503).json({ ok: false, whatsappReady, whatsappState });
    return;
  }

  try {
    const chats = await client.getChats();
    res.json({
      ok: true,
      count: chats.length,
      chats: chats.slice(0, 10).map((chat) => ({
        id: chat.id && chat.id._serialized,
        name: chat.name,
        unreadCount: chat.unreadCount,
        timestamp: chat.timestamp,
        lastMessageFromMe: chat.lastMessage ? chat.lastMessage.fromMe : null,
        lastMessageFrom: chat.lastMessage ? chat.lastMessage.from : null,
        lastMessageTo: chat.lastMessage ? chat.lastMessage.to : null,
        lastMessageBody: chat.lastMessage && chat.lastMessage.body ? chat.lastMessage.body.slice(0, 80) : null,
      })),
    });
  } catch (error) {
    res.status(500).json({ ok: false, message: error.message });
  }
});

app.post('/debug/send', async (req, res) => {
  if (!whatsappReady) {
    res.status(503).json({ ok: false, whatsappReady, whatsappState });
    return;
  }

  const chatId = req.body && req.body.chatId;
  const message = req.body && req.body.message;
  if (!chatId || !message) {
    res.status(422).json({ ok: false, message: 'chatId and message are required' });
    return;
  }

  try {
    const chat = await client.getChatById(chatId);
    console.log(`Debug send chat: chat_id=${chat.id ? chat.id._serialized : 'none'} chat_name=${chat.name || 'none'} isGroup=${chat.isGroup} unreadCount=${chat.unreadCount} lastMessage_id=${chat.lastMessage && chat.lastMessage.id ? chat.lastMessage.id._serialized : 'none'} lastMessage_ack=${chat.lastMessage && chat.lastMessage.ack !== undefined ? chat.lastMessage.ack : 'unknown'} lastMessage_body=${truncateForLog(chat.lastMessage ? chat.lastMessage.body || '' : '', 120)}`);
    const sent = await client.sendMessage(chatId, message);
    console.log(`Debug send result: chatId=${chatId} id=${sent && sent.id ? sent.id._serialized : 'none'} to=${sent ? sent.to || 'none' : 'none'} from=${sent ? sent.from || 'none' : 'none'} timestamp=${sent ? sent.timestamp || 'none' : 'none'} ack=${sent && sent.ack !== undefined ? sent.ack : 'unknown'} rawData=${safeJsonForLog(sent ? sent.rawData : {})}`);
    res.json({
      ok: true,
      chat: {
        id: chat.id && chat.id._serialized,
        name: chat.name,
        isGroup: chat.isGroup,
        unreadCount: chat.unreadCount,
      },
      sent: {
        id: sent && sent.id && sent.id._serialized,
        to: sent && sent.to,
        from: sent && sent.from,
        timestamp: sent && sent.timestamp,
        ack: sent && sent.ack,
      },
    });
  } catch (error) {
    console.error(`Debug send failed: chatId=${chatId} error=${error.message}`);
    res.status(500).json({ ok: false, message: error.message });
  }
});

app.post('/debug/reply-last', async (req, res) => {
  if (!whatsappReady) {
    res.status(503).json({ ok: false, whatsappReady, whatsappState });
    return;
  }

  const chatId = req.body && req.body.chatId;
  const message = req.body && req.body.message;
  if (!chatId || !message) {
    res.status(422).json({ ok: false, message: 'chatId and message are required' });
    return;
  }

  try {
    const chat = await client.getChatById(chatId);
    const messages = await chat.fetchMessages({ limit: 20 });
    const lastIncoming = messages
      .filter((item) => !item.fromMe)
      .sort((a, b) => Number(b.timestamp || 0) - Number(a.timestamp || 0))[0];
    if (!lastIncoming) {
      res.status(404).json({ ok: false, message: 'No incoming message found in chat' });
      return;
    }

    console.log(`Debug reply-last source: chatId=${chatId} lastIncoming_id=${lastIncoming.id ? lastIncoming.id._serialized : 'none'} lastIncoming_from=${lastIncoming.from} lastIncoming_to=${lastIncoming.to} lastIncoming_body=${truncateForLog(lastIncoming.body || '', 120)}`);
    const sent = await lastIncoming.reply(message);
    console.log(`Debug reply-last result: chatId=${chatId} lastIncoming_id=${lastIncoming.id ? lastIncoming.id._serialized : 'none'} sent_id=${sent && sent.id ? sent.id._serialized : 'none'} to=${sent ? sent.to || 'none' : 'none'} from=${sent ? sent.from || 'none' : 'none'} ack=${sent && sent.ack !== undefined ? sent.ack : 'unknown'} rawData=${safeJsonForLog(sent ? sent.rawData : {})}`);

    await new Promise((resolve) => setTimeout(resolve, Number(req.body.waitMs || 10000)));

    const refreshedChat = await client.getChatById(chatId);
    const refreshedMessages = await refreshedChat.fetchMessages({ limit: 20 });
    const refreshedSent = refreshedMessages.find((item) => item.id && sent && sent.id && item.id._serialized === sent.id._serialized);
    console.log(`Debug reply-last ack after wait: sent_id=${sent && sent.id ? sent.id._serialized : 'none'} ack=${refreshedSent && refreshedSent.ack !== undefined ? refreshedSent.ack : 'not_found'} lastMessage_id=${refreshedChat.lastMessage && refreshedChat.lastMessage.id ? refreshedChat.lastMessage.id._serialized : 'none'} lastMessage_ack=${refreshedChat.lastMessage && refreshedChat.lastMessage.ack !== undefined ? refreshedChat.lastMessage.ack : 'unknown'} lastMessage_body=${truncateForLog(refreshedChat.lastMessage ? refreshedChat.lastMessage.body || '' : '', 120)}`);

    res.json({
      ok: true,
      lastIncoming: {
        id: lastIncoming.id && lastIncoming.id._serialized,
        from: lastIncoming.from,
        to: lastIncoming.to,
        body: truncateForLog(lastIncoming.body || '', 160),
      },
      sent: {
        id: sent && sent.id && sent.id._serialized,
        to: sent && sent.to,
        from: sent && sent.from,
        timestamp: sent && sent.timestamp,
        ack: sent && sent.ack,
      },
      afterWait: {
        ack: refreshedSent && refreshedSent.ack,
        found: Boolean(refreshedSent),
        lastMessageId: refreshedChat.lastMessage && refreshedChat.lastMessage.id && refreshedChat.lastMessage.id._serialized,
        lastMessageAck: refreshedChat.lastMessage && refreshedChat.lastMessage.ack,
        lastMessageBody: truncateForLog(refreshedChat.lastMessage ? refreshedChat.lastMessage.body || '' : '', 160),
      },
    });
  } catch (error) {
    console.error(`Debug reply-last failed: chatId=${chatId} error=${error.message}`);
    res.status(500).json({ ok: false, message: error.message });
  }
});

app.post('/debug/chat-messages', async (req, res) => {
  if (!whatsappReady) {
    res.status(503).json({ ok: false, whatsappReady, whatsappState });
    return;
  }

  const chatId = req.body && req.body.chatId;
  if (!chatId) {
    res.status(422).json({ ok: false, message: 'chatId is required' });
    return;
  }

  try {
    const chat = await client.getChatById(chatId);
    const messages = await chat.fetchMessages({ limit: Number(req.body.limit || 10) });
    res.json({
      ok: true,
      chat: {
        id: chat.id && chat.id._serialized,
        name: chat.name,
        isGroup: chat.isGroup,
        unreadCount: chat.unreadCount,
      },
      messages: messages.map((item) => ({
        id: item.id && item.id._serialized,
        from: item.from,
        to: item.to,
        fromMe: item.fromMe,
        timestamp: item.timestamp,
        ack: item.ack,
        body: truncateForLog(item.body || '', 160),
      })),
    });
  } catch (error) {
    console.error(`Debug chat messages failed: chatId=${chatId} error=${error.message}`);
    res.status(500).json({ ok: false, message: error.message });
  }
});

app.post('/simulate-incoming', async (req, res) => {
  try {
    const response = await api.post('/whatsapp/incoming-message', req.body);
    res.json(response.data);
  } catch (error) {
    res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { message: error.message });
  }
});

client.initialize().catch((error) => {
  console.error('WhatsApp client initialization failed:', error);
  process.exit(1);
});
setInterval(pollOutgoingMessages, pollIntervalMs);
setInterval(pollLeadNotifications, pollIntervalMs);
setInterval(pollUnreadIncomingMessages, 5000);
setInterval(refreshWhatsappState, 15000);

const server = app.listen(port, () => {
  console.log(`AutoRC WhatsApp Node server listening on ${port}`);
  console.log(`Laravel API: ${laravelApiUrl}`);
});

server.on('error', (error) => {
  console.error('HTTP server failed:', error);
  process.exit(1);
});
