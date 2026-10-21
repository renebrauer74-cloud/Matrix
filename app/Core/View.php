<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** Rendert ein Template innerhalb eines Layouts. */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/site'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, $data + ['content' => $content]);
    }

    /** Rendert ein einzelnes Template (ohne Layout). */
    public static function partial(string $view, array $data = []): string
    {
        $file = VIEW_PATH . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View nicht gefunden: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
