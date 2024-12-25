<?php
require_once __DIR__.'/boot.php';

include 'includes/db_connect.php';
$stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :username");
$stmt->execute(['username' => $_POST['username']]);
if ($stmt->rowCount() > 0) {
    flash('Это имя пользователя уже занято.');
    header('Location: /');
    die('Block');
}

$stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password_hash`) VALUES (:username, :password)");
$stmt->bindParam(':username',$_POST['username']);
$stmt->bindParam(':password', password_hash($_POST['password'], PASSWORD_DEFAULT));

try {
    $stmt->execute();

    // Генерируем оповещения для всех пациентов

    header(header: 'Location: login.php');
    exit;
} catch (PDOException $e) {
    die("Ошибка при регистрации пользователя: " . $e->getMessage());
}
