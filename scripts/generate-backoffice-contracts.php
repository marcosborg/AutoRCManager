<?php

// Run from the project root: php scripts/generate-backoffice-contracts.php
require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$routes = require $root.'/routes/backoffice-api-map.php';
$lines = [
    '# Contratos do espelho do backoffice',
    '',
    'Este índice aponta para a implementação e a validação efetivas de cada operação. Para os métodos com `Request` genérico, consultar as regras `validate(...)` no método indicado. Atualizar com `php scripts/generate-backoffice-contracts.php`.',
    '',
    '| Método | Endpoint API | Implementação | Validação |',
    '| --- | --- | --- | --- |',
];

foreach ($routes as [$methods, $path, $action]) {
    [$class, $method] = explode('@', ltrim($action, '\\'), 2);
    try {
        $reflection = new ReflectionMethod($class, $method);
    } catch (ReflectionException $exception) {
        $lines[] = '| `'.implode('/', $methods).'` | `/api/v1/backoffice'.($path === '' ? '' : '/'.$path).'` | **Método inexistente: `'.$class.'::'.$method.'`** | — |';

        continue;
    }
    $source = str_replace($root.'/', '../', $reflection->getFileName());
    $implementation = '['.$reflection->getDeclaringClass()->getShortName().'::'.$method.']('.$source.'#L'.$reflection->getStartLine().')';
    $validation = '—';
    foreach ($reflection->getParameters() as $parameter) {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType) {
            continue;
        }
        $typeName = $type->getName();
        if (is_subclass_of($typeName, Illuminate\Foundation\Http\FormRequest::class)) {
            $requestReflection = new ReflectionClass($typeName);
            $requestSource = str_replace($root.'/', '../', $requestReflection->getFileName());
            $validation = '['.$requestReflection->getShortName().']('.$requestSource.')';
            break;
        }
        if ($typeName === Illuminate\Http\Request::class) {
            $validation = 'No controlador';
        }
    }

    $lines[] = '| `'.implode('/', $methods).'` | `/api/v1/backoffice'.($path === '' ? '' : '/'.$path).'` | '.$implementation.' | '.$validation.' |';
}

file_put_contents($root.'/docs/api-backoffice-contracts.md', implode("\n", $lines)."\n");
echo 'Documentados '.count($routes)." contratos de backoffice.\n";
