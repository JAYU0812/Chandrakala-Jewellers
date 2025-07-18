<?php
require 'db.php';

// Fetch latest rates
$rate = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;

// Fetch all jewellery items
$items = $conn->query("SELECT * FROM jewellery_items ORDER BY id DESC");

// Price Calculation Function
function calculate_price($metal, $weight, $gold_rate, $silver_rate, $silver_925_rate, $making) {
    if ($metal == 'gold') {
        $base = $gold_rate;
    } elseif ($metal == 'silver') {
        $base = $silver_rate;
    } elseif ($metal == 'silver_925') {
        $base = $silver_925_rate;
    } else {
        $base = 0;
    }

    if ($base == 0 || $weight == 0) {
        return 0;
    }

    $price = $base * $weight;
    return $price + ($price * ($making / 100));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jewelry Collection</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #f5f5f5;
            color: #333;
            text-align: center;
        }
        h1 {
            font-size: 2.5rem;
            margin-top: 20px;
            font-family: 'Playfair Display', serif;
            color: #444;
        }
        .gallery-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .gallery-item {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: scale(1.05);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .gallery-item h3 {
            margin: 10px 0 5px;
            font-size: 1.1rem;
            color: #555;
        }
        .gallery-item p {
            margin-bottom: 10px;
            color: #777;
            font-size: 0.9rem;
        }
        .price {
            color: #000;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .back-btn {
            display: inline-block;
            margin: 25px auto;
            padding: 12px 24px;
            background: gold;
            color: black;
            font-size: 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #ffd700;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

    <h1>Our Exquisite Jewelry Collection</h1>
    <h2><b>The Jewellery product shown may vary from the actual product*</b></h2> 

    <div class="gallery-container">
        <?php while($item = $items->fetch_assoc()): 
            $auto_price = calculate_price($item['metal_type'], $item['weight'], $gold_rate, $silver_rate, $silver_925_rate, $making_charges);
            $final_price = (!is_null($item['manual_price']) && $item['manual_price'] > 0) ? $item['manual_price'] : $auto_price;
        ?>
            <div class="gallery-item">
                <a href="product.php?id=<?= $item['id'] ?>">
                    <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                </a>
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <p>(<?= ucwords(str_replace('_', ' ', $item['metal_type'])) ?> • <?= number_format($item['weight'], 2) ?>g)</p>
                <div class="price">₹<?= number_format($final_price, 2) ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <a href="index1.php" class="back-btn">← Back to Home</a>

</body>
</html>
