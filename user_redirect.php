<?php
session_start();

// ako e lognat -> kum profila
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

// ako ne e lognat -> kum login
header("Location: login.php");
exit;
?>
