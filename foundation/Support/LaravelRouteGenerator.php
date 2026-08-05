<?php

declare(strict_types=1);

namespace Foundation\Support;

use Foundation\Contracts\HttpApiContract;

class LaravelRouteGenerator
{
    private string $namespace = 'App\Http\Controllers';

    public function __construct(string $namespace = 'App\Http\Controllers')
    {
        $this->namespace = $namespace;
    }

    public function generateRoutes(string $contractClass): string
    {
        if (!is_subclass_of($contractClass, HttpApiContract::class)) {
            throw new \InvalidArgumentException("Contract must implement HttpApiContract");
        }

        $basePath = $contractClass::getBasePath();
        $endpoints = $contractClass::getEndpoints();
        $controllerName = $this->inferControllerName($contractClass);

        $routes = "<?php\n\nuse Illuminate\Routing\Router;\nuse {$this->namespace}\\{$controllerName};\n\n";
        $routes .= "\$router->group(['prefix' => '{$basePath}', 'middleware' => ['api']], function (Router \$router) {\n";

        foreach ($endpoints as $operationId => $endpoint) {
            $method = strtolower($endpoint['method']);
            $path = $this->extractPath($endpoint['path']);
            $action = $this->inferAction($operationId);

            $routes .= "    \$router->{$method}('{$path}', [{$controllerName}::class, '{$action}'])->name('{$operationId}');\n";
        }

        $routes .= "});\n";
        return $routes;
    }

    public function generateController(string $contractClass): string
    {
        if (!is_subclass_of($contractClass, HttpApiContract::class)) {
            throw new \InvalidArgumentException("Contract must implement HttpApiContract");
        }

        $endpoints = $contractClass::getEndpoints();
        $controllerName = $this->inferControllerName($contractClass);

        $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->namespace};\n\n";
        $code .= "use Illuminate\Http\JsonResponse;\nuse Illuminate\Http\Request;\n\n";
        $code .= "class {$controllerName}\n{\n";

        foreach ($endpoints as $operationId => $endpoint) {
            $action = $this->inferAction($operationId);
            $method = strtoupper($endpoint['method']);

            $code .= "    public function {$action}(Request \$request";
            if ($this->hasPathParameter($endpoint['path'])) $code .= ", string \$id";
            $code .= "): JsonResponse\n    {\n        return response()->json(['message' => '{$endpoint['summary']}']);\n    }\n\n";
        }

        $code .= "}\n";
        return $code;
    }

    private function extractPath(string $fullPath): string
    {
        $base = preg_replace('#^/api[^/]*#', '', $fullPath);
        return trim($base, '/');
    }

    private function inferAction(string $operationId): string
    {
        $parts = explode('_', $operationId);
        $camelCase = '';
        foreach ($parts as $part) $camelCase .= ucfirst($part);
        return lcfirst($camelCase);
    }

    private function inferControllerName(string $contractClass): string
    {
        $className = class_basename($contractClass);
        return str_replace('HttpApiContract', 'Controller', $className);
    }

    private function hasPathParameter(string $path): bool
    {
        return strpos($path, '{') !== false;
    }
}
