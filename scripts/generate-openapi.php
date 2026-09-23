<?php

// Run from the project root: php scripts/generate-openapi.php
$root = dirname(__DIR__);
$raw = shell_exec('cd '.escapeshellarg($root).' && php artisan route:list --path=api --json');
$routes = json_decode($raw ?: '', true);
if (! is_array($routes)) {
    fwrite(STDERR, "Não foi possível ler as rotas API.\n");
    exit(1);
}

$paths = [];
foreach ($routes as $route) {
    $methods = array_values(array_diff(explode('|', $route['method']), ['HEAD']));
    $originalPath = '/'.$route['uri'];
    $optionalPaths = [$originalPath];
    if (preg_match('#/\{[^}]+\?\}#', $originalPath, $match)) {
        $optionalPaths = [str_replace($match[0], '', $originalPath), str_replace('?', '', $originalPath)];
    }

    foreach ($optionalPaths as $path) {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        $parameters = array_map(fn ($name) => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ], $matches[1]);

        foreach ($methods as $method) {
            $method = strtolower($method);
            if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                continue;
            }

            $isBackoffice = str_starts_with($path, '/api/v1/backoffice');
            $isNode = str_starts_with($path, '/api/whatsapp/') && ! str_contains($path, '/webhook');
            $isPublic = str_starts_with($path, '/api/meta/')
                || str_contains($path, '/webhook')
                || str_starts_with($path, '/api/v1/lead-access/')
                || in_array($path, ['/api/mobile/auth/login', '/api/v1/auth/forgot-password', '/api/v1/auth/reset-password'], true);
            $segments = explode('/', trim($path, '/'));
            $tag = $isBackoffice ? 'backoffice:'.($segments[3] ?? 'home') : ($segments[1] ?? 'api');
            $action = $route['action'];

            $operation = [
                'tags' => [$tag],
                'summary' => $action,
                'description' => $isBackoffice
                    ? 'Espelho do backoffice. Exige token de integração de um administrador. Respostas de páginas e redirecionamentos são convertidas para JSON; consultar docs/api-integracao.md.'
                    : ($isNode ? 'Interface interna Node/WhatsApp; usa o token Node configurado no servidor.' : 'Consultar docs/api-integracao.md para autenticação, permissões e formatos.'),
                'operationId' => substr(hash('sha256', $method.' '.$path.' '.$action), 0, 20),
                'responses' => [
                    '200' => ['description' => 'Pedido processado'],
                    '401' => ['description' => 'Credencial ausente ou inválida'],
                    '403' => ['description' => 'Sem permissão'],
                    '422' => ['description' => 'Dados inválidos'],
                ],
                'security' => $isNode ? [['NodeBearer' => []]] : ($isPublic ? [] : [['BearerAuth' => []]]),
            ];
            if ($parameters) {
                $operation['parameters'] = $parameters;
            }
            if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
                $operation['requestBody'] = [
                    'description' => 'Campos definidos pela validação do controlador/Form Request correspondente.',
                    'content' => [
                        'application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]],
                        'multipart/form-data' => ['schema' => ['type' => 'object', 'additionalProperties' => true]],
                    ],
                ];
            }
            $paths[$path][$method] = $operation;
        }
    }
}
ksort($paths);

$spec = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => 'AutoRCManager API',
        'version' => '1.0.0',
        'description' => 'Inventário de rotas gerado a partir da aplicação. Os contratos específicos estão em docs/api-integracao.md e nos Form Requests/controladores do projeto.',
    ],
    'servers' => [['url' => '/']],
    'paths' => $paths,
    'components' => [
        'securitySchemes' => [
            'BearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
            'NodeBearer' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'Token exclusivo do serviço Node/WhatsApp.'],
        ],
    ],
];

file_put_contents($root.'/docs/openapi.json', json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
echo 'Geradas '.count($paths)." entradas OpenAPI.\n";
