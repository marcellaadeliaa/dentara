<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares = []): void {
        $this->routes[] = [
            'method'      => strtoupper($method),
            'path'        => '/' . trim($path, '/'),
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Bersihkan subdirektori (jika berjalan di localhost/dentara/)
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
            $requestUri = substr($requestUri, strlen($scriptName));
        }

        $requestUri = '/' . trim($requestUri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Ubah placeholder {param} menjadi regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $requestUri, $matches)) {
                // Ekstrak parameter url
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Jalankan Middleware
                foreach ($route['middlewares'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                // Eksekusi Handler
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_string($handler) && strpos($handler, '@') !== false) {
                    [$controllerName, $action] = explode('@', $handler);
                    $fullControllerClass = "App\\Controllers\\{$controllerName}";

                    if (!class_exists($fullControllerClass)) {
                        die("Controller class '{$fullControllerClass}' tidak ditemukan.");
                    }

                    $controller = new $fullControllerClass();
                    if (!method_exists($controller, $action)) {
                        die("Action '{$action}' tidak ditemukan di controller '{$fullControllerClass}'.");
                    }

                    call_user_func_array([$controller, $action], $params);
                    return;
                }
            }
        }

        // Jika rute tidak ditemukan -> 404
        http_response_code(404);
        $errorFile = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($errorFile)) {
            require $errorFile;
        } else {
            echo "<h1>404 - Halaman Tidak Ditemukan</h1><p>Halaman yang Anda tuju tidak tersedia.</p>";
        }
        exit;
    }

    private function runMiddleware(string $middleware): void {
        if ($middleware === 'auth') {
            Auth::requireAuth();
            return;
        }

        if (strpos($middleware, 'role:') === 0) {
            $roles = explode(',', substr($middleware, 5));
            Auth::requireRole($roles);
            return;
        }

        if ($middleware === 'guest') {
            if (Auth::check()) {
                header('Location: ' . Auth::getDashboardUrl());
                exit;
            }
            return;
        }
    }
}
