<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "kursov_proektbd");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['update'])) {
    $order_id = intval($_POST['order_id']);
    $order_status = $_POST['order_status'];
    $shipping_status = $_POST['shipping_status'];

    $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $update_shipping = $conn->prepare("UPDATE shipping SET shipping_status = ? WHERE order_id = ?");

    if ($update_order && $update_shipping) {
        $update_order->bind_param("si", $order_status, $order_id);
        $update_shipping->bind_param("si", $shipping_status, $order_id);

        $update_order->execute();
        $update_shipping->execute();

        $update_order->close();
        $update_shipping->close();

        $_SESSION['success_message'] = "Order and shipping status successfully updated!";
    } else {
        $_SESSION['error_message'] = "Failed to prepare statement for updating status.";
    }

    $conn->close();
    header("Location: admin_panel.php");
    exit();
}
?>
