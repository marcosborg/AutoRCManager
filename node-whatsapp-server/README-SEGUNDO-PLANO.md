# AutoRC WhatsApp em segundo plano

Esta pasta inclui uma forma de correr o `node index.js` sem janela visivel e com reinicio automatico se o Node crashar.

## Instalar e iniciar

Abre:

```text
install-background-startup.bat
```

Isto faz duas coisas:

1. Cria um atalho no Arranque do Windows para iniciar automaticamente quando o utilizador entra no Windows.
2. Inicia logo o servidor em segundo plano.

## Parar

Abre:

```text
stop-background.bat
```

## Remover do arranque automatico

Abre:

```text
uninstall-background-startup.bat
```

## Logs

Os logs ficam na pasta:

```text
logs
```

Ficheiros principais:

- `whatsapp-node.out.log`: mensagens normais do servidor.
- `whatsapp-node.err.log`: erros do servidor.
- `whatsapp-node-restarts.log`: arranques, paragens e reinicios automaticos.

## QR code

Se for preciso ler um QR novo, para o modo de segundo plano com `stop-background.bat` e corre manualmente:

```text
node index.js
```

Depois de autenticar, podes voltar a iniciar em segundo plano com `install-background-startup.bat`.
