<?php
$entered = $result['session_entered'];
$class = !$entered ? 'info' : ($result['level'] === 'Риск отчисления' ? 'bad' : 'ok');
$chips = [
    'reached'  => ['ok', 'Сохраняется'],
    'missed'   => ['bad', 'Не сохраняется'],
    'possible' => ['warn', 'Достижимо'],
    'short'    => ['bad', 'Нужно добрать'],
];
?>
<section class="panel result">
    <h2>Результат</h2>

    <div class="verdict <?= e($class) ?>">
        <div class="score"><?= e(fmt($result['earned'])) ?></div>
        <div>
            <div class="grade"><?= $entered ? e($result['level']) : 'Сессия ещё не сдана' ?></div>
            <div class="sub"><?= $entered ? 'Итоговый рейтинг из 100 баллов' : 'Набрано баллов из 100 до сессии' ?></div>
        </div>
    </div>

    <?php if (!$entered): ?>
        <div class="notice info">До сессии набрано <strong><?= e(fmt($result['pre_earned'])) ?></strong>
            из <strong><?= e(fmt($result['pre_max'])) ?></strong> возможных баллов.
            Можно добрать ещё <strong><?= e(fmt($result['pre_left'])) ?></strong>.</div>
    <?php endif; ?>

    <?php foreach ($result['warnings'] as $warning): ?>
        <div class="notice warn" role="status"><?= e($warning) ?></div>
    <?php endforeach; ?>

    <h3>Платник, грантник и повышка</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Категория</th>
                <th>Проходной балл</th>
                <th>Не хватает до проходного балла</th>
                <th>Не хватает до сессии</th>
                <th>Нужно набрать на сессии</th>
                <th>Итог</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (array_reverse($result['statuses']) as $st): ?>
                <?php [$cls, $label] = $chips[$st['status']]; ?>
                <tr>
                    <td><strong><?= e($st['title']) ?></strong><br><small><?= e($st['hint']) ?></small></td>
                    <td><?= (int) $st['min'] ?></td>
                    <td><?= $st['gap_total'] < 0.05 ? 'Набран' : e(fmt($st['gap_total'])) ?></td>
                    <td><?php
                        if ($entered) {
                            echo 'Сессия учтена';
                        } elseif ($st['gap_pre'] > 0.05) {
                            echo 'Добрать ' . e(fmt($st['gap_pre']));
                        } else {
                            echo 'Добирать не нужно';
                        }
                    ?></td>
                    <td><?php
                        if ($entered) {
                            echo '—';
                        } elseif ($st['status'] === 'short') {
                            echo 'Недостижимо';
                        } else {
                            echo 'не менее ' . e(fmt(ceil($st['need_session'] * 10) / 10));
                        }
                    ?></td>
                    <td><span class="chip <?= e($cls) ?>"><?= e($label) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="hint">«Не хватает до сессии» — сколько баллов нужно добрать по лекциям и практике, чтобы категория осталась достижимой даже при 100 баллах на сессии.</p>

    <h3>Баллы по компонентам</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Компонент</th><th>Оценки</th><th>Среднее</th><th>Вклад в итог</th></tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['label']) ?></td>
                    <td><?= $row['scores'] ? e(implode(', ', array_map(fn ($s) => fmt($s, $s == floor($s) ? 0 : 1), $row['scores']))) : '—' ?></td>
                    <td><?= $row['avg'] === null ? '—' : e(fmt($row['avg'])) ?></td>
                    <td><?= $row['share'] === null ? '—' : e(fmt($row['share'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
