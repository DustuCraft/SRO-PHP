<?php
declare(strict_types=1);

// Запуск: php tests/run.php
$root = dirname(__DIR__);
$config = require $root . '/src/config.php';
require $root . '/src/helpers.php';
require $root . '/src/calculator.php';

$failed = 0;
function check(string $name, bool $ok): void
{
    global $failed;
    echo ($ok ? 'PASS  ' : 'FAIL  ') . $name . PHP_EOL;
    $failed += $ok ? 0 : 1;
}
function near(float $a, float $b): bool { return abs($a - $b) < 0.01; }
function by_min(array $r, string $field): array { return array_column($r['statuses'], $field, 'min'); }

// Полный расчёт
$r = calculate(['lecture' => '100', 'practice' => '100', 'session' => '100'], $config);
check('T1 все 100 -> 100 баллов, «Повышка»', near($r['earned'], 100.0) && $r['level'] === 'Повышка');

$r = calculate(['lecture' => '80', 'practice' => '70', 'session' => '90'], $config);
check('T2 80/70/90 -> 81.82 балла, «Грантник»', near($r['earned'], 81.82) && $r['level'] === 'Грантник');
$s = by_min($r, 'status');
check('T3 на 81.8: платник и грантник сохраняются, повышка нет', $s[50] === 'reached' && $s[70] === 'reached' && $s[90] === 'missed');
check('T4 до повышки не хватает 8.2', near(by_min($r, 'gap_total')[90], 8.18));
check('T5 нет лишних предупреждений', $r['warnings'] === []);

// Сессия ниже проходного
$r = calculate(['lecture' => '100', 'practice' => '100', 'session' => '45'], $config);
$s = by_min($r, 'status');
check('T6 итог 70, но сессия 45 < 50 -> ничего не сохраняется', near($r['earned'], 70.0) && $s[70] === 'missed' && $s[50] === 'missed' && $r['level'] === 'Риск отчисления');

$r = calculate(['lecture' => '100', 'practice' => '100', 'session' => '50'], $config);
check('T7 сессия ровно 50 -> проходной балл достигнут', !$r['failed'] && by_min($r, 'status')[70] === 'reached');

$r = calculate(['lecture' => '40', 'practice' => '40', 'session' => '40'], $config);
check('T8 итог 40 -> «Риск отчисления» с предупреждением', $r['level'] === 'Риск отчисления' && $r['warnings'] !== []);

// До сессии
$r = calculate(['lecture' => '80', 'practice' => '70', 'session' => ''], $config);
check('T9 до сессии набрано 32.7 из 45.5, можно добрать 12.7',
    !$r['session_entered'] && near($r['pre_earned'], 32.73) && near($r['pre_max'], 45.45) && near($r['pre_left'], 12.73));
$g = by_min($r, 'gap_total');
check('T10 не хватает до проходных баллов: 17.3 / 37.3 / 57.3', near($g[50], 17.27) && near($g[70], 37.27) && near($g[90], 57.27));
$n = by_min($r, 'need_session');
check('T11 на сессии: платнику 50 (проходной), грантнику 68.3', near($n[50], 50.0) && near($n[70], 68.33));
check('T12 до сессии нужно добрать: платнику 0, грантнику 0, повышке 2.7',
    near(by_min($r, 'gap_pre')[50], 0.0) && near(by_min($r, 'gap_pre')[70], 0.0) && near(by_min($r, 'gap_pre')[90], 2.73));
check('T13 повышка при этих баллах: «добрать до сессии»', by_min($r, 'status')[90] === 'short' && by_min($r, 'status')[70] === 'possible');

$r = calculate(['lecture' => '100', 'practice' => '100', 'session' => ''], $config);
check('T14 100/100 до сессии: добирать нечего, повышка достижима при 81.7 на сессии', by_min($r, 'status')[90] === 'possible' && near($r['pre_left'], 0.0) && near(by_min($r, 'need_session')[90], 81.67));

$r = calculate(['lecture' => '100', 'practice' => '', 'session' => ''], $config);
check('T15 нет практики и сессии -> предупреждение о недостающих оценках', count($r['warnings']) === 1 && str_contains($r['warnings'][0], 'Практика'));

// Ввод
$r = calculate(['lecture' => '101', 'practice' => '70', 'session' => ''], $config);
check('T16 101 отклоняется', !$r['ok']);
$r = calculate(['lecture' => 'abc', 'practice' => '', 'session' => ''], $config);
check('T17 текст вместо числа отклоняется', !$r['ok']);
$r = calculate(['lecture' => '', 'practice' => '', 'session' => ''], $config);
check('T18 пустая форма отклоняется', !$r['ok']);
$r = calculate(['lecture' => '50, 70', 'practice' => '60', 'session' => '40'], $config);
check('T19 несколько оценок усредняются: 49.09', near($r['earned'], 49.09));

echo ($failed === 0 ? 'Все тесты пройдены' : "Провалено: $failed") . PHP_EOL;
exit($failed === 0 ? 0 : 1);
