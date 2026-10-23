<?php
declare(strict_types=1);

function history_load(string $file): array
{
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function history_save(string $file, array $items): void
{
    @mkdir(dirname($file), 0775, true);
    file_put_contents($file, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

function history_add(string $file, array $entry, int $limit): void
{
    $items = history_load($file);
    array_unshift($items, $entry);
    history_save($file, array_slice($items, 0, $limit));
}

function history_clear(string $file): void
{
    history_save($file, []);
}
