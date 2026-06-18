require('dotenv').config();

const axios = require('axios');
const express = require('express');
const qrcode = require('qrcode-terminal');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.use(express.json());

const port = Number(process.env.PORT || 3099);
const pollIntervalMs = Number(process.env.POLL_INTERVAL_MS || 10000);
const laravelApiUrl = (process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000/api').replace(/\/$/, '');
const laravelApiToken = process.env.LARAVEL_API_TOKEN || '';
const startedAt = new Date().toISOString();
let whatsappReady = false;

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

const client = new Client({
  authStrategy: new LocalAuth({ clientId: process.env.WHATSAPP_SESSION_NAME || 'autorc-manager' }),
  puppeteer: {
    executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || undefined,
    timeout: Number(process.env.PUPPETEER_TIMEOUT_MS || 120000),
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
});

client.on('qr', (qr) => {
  console.log('Scan this QR code with WhatsApp:');
  qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
  whatsappReady = true;
  console.log('WhatsApp client ready.');
});

client.on('message_create', async (message) => {
  if (!message.fromMe) return;
  if (!message.body || !message.body.trim()) return;

  const messageId = message.id && message.id._serialized;
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
      phone: message.to.replace('@c.us', ''),
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

client.on('message', async (message) => {
  if (message.fromMe) return;
  if (!message.body || !message.body.trim()) return;

  try {
    const contact = await message.getContact();
    const response = await api.post('/whatsapp/incoming-message', {
      channel: 'whatsapp',
      phone: message.from.replace('@c.us', ''),
      name: contact.pushname || contact.name || null,
      message: message.body,
      message_id: message.id && message.id._serialized,
      metadata: {
        whatsapp_from: message.from,
        whatsapp_to: message.to,
        timestamp: message.timestamp,
      },
    });

    if (response.data && response.data.reply) {
      rememberAssistantTarget(message.from);
      const sent = await message.reply(response.data.reply);
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
});

async function pollOutgoingMessages() {
  if (!whatsappReady) return;

  try {
    const response = await api.get('/whatsapp/outgoing-messages', {
      params: { created_after: startedAt },
    });
    const messages = response.data && response.data.data ? response.data.data : [];

    for (const item of messages) {
      if (!item.phone || !item.message) continue;

      const chatId = item.phone.includes('@c.us') ? item.phone : `${item.phone.replace(/\D/g, '')}@c.us`;
      try {
        rememberAssistantTarget(chatId);
        const sent = await client.sendMessage(chatId, item.message);
        rememberAssistantMessage(sent.id && sent.id._serialized);
        await api.post(`/whatsapp/outgoing-messages/${item.id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { polled: true },
        });
      } catch (sendError) {
        await api.post(`/whatsapp/outgoing-messages/${item.id}/failed`, {
          error: sendError.message,
          metadata: { polled: true },
        });
        console.error(`Failed to send pending message ${item.id}:`, sendError.message);
      }
    }
  } catch (error) {
    console.error('Polling outgoing messages failed:', error.response ? error.response.data : error.message);
  }
}

async function pollLeadNotifications() {
  if (!whatsappReady) return;

  try {
    const response = await api.get('/whatsapp/lead-notifications');
    const messages = response.data && response.data.data ? response.data.data : [];

    for (const item of messages) {
      if (!item.phone || !item.message) continue;

      const chatId = item.phone.includes('@c.us') ? item.phone : `${item.phone.replace(/\D/g, '')}@c.us`;
      try {
        rememberAssistantTarget(chatId);
        const sent = await client.sendMessage(chatId, item.message);
        rememberAssistantMessage(sent.id && sent.id._serialized);
        await api.post(`/whatsapp/lead-notifications/${item.id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { polled: true },
        });
      } catch (sendError) {
        await api.post(`/whatsapp/lead-notifications/${item.id}/failed`, {
          error: sendError.message,
          metadata: { polled: true },
        });
        console.error(`Failed to send lead notification ${item.id}:`, sendError.message);
      }
    }
  } catch (error) {
    console.error('Polling lead notifications failed:', error.response ? error.response.data : error.message);
  }
}

app.get('/health', (req, res) => {
  res.json({ ok: true, laravelApiUrl });
});

app.post('/simulate-incoming', async (req, res) => {
  try {
    const response = await api.post('/whatsapp/incoming-message', req.body);
    res.json(response.data);
  } catch (error) {
    res.status(error.response ? error.response.status : 500).json(error.response ? error.response.data : { message: error.message });
  }
});

client.initialize();
setInterval(pollOutgoingMessages, pollIntervalMs);
setInterval(pollLeadNotifications, pollIntervalMs);

app.listen(port, () => {
  console.log(`AutoRC WhatsApp Node server listening on ${port}`);
  console.log(`Laravel API: ${laravelApiUrl}`);
});
