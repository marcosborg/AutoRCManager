<?php

// Run from the project root: php scripts/generate-api-endpoints.php
$projectRoot = dirname(__DIR__);
$command = 'cd '.escapeshellarg($projectRoot).' && php artisan route:list --path=api --json';
$raw = shell_exec($command);
$routes = json_decode($raw ?: '', true);

if (! is_array($routes)) {
    fwrite(STDERR, "Não foi possível ler as rotas da aplicação.\n");
    exit(1);
}

$groups = [];
foreach ($routes as $route) {
    $uri = $route['uri'];
    $parts = explode('/', $uri);
    $group = $parts[1] ?? 'outros';
    if ($group === 'v1') {
        $group = 'v1/'.($parts[2] ?? 'outros');
    }
    $groups[$group][] = $route;
}
ksort($groups);

$lines = [
    '# Inventário de endpoints HTTP',
    '',
    'Gerado a partir das rotas Laravel. Voltar a gerar com `php scripts/generate-api-endpoints.php` após alterar `routes/api.php`.',
    '',
    'Base: `/api`. Consultar [api-integracao.md](api-integracao.md) para autenticação, permissões, formatos e limites.',
    '',
];

foreach ($groups as $group => $items) {
    $lines[] = '## '.$group;
    $lines[] = '';
    $lines[] = '| Método | Caminho |';
    $lines[] = '| --- | --- |';
    foreach ($items as $route) {
        $methods = str_replace('|HEAD', '', $route['method']);
        $lines[] = '| `'.$methods.'` | `/'.$route['uri'].'` |';
    }
    $lines[] = '';
}

file_put_contents($projectRoot.'/docs/api-endpoints.md', rtrim(implode("\n", $lines), "\n")."\n");
echo 'Documentados '.count($routes)." endpoints.\n";
