<?php
require_once __DIR__ . '/boot.php'; // Убедитесь, что путь верный

// Проверка авторизации

// Переменная для сообщений
$message = "";

// Получение списка пациентов для выпадающего списка
try {
    $stmt = $pdo->prepare("SELECT patient_id, full_name FROM patients ORDER BY full_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit("Ошибка при получении списка пациентов: " . $e->getMessage());
}

// Обработка формы при отправке
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение и очистка данных из формы
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $ct_name    = isset($_POST['ct_name']) ? trim($_POST['ct_name']) : '';
    $image_file = isset($_FILES['ct_image']) ? $_FILES['ct_image'] : null;

    // Валидация данных
    if ($patient_id <= 0) {
        $message = "<div class='message error'>Пожалуйста, выберите пациента.</div>";
    } elseif (empty($ct_name)) {
        $message = "<div class='message error'>Пожалуйста, введите название КТ-снимка.</div>";
    } elseif (empty($image_file) || $image_file['error'] !== UPLOAD_ERR_OK) {
        $message = "<div class='message error'>Пожалуйста, загрузите изображение КТ-снимка.</div>";
    } else {
        // Проверка типа файла
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($image_file['type'], $allowed_types)) {
            $message = "<div class='message error'>Только изображения форматов JPEG, PNG и GIF разрешены.</div>";
        } else {
            // Ограничение размера файла (например, 5MB)
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($image_file['size'] > $max_size) {
                $message = "<div class='message error'>Размер изображения не должен превышать 5MB.</div>";
            } else {
                // Чтение файла и конвертация в Base64
                $image_data = file_get_contents($image_file['tmp_name']);
                $base64 = base64_encode($image_data);

                // Определение MIME типа изображения
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($image_file['tmp_name']);

                // Вставка данных в базу
                try {
                    $insert_sql = "INSERT INTO ct_images (ct_name, image_path, processed_status, patient_id) 
                                   VALUES (:ct_name, :image_path, 'unprocessed', :patient_id)";
                    $stmt_insert = $pdo->prepare($insert_sql);
                    $stmt_insert->execute([
                        ':ct_name'    => $ct_name,
                        ':image_path' => $base64,
                        ':patient_id' => $patient_id
                    ]);
                    $message = "<div class='message success'>КТ-снимок успешно добавлен.</div>";
                } catch (PDOException $e) {
                    $message = "<div class='message error'>Ошибка при добавлении КТ-снимка: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
}
?>

    <?php include 'includes/header.php'; ?>
        <div class="container">
            <h1>Добавить КТ-снимок</h1>
            <?php
                if (!empty($message)) {
                    echo $message;
                }
            ?>
            <form action="add_ct_image.php" method="post" enctype="multipart/form-data">
                <label for="patient_id">Выберите пациента:
                    <select name="patient_id" id="patient_id" required>
                        <option value="">-- Выберите пациента --</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= htmlspecialchars($patient['patient_id']) ?>" <?= (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($patient['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label for="ct_name">Название КТ-снимка:
                    <input type="text" name="ct_name" id="ct_name" value="<?= isset($_POST['ct_name']) ? htmlspecialchars($_POST['ct_name']) : '' ?>" required>
                </label>

                <label for="ct_image">Загрузите изображение КТ-снимка:
                    <input type="file" name="ct_image" id="ct_image" accept="image/*" required>
                </label>

                <button type="submit">Добавить</button>
            </form>
            <div class="return-link">
                <a href="edit_patient.php">Вернуться к управлению пациентами</a>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>