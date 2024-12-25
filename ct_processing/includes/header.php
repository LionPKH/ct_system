<!-- код header.php -->
<?php 
require_once 'boot.php';

if (!check_auth()) {
    header('Location: /ct_processing');
    die("");
} 
?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Веб-интерфейс "ct_system"</title>
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/patient.css">

    <script>
        function enableEdit(rowId) {
            var inputs = document.querySelectorAll('[data-row="' + rowId + '"]');
            inputs.forEach(function(input) {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '#fff';
            });
            document.getElementById('edit-btn-' + rowId).style.display = 'none';
            document.getElementById('save-btn-' + rowId).style.display = 'inline';
        }
    </script>
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