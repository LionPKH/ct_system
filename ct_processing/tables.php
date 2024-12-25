<!-- tables.php -->
<?php include 'includes/header.php'; ?>

<?php
// Параметры подключения к базе данных
$servername = "localhost";
$username = "root"; // По умолчанию в XAMPP
$password = ""; // По умолчанию пусто
$dbname = "ct_processing_system";

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Функция для вывода таблиц
function displayTable($conn, $tableName) {
    $sql = "SELECT * FROM $tableName";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<h2>" . ucfirst($tableName) . "</h2>";
        echo "<table><tr>";

        // Получаем метаданные (названия полей)
        $fields = $result->fetch_fields();
        foreach ($fields as $field_info) {
            echo "<th>" . htmlspecialchars($field_info->name) . "</th>";
        }
        echo "</tr>";

        // Возвращаемся в начало набора данных
        $result->data_seek(0);

        // Вывод данных
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            // Перебираем все поля строки
            foreach ($row as $key => $cell) {
                // Проверяем условие: если это таблица ct_images и поле image_path
                if ($tableName === 'ct_images' && $key === 'image_path') {
                    // Выводим <img>, предполагая, что в $cell уже лежит Base64-строка
                    echo "<td>";
                    // Если изображение JPEG, используйте 'image/jpeg'; если PNG — 'image/png'
                    echo "<img src=\"data:image/jpeg;base64," . htmlspecialchars($cell, ENT_QUOTES) . "\" alt=\"CT Image\" width=\"100\" />";
                    echo "</td>";
                } else {
                    // Обычный вывод текста
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "0 results for table: " . htmlspecialchars($tableName) . "<br>";
    }
}

// Вывод всех таблиц, включая ct_images
$tables = ['users', 'sessions', 'patients', 'hospitals', 'specialists', 'ct_images', 'reports'];
foreach ($tables as $table) {
    displayTable($conn, $table);
}

// Закрытие соединения
$conn->close();
?>

<?php include 'includes/footer.php'; ?>