<?php
require 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Invalid Product.");

$product = $conn->query("SELECT * FROM jewellery_items WHERE id = $id")->fetch_assoc();
if (!$product) die("Product not found.");

$rate = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;

function calculate_price($metal, $weight, $gold, $silver, $silver_925, $making) {
    $base = 0;
    if ($metal == 'gold') $base = $gold;
    elseif ($metal == 'silver') $base = $silver;
    elseif ($metal == 'silver_925') $base = $silver_925;
    if ($base <= 0 || $weight <= 0) return 0;
    $price = $base * $weight;
    return $price + ($price * ($making / 100));
}

$auto_price = calculate_price($product['metal_type'], $product['weight'], $gold_rate, $silver_rate, $silver_925_rate, $making_charges);
$final_price = (!is_null($product['manual_price']) && $product['manual_price'] > 0) ? $product['manual_price'] : $auto_price;

$amount_in_paise = round($final_price * 100);  // Razorpay accepts amount in paise
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Now - <?= htmlspecialchars($product['name']) ?></title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f0f0f0; padding: 30px; }
        .buy-container { background: #fff; padding: 20px; border-radius: 10px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        img { max-width: 300px; border-radius: 10px; margin-bottom: 15px; }
        h1 { margin-bottom: 10px; }
        .price { font-size: 1.5rem; font-weight: bold; color: green; margin-top: 15px; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: gold; border-radius: 5px; text-decoration: none; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #ffd700; }
    </style>
</head>
<body>

<div class="buy-container">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    <p><strong>Weight:</strong> <?= number_format($product['weight'], 2) ?>g</p>
    <div class="price">₹ <?= number_format($final_price, 2) ?></div>

    <button class="btn" id="payBtn">Proceed to Payment</button><br><br>
    <a href="gallery.php">← Back to Gallery</a>
</div>

<script>
document.getElementById('payBtn').onclick = function(e){
    var options = {
        "key": "YOUR_KEY_ID",  // ✅ Replace with your Razorpay Key ID
        "amount": "<?= $amount_in_paise ?>", 
        "currency": "INR",
        "name": "ChandraKala Jewellers",
        "description": "Purchase of <?= htmlspecialchars($product['name']) ?>",
        "image": "logo.png",
        "handler": function (response){
            alert("Payment Successful!\nPayment ID: " + response.razorpay_payment_id);
            window.location.href = "success.php?payment_id=" + response.razorpay_payment_id + "&product_id=<?= $id ?>";
        },
        "prefill": {
            "name": "",
            "email": "",
            "contact": ""
        },
        "theme": {
            "color": "#F37254"
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.open();
    e.preventDefault();
}
</script>

</body>
</html>
