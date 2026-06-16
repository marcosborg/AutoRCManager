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

const client = new Client({
  authStrategy: new LocalAuth({ clientId: process.env.WHATSAPP_SESSION_NAME || 'autorc-manager' }),
  puppeteer: {
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  },
});

client.on('qr', (qr) => {
  console.log('Scan this QR code with WhatsApp:');
  qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
  console.log('WhatsApp client ready.');
});

client.on('message', async (message) => {
  if (message.fromMe) return;

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
      const sent = await message.reply(response.data.reply);
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
  try {
    const response = await api.get('/whatsapp/outgoing-messages');
    const messages = response.data && response.data.data ? response.data.data : [];

    for (const item of messages) {
      if (!item.phone || !item.message) continue;

      const chatId = item.phone.includes('@c.us') ? item.phone : `${item.phone.replace(/\D/g, '')}@c.us`;
      try {
        const sent = await client.sendMessage(chatId, item.message);
        await api.post(`/whatsapp/outgoing-messages/${item.id}/sent`, {
          external_id: sent.id && sent.id._serialized,
          metadata: { polled: true },
        });
      } catch (sendError) {
        console.error(`Failed to send pending message ${item.id}:`, sendError.message);
      }
    }
  } catch (error) {
    console.error('Polling outgoing messages failed:', error.response ? error.response.data : error.message);
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

app.listen(port, () => {
  console.log(`AutoRC WhatsApp Node server listening on ${port}`);
  console.log(`Laravel API: ${laravelApiUrl}`);
});
