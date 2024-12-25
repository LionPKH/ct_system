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

// Получение report_id из GET или POST
$report_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
}

if ($report_id <= 0) {
    exit("Некорректный идентификатор отчёта.");
}

$message = "";

// Обработка формы при отправке
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_text = isset($_POST['report_text']) ? trim($_POST['report_text']) : '';
    
    if (empty($report_text)) {
        $message = "<div class='message error'>Пожалуйста, введите текст отчёта.</div>";
    } else {
        try {
            // Проверка принадлежности отчёта специалисту
            $stmt = $pdo->prepare("SELECT image_id FROM reports WHERE report_id = :rid AND specialist_id = :sid");
            $stmt->execute([':rid' => $report_id, ':sid' => $specialist_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$report) {
                exit("Отчёт не найден или у вас нет прав на его редактирование.");
            }
            
            // Обновление отчёта
            $updateStmt = $pdo->prepare("UPDATE reports SET report_text = :rt WHERE report_id = :rid");
            $updateStmt->execute([
                ':rt' => $report_text,
                ':rid' => $report_id
            ]);
            
            $message = "<div class='message success'>Отчёт успешно обновлён.</div>";
        } catch (PDOException $e) {
            $message = "<div class='message error'>Ошибка при обновлении отчёта: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Получение текущего текста отчёта
try {
    $stmt = $pdo->prepare("SELECT report_text FROM reports WHERE report_id = :rid AND specialist_id = :sid");
    $stmt->execute([':rid' => $report_id, ':sid' => $specialist_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        exit("Отчёт не найден или у вас нет прав на его редактирование.");
    }
    
    $current_report_text = $report['report_text'];
} catch (PDOException $e) {
    exit("Ошибка при получении отчёта: " . htmlspecialchars($e->getMessage()));
}
?>
<?php include 'includes/header.php';?>

        <div class="container">
            <h1>Редактировать Отчёт (ID: <?= htmlspecialchars($report_id) ?>)</h1>
            <?php
                if (!empty($message)) {
                    echo $message;
                }
            ?>
            <form action="edit_report.php" method="post">
                <input type="hidden" name="report_id" value="<?= htmlspecialchars($report_id) ?>">
                <label for="report_text">Текст отчёта:
                    <textarea name="report_text" id="report_text" rows="10" required><?= htmlspecialchars($current_report_text) ?></textarea>
                </label>
                <button type="submit">Сохранить Изменения</button>
            </form>
            <div class="return-link">
                <a href="edit_patient.php">Вернуться к управлению пациентами</a>
            </div>
        </div>
    </main>
</body>
</html>