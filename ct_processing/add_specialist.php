<?php
// add_specialist.php
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /ct_processing');
    die("");
}

$error   = "";
$success = "";

// Если форма отправлена (метод POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получаем user_id из сессии
    $userId        = $_SESSION['user_id'];
    // Отдельные поля для имени и фамилии
    $firstName     = trim($_POST["first_name"]);
    $lastName      = trim($_POST["last_name"]);
    // Соединяем в одно поле (full_name)
    $fullName      = $firstName . " " . $lastName;

    $specialization = trim($_POST["specialization"]);
    $contactInfo    = trim($_POST["contact_info"]);

    // Минимальная валидация
    if (empty($firstName) || empty($lastName)) {
        $error = "Пожалуйста, укажите имя и фамилию.";
    } else {
        try {
            // Подготавливаем SQL-запрос
            $sql = "INSERT INTO specialists (user_id, full_name, specialization, contact_info)
                    VALUES (:user_id, :full_name, :specialization, :contact_info)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
            $stmt->bindParam(':specialization', $specialization, PDO::PARAM_STR);
            $stmt->bindParam(':contact_info', $contactInfo, PDO::PARAM_STR);

            $stmt->execute();

            // Если вставка прошла успешно
            // Вместо вывода сообщения — перенаправляем на tables.php
            header('Location: tables.php');
            exit;

        } catch (PDOException $e) {
            $error = "Ошибка при добавлении специалиста: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавление специалиста</title>
    <link rel="stylesheet" href="css/add.css"> <!-- Подключаем файл стилей -->
</head>
<body>

<div class="container">
    <h1>Добавить данные специалиста</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <label for="first_name">Имя:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Фамилия:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="specialization">Специализация:</label>
        <input type="text" id="specialization" name="specialization">

        <label for="contact_info">Контактная информация:</label>
        <input type="text" id="contact_info" name="contact_info">

        <button type="submit">Сохранить</button>
    </form>
</div>

</body>
</html>