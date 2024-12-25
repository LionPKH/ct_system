<?php
// Включение отображения ошибок для отладки (убрать на продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/boot.php';

// Генерация CSRF токена, если он ещё не создан
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Функция для получения specialist_id и информации по user_id
function getSpecialistInfo($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT s.specialist_id, s.full_name, s.contact_info, u.username 
                           FROM specialists s
                           JOIN users u ON s.user_id = u.user_id
                           WHERE u.user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Функция для получения отчётов специалиста
function getSpecialistReports($pdo, $specialist_id) {
    $stmt = $pdo->prepare("SELECT r.report_id, r.report_text, r.report_date, c.ct_name, p.full_name
                           FROM reports r
                           JOIN ct_images c ON r.image_id = c.image_id
                           JOIN patients p ON c.patient_id = p.patient_id
                           WHERE r.specialist_id = :sid
                           ORDER BY r.report_date DESC");
    $stmt->execute([':sid' => $specialist_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получение user_id из сессии
$user_id = $_SESSION['user_id'];

// Получение информации о специалисте
$specialist = getSpecialistInfo($pdo, $user_id);

if (!$specialist) {
    exit("Информация о специалисте не найдена.");
}

// Получение отчётов специалиста
$reports = getSpecialistReports($pdo, $specialist['specialist_id']);

// Обработка сообщений (например, после удаления отчёта)
$message = "";
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аккаунт Специалиста</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/account.css">

</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="tables.php">Главная</a></li>
                <li><a href="add_patient.php">Добавить пациента</a></li>
                <li><a href="edit_patient.php">Просмотр данных пациента</a></li>
                <li><a href="add_ct_image.php">Добавить кт снимок</a></li>
                <li><a href="account.php">Аккаунт</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="container">
            <h1>Аккаунт Специалиста</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>
            
            <section class="profile-section">
                <h2>Информация о Специалисте</h2>
                <ul>
                    <li><strong>ФИО:</strong> <?= htmlspecialchars($specialist['full_name']) ?></li>
                    <li><strong>Имя пользователя:</strong> <?= htmlspecialchars($specialist['username']) ?></li>
                    <li><strong>Контактная информация:</strong> <?= nl2br(htmlspecialchars($specialist['contact_info'])) ?></li>
                </ul>
                <a href="edit_account.php" class="btn">Редактировать Аккаунт</a>
                <a href="do_logout.php" class="btn delete-btn">Выйти</a>
            </section>
            
            <hr>
            
            <section class="reports-section">
                <h2>Мои Отчёты</h2>
                <?php if (!empty($reports)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID Отчёта</th>
                                <th>Пациент</th>
                                <th>КТ-снимок</th>
                                <th>Описание</th>
                                <th>Дата Создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= htmlspecialchars($report['report_id']) ?></td>
                                    <td><?= htmlspecialchars($report['full_name']) ?></td>
                                    <td><?= htmlspecialchars($report['ct_name'] ?: 'Без названия') ?></td>
                                    <td><?= htmlspecialchars(explode(' ', trim($report['report_text']))[0] ?? '') ?></td>
                                    <td><?= htmlspecialchars($report['report_date']) ?></td>
                                    <td>
                                        <a href="view_report.php?report_id=<?= $report['report_id'] ?>" class="btn">Просмотреть</a>
                                        <a href="edit_report.php?report_id=<?= $report['report_id'] ?>" class="btn">Изменить</a>
                                        <form action="delete_report.php" method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот отчёт?');">
                                            <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="btn delete-btn">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Вы ещё не создали ни одного отчёта.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>