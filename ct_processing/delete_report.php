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

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Некорректный метод запроса.");
}

// Получение specialist_id из сессии
$user_id = $_SESSION['user_id'];
$specialist_id = getSpecialistId($pdo, $user_id);

if (!$specialist_id) {
    exit("Специалист не найден для данного пользователя.");
}

// Получение report_id из POST
$report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

if ($report_id <= 0) {
    exit("Некорректный идентификатор отчёта.");
}

try {
    // Начало транзакции
    $pdo->beginTransaction();

    // Получение image_id отчёта
    $stmt = $pdo->prepare("SELECT image_id FROM reports WHERE report_id = :rid AND specialist_id = :sid FOR UPDATE");
    $stmt->execute([':rid' => $report_id, ':sid' => $specialist_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        // Откат транзакции
        $pdo->rollBack();
        exit("Отчёт не найден или у вас нет прав на его удаление.");
    }
    
    $image_id = $report['image_id'];
    
    // Удаление отчёта
    $deleteStmt = $pdo->prepare("DELETE FROM reports WHERE report_id = :rid");
    $deleteStmt->execute([':rid' => $report_id]);
    
    // Обновление статуса КТ-снимка на 'unprocessed' и установка processed_date в NULL
    $updateImgStmt = $pdo->prepare("UPDATE ct_images SET processed_status = 'unprocessed', processed_date = NULL WHERE image_id = :iid");
    $updateImgStmt->execute([':iid' => $image_id]);
    
    // Подтверждение транзакции
    $pdo->commit();
    
    // Перенаправление с сообщением об успехе
    header("Location: edit_patient.php?message=Отчёт успешно удалён и статус КТ-снимка обновлён.");
    exit;
} catch (PDOException $e) {
    // Откат транзакции в случае ошибки
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit("Ошибка при удалении отчёта: " . htmlspecialchars($e->getMessage()));
}
?>