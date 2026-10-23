<?php
declare(strict_types=1);

session_start();

$root   = dirname(__DIR__);
$config = require $root . '/src/config.php';
require $root . '/src/helpers.php';
require $root . '/src/calculator.php';
require $root . '/src/history.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$input  = ['discipline' => '', 'lecture' => '', 'practice' => '', 'session' => ''];
$weights = [];
foreach ($config['components'] as $key => $component) {
    $weights[$key] = (string) $component['weight'];
}
$result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Сессия формы устарела. Обновите страницу.');
    }

    if (($_POST['action'] ?? '') === 'clear') {
        history_clear($config['history_file']);
        header('Location: ./');
        exit;
    }

    foreach ($input as $key => $_) {
        $input[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $input['discipline'] = truncate($input['discipline'], 80);

    $weightErrors = [];
    $weightSum = 0.0;
    foreach ($config['components'] as $key => $component) {
        $rawWeight = trim((string) ($_POST['weight_' . $key] ?? $component['weight']));
        $weights[$key] = str_replace(',', '.', $rawWeight);
        if (!is_numeric($weights[$key]) || (float) $weights[$key] < 0 || (float) $weights[$key] > 1) {
            $weightErrors[] = $component['label'] . ': доля должна быть от 0 до 1.';
            continue;
        }
        $weightSum += (float) $weights[$key];
        $config['components'][$key]['weight'] = (float) $weights[$key];
    }
    if ($weightSum <= 0) {
        $weightErrors[] = 'Сумма долей должна быть больше нуля.';
    }
    if (isset($config['components']['session']) && (float) $config['components']['session']['weight'] <= 0) {
        $weightErrors[] = 'Доля сессии должна быть больше нуля, чтобы рассчитать необходимую оценку.';
    }

    if ($weightErrors) {
        $errors = $weightErrors;
    } else {
        $result = calculate($input, $config);
    }
    if ($result['ok']) {
        history_add($config['history_file'], [
            'time'       => date('d.m.Y H:i'),
            'discipline' => $input['discipline'] !== '' ? $input['discipline'] : 'Без названия',
            'earned'     => round($result['earned'], 2),
            'level'      => $result['level'],
            'complete'   => $result['session_entered'],
        ], $config['history_limit']);
    } else {
        $errors = $result['errors'];
        $result = null;
    }
}

$content = render('form', ['config' => $config, 'input' => $input, 'weights' => $weights, 'errors' => $errors, 'csrf' => $_SESSION['csrf']])
    . ($result ? render('result', ['config' => $config, 'result' => $result]) : '')
    . render('history', ['items' => history_load($config['history_file']), 'csrf' => $_SESSION['csrf']]);

echo render('layout', ['content' => $content]);
