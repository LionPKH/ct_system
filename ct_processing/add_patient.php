<?php include 'includes/header.php'; ?>

<?php

require_once __DIR__ . '/boot.php';

// Проверяем, авторизован ли пользователь (по желанию)

// Сюда будем выводить сообщения
$message = "";

// Получаем список доступных больниц из таблицы `hospitals`
$hospitals = [];
try {
    $stmt = $pdo->query("SELECT hospital_id, hospital_name FROM hospitals ORDER BY hospital_name");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Ошибка при получении списка больниц: " . $e->getMessage();
}

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName       = trim($_POST["first_name"]);
    $lastName        = trim($_POST["last_name"]);
    $dateOfBirth     = trim($_POST["date_of_birth"]);
    $hospitalId      = trim($_POST["hospital_id"]);
    $additionalInfo  = trim($_POST["additional_info"]);

    // Объединяем имя и фамилию в full_name
    $fullName = $firstName . " " . $lastName;

    if (empty($firstName) || empty($lastName) || empty($dateOfBirth) || empty($hospitalId)) {
        $message = "Пожалуйста, заполните обязательные поля (Имя, Фамилия, Дата рождения, Больница).";
    } else {
        try {
            $sql = "INSERT INTO patients (full_name, date_of_birth, hospital_id, additional_info)
                    VALUES (:full_name, :date_of_birth, :hospital_id, :additional_info)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'full_name'       => $fullName,
                'date_of_birth'   => $dateOfBirth,
                'hospital_id'     => $hospitalId,
                'additional_info' => $additionalInfo
            ]);

            $message = "Пациент успешно добавлен!";
            header('Location: tables.php');

        } catch (PDOException $e) {
            $message = "Ошибка при добавлении пациента: " . $e->getMessage();
        }
    }
}
?>

<h1>Добавить нового пациента</h1>

<?php if (!empty($message)): ?>
    <p><strong><?=htmlspecialchars($message, ENT_QUOTES)?></strong></p>
<?php endif; ?>

<form action="" method="post">
    <label>Имя: <input type="text" name="first_name" required></label><br><br>
    <label>Фамилия: <input type="text" name="last_name" required></label><br><br>
    <label>Дата рождения: <input type="date" name="date_of_birth" required></label><br><br>

    <label>Больница:
        <select name="hospital_id" required>
            <option value="">Выберите больницу</option>
            <?php foreach ($hospitals as $h): ?>
                <option value="<?=$h['hospital_id']?>">
                    <?=htmlspecialchars($h['hospital_name'], ENT_QUOTES)?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Дополнительная информация:<br>
        <textarea name="additional_info" rows="4" cols="50"></textarea>
    </label><br><br>

    <button type="submit">Сохранить</button>
</form>
</body>
</html>