<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string|array $methods, string $pattern, callable|array $handler, array $middleware = []): void
    {
        $methods = array_map('strtoupper', (array)$methods);
        [$regex, $params] = $this->compile($pattern);
        foreach ($methods as $m) {
            $this->routes[$m][] = [
                'pattern' => $pattern,
                'regex' => $regex,
                'params' => $params,
                'handler' => $handler,
                'middleware' => $middleware,
            ];
        }
    }

    public function get(string $p, $h, array $m = []): void { $this->add('GET', $p, $h, $m); }
    public function post(string $p, $h, array $m = []): void { $this->add('POST', $p, $h, $m); }

    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', function ($mm) use (&$params) {
            $params[] = $mm[1];
            return '([^/]+)';
        }, $pattern);
        return ['#^' . $regex . '$#', $params];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        // Method override untuk form: _method=PATCH/PUT/DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $ov = strtoupper((string)$_POST['_method']);
            if (in_array($ov, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $ov;
            }
        }
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $r) {
            if (preg_match($r['regex'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($r['params'] as $i => $name) {
                    $params[$name] = urldecode($matches[$i] ?? '');
                }
                foreach ($r['middleware'] as $mw) {
                    $res = $this->runMiddleware($mw, $params);
                    if ($res === false) {
                        return; // middleware sudah mengirim respons
                    }
                }
                $this->callHandler($r['handler'], $params);
                return;
            }
        }
        // Cek apakah path ada di method lain -> 405
        foreach ($this->routes as $m => $list) {
            if ($m === $method) continue;
            foreach ($list as $r) {
                if (preg_match($r['regex'], $path)) {
                    http_response_code(405);
                    if (str_starts_with($path, '/api/')) {
                        Response::json(['success' => false, 'message' => 'Method tidak diizinkan'], 405);
                    } else {
                        View::render('errors/405', ['title' => 'Method Tidak Diizinkan'], 'guest');
                    }
                    return;
                }
            }
        }
        http_response_code(404);
        if (str_starts_with($path, '/api/')) {
            Response::json(['success' => false, 'message' => 'Endpoint tidak ditemukan'], 404);
        } else {
            View::render('errors/404', ['title' => 'Halaman Tidak Ditemukan'], 'guest');
        }
    }

    private function runMiddleware(string $mw, array $params)
    {
        return match ($mw) {
            'auth' => \App\Middleware\AuthMiddleware::handle(),
            'guest' => \App\Middleware\AuthMiddleware::guest(),
            default => $this->runRole($mw),
        };
    }

    private function runRole(string $mw)
    {
        if (str_starts_with($mw, 'role:')) {
            $roles = explode(',', substr($mw, 5));
            return \App\Middleware\RoleMiddleware::handle($roles);
        }
        return true;
    }

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            $handler($params);
            return;
        }
        [$class, $method] = $handler;
        $obj = new $class();
        $obj->$method($params);
    }
}
