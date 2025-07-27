<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];

    $conn = new mysqli("localhost", "root", "", "kursov_proektbd");
    $conn->set_charset("utf8");

    if ($conn->connect_error) {
        die("Грешка при свързване: " . $conn->connect_error);
    }

    // Проверка дали поръчката е на текущия потребител и дали е в подходящ статус
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

    if (in_array($status, ['pending', 'processing'])) {
        $update = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $update->bind_param("i", $order_id);
        $update->execute();
        $update->close();
    }

    $conn->close();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
