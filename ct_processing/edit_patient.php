<?php
require_once __DIR__ . '/boot.php';

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

/*
Логика:
1) Без параметров -> Выбираем больницу
2) С GET['hospital_id'] -> показываем список пациентов в выбранной больнице
3) С GET['patient_id'] и action=view -> просмотр данных пациента
4) С GET['patient_id'] и action=edit -> редактирование данных пациента
*/

// Определяем параметры из GET
$hospitalId = isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 0;
$patientId  = isset($_GET['patient_id'])  ? (int)$_GET['patient_id']  : 0;
$action     = isset($_GET['action'])      ? $_GET['action']           : '';

// Получение сообщения из GET, если есть
if (isset($_GET['message'])) {
    $message = "<div class='message success'>" . htmlspecialchars($_GET['message']) . "</div>";
}

// ------------------------------------------------------------------
// 0. Нет hospital_id и patient_id -> выводим список больниц
// ------------------------------------------------------------------
if ($hospitalId === 0 && $patientId === 0) {
    // Получаем список больниц
    try {
        $stmt = $pdo->query("SELECT hospital_id, hospital_name, address, contact_number FROM hospitals ORDER BY hospital_name");
        $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        exit("Ошибка при получении списка больниц: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Выбор больницы</title>
        <link rel="stylesheet" href="css/info.css">
        <!-- Подключаем Font Awesome для иконок -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-p+4dydO8Mj6eI+8ar8+FHQwCkhEwJoZ0t1W6C7/yZZ7t4Am67V+HhV6DEGLaE6j6i2w0Eo6Yq5bzZiq1p9rERw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <div class="container">
                <h1>Выберите больницу</h1>
                <?php
                    if (!empty($message)) {
                        echo $message;
                    }
                ?>
                <?php if (!empty($hospitals)): ?>
                    <div class="hospitals-grid">
                        <?php foreach($hospitals as $h): ?>
                            <div class="hospital-card">
                                <div class="hospital-icon">
                                    <i class="fas fa-hospital-symbol"></i>
                                </div>
                                <div class="hospital-content">
                                    <h2><?= htmlspecialchars($h['hospital_name']) ?></h2>
                                    <p><strong>Адрес:</strong> <?= htmlspecialchars($h['address'] ?: 'Адрес отсутствует.') ?></p>
                                    <p><strong>Контактный телефон:</strong> <?= htmlspecialchars($h['contact_number'] ?: 'Контактная информация отсутствует.') ?></p>
                                    <a href="edit_patient.php?hospital_id=<?= $h['hospital_id'] ?>" class="btn">Выбрать</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Нет зарегистрированных больниц.</p>
                <?php endif; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// ------------------------------------------------------------------
// 1. Есть hospital_id, но нет patient_id -> показываем список пациентов
// ------------------------------------------------------------------
if ($hospitalId > 0 && $patientId === 0) {
    // Получаем название больницы
    $hospitalName = "";
    try {
        $stmtH = $pdo->prepare("SELECT hospital_name FROM hospitals WHERE hospital_id = :hid");
        $stmtH->execute(['hid' => $hospitalId]);
        $resH = $stmtH->fetch(PDO::FETCH_ASSOC);
        if ($resH) {
            $hospitalName = $resH['hospital_name'];
        } else {
            exit("Больница с ID $hospitalId не найдена.");
        }
    } catch (PDOException $e) {
        exit("Ошибка при получении больницы: " . $e->getMessage());
    }

    // Получаем пациентов этой больницы
    try {
        $sql = "SELECT patient_id, full_name, date_of_birth FROM patients
                WHERE hospital_id = :hid
                ORDER BY patient_id";
        $stmtP = $pdo->prepare($sql);
        $stmtP->execute(['hid' => $hospitalId]);
        $patients = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        exit("Ошибка при получении пациентов: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Список пациентов</title>
        <link rel="stylesheet" href="css/info.css">
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
            <div class="container">
                <h1>Пациенты в больнице «<?= htmlspecialchars($hospitalName, ENT_QUOTES) ?>»</h1>
                <?php
                    if (!empty($message)) {
                        echo $message;
                    }
                ?>
                <p><a href="edit_patient.php">Вернуться к выбору больницы</a></p>

                <?php if (!empty($patients)): ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Дата рождения</th>
                            <th>Действие</th>
                        </tr>
                        <?php foreach($patients as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['patient_id'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($p['full_name'], ENT_QUOTES) ?></td>
                                <td><?= htmlspecialchars($p['date_of_birth'], ENT_QUOTES) ?></td>
                                <td>
                                    <a href="edit_patient.php?hospital_id=<?= $hospitalId ?>&patient_id=<?= $p['patient_id'] ?>&action=view">Просмотреть</a>
                                    |
                                    <a href="edit_patient.php?hospital_id=<?= $hospitalId ?>&patient_id=<?= $p['patient_id'] ?>&action=edit">Редактировать</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>В этой больнице пока нет пациентов.</p>
                <?php endif; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// ------------------------------------------------------------------
// 2. Есть hospital_id и patient_id, action=view -> просмотр данных пациента
// ------------------------------------------------------------------
if ($hospitalId > 0 && $patientId > 0 && $action === 'view') {
    try {
        // Получаем данные пациента
        $stmtPt = $pdo->prepare("
            SELECT p.patient_id, p.full_name, p.date_of_birth, p.additional_info, h.hospital_name
            FROM patients p
            JOIN hospitals h ON p.hospital_id = h.hospital_id
            WHERE p.patient_id = :pid AND p.hospital_id = :hid
        ");
        $stmtPt->execute([
            'pid' => $patientId,
            'hid' => $hospitalId
        ]);
        $patient = $stmtPt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            exit("Пациент с ID $patientId не найден или не относится к больнице $hospitalId");
        }

        // Получаем снимки пациента (ct_images, где patient_id = :pid)
        $stmtImg = $pdo->prepare("
            SELECT image_id, ct_name, image_path, processed_status
            FROM ct_images
            WHERE patient_id = :pid
        ");
        $stmtImg->execute(['pid' => $patientId]);
        $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

        // Для каждого снимка получаем связанные отчёты
        $reports = [];
        if (!empty($images)) {
            $image_ids = array_column($images, 'image_id');
            $in = str_repeat('?,', count($image_ids) - 1) . '?';
            $stmtReports = $pdo->prepare("SELECT report_id, image_id, report_text, report_date FROM reports WHERE image_id IN ($in) ORDER BY report_date DESC");
            $stmtReports->execute($image_ids);
            $reports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        exit("Ошибка при получении данных: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Просмотр пациента</title>
        <link rel="stylesheet" href="css/info.css">
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
            <div class="container">
                <h1>Просмотр данных пациента (ID: <?= htmlspecialchars($patient['patient_id'], ENT_QUOTES) ?>)</h1>
                <?php
                    if (!empty($message)) {
                        echo $message;
                    }
                ?>
                <p><a href="edit_patient.php?hospital_id=<?= htmlspecialchars($hospitalId) ?>">Вернуться к списку пациентов</a></p>

                <h2><?= htmlspecialchars($patient['full_name'], ENT_QUOTES) ?></h2>
                <ul>
                    <li><strong>Дата рождения:</strong> <?= htmlspecialchars($patient['date_of_birth'], ENT_QUOTES) ?></li>
                    <li><strong>Больница:</strong> <?= htmlspecialchars($patient['hospital_name'], ENT_QUOTES) ?></li>
                    <li><strong>Дополнительная информация:</strong> <?= nl2br(htmlspecialchars($patient['additional_info'], ENT_QUOTES)) ?></li>
                </ul>

                <hr>
                <h3>КТ-снимки пациента</h3>
                <?php if (!empty($images)): ?>
                    <div class="images-container">
                        <?php foreach ($images as $img): ?>
                            <div class="ct-image">
                                <p><strong><?= htmlspecialchars($img['ct_name'] ?: 'Без названия') ?></strong></p>
                                <img src="data:image/jpeg;base64,<?= htmlspecialchars($img['image_path']) ?>" alt="CT Image">
                                <p>Статус: <?= htmlspecialchars($img['processed_status']) ?></p>
                                <a href="create_report.php?patient_id=<?= $patientId ?>&image_id=<?= $img['image_id'] ?>" class="btn">Составить Отчёт</a>
                                
                                <?php
                                    // Отображение отчётов, связанных с этим снимком
                                    $image_reports = array_filter($reports, function($report) use ($img) {
                                        return $report['image_id'] == $img['image_id'];
                                    });
                                ?>
                                <?php if (!empty($image_reports)): ?>
                                    <div class="reports-section">
                                        <h4>Отчёты:</h4>
                                        <?php foreach ($image_reports as $report): ?>
                                            <div class="report">
                                                <p><strong>Дата:</strong> <?= htmlspecialchars($report['report_date']) ?></p>
                                                <p><strong>Описание:</strong> <?= htmlspecialchars(explode(' ', trim($report['report_text']))[0] ?? '') ?></p>
                                                <p>
                                                    <a href="view_report.php?report_id=<?= $report['report_id'] ?>" class="btn">Просмотреть</a>
                                                    <a href="edit_report.php?report_id=<?= $report['report_id'] ?>" class="btn">Изменить</a>
                                                    <form action="delete_report.php" method="post" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот отчёт?');">
                                                        <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                                        <button type="submit" class="btn delete-btn">Удалить</button>
                                                    </form>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Нет привязанных снимков.</p>
                <?php endif; ?>

                <hr>
               
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// ------------------------------------------------------------------
// 3. Есть hospital_id, patient_id, action=edit -> редактирование данных пациента
// ------------------------------------------------------------------
if ($hospitalId > 0 && $patientId > 0 && $action === 'edit') {
    // Получаем данные пациента
    try {
        $stmtPt = $pdo->prepare("
            SELECT patient_id, full_name, date_of_birth, additional_info, hospital_id
            FROM patients
            WHERE patient_id = :pid AND hospital_id = :hid
        ");
        $stmtPt->execute([
            'pid' => $patientId,
            'hid' => $hospitalId
        ]);
        $patient = $stmtPt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            exit("Пациент с ID $patientId не найден в больнице $hospitalId");
        }
    } catch (PDOException $e) {
        exit("Ошибка при получении пациента: " . $e->getMessage());
    }

    // Получаем список больниц для смены
    try {
        $sqlH = "SELECT hospital_id, hospital_name FROM hospitals ORDER BY hospital_name";
        $stmtH = $pdo->query($sqlH);
        $hospitals = $stmtH->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Ошибка при получении списка больниц: " . $e->getMessage();
    }

    // Получаем список КТ-снимков, доступных для привязки (т.е. unprocessed)
    try {
        $sqlImg = "SELECT image_id, ct_name, image_path, processed_status FROM ct_images
                   WHERE patient_id IS NULL OR patient_id = :pid
                   ORDER BY image_id ASC";
        $stmtImg = $pdo->prepare($sqlImg);
        $stmtImg->execute(['pid' => $patientId]);
        $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Ошибка при получении КТ-снимков: " . $e->getMessage();
    }

    // Обработка отправки формы
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $firstName        = trim($_POST["first_name"]);
        $lastName         = trim($_POST["last_name"]);
        $dateOfBirth      = trim($_POST["date_of_birth"]);
        $newHospitalId    = (int) $_POST["hospital_id"];
        $additionalInfo    = trim($_POST["additional_info"]);
        $selectedImageId  = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;

        // Собираем full_name
        $fullName = $firstName . " " . $lastName;

        try {
            // Обновляем данные пациента
            $updSql = "UPDATE patients
                       SET full_name = :full_name,
                           date_of_birth = :dob,
                           hospital_id = :hid,
                           additional_info = :ainfo
                       WHERE patient_id = :pid";
            $updStmt = $pdo->prepare($updSql);
            $updStmt->execute([
                'full_name' => $fullName,
                'dob'       => $dateOfBirth,
                'hid'       => $newHospitalId,
                'ainfo'     => $additionalInfo,
                'pid'       => $patientId
            ]);

            // Привязка КТ-снимка, если выбран
            if ($selectedImageId > 0) {
                // Проверяем, доступен ли снимок для привязки
                $chkStmt = $pdo->prepare("SELECT patient_id FROM ct_images WHERE image_id = :iid");
                $chkStmt->execute(['iid' => $selectedImageId]);
                $image = $chkStmt->fetch(PDO::FETCH_ASSOC);
                if ($image) {
                    if ($image['patient_id'] === null || $image['patient_id'] == $patientId) {
                        // Привязываем снимок
                        $imgUpdSql = "UPDATE ct_images SET patient_id = :pid WHERE image_id = :iid";
                        $imgUpdStmt = $pdo->prepare($imgUpdSql);
                        $imgUpdStmt->execute([
                            'pid' => $patientId,
                            'iid' => $selectedImageId
                        ]);
                        $message = "Данные пациента обновлены и КТ-снимок привязан.";
                    } else {
                        $message = "Этот КТ-снимок уже привязан к другому пациенту.";
                    }
                } else {
                    $message = "Выбранный КТ-снимок не найден.";
                }
            } else {
                $message = "Данные пациента успешно обновлены.";
            }

            // Обновляем данные пациента для отображения в форме
            $stmtPt->execute(['pid' => $patientId, 'hid' => $newHospitalId]);
            $patient = $stmtPt->fetch(PDO::FETCH_ASSOC);

            // Обновляем список изображений
            $stmtImg->execute(['pid' => $patientId]);
            $images = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "Ошибка при обновлении: " . $e->getMessage();
        }
    }

    // Разделяем full_name на имя и фамилию
    $parts = explode(' ', $patient['full_name'], 2);
    $firstName = $parts[0] ?? '';
    $lastName  = $parts[1] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Редактирование пациента #<?= htmlspecialchars($patientId, ENT_QUOTES) ?></title>
        <link rel="stylesheet" href="css/info.css">
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
            <div class="container">
                <h1>Редактирование пациента (ID: <?= htmlspecialchars($patientId) ?>)</h1>
                <?php
                    if (!empty($message)) {
                        echo "<div class='message " . (strpos($message, 'Ошибка') !== false ? 'error' : 'success') . "'>" . $message . "</div>";
                    }
                ?>
                <p><a href="edit_patient.php?hospital_id=<?= htmlspecialchars($hospitalId) ?>">Вернуться к списку пациентов</a></p>

                <form method="post" action="">
                    <label>Имя:
                        <input type="text" name="first_name" value="<?= htmlspecialchars($firstName) ?>" required>
                    </label>
                    <label>Фамилия:
                        <input type="text" name="last_name" value="<?= htmlspecialchars($lastName) ?>" required>
                    </label>
                    <label>Дата рождения:
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($patient['date_of_birth']) ?>" required>
                    </label>
                    <label>Больница:
                        <select name="hospital_id" required>
                            <option value="">Выберите больницу</option>
                            <?php foreach($hospitals as $h): ?>
                                <option value="<?= htmlspecialchars($h['hospital_id']) ?>" <?= $h['hospital_id'] == $patient['hospital_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($h['hospital_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Дополнительная информация:<br>
                        <textarea name="additional_info" rows="4"><?= htmlspecialchars($patient['additional_info']) ?></textarea>
                    </label>
                    <hr>
                    <h3>Привязать КТ-снимок</h3>
                    <label>Выберите КТ-снимок:
                        <select name="image_id">
                            <option value="">-- Не выбирать --</option>
                            <?php foreach ($images as $img): ?>
                                <option value="<?= htmlspecialchars($img['image_id']) ?>" <?= ($img['patient_id'] == $patientId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($img['ct_name'] ?: 'Без названия') ?> <?= ($img['patient_id'] == $patientId) ? '(уже привязан)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit">Сохранить</button>
                </form>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}
?>