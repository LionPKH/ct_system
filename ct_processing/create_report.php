<?php
require_once __DIR__ . '/boot.php';

// Проверка авторизации

// Переменная для сообщений
$message = "";

// Функция для получения specialist_id по user_id
function getSpecialistId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT specialist_id FROM specialists WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['specialist_id'] : null;
}

// Получение specialist_id из сессии
$user_id = $_SESSION['user_id'];
$specialist_id = getSpecialistId($pdo, $user_id);

if (!$specialist_id) {
    exit("Специалист не найден для данного пользователя.");
}

// Получение списка пациентов для выпадающего списка
try {
    $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients ORDER BY full_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit("Ошибка при получении списка пациентов: " . $e->getMessage());
}

// Получение предустановленных значений из GET, если есть
$pre_selected_patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$pre_selected_image_id   = isset($_GET['image_id']) ? (int)$_GET['image_id'] : 0;

// Обработка формы при отправке
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение и очистка данных из формы
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $image_id   = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
    $report_text = isset($_POST['report_text']) ? trim($_POST['report_text']) : '';
    
    // Валидация данных
    if ($patient_id <= 0) {
        $message = "<div class='message error'>Пожалуйста, выберите пациента.</div>";
    } elseif ($image_id <= 0) {
        $message = "<div class='message error'>Пожалуйста, выберите КТ-снимок.</div>";
    } elseif (empty($report_text)) {
        $message = "<div class='message error'>Пожалуйста, введите текст отчёта.</div>";
    } else {
        // Проверка, что выбранный снимок принадлежит выбранному пациенту и не обработан
        try {
            $stmt_check = $pdo->prepare("SELECT processed_status FROM ct_images WHERE image_id = :iid AND patient_id = :pid");
            $stmt_check->execute([':iid' => $image_id, ':pid' => $patient_id]);
            $image = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$image) {
                $message = "<div class='message error'>Выбранный КТ-снимок не найден или не принадлежит выбранному пациенту.</div>";
            } elseif ($image['processed_status'] === 'processed') {
                $message = "<div class='message error'>Этот КТ-снимок уже обработан.</div>";
            } else {
                // Вставка отчёта в базу данных, включая specialist_id
                $stmt_insert = $pdo->prepare("INSERT INTO reports (image_id, specialist_id, report_text) VALUES (:image_id, :specialist_id, :report_text)");
                $stmt_insert->execute([
                    ':image_id'     => $image_id,
                    ':specialist_id'=> $specialist_id,
                    ':report_text'  => $report_text
                ]);
                
                // Обновление статуса снимка на 'processed' и установка processed_date
                $stmt_update = $pdo->prepare("UPDATE ct_images SET processed_status = 'processed', processed_date = NOW() WHERE image_id = :iid");
                $stmt_update->execute([':iid' => $image_id]);
                
                $message = "<div class='message success'>Отчёт успешно создан и КТ-снимок помечен как обработанный.</div>";
                
                // Очистка полей формы после успешной отправки
                $_POST = [];
                $pre_selected_patient_id = $patient_id;
                $pre_selected_image_id = $image_id;
            }
        } catch (PDOException $e) {
            $message = "<div class='message error'>Ошибка при создании отчёта: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Получение списка КТ-снимков для выбранного пациента
$selected_patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : $pre_selected_patient_id;
$images = [];

if ($selected_patient_id > 0) {
    try {
        $stmt_images = $pdo->prepare("SELECT image_id, ct_name FROM ct_images WHERE patient_id = :pid AND processed_status = 'unprocessed' ORDER BY image_id DESC");
        $stmt_images->execute([':pid' => $selected_patient_id]);
        $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "<div class='message error'>Ошибка при получении КТ-снимков: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
<?php include 'includes/header.php';?>
        <div class="container">
            <h1>Создать Отчёт для Пациента</h1>
            <?php
                if (!empty($message)) {
                    echo $message;
                }
            ?>
            <form action="create_report.php" method="post">
                <label for="patient_id">Выберите пациента:
                    <select name="patient_id" id="patient_id" required onchange="this.form.submit()">
                        <option value="">-- Выберите пациента --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= htmlspecialchars($patient['patient_id']) ?>" <?= ($patient['patient_id'] == $selected_patient_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($patient['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <?php if ($selected_patient_id > 0): ?>
                    <?php if (!empty($images)): ?>
                        <label for="image_id">Выберите КТ-снимок:
                            <select name="image_id" id="image_id" required>
                                <option value="">-- Выберите КТ-снимок --</option>
                                <?php foreach ($images as $img): ?>
                                    <option value="<?= htmlspecialchars($img['image_id']) ?>" <?= ($img['image_id'] == $pre_selected_image_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($img['ct_name'] ?: 'Без названия') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php else: ?>
                        <p>У этого пациента нет доступных для обработки КТ-снимков.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($selected_patient_id > 0 && !empty($images)): ?>
                    <label for="report_text">Текст отчёта:
                        <textarea name="report_text" id="report_text" rows="5" required><?= isset($_POST['report_text']) ? htmlspecialchars($_POST['report_text']) : '' ?></textarea>
                    </label>

                    <button type="submit">Создать Отчёт</button>
                <?php endif; ?>
            </form>
            <div class="return-link">
                <a href="edit_patient.php">Вернуться к управлению пациентами</a>
            </div>
        </div>
    </main>
</body>
</html>