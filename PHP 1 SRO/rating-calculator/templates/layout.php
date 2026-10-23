<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Калькулятор рейтинга студента</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
    <h1>Калькулятор рейтинга студента</h1>
    <p>Введите оценки по дисциплине и узнайте, какой балл нужен для целевой оценки.</p>
</header>
<main>
<?= $content ?>
</main>
<script>
const weightsToggle = document.getElementById('weightsToggle');
const weightsPanel = document.getElementById('weightsPanel');
if (weightsToggle && weightsPanel) {
    weightsToggle.addEventListener('click', () => {
        const isHidden = weightsPanel.hidden;
        weightsPanel.hidden = !isHidden;
        weightsToggle.textContent = isHidden ? 'Скрыть настройки' : 'Показать настройки';
        weightsToggle.setAttribute('aria-expanded', String(isHidden));
    });
}
</script>
</body>
</html>
