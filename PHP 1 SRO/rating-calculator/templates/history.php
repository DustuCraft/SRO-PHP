<section class="panel">
    <h2>История расчётов</h2>
    <?php if (!$items): ?>
        <p class="hint">Пока пусто. Рассчитайте рейтинг, и запись появится здесь.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Время</th><th>Дисциплина</th><th>Набрано</th><th>Статус</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['time'] ?? '') ?></td>
                        <td><?= e($item['discipline'] ?? '') ?></td>
                        <td><?= e(fmt((float) ($item['earned'] ?? $item['rating'] ?? 0), 2)) ?><?= empty($item['complete']) ? ' (до сессии)' : '' ?></td>
                        <td><?= e($item['level'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="./">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <button type="submit" name="action" value="clear" class="secondary">Очистить историю</button>
        </form>
    <?php endif; ?>
</section>
