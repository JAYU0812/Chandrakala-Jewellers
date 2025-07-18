<?php
require 'db.php';

// Validate and get product ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("Invalid Product.");
}

// Fetch product details
$product = $conn->query("SELECT * FROM jewellery_items WHERE id = $id")->fetch_assoc();
if (!$product) {
    die("Product not found.");
}

// Fetch latest rates
$rate_result = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1");
$rate = $rate_result->fetch_assoc();

$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;
$apply_making_to_silver = $rate['apply_making_to_silver'] ?? 0; // Get this from rates table

// Assign readable metal name
$metal_display = '';
switch ($product['metal_type']) {
    case 'gold':
        $metal_display = 'Gold';
        break;
    case 'silver':
        $metal_display = 'Silver';
        break;
    case 'silver_925':
        $metal_display = '925 Silver';
        break;
    default:
        $metal_display = 'Unknown';
}

// Price Calculation Function
function calculate_price($metal, $weight, $gold_rate, $silver_rate, $silver_925_rate, $making, $apply_making_to_silver_flag) {
    if ($metal == 'gold') {
        $base = $gold_rate;
    } elseif ($metal == 'silver') {
        $base = $silver_rate;
    } elseif ($metal == 'silver_925') {
        $base = $silver_925_rate;
    } else {
        $base = 0;
    }

    $price = $base * $weight;
    // Apply making charges only if metal is gold or if apply_making_to_silver is true for silver
    if ($metal === 'gold' || (($metal === 'silver' || $metal === 'silver_925') && $apply_making_to_silver_flag)) {
        $price += ($price * ($making / 100));
    }
    return $price;
}

// Calculate Auto Price & Final Price
$auto_price = calculate_price($product['metal_type'], $product['weight'], $gold_rate, $silver_rate, $silver_925_rate, $making_charges, $apply_making_to_silver);
$rounded_auto_price = round($auto_price); // Round the auto-calculated price

$final_price = (!is_null($product['manual_price']) && $product['manual_price'] > 0) ? round($product['manual_price']) : $rounded_auto_price;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
            color: #333;
            text-align: center;
            padding: 20px;
        }
        .product-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .product-image {
            width: 80%;
            max-width: 300px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .product-description {
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .product-weight {
            font-size: 0.9rem;
            margin-bottom: 10px;
            color: #666;
        }
        .product-price {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .add-to-cart-button {
            padding: 12px 24px;
            background: gold;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .add-to-cart-button:hover {
            background: #ffd700;
            transform: scale(1.05);
        }
        .back-to-gallery {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .back-to-gallery:hover {
            text-decoration: underline;
        }
        .strike {
            text-decoration: line-through;
            color: #999;
        }
        .highlight-price {
            color: green;
            font-weight: bold;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body>

    <div class="product-container">
        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">

        <p class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <p class="product-weight">Weight: <?= number_format($product['weight'], 2) ?>g (<?= $metal_display ?>)</p>

        <p class="product-price">
            <?php if (!is_null($product['manual_price']) && $product['manual_price'] > 0): ?>
                <span class="strike">₹<?= number_format($rounded_auto_price, 0) ?></span><br>
                <span class="highlight-price">₹<?= number_format(round($product['manual_price']), 0) ?></span>
            <?php else: ?>
                ₹<?= number_format($rounded_auto_price, 0) ?>
            <?php endif; ?>
        </p>

<button class="btn" onclick="addToCart(<?= $product['id'] ?>)">Add to Cart</button>
        <br>
        <a href="gallery.php" class="back-to-gallery">← Back to Gallery</a>
    </div>
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
    // Assuming there's an element with id 'cart-count' on this page, or it's part of a shared header.
    // If not, this line might cause an error or do nothing.
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.innerText = cart.length;
    }
}

// Initialize count on page load
updateCartCount();
</script>

</body>
</html>
