<?php

// --- START DEBUGGING: Enable Error Reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require 'db.php'; // Database connection

// Check if the database connection was successful
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fetch latest rates
// Ensure 'rates' table exists and has 'gold_rate', 'silver_rate', 'silver_925_rate', 'making_charges_percent', 'updated_at' columns
$rate_result = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1");
$rate = $rate_result->fetch_assoc();

$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;
$apply_making_to_silver = $rate['apply_making_to_silver'] ?? 0; // Get this from rates table

// Fetch up to 3 Hot Favourite Products
// Ensure 'jewellery_items' table exists and has 'is_favourite', 'id' columns
$items = $conn->query("SELECT * FROM jewellery_items WHERE is_favourite = 1 ORDER BY id DESC LIMIT 3");

// Price Calculation Function
function calculate_price($metal, $weight, $gold_rate, $silver_rate, $silver_925_rate, $making, $apply_making_to_silver_flag) {
    if ($metal == 'gold') {
        $base = $gold_rate;
    } elseif ($metal == 'silver') {
        $base = $silver_rate;
    } elseif ($metal == 'silver_925') { // This must match the ENUM value in your DB
        $base = $silver_925_rate;
    } else {
        $base = 0;
    }

    if ($base == 0 || $weight == 0) {
        return 0;
    }

    $price = $base * $weight;
    
    // Apply making charges only if metal is gold or if apply_making_to_silver is true for silver
    if ($metal === 'gold' || (($metal === 'silver' || $metal === 'silver_925') && $apply_making_to_silver_flag)) {
        $price += ($price * ($making / 100));
    }
    
    return $price;
}

// Metal Name Map for Display
function get_metal_name($metal) {
    $map = [
        'gold' => 'Gold',
        'silver' => 'Silver',
        'silver_925' => '925 Silver' // This must match the ENUM value in your DB
    ];
    return $map[$metal] ?? 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WELCOME TO CHANDRAKALA JEWELLERS</title>
<link rel="stylesheet" href="style.css">
<style>
@keyframes blink {
50% { opacity: 0.6; }
}
.blink { animation: blink 1s infinite; }
.rate-box {
background-color: #fffbe6;
padding: 15px;
border-radius: 10px;
margin-bottom: 15px;
box-shadow: 0 2px 5px rgba(0,0,0,0.1);
text-align: center;
}
.product-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
gap: 20px;
margin-top: 20px;
}
.product {
background: #fff;
border-radius: 10px;
padding: 10px;
box-shadow: 0 2px 5px rgba(0,0,0,0.1);
transition: transform 0.3s;
}
.product:hover { transform: scale(1.05); }
.product img {
width: 100%;
height: 180px;
object-fit: cover;
border-radius: 10px;
}
.btn {
display: inline-block;
padding: 8px 6px;
margin-top: 10px;
background-color: gold;
color: #000;
text-decoration: none;
border-radius: 5px;
font-weight: bold;
}
.btn:hover { background-color: #ffd700; }

/* New styles for the secondary 'Add to Cart' button */
.btn-secondary {
background-color: #f0f0f0; /* Lighter background */
color: #333; /* Darker text */
border: 1px solid #ccc; /* Subtle border */
}
.btn-secondary:hover {
background-color: #e0e0e0;
}

/* Flexbox for button alignment */
.product-actions {
display: flex;
justify-content: space-between; /* Space out buttons */
align-items: center;
margin-top: 10px; /* Adjust as needed */
gap: 10px; /* Gap between buttons */
}
.product-actions .btn {
flex-grow: 1; /* Allow buttons to grow and fill space */
text-align: center;
}

</style>
</head>
<body>

<header class="header">
<div class="header-content">
<img src="logo.png" alt="Jewellery Store Logo" class="header-logo">
<h1>Welcome To ChandraKala Jewellers</h1>
<nav>
<a href="index1.php">Home</a>
<a href="gallery.php">Shop</a>
<a href="#about">About Us</a>
<a href="#contact">Contact</a>
<a href="#blog">Blog</a>
</nav>
<div class="cart-icon">
<a href="cart.php" style="display: flex; align-items: center;">
<img src="cart.png" alt="Cart" style="width: 30px;">
<span id="cart-count" style="margin-left: 5px;">0</span>
</a>
</div>

</div>
</header>

<main class="container">

<!-- 🔔 Today's Rates -->
<section id="hero">
<h1>See and explore our latest jewellery collection</h1>
<p>Timeless pieces crafted with passion.</p>

<div class="rate-box">
<h3 style="margin-bottom: 10px;">OUR TODAY'S GOLD 916 RATE</h3>
<p>GOLD: ₹<?= number_format($gold_rate, 2) ?> per gram*</p>

</div>

<h2><b>The Jewellery product shown may vary from the actual product*</b></h2>

<a href="gallery.php" class="btn">Shop Now</a>
<a href="Catalogue.php" class="btn">Catalogue</a>
</section>

<!-- 💖 Hot Favourite Items -->
<section id="shop">
<h2>Hot Favourite Items</h2>
<div class="product-grid">
<?php
// Check if $items is a valid result set before fetching
if ($items) {
    while($row = $items->fetch_assoc()):
        $auto_price = calculate_price($row['metal_type'], $row['weight'], $gold_rate, $silver_rate, $silver_925_rate, $making_charges, $apply_making_to_silver);
        // Round the auto-calculated price to the nearest whole number
        $rounded_auto_price = round($auto_price);

        // If manual price exists, use it and round it; otherwise, use the rounded auto-price
        $final_price_display = (!is_null($row['manual_price']) && $row['manual_price'] > 0) ? round($row['manual_price']) : $rounded_auto_price;
        
        $metal_display = get_metal_name($row['metal_type']);
?>
<div class="product">
<img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
<h3><?= htmlspecialchars($row['name']) ?></h3>
<p><?= $metal_display ?> | <?= number_format($row['weight'], 2) ?>g</p>
<p>
<?php if (!is_null($row['manual_price']) && $row['manual_price'] > 0): ?>
<span style="text-decoration: line-through; color: #a00;">₹<?= number_format($rounded_auto_price, 0) ?></span><br>
<span class="blink" style="color: green; font-weight: bold;">₹<?= number_format(round($row['manual_price']), 0) ?></span>
<?php else: ?>
₹<?= number_format($rounded_auto_price, 0) ?>
<?php endif; ?>
</p>
<!-- Buttons are now wrapped in a div for better layout control -->
<div class="product-actions">
<button class="btn btn-secondary" onclick="addToCart(<?= $row['id'] ?>)">Add to Cart</button>
<a href="buynow.php?id=<?= $row['id'] ?>" class="btn">Buy Now!</a>
</div>
</div>
<?php
    endwhile;
} else {
    echo "<p>No hot favourite items found or an error occurred fetching them. Error: " . $conn->error . "</p>";
}
?>
</div>
</section>

<section id="about">
<h2>About Us</h2>
<p>We are a family-owned jewelry store dedicated to providing high-quality, handcrafted jewelry. Our passion for craftsmanship and attention to detail sets us apart.</p>
</section>

<section id="contact">
<h2>Contact Us</h2>
<p>Visit us at: CHANDRAKALA JEWELLERS, OPP. BHOOMI COMPLEX, CIVIL ROAD, KHEDBRAHMA-383255</p>
<p>Phone: +91 9427080359</p>
<p>Email: chandrakalajewellers849@gmail.com</p>
</section>

<section id="blog">
<h2>Blog</h2>
<div class="blog-posts">
<div class="blog-post">
<h3>Jewelry Care Tips</h3>
<p>Learn how to keep your jewelry sparkling with our expert care tips...</p>
<a href="blog-post.html" class="btn">Read More</a>
</div>
</div>
</section>

</main>

<footer>
<p>&copy; 2024 ChandraKala Jewellers</p>
</footer>

<script src="script.js"></script>
<script>
function addToCart(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    if (!cart.includes(productId)) {
        cart.push(productId);
        localStorage.setItem('cart', JSON.stringify(cart));
        alert('Product added to cart!');
        updateCartCount();
    } else {
        alert('Product already in cart!');
    }
}

function updateCartCount() {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    document.getElementById('cart-count').innerText = cart.length;
}

// Initialize count on page load
updateCartCount();
</script>

</body>
</html>
