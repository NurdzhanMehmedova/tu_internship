<?php
$conn = new mysqli("localhost", "root", "", "kursov_proektbd");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = intval($_GET['product_id']);

$stmt = $conn->prepare("SELECT size FROM stock WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$sizes = [];
while ($row = $result->fetch_assoc()) {
    $sizes[] = $row['size'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($sizes);
?>
