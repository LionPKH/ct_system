<?php
require_once __DIR__ . '/boot.php';

// Проверяем наличие пользователя с указанным username
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username");
$stmt->execute(['username' => $_POST['username']]);
if ($stmt->rowCount() === 0) {
    flash('Пользователь с такими данными не зарегистрирован');
    header('Location: login.php');
    exit;
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверяем пароль
if (password_verify($_POST['password'], $user['password_hash'])) {
    // При необходимости пересчитываем хэш на более современный
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE `users` SET `password_hash` = :password_hash WHERE `user_id` = :uid');
        $updateStmt->execute([
            'uid' => $user['user_id'],
            'password_hash' => $newHash,
        ]);
    }

    // Сохраняем user_id в сессии
    $_SESSION['user_id'] = $user['user_id'];

    // Проверяем, есть ли запись в таблице specialists для этого user_id
    $specStmt = $pdo->prepare("SELECT * FROM `specialists` WHERE `user_id` = :uid");
    $specStmt->execute(['uid' => $user['user_id']]);
    
    if ($specStmt->rowCount() === 0) {
        // Нет записи о специалисте — переходим на add_specialist.php
        header('Location: add_specialist.php');
        exit;
    } else {
        // Запись о специалисте есть — получаем specialist_id
        $specialist = $specStmt->fetch(PDO::FETCH_ASSOC);
        $specialist_id = $specialist['specialist_id'];
        
        // Генерация уникального токена сессии
        $session_token = bin2hex(random_bytes(32));

        // Вставка новой записи в таблицу sessions
        $sessionStmt = $pdo->prepare("INSERT INTO `sessions` (`specialist_id`, `session_token`) VALUES (:sid, :token)");
        $sessionStmt->execute([
            ':sid' => $specialist_id,
            ':token' => $session_token,
        ]);

        // Сохранение session_token в сессии для дальнейшего использования при выходе
        $_SESSION['session_token'] = $session_token;

        // Перенаправление на страницу tables.php
        header('Location: tables.php');
        exit;
    }
}

// Если пароль неверен
flash('Пароль неверен');
header('Location: login.php');
exit;
?>