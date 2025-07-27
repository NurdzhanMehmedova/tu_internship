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

$women_categories = [1, 2, 3, 4, 5, 6, 7, 9, 10];
$men_categories = [1, 2, 4, 5, 6, 8, 9, 10];
$kids_categories = [1, 2, 4, 5, 6, 8, 9];
$accessories_categories = [11, 12, 13, 14, 15, 16, 17];

$product_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$product_id) {
    die("Invalid product.");
}

$product_sql = "SELECT p.*, c.name AS category_name 
                FROM products p
                JOIN product_categories c ON p.category_id = c.category_id
                WHERE p.product_id = ?";
$stmt = $conn->prepare($product_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "The product is not found.";
    exit;
}

$product = $result->fetch_assoc();
$category_id = $product['category_id'];
$category_name = strtolower($product['category_name']);

$stock_sql = "SELECT size, quantity FROM stock WHERE product_id = ?";
$stock_stmt = $conn->prepare($stock_sql);

if (!$stock_stmt) {
    die("Error with prepare in stocks query: " . $conn->error);
}

$stock_stmt->bind_param("i", $product_id);
$stock_stmt->execute();
$stock_result = $stock_stmt->get_result();

$available_sizes = [];
while ($row = $stock_result->fetch_assoc()) {
    $available_sizes[$row['size']] = $row['quantity'];
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['rating']) && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $rating = intval($_POST['rating']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $user_id = intval($_SESSION['user_id']);

    $insert_sql = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
    $insert_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> – Детайли</title>
    <link rel="stylesheet" href="nachalna_stranica.css">
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
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .product-content {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: flex-start;
            justify-content: center;
        }

        .product-left img {
            max-width: 400px;
            width: 100%;
            border-radius: 10px;
        }

        .product-right {
            flex: 1;
            min-width: 300px;
        }

        .product-right .description {
            font-size: 16px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 15px;
        }

        .product-right .price {
            font-size: 22px;
            color: #e6005c;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .quantity-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 10px 20px;
    background-color: #ffe6eb;
    border-radius: 10px;
    width: fit-content;
    margin: 0 auto;
}

.quantity-btn,
.quantity-input {
    height: 40px;
    width: 40px;
    font-size: 18px;
    border-radius: 6px;
    text-align: center;
    padding: 0;
}

.quantity-btn {
    background-color: #ffb6c1;
    border: none;
    cursor: pointer;
}

.quantity-input {
    border: 1px solid #ccc;
    width: 50px;
}

        .add-to-cart-btn {
            background-color: #ff4d79;
            color: white;
            border: none;
            padding: 10px 20px;
            margin-top: 10px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        .add-to-cart-btn:hover {
            background-color: #ff1a57;
        }
        
        .sizes-button-group {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.size-btn {
    background-color: #ffb6c1;
    border: none;
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 5px;
    font-weight: bold;
    font-size: 16px;
}

.size-btn.active {
    background-color: #ff4d79;
    color: white;
}

.size-btn.disabled-size {
    background-color: #ddd !important;
    color: #999;
    text-decoration: line-through;
    cursor: not-allowed;
}
.info-box {
    background-color: #fff0f5;
    padding: 25px;
    border: 2px solid #ffb6c1;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}



        @media (max-width: 768px) {
            .product-content {
                flex-direction: column;
                align-items: center;
            }

            .product-left img {
                max-width: 100%;
            }

            .product-right {
                text-align: center;
            }
        }
 
.star-rating {
    font-size: 24px;
    color: #ccc;
    cursor: pointer;
    display: flex;
    gap: 8px;
    margin-top: 10px;
}
.star-rating span:hover,
.star-rating span:hover ~ span {
    color: #ffb6c1;
}
.reviews-container {
    background-color: #fff0f5;
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #ffb6c1;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    max-width: 1200px;
    margin: 40px auto;
}

.reviews-container h3 {
    font-size: 24px;
    color: #ff4d79;
    margin-bottom: 20px;
}

.review-item {
    margin-bottom: 25px;
    padding: 15px;
    background-color: #fff;
    border-radius: 8px;
    border: 1px solid #ffb6c1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.review-item:hover {
    transform: translateY(-5px);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.review-username {
    font-weight: bold;
    color: #ff4d79;
    font-size: 16px;
}

.review-rating {
    font-size: 18px;
    color: #ff4d79;
}

.star {
    color: #ccc;
    transition: color 0.3s ease;
}

.star.filled {
    color: #ff4d79;
}

.review-comment {
    font-size: 16px;
    color: #555;
    line-height: 1.6;
    margin-top: 10px;
}

.review-comment p {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .reviews-container {
        padding: 20px;
    }

    .review-item {
        padding: 10px;
    }

    .review-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .review-username {
        font-size: 14px;
    }

    .review-rating {
        font-size: 16px;
    }
}
#showAllReviewsBtn {
    margin-top: 15px;
    padding: 10px 20px;
    background-color: #ff4d79;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

#showAllReviewsBtn:hover {
    background-color: #e6005c;
    transform: translateY(-2px);
}
    </style>
</header>


<div class="product-detail-container">
    <h1><?= htmlspecialchars($product['name']) ?></h1>

    <div class="product-content">
        <div class="product-left">
            <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="info-box">
        <div class="product-right">
            <p class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <p class="price"><?= number_format($product['price'], 2) ?> BGN</p>
            <p id="available-stock" style="margin-top: 10px; font-size: 14px; color: #555;"></p>

            <form method="post" action="add_to_cart.php" onsubmit="return validateSizeSelection();">
                <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                <input type="hidden" name="price" value="<?= $product['price'] ?>">
                <input type="hidden" name="image" value="<?= htmlspecialchars($product['image_url']) ?>">

                <div class="size-selector">
                    <p>Размер:</p>
                    <div class="sizes-button-group">
                        <?php
if (in_array($category_id, $accessories_categories)) {
    echo '<button type="button" class="size-btn active" disabled>One Size</button>';
    echo '<input type="hidden" name="size" id="selected-size" value="One Size">';
    $is_one_size = true;
} else {
    $is_one_size = false;
    foreach ($available_sizes as $size => $quantity) {
    $available = $quantity > 0;
    echo '<button type="button" class="size-btn ' . (!$available ? 'disabled-size' : '') . '" 
            data-size="' . htmlspecialchars($size) . '" 
            data-quantity="' . intval($quantity) . '" 
            ' . (!$available ? 'disabled' : '') . '>' . htmlspecialchars($size) . '</button>';
}
    echo '<input type="hidden" name="size" id="selected-size" value="">';
}
?>
                    </div>
                </div>

                <div class="product-actions">
                    <div class="quantity-wrapper">
                        <button type="button" class="quantity-btn" onclick="changeQuantity(this, -1)">-</button>
                        <input type="number" name="quantity" value="1" min="1" class="quantity-input" readonly>
                        <button type="button" class="quantity-btn" onclick="changeQuantity(this, 1)">+</button>
                    </div>
                    <button type="submit" class="add-to-cart-btn large" id="add-to-cart-btn" <?= !$is_one_size && empty($_POST['size']) ? 'disabled' : '' ?>>🛒 Add</button>
                </div>

            </form>
        </div>
        </div>
    </div>
    
    <div class="reviews-container info-box" style="margin-top: 40px;">
    <h3>Product Reviews</h3>
    <?php
    // Fetch all reviews at once
    $reviews_sql = "SELECT r.rating, r.comment, u.username FROM reviews r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.product_id = ? ORDER BY r.review_id DESC";
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bind_param("i", $product_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();

    $review_count = 0;

    if ($reviews_result->num_rows > 0) {
        while ($review = $reviews_result->fetch_assoc()) {
            $review_count++;
            $rating = $review['rating'];
            $comment = nl2br(htmlspecialchars($review['comment']));
            $username = htmlspecialchars($review['username']);

            // First 3 reviews shown normally, others hidden
            $hidden_class = $review_count > 3 ? 'hidden-review' : '';
            ?>
            <div class="review-item <?= $hidden_class ?>">
                <div class="review-header">
                    <strong class="review-username"><?= $username ?></strong>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="review-comment"><?= $comment ?></p>
            </div>
            <?php
        }
    } else {
        echo "<p>No reviews yet for this product.</p>";
    }

    // Show button only if there are more than 3
    if ($review_count > 3) {
        echo '<button id="showAllReviewsBtn" onclick="showAllReviews()">Show All Reviews</button>';
    }
    ?>
</div>

<script>
    let reviewBatchSize = 3;
    let currentVisible = 3;

    // Initially hide all hidden reviews
    document.querySelectorAll('.hidden-review').forEach(el => {
        el.style.display = 'none';
    });

    function showAllReviews() {
        const hiddenReviews = document.querySelectorAll('.hidden-review');
        let shown = 0;

        for (let i = 0; i < hiddenReviews.length && shown < reviewBatchSize; i++) {
            if (hiddenReviews[i].style.display === 'none') {
                hiddenReviews[i].style.display = 'block';
                shown++;
                currentVisible++;
            }
        }

        // Check if more hidden reviews remain
        const remaining = Array.from(hiddenReviews).some(el => el.style.display === 'none');
        if (!remaining) {
            const btn = document.getElementById('showAllReviewsBtn');
            btn.disabled = true;
            btn.textContent = 'No more reviews';
            btn.style.backgroundColor = '#ccc';
            btn.style.cursor = 'default';
        }
    }
</script>

    
    <div class="review-box info-box" style="margin-top: 40px;">
    <h3>Leave a Review</h3>
    <?php if (isset($_SESSION['user_id'])): ?>
    <form method="post" id="review-form">
        <div class="star-rating" id="star-rating">
            <span data-value="1">★</span>
            <span data-value="2">★</span>
            <span data-value="3">★</span>
            <span data-value="4">★</span>
            <span data-value="5">★</span>
        </div>
        <input type="hidden" name="rating" id="rating-value" value="0">
        <textarea name="comment" placeholder="Your review..." required style="width: 100%; margin-top: 15px; padding: 10px; border-radius: 5px;"></textarea>
        <br><br>
        <button type="submit" class="add-to-cart-btn">Submit Review</button>
    </form>
    <?php else: ?>
        <p><a href="login.php">Log in</a> to write a review.</p>
    <?php endif; ?>
</div>
    
</div>

<footer style="background-color: #ffedf3; padding: 40px 20px; margin-top: 50px; border-top: 1px solid #ffd6e0;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 40px;">
        <!-- About Us -->
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

<script>
function changeQuantity(button, delta) {
    const input = button.parentElement.querySelector('.quantity-input');
    let value = parseInt(input.value);
    const max = parseInt(input.max) || Infinity;

    if (isNaN(value)) value = 1;

    value += delta;

    if (value < 1) value = 1;
    if (value > max) value = max;

    input.value = value;
}
 
document.querySelectorAll('.size-btn').forEach(button => {
    button.addEventListener('click', function () {
        if (this.disabled) return;
 
        // Clear other active states
        document.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
 
        const selected = document.getElementById('selected-size');
        if (selected) {
            selected.value = this.dataset.size;
        }
 
        const cartBtn = document.getElementById('add-to-cart-btn');
        if (cartBtn && selected.value !== '') {
            cartBtn.disabled = false;
        }
 
        // Use backticks here to insert variable
        const availableQty = this.dataset.quantity;
        const availableStock = document.getElementById('available-stock');
        if (availableStock) {
            availableStock.textContent = `Availability: ${availableQty} pieces`;
        }
 
        // Limit max quantity
        const qtyInput = document.querySelector('.quantity-input');
        if (qtyInput) {
            qtyInput.max = availableQty;
            if (parseInt(qtyInput.value) > parseInt(availableQty)) {
                qtyInput.value = availableQty;
            }
        }
    });
});
 
function validateSizeSelection() {
    const sizeInput = document.getElementById('selected-size');
    if (!sizeInput || sizeInput.value.trim() === '') {
        alert('Please, choose a size.');
        return false;
    }
    return true;
}
 
// One Size logic
document.addEventListener('DOMContentLoaded', function () {
    const selectedSize = document.getElementById('selected-size');
    const cartBtn = document.getElementById('add-to-cart-btn');
    const availableStock = document.getElementById('available-stock');
    const qtyInput = document.querySelector('.quantity-input');
 
    if (selectedSize && selectedSize.value === "One Size") {
        cartBtn.disabled = false;
 
        <?php if ($is_one_size): ?>
            const oneSizeQty = <?= isset($available_sizes['One Size']) ? json_encode($available_sizes['One Size']) : 1 ?>;
            if (availableStock) {
                availableStock.textContent = `Availability: ${oneSizeQty} pieces`;
            }
            if (qtyInput) {
                qtyInput.max = oneSizeQty;
                if (parseInt(qtyInput.value) > oneSizeQty) {
                    qtyInput.value = oneSizeQty;
                }
            }
        <?php endif; ?>
    }
});

document.querySelectorAll('#star-rating span').forEach(star => {
    star.addEventListener('click', function () {
        const rating = this.getAttribute('data-value');
        document.getElementById('rating-value').value = rating;

        // Highlight the stars up to the selected one
        document.querySelectorAll('#star-rating span').forEach(s => {
            s.style.color = s.getAttribute('data-value') <= rating ? '#ff4d79' : '#ccc';
        });
    });
});
</script>
</body>
</html>