<?php
declare(strict_types=1);

// Вся настройка расчёта — здесь.
return [
    // stage: 'before' — набирается до сессии, 'session' — сама сессия.
    'components' => [
        'lecture'  => ['label' => 'Лекции',                 'weight' => 0.1, 'stage' => 'before'],
        'practice' => ['label' => 'Практика / лаборатория', 'weight' => 0.4, 'stage' => 'before'],
        'session'  => ['label' => 'Сессия',                 'weight' => 0.6, 'stage' => 'session', 'pass_min' => 50],
    ],

    // true: итог делится на сумму долей (сейчас 1.1), шкала остаётся 0–100.
    'normalize' => true,

    // Проходные баллы категорий (от высшего к низшему).
    'statuses' => [
        ['min' => 90, 'title' => 'Повышка',  'hint' => 'повышенная стипендия', 'ok' => 'Повышенная стипендия сохраняется'],
        ['min' => 70, 'title' => 'Грантник', 'hint' => 'сохранение гранта',    'ok' => 'Грант сохраняется'],
        ['min' => 50, 'title' => 'Платник',  'hint' => 'без отчисления',       'ok' => 'Угрозы отчисления нет'],
    ],

    'history_limit' => 20,
    'history_file'  => __DIR__ . '/../var/data/history.json',
];
