<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Exceptions\NotFoundException;
use Bramus\Router\Router as BramusRouter;
use Closure;
use RuntimeException;

final class Router
{
    private BramusRouter $engine;

    private array $groupStack = [];

    public function __construct(?string $basePath = null)
    {
        $this->engine = new BramusRouter();
        // setBasePath'i her zaman çağır (boş string dahil).
        // Çağrılmazsa bramus, SCRIPT_NAME'den /public prefix'ini otomatik
        // algılar ve tüm route'lar /public/admin/ gibi yanlış path'te aranır.
        $this->engine->setBasePath($basePath ?? '');

        $this->engine->set404(static function (): void {
            throw new NotFoundException('Route not found.');
        });
    }

    public function getEngine(): BramusRouter
    {
        return $this->engine;
    }

    public function get(string $pattern, $handler): void
    {
        $this->register('GET', $pattern, $handler);
    }

    public function post(string $pattern, $handler): void
    {
        $this->register('POST', $pattern, $handler);
    }

    public function put(string $pattern, $handler): void
    {
        $this->register('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, $handler): void
    {
        $this->register('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, $handler): void
    {
        $this->register('DELETE', $pattern, $handler);
    }

    public function options(string $pattern, $handler): void
    {
        $this->register('OPTIONS', $pattern, $handler);
    }

    public function any(string $pattern, $handler): void
    {
        $this->register('GET|POST|PUT|PATCH|DELETE|OPTIONS', $pattern, $handler);
    }

    public function before(string $methods, string $pattern, callable $middleware): void
    {
        $this->engine->before($methods, $this->applyPrefix($pattern), $middleware);
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $this->groupStack[] = ['prefix' => $prefix, 'middlewares' => $middlewares];
        $callback($this);
        array_pop($this->groupStack);
    }

    public function run(): void
    {
        $this->engine->run();
    }

    private function register(string $methods, string $pattern, $handler): void
    {
        $full = $this->applyPrefix($pattern);
        $middlewares = $this->collectMiddlewares();

        [$regex, $names] = $this->compile($full);

        $wrapped = function (...$args) use ($handler, $names, $middlewares) {
            $params = [];
            foreach ($names as $i => $name) {
                $params[$name] = $args[$i] ?? null;
            }

            $pipeline = array_reduce(
                array_reverse($middlewares),
                static fn (callable $next, callable $middleware): Closure => static fn (array $params) => $middleware($params, $next),
                fn (array $params) => $this->dispatchHandler($handler, $params)
            );

            return $pipeline($params);
        };

        $this->engine->match($methods, $regex, $wrapped);
    }

    private function applyPrefix(string $pattern): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
        }

        if ($prefix === '') {
            return $pattern;
        }

        $full = rtrim($prefix, '/') . '/' . ltrim($pattern, '/');
        $full = rtrim($full, '/');

        return $full === '' ? '/' : $full;
    }

    private function collectMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            foreach ($group['middlewares'] as $middleware) {
                $middlewares[] = $middleware;
            }
        }

        return $middlewares;
    }

    private function compile(string $pattern): array
    {
        $names = [];

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];
                return '(' . ($matches[2] ?? '[^/]+') . ')';
            },
            $pattern
        );

        return [$regex ?? $pattern, $names];
    }

    private function dispatchHandler($handler, array $params)
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $handler = [$class, $method];
        }

        if (is_array($handler) && count($handler) === 2) {
            $instance = is_object($handler[0]) ? $handler[0] : new $handler[0]();
            if (!method_exists($instance, $handler[1])) {
                throw new RuntimeException(sprintf('Method "%s" does not exist on "%s".', $handler[1], get_class($instance)));
            }
            return $instance->{$handler[1]}($params);
        }

        if (is_callable($handler)) {
            return $handler($params);
        }

        throw new RuntimeException('Invalid route handler.');
    }
}
