<?php
namespace App\Core;

class Controller {
    protected function render(string $viewPath, array $data = [], string $layout = 'layouts/main'): void {
        extract($data);

        // Render konten view ke dalam buffer dari app/Views/
        $viewFile = __DIR__ . '/../Views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($viewFile)) {
            die("View file tidak ditemukan: " . $viewFile);
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Jika layout kosong atau null, langsung tampilkan konten view
        if (!$layout) {
            echo $content;
            return;
        }

        // Render layout pembungkus dari app/Views/
        $layoutFile = __DIR__ . '/../Views/' . ltrim($layout, '/') . '.php';
        if (!file_exists($layoutFile)) {
            die("Layout file tidak ditemukan: " . $layoutFile);
        }

        require $layoutFile;
    }

    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}
