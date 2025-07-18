<?php
// --- START DEBUGGING: Enable Error Reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require 'db.php'; // Database connection

$product = null;
$message = '';

// Fetch product details if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM jewellery_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        $message = "Product not found.";
    }
} else if (isset($_POST['update_item'])) {
    // Handle Update
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $desc = $conn->real_escape_string($_POST['description']);
    $weight = floatval($_POST['weight']);
    $metal = $conn->real_escape_string($_POST['metal_type']);
    $manual_price = ($_POST['manual_price'] !== '') ? floatval($_POST['manual_price']) : null;
    $is_fav = isset($_POST['is_fav']) ? 1 : 0;
    $old_image = $conn->real_escape_string($_POST['old_image']);
    $image = $old_image; // Default to old image

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        // Ensure 'uploads' directory exists and is writable
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        $new_image_name = $_FILES['image']['name'];
        if (move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $new_image_name)) {
            $image = $new_image_name;
            // Delete old image if it's different from the new one and exists
            if ($old_image && $old_image != $new_image_name && file_exists("uploads/" . $old_image)) {
                unlink("uploads/" . $old_image);
            }
        } else {
            $message = "Failed to upload new image.";
        }
    }

    // Check Hot Favourite Limit
    // Only check if trying to set as favorite and it wasn't already
    $current_is_fav_query = $conn->query("SELECT is_favourite FROM jewellery_items WHERE id = $id")->fetch_assoc();
    $current_is_fav = $current_is_fav_query['is_favourite'];

    if ($is_fav == 1 && $current_is_fav == 0) { // If trying to make it favorite and it wasn't
        $fav_count_query = $conn->query("SELECT COUNT(*) as total FROM jewellery_items WHERE is_favourite = 1 AND id != $id");
        $fav_count = $fav_count_query->fetch_assoc()['total'];
        if ($fav_count >= 3) {
            $is_fav = 0; // Don't mark as favorite if limit reached
            $message = "Hot Favourite limit (3 items) reached. This item will not be marked as favourite.";
        }
    }

    $manual_price_sql = is_null($manual_price) ? "NULL" : $manual_price;

    $stmt = $conn->prepare("UPDATE jewellery_items SET name = ?, image = ?, description = ?, weight = ?, metal_type = ?, manual_price = ?, is_favourite = ? WHERE id = ?");
    $stmt->bind_param("sssdssii", $name, $image, $desc, $weight, $metal, $manual_price_sql, $is_fav, $id);

    if ($stmt->execute()) {
        $message = "Product updated successfully!";
        // Re-fetch product to show updated details
        $stmt = $conn->prepare("SELECT * FROM jewellery_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
    } else {
        $message = "Error updating product: " . $conn->error;
    }
} else {
    $message = "No product ID provided.";
}

// Fetch Rates for calculation
$rate_query = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1");
$rate = $rate_query->fetch_assoc() ?? []; // Initialize as empty array if no rates found
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Mr. Hasmukh Hiralal Soni!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background-color: #3b82f6; /* blue-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600; /* font-semibold */
            transition: background-color 0.2s ease-in-out;
        }
        .btn-primary:hover {
            background-color: #2563eb; /* blue-600 */
        }
        .btn-green {
            background-color: #22c55e; /* green-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600; /* font-semibold */
            transition: background-color 0.2s ease-in-out;
        }
        .btn-green:hover {
            background-color: #16a34a; /* green-600 */
        }
        .btn-red {
            background-color: #ef4444; /* red-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600; /* font-semibold */
            transition: background-color 0.2s ease-in-out;
        }
        .btn-red:hover {
            background-color: #dc2626; /* red-600 */
        }
    </style>
    <script>
        function calculatePrice() {
            let metal = document.getElementById('metal_type').value;
            let weight = parseFloat(document.getElementById('weight').value) || 0;
            let gold = parseFloat(<?= $rate['gold_rate'] ?? 0 ?>);
            let silver = parseFloat(<?= $rate['silver_rate'] ?? 0 ?>);
            let silver925 = parseFloat(<?= $rate['silver_925_rate'] ?? 0 ?>);
            let making = parseFloat(<?= $rate['making_charges_percent'] ?? 0 ?>);

            let base = 0;
            if (metal === 'gold') base = gold;
            else if (metal === 'silver') base = silver;
            else if (metal === 'silver_925') base = silver925;

            let price = base * weight;
            // Apply making charges only if 'apply_making_to_silver' is true or metal is gold
            const applyMakingToSilver = <?= isset($rate['apply_making_to_silver']) ? $rate['apply_making_to_silver'] : 0 ?>;
            if (metal === 'gold' || (metal === 'silver' && applyMakingToSilver) || (metal === 'silver_925' && applyMakingToSilver)) {
                price += (price * (making / 100));
            }
            document.getElementById('calculated_price').innerHTML = "Calculated Price: ₹ " + price.toFixed(2);
        }

        window.onload = calculatePrice; // Call on page load
    </script>
</head>
<body class="bg-gray-100 p-4 sm:p-6 text-gray-800">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-xl p-6 sm:p-8">

        <h1 class="text-3xl sm:text-4xl font-bold mb-6 text-center text-gray-900">Edit Jewellery Item</h1>

        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form method="POST" enctype="multipart/form-data" class="bg-gray-50 p-6 rounded-lg shadow-inner mb-10 border border-gray-200">
                <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                <input type="hidden" name="old_image" value="<?= htmlspecialchars($product['image']) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($product['name']) ?>" placeholder="Item Name" class="p-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 w-full" required>
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-1">Weight (grams)</label>
                        <input type="number" name="weight" id="weight" value="<?= htmlspecialchars($product['weight']) ?>" placeholder="Weight (grams)" class="p-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 w-full" step="0.01" oninput="calculatePrice()" required>
                    </div>

                    <div>
                        <label for="metal_type" class="block text-sm font-medium text-gray-700 mb-1">Metal Type</label>
                        <select name="metal_type" id="metal_type" class="p-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 w-full" onchange="calculatePrice()" required>
                            <option value="gold" <?= ($product['metal_type'] == 'gold') ? 'selected' : '' ?>>Gold</option>
                            <option value="silver" <?= ($product['metal_type'] == 'silver') ? 'selected' : '' ?>>Silver</option>
                            <option value="silver_925" <?= ($product['metal_type'] == 'silver_925') ? 'selected' : '' ?>>925 Silver</option>
                        </select>
                    </div>

                    <div>
                        <label for="manual_price" class="block text-sm font-medium text-gray-700 mb-1">Manual Price (optional)</label>
                        <input type="number" name="manual_price" id="manual_price" value="<?= htmlspecialchars($product['manual_price'] ?? '') ?>" placeholder="Manual Price (optional)" class="p-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 w-full" step="0.01">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" placeholder="Description" class="p-3 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 w-full min-h-[100px]"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label for="current_image" class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <?php if ($product['image'] && file_exists("uploads/" . $product['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-32 h-32 object-cover rounded-md border border-gray-300 mb-4">
                    <?php else: ?>
                        <p class="text-gray-500 mb-4">No image uploaded or image not found.</p>
                    <?php endif; ?>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Upload New Image (optional)</label>
                    <input type="file" name="image" id="image" class="p-3 border border-gray-300 rounded-md bg-white w-full" accept="image/*">
                </div>
                
                <div class="mb-6 flex items-center">
                    <input type="checkbox" id="is_fav" name="is_fav" class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500" <?= ($product['is_favourite']) ? 'checked' : '' ?>>
                    <label for="is_fav" class="ml-2 text-gray-700">Mark as Hot Favourite (Max 3)</label>
                </div>

                <p class="mt-4 text-green-700 font-bold text-lg animate-pulse" id="calculated_price">Calculated Price: ₹ 0.00</p>

                <div class="flex justify-between items-center mt-6">
                    <button name="update_item" class="px-6 py-3 btn-green rounded-md shadow-md hover:shadow-lg">Update Item</button>
                    <a href="admin.php" class="px-6 py-3 btn-primary rounded-md shadow-md hover:shadow-lg">Back to Admin Panel</a>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center text-gray-600 text-lg">
                <p>Please go back to the <a href="admin.php" class="text-blue-600 hover:underline">Admin Panel</a> to select a product to edit.</p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
