<?php
/**
 * Auth guard — include at the top of every protected admin page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
