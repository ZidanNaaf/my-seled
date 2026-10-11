<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $page, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);
        $pageFile = BASE_PATH . '/pages/' . $page . '.php';
        if (!is_file($pageFile)) {
            http_response_code(500);
            echo 'Template tidak ditemukan: ' . htmlspecialchars($page);
            exit;
        }
        ob_start();
        require $pageFile;
        $content = ob_get_clean();
        $layoutFile = BASE_PATH . '/components/layouts/' . $layout . '.php';
        if (is_file($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function e(mixed $v): string
    {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
