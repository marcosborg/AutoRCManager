# Declaração de venda Car7

Primeira versão para conferência com a Dina, baseada no modelo recebido por WhatsApp em 6 de novembro de 2026. Original preservado em `resources/documents/car7/declaracao-venda-original.docx`; logótipo extraído desse ficheiro. Não foi efetuada uma revisão jurídica do texto.

## Utilização

Na ficha da viatura, abrir **Documentos da viatura → Preparar declaração de venda**. O cliente é o `client_id` usado no separador de venda, não o fornecedor da compra. Os dados são sugestões editáveis apenas no impresso. Os montantes ficam em branco até serem confirmados; não se infere um sinal a partir dos pagamentos existentes nem se confunde PVP com preço acordado.

Confirmar que a vendedora é Geração Determinada, rever dados e descarregar o PDF. O rascunho fica na sessão, isolado por utilizador e viatura; não é um arquivo durável. Imprimir não conclui a venda, não lança pagamentos, não regista assinatura nem consentimento. O texto RGPD original permanece inalterado e carece de validação da empresa antes da utilização.

Após assinatura, usar o carregamento de documentos já existente na viatura e classificar a cópia como **Declaração de venda assinada**. Este tipo não preenche automaticamente o checklist de documentos de aquisição e não é confundido com o impresso de compra a particular.

## Segurança e validação

Requer as permissões `vehicle_edit`, `vehicle_documents_area_access` e `vehicle_others_area_access`; os perfis de oficina são bloqueados. Saídas privadas sem cache, texto escapado e carregamento remoto desativado no PDF.

Testes: `php artisan test --filter=VehicleSaleDeclarationTest`, apenas com `DB_PROFILE=sandbox`. Sem novas migrações para esta funcionalidade. Antes de publicar: validar o exemplo com a Dina, confirmar a utilização do modelo e verificar o formulário no alojamento. Esta versão não foi publicada automaticamente.
