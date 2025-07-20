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

if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);

if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_name = basename($_FILES['image']['name']);
    $target_file = $target_dir . uniqid() . "_" . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));


    $check = getimagesize($_FILES['image']['tmp_name']);
    if($check !== false) {
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        } else {
            $_SESSION['error_message'] = "Image upload failed.";
            header("Location: admin_panel.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "File is not an image.";
        header("Location: admin_panel.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "No image file selected.";
    header("Location: admin_panel.php");
    exit();
}

    $category_id = intval($_POST['category_id']);
    $gender_id = intval($_POST['gender_id']);


    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category_id, gender_id) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssdssi", $name, $description, $price, $image_url, $category_id, $gender_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $product_id = $stmt->insert_id;

        $stmt->close();


        $size = $_POST['size'];

$stmt = $conn->prepare("INSERT INTO stock (product_id, size, quantity) VALUES (?, ?, ?)");
if ($stmt === false) {
    die("Prepare failed (stock): " . $conn->error);
}
$stmt->bind_param("isi", $product_id, $size, $quantity);
$stmt->execute();
$stmt->close();

        $_SESSION['success_message'] = "The product was successfully added!";
        header("Location: admin_panel.php");
        exit();
    } else {
         $_SESSION['error_message'] = "Failed to add the product.";
        header("Location: admin_panel.php");
        exit();
    }
}


if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);

    $stmt = $conn->prepare("DELETE FROM stock WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "The product was successfully deleted!";
    header("Location: admin_panel.php");
    exit();
}


if (isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $size = $_POST['update_size'];

    $stmt = $conn->prepare("UPDATE stock SET quantity = ? WHERE product_id = ? AND size = ?");
    if ($stmt === false) {
        die("Prepare failed (update stock): " . $conn->error);
    }
    $stmt->bind_param("iis", $quantity, $product_id, $size);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Stock updated successfully!";
    header("Location: admin_panel.php");
    exit();
}


$products = [];

$products_result = $conn->query("SELECT * FROM products ORDER BY product_id DESC");

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
} elseif (!$products_result) {
    die("Грешка при заявката към таблицата products: " . $conn->error);
}

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

$sql = "SELECT category_id, name FROM product_categories";
$result = $conn->query($sql);

if (!$result) {
    die("Грешка при изпълнение на заявката: " . $conn->error);
}


$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row["category_id"]] = $row["name"];
    }
}

$order_result = $conn->query("
    SELECT o.order_id, s.tracking_number
    FROM orders o
    JOIN shipping s ON o.order_id = s.order_id
    ORDER BY o.order_id DESC
");

$orders = [];
if ($order_result && $order_result->num_rows > 0) {
    while ($row = $order_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();

$women_categories = [1, 2, 3, 4, 5, 6, 7, 9, 10];
$men_categories = [1, 2, 4, 5, 6, 8, 9, 10];
$kids_categories = [1, 2, 4, 5, 6, 8, 9];
$accessories_categories = [11, 12, 13, 14, 15, 16, 17];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="nachalna_stranica.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панел</title>
</head>
<body>
    <style>
    .admin-panel {
        background-color: #ffedf3;
        font-family: Arial, sans-serif;
        padding: 20px;
        color: #333;
    }

    .admin-panel h2 {
        color: #ff4d79;
        text-align: center;
        margin-top: 40px;
    }

    .admin-panel input[type="text"],
    .admin-panel input[type="number"],
    .admin-panel textarea,
    .admin-panel select {
        width: 100%;
        padding: 12px 15px;
        margin: 10px 0 20px 0;
        border: 1px solid #ffd6e0;
        border-radius: 10px;
        background-color: #fff8fa;
        font-size: 16px;
        box-sizing: border-box;
    }

    .admin-panel button {
        background-color: #ff4d79;
        color: white;
        border: none;
        padding: 12px 20px;
        text-align: center;
        font-size: 16px;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 100%;
        margin-top: 10px;
    }

    .admin-panel button:hover {
        background-color: #e0446c;
    }

    .admin-panel p {
        text-align: center;
        background-color: #d4f4dd;
        color: #2d7a38;
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
.admin-content {
    display: flex;
    justify-content: space-between;
    gap: 40px;
    flex-wrap: wrap;
}

.left-panel, .right-panel {
    flex: 1;
    min-width: 300px;
}

.admin-panel form {
    background-color: #ffffff;
    padding: 30px;
    margin: 20px 0;
    border-radius: 20px;
    box-shadow: 0px 4px 10px rgba(255, 77, 121, 0.2);
}

.popup {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: #d4f4dd;
    color: #2d7a38;
    padding: 15px 25px;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
    font-size: 16px;
    z-index: 1000;
    animation: fadeOut 3s forwards;
}

.popup.error {
    background-color: #ffd6d6;
    color: #a10000;
}

@keyframes fadeOut {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; display: none; }
}


input[type="file"].styled-file {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0 20px 0;
    border: 1px solid #ffd6e0;
    border-radius: 10px;
    background-color: #fff8fa;
    font-size: 16px;
    box-sizing: border-box;
    cursor: pointer;
}

/* Малко по-добър ефект при hover */
input[type="file"].styled-file:hover {
    background-color: #ffe6ec;
}
</style>

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
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="popup success"><?= $_SESSION['success_message']; ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="popup error"><?= $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="admin-panel">
    <div class="admin-content">
        <div class="left-panel">
            <!-- Форма за добавяне на нов продукт -->
            <h2>Add New Product</h2>
<form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Product Name" required>
    <textarea name="description" placeholder="Description" required></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="file" name="image" accept="image/*" class="styled-file" required>
    <select name="category_id" id="category_id" required>
    <option value="">Select Category</option>
    <?php foreach ($categories as $id => $name): ?>
        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
    <?php endforeach; ?>
</select>
    <select name="gender_id" id="gender_id" required>
    <option value="">Select Gender</option>
    <option value="1">Male</option>
    <option value="2">Female</option>
    <option value="3">Kids</option>
</select>

    <select name="size" id="size" required></select>

    <input type="number" name="quantity" placeholder="Quantity for this size" required>

    <button type="submit" name="add_product">Add</button>
</form>

        </div>

        <div class="right-panel">
            <!-- Форма за редактиране на наличност -->
            <h2>Update Stock</h2>
<form method="post">
    <select name="product_id" required>
        <?php foreach ($products as $product): ?>
            <option value="<?= $product['product_id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <select name="update_size" id="update_size" required>
    <option value="">Select Size</option>
</select>


    <input type="number" name="quantity" placeholder="New Stock Quantity" required><br>

    <button type="submit" name="update_quantity">Update</button>
</form>


            <!-- Форма за изтриване на продукт -->
            <h2>Delete Product</h2>
            <form method="post">
                <select name="product_id" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['product_id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit" name="delete_product">Delete</button>
            </form>
            
            <!-- Форма за редакция на поръчка и доставка -->
<form method="post" action="update_status.php">
    <h3>Editing order and delivery status</h3>

    <label for="order_id">Tracking number:</label>
<select name="order_id" required>
    <option value="">Select Tracking Number</option>
    <?php foreach ($orders as $order): ?>
        <option value="<?= $order['order_id'] ?>">
            <?= "Tracking: " . htmlspecialchars($order['tracking_number']) . " (ID: " . $order['order_id'] . ")" ?>
        </option>
    <?php endforeach; ?>
</select>


    <label for="order_status">Order status:</label>
    <select name="order_status" required>
        <option value="pending">Pending</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
    </select>

    <label for="shipping_status">Shipping status:</label>
    <select name="shipping_status" required>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="in transit">In Transit</option>
        <option value="delivered">Delivered</option>
        <option value="returned">Returned</option>
    </select>

    <button type="submit" name="update">Save Changes</button>
</form>

        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sizeSelect = document.getElementById('size');
    const genderSelect = document.getElementById('gender_id');
    const categorySelect = document.getElementById('category_id');

    const sizes = {
        adults: ["S", "M", "L", "XL"],
        kids: ["4Y", "6Y", "8Y", "10Y", "12Y"],
        onesize: ["One Size"]
    };

    function updateSizeOptions() {
        const gender = parseInt(genderSelect.value);
        const category = parseInt(categorySelect.value);

        sizeSelect.innerHTML = "";
        let options = [];

        if ([1, 2].includes(gender) && category >= 1 && category <= 10) {
            options = sizes.adults;
        } else if (gender === 3 && category >= 1 && category <= 10) {
            options = sizes.kids;
        } else if (category >= 11 && category <= 17) {
            options = sizes.onesize;
        }

        sizeSelect.appendChild(new Option("Select Size", ""));
        options.forEach(size => {
            sizeSelect.appendChild(new Option(size, size));
        });
    }

    genderSelect.addEventListener("change", updateSizeOptions);
    categorySelect.addEventListener("change", updateSizeOptions);

    updateSizeOptions();

    const productSelect = document.querySelector('select[name="product_id"]');
    const updateSizeSelect = document.querySelector('select[name="update_size"]');

    if (productSelect && updateSizeSelect) {
        productSelect.addEventListener('change', function () {
            const productId = this.value;

            updateSizeSelect.innerHTML = '<option value="">Loading...</option>';

            fetch(`get_sizes.php?product_id=${productId}`)
                .then(response => response.json())
                .then(sizes => {
                    updateSizeSelect.innerHTML = '<option value="">Select Size</option>';
                    sizes.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size;
                        option.textContent = size;
                        updateSizeSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    updateSizeSelect.innerHTML = '<option value="">Error loading sizes</option>';
                    console.error('Error fetching sizes:', error);
                });
        });
    }
});
</script>

</body>
</html>