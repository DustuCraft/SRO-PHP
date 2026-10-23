<?php
declare(strict_types=1);

/** Разбирает строку «85, 90; 70» в список оценок 0–100. */
function parse_scores(string $raw): array
{
    $values = [];
    $errors = [];
    $parts = preg_split('/[\s,;]+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $part) {
        if (!is_numeric($part)) {
            $errors[] = "«{$part}» не число";
            continue;
        }
        $number = (float) $part;
        if ($number < 0 || $number > 100) {
            $errors[] = "{$part} вне диапазона 0–100";
            continue;
        }
        $values[] = $number;
    }
    return ['values' => $values, 'errors' => $errors];
}

function average(array $values): ?float
{
    return $values === [] ? null : array_sum($values) / count($values);
}

/**
 * Средний балл, который нужно получить за сессию, чтобы итог достиг порога.
 * Не ниже проходного балла сессии.
 */
function required_score(float $target, float $earned, float $divisor, float $sessionWeight, float $minPass): float
{
    return max(($target - $earned) * $divisor / $sessionWeight, $minPass);
}

function calculate(array $raw, array $config): array
{
    $errors = [];
    $rows = [];

    foreach ($config['components'] as $key => $component) {
        $parsed = parse_scores((string) ($raw[$key] ?? ''));
        foreach ($parsed['errors'] as $error) {
            $errors[] = $component['label'] . ': ' . $error;
        }
        $rows[$key] = [
            'label'    => $component['label'],
            'weight'   => (float) $component['weight'],
            'pass_min' => (float) ($component['pass_min'] ?? 0),
            'stage'    => $component['stage'] ?? 'before',
            'scores'   => $parsed['values'],
            'avg'      => average($parsed['values']),
        ];
    }

    $entered = array_filter($rows, fn ($r) => $r['avg'] !== null);
    if ($entered === [] && $errors === []) {
        $errors[] = 'Введите оценки хотя бы по одному компоненту';
    }
    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }

    $sumAll  = array_sum(array_column($rows, 'weight'));
    $divisor = $config['normalize'] ? $sumAll : 1.0;

    // earned — баллов набрано из 100 (то, чего нет, считается нулём).
    $earned = $preEarned = $preMax = $sessMax = $wSession = $minPass = 0.0;
    $sessionEntered = true;
    foreach ($rows as $key => $row) {
        $max   = 100 * $row['weight'] / $divisor;
        $share = $row['avg'] === null ? null : $row['avg'] * $row['weight'] / $divisor;
        $rows[$key]['share'] = $share;
        $earned += $share ?? 0.0;

        if ($row['stage'] === 'session') {
            $sessMax += $max;
            if ($row['avg'] === null) {
                $sessionEntered = false;
                $wSession += $row['weight'];
                $minPass = max($minPass, $row['pass_min']);
            }
        } else {
            $preMax    += $max;
            $preEarned += $share ?? 0.0;
        }
    }

    // Проходной балл компонента (сессии): не набран — ни одна категория не сохраняется.
    $failedRows = array_filter($entered, fn ($r) => $r['pass_min'] > 0 && $r['avg'] + 1e-9 < $r['pass_min']);
    $failed = $failedRows !== [];

    $statuses = [];
    foreach ($config['statuses'] as $st) {
        $t = (float) $st['min'];
        $item = $st + ['gap_total' => max(0.0, $t - $earned), 'gap_pre' => null, 'need_session' => null, 'status' => 'missed'];

        if ($failed) {
            $item['status'] = 'missed';
        } elseif ($sessionEntered) {
            $item['status'] = $earned + 1e-9 >= $t ? 'reached' : 'missed';
        } else {
            // Сколько нужно добрать до сессии, чтобы порог остался достижимым даже при 100 на сессии.
            $item['gap_pre'] = max(0.0, max(0.0, $t - $sessMax) - $preEarned);
            $need = required_score($t, $earned, $divisor, $wSession, $minPass);
            $item['need_session'] = $need;
            $item['status'] = $item['gap_pre'] > 1e-9 ? 'short' : ($need <= 0 ? 'reached' : 'possible');
        }
        $statuses[] = $item;
    }

    $level = '—';
    if ($sessionEntered) {
        $level = 'Риск отчисления';
        foreach ($statuses as $item) {
            if ($item['status'] === 'reached') {
                $level = $item['title'];
                break;
            }
        }
    }

    $warnings = [];
    foreach ($failedRows as $row) {
        $warnings[] = 'Балл по «' . $row['label'] . '» (' . fmt($row['avg']) . ') ниже проходного '
            . fmt($row['pass_min'], 0) . '. Ни одна категория не сохраняется.';
    }
    $names = [];
    foreach ($rows as $row) {
        if ($row['avg'] === null) {
            $names[] = '«' . $row['label'] . '»';
        }
    }
    if ($names !== []) {
        $warnings[] = 'Нет оценок по: ' . implode(', ', $names) . '. Расчёт предварительный.';
    }
    if ($level === 'Риск отчисления') {
        $limits = $config['statuses'];
        $warnings[] = 'Риск отчисления: нужно не менее ' . end($limits)['min'] . ' баллов, и сессия должна быть сдана.';
    }

    return [
        'ok' => true, 'rows' => $rows, 'earned' => $earned,
        'pre_earned' => $preEarned, 'pre_max' => $preMax, 'pre_left' => max(0.0, $preMax - $preEarned),
        'session_entered' => $sessionEntered, 'failed' => $failed,
        'statuses' => $statuses, 'level' => $level, 'warnings' => $warnings,
    ];
}
