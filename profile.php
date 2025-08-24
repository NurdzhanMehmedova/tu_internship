<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "kursov_proektbd");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Грешка при свързване: " . $conn->connect_error);
}

// cqlata informaciq za potrebitelq
$sql_user = "SELECT username, email, full_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $full_name);
$stmt->fetch();
$stmt->close();

// Handle the selected status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the SQL query with the status filter
$sql_orders = "SELECT order_id, order_date, total_price, status FROM orders WHERE user_id = ?";

// Add the filter for status if it's set
if ($status_filter) {
    $sql_orders .= " AND status = ?";
}

// Add ordering
$sql_orders .= " ORDER BY order_date DESC";

$stmt_orders = $conn->prepare($sql_orders);
if ($status_filter !== '') {
    $status_filter = (string) $status_filter;
    $stmt_orders->bind_param("is", $user_id, $status_filter);
} else {
    $stmt_orders->bind_param("i", $user_id);
}

$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$sql = "SELECT category_id, name FROM product_categories";
$result = $conn->query($sql);

$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row["category_id"]] = $row["name"];
    }
}

$orders = [];
while ($row = $result_orders->fetch_assoc()) {
    $orders[] = $row;
}
$stmt_orders->close();
$conn->close();

$women_categories = [1, 2, 3, 4, 5, 6, 7, 9, 10];
$men_categories = [1, 2, 4, 5, 6, 8, 9, 10];
$kids_categories = [1, 2, 4, 5, 6, 8, 9];
$accessories_categories = [11, 12, 13, 14, 15, 16, 17];
?>


<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="nachalna_stranica.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #fff6f9;
            margin: 0;
            padding: 0;
        }
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(255, 80, 120, 0.1);
        }
        h1 {
            color: #ff4d79;
            text-align: center;
            margin-bottom: 20px;
        }
        .user-info, .order-history {
            margin-bottom: 30px;
        }
        .user-info p, .order-history p {
            font-size: 16px;
            color: #333;
            margin: 6px 0;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-table th, .order-table td {
            border: 1px solid #ffd6e0;
            padding: 10px;
            text-align: center;
        }
        .order-table th {
            background-color: #ffe6ec;
            color: #ff4d79;
        }
         /* Стилове за филтърния dropdown */
        .status-filter-container {
            margin-bottom: 30px;
            text-align: center;
        }

        .status-filter-container label {
            font-size: 16px;
            color: #333;
            margin-right: 10px;
        }

        .status-filter-container select {
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ffd6e0;
            background-color: #fff;
            color: #333;
            outline: none;
        }

        .status-filter-container button {
            background-color: #ff4d79;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-left: 10px;
        }

        .status-filter-container button:hover {
            background-color: #e8436f;
        }
        .logout {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #fff;
            background-color: #ff4d79;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
        .logout:hover {
            background-color: #e8436f;
        }
         #orderModal {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    #orderModalContent {
        background: #ffffff;
        padding: 30px;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 4px 15px rgba(255, 80, 120, 0.2);
        animation: fadeIn 0.3s ease;
    }

    #modalContent table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    #modalContent th, #modalContent td {
        padding: 12px;
        text-align: center;
        border: 1px solid #ffd6e0;
    }

    #modalContent th {
        background-color: #ffe6ec;
        color: #ff4d79;
        font-weight: bold;
    }

    #modalContent td {
        color: #555;
    }

    #orderModalContent button {
        background-color: #ff4d79;
        color: #ffffff;
        padding: 10px 20px;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        display: block;
        margin: 0 auto;
        font-size: 16px;
        transition: background-color 0.3s;
    }

    #orderModalContent button:hover {
        background-color: #e8436f;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
        .details-btn {
            background-color: #ff4d79;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .details-btn:hover {
            background-color: #e8436f;
        }
    </style>
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
<div class="profile-container">
        <h1>Welcome, <?= htmlspecialchars($full_name ?: $username) ?>!</h1>

        <div class="user-info">
            <h2>🔐 Profile Information</h2>
            <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
        </div>

        <!-- Order Status Filter -->
        <div class="status-filter-container">
        <form method="get" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <label for="status">Filter by Order Status:</label>
            <select name="status" id="status">
                <option value="">All</option>
                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit">Filter</button>
        </form>
        </div>
        <div class="order-history">
            <h2>🛍️ Order History</h2>
            <?php if (count($orders) > 0): ?>
                <table class="order-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_date']) ?></td>
                                <td><?= number_format($order['total_price'], 2) ?> лв</td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td>
    <button class="details-btn" data-order-id="<?= $order['order_id'] ?>">Details</button>
    <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
        <form method="post" action="cancel_order.php" style="display:inline;">
            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
            <button type="submit" onclick="return confirm('Are you sure you want to cancel this order?')" class="details-btn" style="background-color: #999; margin-left: 10px;">Cancel</button>
        </form>
    <?php endif; ?>
</td>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No history of orders.</p>
            <?php endif; ?>
        </div>

        <a href="logout.php" class="logout">Log Out</a>
    </div>

    <div id="orderModal">
        <div id="orderModalContent">
            <div id="modalContent">Loading...</div>
            <br>
            <button onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        const modal = document.getElementById("orderModal");
        const modalContent = document.getElementById("modalContent");

        function closeModal() {
            modal.style.display = "none";
            modalContent.innerHTML = "Loading...";
        }

        document.querySelectorAll(".details-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const orderId = this.dataset.orderId;

                fetch("order_details.php?order_id=" + orderId)
                    .then(res => res.text())
                    .then(data => {
                        modalContent.innerHTML = data;
                        modal.style.display = "flex";
                    })
                    .catch(err => {
                        modalContent.innerHTML = "Error with loading the details.";
                    });
            });
        });
    </script>
</body>
<footer style="background-color: #ffedf3; padding: 40px 20px; margin-top: 50px; border-top: 1px solid #ffd6e0;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 40px;">
        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: #ff4d79; margin-bottom: 15px;">Dressify</h3>
            <p style="color: #555; line-height: 1.6;">
                <a href="aboutUs.php" style="color: #ff4d79; text-decoration: none;">Learn more about us</a>
            </p>
        </div>

        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: #ff4d79; margin-bottom: 15px;">Contacts</h3>
            <p style="color: #555;">📞 Mobile Phone: +359 895 093 700</p>
            <p style="color: #555;">📧 Email: nurdzhann31@gmail.com</p>
            <p style="color: #555;">🕒 Bussiness hours: Mon-Fri: 9:00 - 18:00</p>
        </div>

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
</html>
