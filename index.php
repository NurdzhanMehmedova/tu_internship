<!DOCTYPE html>
<?php
session_start();
$conn = new mysqli("localhost", "root", "", "kursov_proektbd");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Грешка при свързване: " . $conn->connect_error);
}

$sql = "SELECT category_id, name FROM product_categories";
$result = $conn->query($sql);

$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row["category_id"]] = $row["name"];
    }
}


$conn->close();

$women_categories = [1, 2, 3, 4, 5, 6, 7, 9, 10];
$men_categories = [1, 2, 4, 5, 6, 8, 9, 10];
$kids_categories = [1, 2, 4, 5, 6, 8, 9];
$accessories_categories = [11, 12, 13, 14, 15, 16, 17];
?>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Онлайн Магазин за Дрехи</title>
    <link rel="stylesheet" href="nachalna_stranica.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="index.php"><h1>Dressify</h1></a>
        </div>
        <div class="category-menu">
            <ul>
                <li class="dropdown">
                    <a href="#">Women</a>
                    <ul class="dropdown-content">
                        <?php foreach ($women_categories as $id): ?>
                            <?php if (isset($categories[$id])): ?>
                                <li><a href="products.php?gender=2&category=<?= $id ?>"><?= htmlspecialchars($categories[$id]) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Men</a>
                    <ul class="dropdown-content">
                        <?php foreach ($men_categories as $id): ?>
                            <?php if (isset($categories[$id])): ?>
                                <li><a href="products.php?gender=1&category=<?= $id ?>"><?= htmlspecialchars($categories[$id]) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Kids</a>
                    <ul class="dropdown-content">
                        <?php foreach ($kids_categories as $id): ?>
                            <?php if (isset($categories[$id])): ?>
                                <li><a href="products.php?gender=3&category=<?= $id ?>"><?= htmlspecialchars($categories[$id]) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Accessory</a>
                    <ul class="dropdown-content">
                        <?php foreach ($accessories_categories as $id): ?>
                            <?php if (isset($categories[$id])): ?>
                                <li><a href="products.php?category=<?= $id ?>"><?= htmlspecialchars($categories[$id]) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <!--<li><a href="#">Sale</a></li>-->
                <li class="search-item">
                    <form action="search.php" method="get">
                        <input type="text" name="search" placeholder="Search category..." />
                        <button type="submit">Search</button>
                    </form>
                </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin_panel.php">Modify</a></li>
                    <?php endif; ?>

            </ul>
        </div>
        <div class="nav-icons">
    <a href="index.php"><img src="images/home.png" alt="Начало" class="home-icon"></a>
    <a href="cart.php"><img src="images/shoppingcart.png" alt="Количка" class="cart-icon"></a>
    <a href="user_redirect.php"><img src="images/profile_picture.png" alt="Профил" class="login-icon"></a>
</div>

    </div>
</header>

<main>
    <section>
        <h2>Newest products</h2>
        <p>Check out the latest offers in our store!</p>
        <div class="product-list">
            <?php
            $conn = new mysqli("localhost", "root", "", "kursov_proektbd");
            $conn->set_charset("utf8");

            if ($conn->connect_error) {
                die("Грешка при свързване: " . $conn->connect_error);
            }

            $sql = "SELECT product_id, name, description, price, image_url FROM products ORDER BY product_id DESC LIMIT 30";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
echo '<a href="product_details.php?id=' . $row["product_id"] . '">';
echo '<img src="' . $row["image_url"] . '" alt="' . htmlspecialchars($row["name"]) . '">';
echo '</a>';
                    echo '<p>' . htmlspecialchars($row["description"]) . '</p>';
                    echo '<p class="price">' . number_format($row["price"], 2) . ' bgn</p>';
                    echo '<form method="get" action="product_details.php">';
echo '<form method="get" action="product_details.php">';
echo '<input type="hidden" name="id" value="' . $row["product_id"] . '">';
echo '<div class="product-actions">';
echo '  <div class="quantity-and-button">';
echo '    <div class="quantity-wrapper">';
echo '      <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">-</button>';
echo '      <input type="number" name="quantity" value="1" min="1" class="quantity-input" readonly>';
echo '      <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">+</button>';
echo '    </div>';
echo '    <button type="submit" class="add-to-basket">🛒 Add</button>';
echo '  </div>';
echo '</div>';
echo '</form>';
echo '</div>';
                }
            } else {
                echo "<p>Not found items.</p>";
            }

            $conn->close();
            ?>
        </div>
    </section>
</main>

<footer style="background-color: #ffedf3; padding: 40px 20px; margin-top: 50px; border-top: 1px solid #ffd6e0;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 40px;">
        <!-- About Us -->
        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: #ff4d79; margin-bottom: 15px;">Dressify</h3>
            <p style="color: #555; line-height: 1.6;">
                <a href="aboutUs.php" style="color: #ff4d79; text-decoration: none;">Learn more about us</a>
            </p>
        </div>

        <!--Contacts -->
        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: #ff4d79; margin-bottom: 15px;">Contacts</h3>
            <p style="color: #555;">📞 Mobile Phone: +359 895 093 700</p>
            <p style="color: #555;">📧 Email: nurdzhann31@gmail.com</p>
            <p style="color: #555;">🕒 Bussiness hours: Mon-Fri: 9:00 - 18:00</p>
        </div>

        <!-- FB and Instagram -->
        <div style="flex: 1; min-width: 250px; text-align: center;">
            <h3 style="color: #ff4d79; margin-bottom: 15px;">Follow Us</h3>
            <div style="display: flex; justify-content: center; gap: 20px;">
                <a href="https://www.instagram.com/nurdzhann/" target="_blank" style="display: flex; align-items: center; justify-content: center;">
                    <img src="images/instagram.png" alt="Instagram" style="height: 36px;">
                </a>
                <a href="https://www.facebook.com/nurdzhann" target="_blank" style="display: flex; align-items: center; justify-content: center;">
                    <img src="images/facebook.jpg" alt="Facebook" style="height: 36px;">
                </a>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; color: #888;">
        © <?= date("Y") ?> Dressify. All rights reserved.
    </div>
</footer>

<script>
function changeQuantity(button, delta) {
    const input = button.parentElement.querySelector('.quantity-input');
    let value = parseInt(input.value);
    if (isNaN(value)) value = 1;
    value += delta;
    if (value < 1) value = 1;
    input.value = value;
}
</script>
</body>
</html>