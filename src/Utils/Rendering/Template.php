<?php

namespace Malik12tree\ZATCA\Utils\Rendering;

class Template
{
    public static function capture(callable $callback, $args = [], bool $return = false)
    {
        ob_start();
        $result = null;

        try {
            $result = call_user_func_array($callback, $args);
        } finally {
            $render = ob_get_clean();
        }
        if ($return) {
            return [$render, $result];
        }

        return $render;
    }

    public static function render(string $templateName, array $variables, bool $return = false, bool $isAbsolute = false)
    {
        $path = $isAbsolute ? $templateName : __DIR__."/../../templates/{$templateName}.php";

        // No variable leakage! Pure magic~
        return self::capture(static function () {
            extract(func_get_arg(1));

            return require func_get_arg(0);
        }, [$path, $variables], $return);
    }
}
