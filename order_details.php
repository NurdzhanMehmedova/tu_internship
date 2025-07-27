<?php
if (!isset($_GET['order_id'])) {
    echo "Invalid number of the order.";
    exit;
}

$order_id = intval($_GET['order_id']);

$conn = new mysqli("localhost", "root", "", "kursov_proektbd");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Грешка при свързване: " . $conn->connect_error);
}

$sql = "SELECT p.name AS product_name, od.quantity, od.price_per_unit AS unit_price
        FROM order_details od
        JOIN products p ON od.product_id = p.product_id
        WHERE od.order_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error: " . $conn->error);
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No details for this order.</p>";
} else {
    echo "<table style='width:100%; border-collapse: collapse;' border='1'>";
    echo "<tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
          </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['product_name']) . "</td>
                <td>" . intval($row['quantity']) . "</td>
                <td>" . number_format($row['unit_price'], 2) . " лв</td>
              </tr>";
    }
    echo "</table>";
}

$stmt->close();
$conn->close();
?>
