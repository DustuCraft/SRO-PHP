<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmt(float $value, int $decimals = 1): string
{
    return number_format($value, $decimals, ',', '');
}

function render(string $template, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/../templates/' . $template . '.php';
    return (string) ob_get_clean();
}

/** Обрезает строку до $length символов (UTF-8) без расширения mbstring. */
function truncate(string $value, int $length): string
{
    return preg_match('/^.{0,' . $length . '}/su', $value, $m) ? $m[0] : '';
}
