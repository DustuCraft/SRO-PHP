<section class="panel">
    <h2>Оценки по дисциплине</h2>

    <?php if ($errors): ?>
        <div class="notice bad" role="alert">
            <strong>Исправьте ввод:</strong>
            <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="./" novalidate>
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

        <label class="field">
            <span>Дисциплина</span>
            <input type="text" name="discipline" maxlength="80" value="<?= e($input['discipline']) ?>" placeholder="Например, Базы данных">
        </label>

        <div class="weights-heading">
            <h3>Настройка долей оценок</h3>
            <button type="button" class="secondary weights-toggle" id="weightsToggle" aria-expanded="false" aria-controls="weightsPanel">Показать настройки</button>
        </div>
        <div class="grid weights-grid" id="weightsPanel" hidden>
            <?php foreach ($config['components'] as $key => $component): ?>
                <label class="field">
                    <span>Доля: <?= e($component['label']) ?></span>
                    <input type="number" name="weight_<?= e($key) ?>" min="0" max="1" step="0.01" required value="<?= e($weights[$key] ?? (string) $component['weight']) ?>">
                </label>
            <?php endforeach; ?>
        </div>

        <div class="grid">
            <?php foreach ($config['components'] as $key => $component): ?>
                <label class="field">
                    <span><?= e($component['label']) ?> <?php if (!empty($component['pass_min'])): ?><small>проходной <?= (int) $component['pass_min'] ?></small><?php endif; ?></span>
                    <input type="text" name="<?= e($key) ?>" inputmode="decimal" autocomplete="off"
                           value="<?= e($input[$key]) ?>" placeholder="85, 90, 70">
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="action" value="calculate">Рассчитать рейтинг</button>
    </form>
</section>
