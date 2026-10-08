# Reconciliação de produção — 8 de outubro de 2026

## Origem da divergência

O Git do cPanel estava em `571bf18c`, enquanto o último marcador de FTP era
`9384fffc`. Os ficheiros tinham recebido outras atualizações por FTP: o historial
Git do alojamento não representava a versão efetivamente em execução.

A comparação de 850 ficheiros versionados encontrou 833 iguais à `main`
`508773b4`, nove diferentes e oito ausentes. Foram comparados código PHP,
vistas, rotas, configurações versionadas, migrações, testes, documentação,
scripts e os manifestos e locks das dependências. Diretórios de dependências,
dados, sessões, logs, uploads e exportações não fazem parte desta comparação.

## Resolução

- Preservar e versionar a proteção já existente em produção que impede uma nova
  saudação automática quando a mesma lead é recebida novamente.
- Publicar a restrição já existente na `main` que limita o catálogo de pintura
  aos trabalhos do pintor autenticado, salvo utilizadores com permissão de gestão.
- Corrigir os dados do teste de pintura para MySQL: viatura, data de entrada e
  contagem independente dos trabalhos já existentes.
- Alinhar os restantes ficheiros versionados comparados com a versão integrada.
  A definição SQLite é mantida para os testes que a utilizam; a ligação MySQL
  e os valores de ambiente de produção permanecem inalterados.
- Preservar integralmente o `.htaccess` gerado pelo cPanel. É uma diferença
  esperada do alojamento, não um ficheiro a substituir numa publicação.
- Arquivar o controlador antigo encontrado fora da pasta `Controllers`, cuja
  classe já é carregada pelo Composer a partir da localização correta, e a
  cópia antiga `.bak` fora das pastas de código ativo.

Não há migrações novas nesta reconciliação. O PR das consignações é separado.

## Publicações seguintes

1. Trabalhar e testar numa base local separada; criar commits por correção.
2. Comparar os ficheiros efetivos do servidor antes de publicar. Um marcador
   Git ou FTP isolado não comprova a versão instalada.
3. Preservar alterações exclusivas de produção antes de alinhar versões.
4. Guardar cópia dos ficheiros e metadados afetados em armazenamento privado.
5. Publicar a versão integrada, verificar os hashes por leitura do servidor e
   confirmar as páginas afetadas. Preservar `.env`, `.htaccess`, dados e uploads.
6. Só depois atualizar as referências de versão. Registar o commit, os hashes,
   as exceções e a cópia de segurança num manifesto privado da publicação.

O cPanel não tem publicação automática configurada. Um merge no GitHub, por si
só, não atualiza a aplicação. Não usar um `reset --hard` nem um upload global
para esconder diferenças do alojamento. Não tratar dependências instaladas e
ficheiros de execução como se tivessem sido auditados por esta comparação.
