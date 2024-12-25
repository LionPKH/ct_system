<?php
include 'includes/db_connect.php';

// Инициализируем сессию
session_start();

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     
    session_destroy();   
}
$_SESSION['LAST_ACTIVITY'] = time(); 
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 60) {
    session_regenerate_id(true);    
    $_SESSION['CREATED'] = time(); 
}
function flash(?string $message = null)
{
    if ($message) {
        $_SESSION['flash'] = $message;
    } else {
        if (!empty($_SESSION['flash'])) { ?>
          <div class="alert alert-danger mb-3">
              <?=$_SESSION['flash']?>
          </div>
        <?php }
        unset($_SESSION['flash']);
    }
}

function check_auth(): bool
{
    return !!($_SESSION['user_id'] ?? false);
}
