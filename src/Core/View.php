<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        $templatePath = __DIR__ . '/../Views/' . $template . '.php';
        if (!is_file($templatePath)) {
            throw new RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        $layoutPath = __DIR__ . '/../Views/' . $layout . '.php';
        if (!is_file($layoutPath)) {
            echo $content;
            return;
        }
        require $layoutPath;
    }

    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
