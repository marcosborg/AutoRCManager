# AutoRC WhatsApp Node Server - Windows

Este pacote liga o WhatsApp Web ao Laravel de producao:

```text
https://autorcmanager.pt/api
```

## Requisitos

1. Windows 10/11.
2. Google Chrome instalado.
3. Node.js LTS instalado: https://nodejs.org/
4. Acesso ao WhatsApp do numero definitivo da AutoRC.

## Instalar

1. Extrai o zip para uma pasta simples, por exemplo:

```text
C:\AutoRC\whatsapp-server
```

2. Abre `install-windows.bat`.
3. Confirma que o ficheiro `.env` tem estes valores:

```env
LARAVEL_API_URL=https://autorcmanager.pt/api
LARAVEL_API_TOKEN=igual_ao_NODE_API_TOKEN_do_laravel_producao
PORT=3099
POLL_INTERVAL_MS=10000
WHATSAPP_SESSION_NAME=autorc-913203600
```

## Iniciar

Abre:

```text
start-windows.bat
```

No primeiro arranque aparece um QR code no terminal. No telemovel:

```text
WhatsApp -> Dispositivos associados -> Associar dispositivo
```

Depois de aparecer:

```text
WhatsApp client ready.
```

o servidor fica pronto.

## Importante

- Mantem a janela do `start-windows.bat` aberta.
- Se fechares a janela, o WhatsApp deixa de responder.
- Nao precisas de Laravel local no Windows; este Node usa o Laravel em producao.
- A pasta `.wwebjs_auth` sera criada automaticamente no Windows depois do QR. Essa pasta guarda a sessao WhatsApp desse PC.
