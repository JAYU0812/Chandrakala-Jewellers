<?php
require 'db.php';

$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

// Prepare SQL safely
$idList = implode(',', array_map('intval', $ids));
$query = "SELECT * FROM jewellery_items WHERE id IN ($idList)";
$result = $conn->query($query);

$items = [];

while($row = $result->fetch_assoc()) {
    $gold_rate = 0; $silver_rate = 0; $silver_925_rate = 0; $making = 0;

    // Get rates
    $rate = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1")->fetch_assoc();
    if ($rate) {
        $gold_rate = $rate['gold_rate'] ?? 0;
        $silver_rate = $rate['silver_rate'] ?? 0;
        $silver_925_rate = $rate['silver_925_rate'] ?? 0;
        $making = $rate['making_charges_percent'] ?? 0;
    }

    $metal = $row['metal_type'];
    $weight = $row['weight'];
    $manual_price = $row['manual_price'];

    if ($metal == 'gold') { $base = $gold_rate; }
    elseif ($metal == 'silver') { $base = $silver_rate; }
    elseif ($metal == 'silver_925') { $base = $silver_925_rate; }
    else { $base = 0; }

    $price = $base * $weight;
    $auto_price = $price + ($price * $making / 100);

    $final_price = ($manual_price && $manual_price > 0) ? $manual_price : $auto_price;

    $items[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'image' => $row['image'],
        'metal_type' => $metal,
        'weight' => $weight,
        'final_price' => $final_price
    ];
}

echo json_encode($items);
?>
