<?php
require_once __DIR__ . '/boot.php';

// Функция для получения specialist_id по user_id
function getSpecialistId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT specialist_id FROM specialists WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['specialist_id'] : null;
}

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получение specialist_id из сессии
$user_id = $_SESSION['user_id'];
$specialist_id = getSpecialistId($pdo, $user_id);

if (!$specialist_id) {
    exit("Специалист не найден для данного пользователя.");
}

// Получение report_id из GET
$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if ($report_id <= 0) {
    exit("Некорректный идентификатор отчёта.");
}

try {
    // Получение отчёта
    $stmt = $pdo->prepare("SELECT r.report_id, r.report_text, r.report_date, p.full_name, c.ct_name
                           FROM reports r
                           JOIN ct_images c ON r.image_id = c.image_id
                           JOIN patients p ON c.patient_id = p.patient_id
                           WHERE r.report_id = :rid AND r.specialist_id = :sid");
    $stmt->execute([':rid' => $report_id, ':sid' => $specialist_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        exit("Отчёт не найден или у вас нет прав на его просмотр.");
    }
} catch (PDOException $e) {
    exit("Ошибка при получении отчёта: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр Отчёта</title>
    <link rel="stylesheet" href="css/info.css">
</head>
<body>
<header>
        <nav>
            <ul>
                <li><a href="tables.php">Главная</a></li>
                <li><a href="add_patient.php">Добавить пациента</a></li>
                <li><a href="edit_patient.php">Просмотр данных пациента</a></li>
                <li><a href="add_ct_image.php">Добавить кт снимок</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="container">
            <h1>Просмотр Отчёта (ID: <?= htmlspecialchars($report['report_id']) ?>)</h1>
            <p><strong>Пациент:</strong> <?= htmlspecialchars($report['full_name']) ?></p>
            <p><strong>КТ-снимок:</strong> <?= htmlspecialchars($report['ct_name'] ?: 'Без названия') ?></p>
            <p><strong>Дата отчёта:</strong> <?= htmlspecialchars($report['report_date']) ?></p>
            <hr>
            <h2>Текст Отчёта:</h2>
            <p><?= nl2br(htmlspecialchars($report['report_text'])) ?></p>
            <div class="return-link">
                <a href="edit_patient.php">Вернуться к управлению пациентами</a>
            </div>
        </div>
    </main>
</body>
</html>