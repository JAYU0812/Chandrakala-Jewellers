<?php
// delete_c.php

// --- Database Configuration ---
// IMPORTANT: Replace with your actual database credentials.
// These should be the same as in your catalogue.php
$dbHost = 'sql101.infinityfree.com';
$dbUser = 'if0_38635607';
$dbPass = 'AYhfHKGaRAkxs0';
$dbName = 'if0_38635607_cjdb';

// --- Image Folder Path ---
$imageFolderPath = './catalogue/';

// --- Message Variable for Feedback ---
$message = '';
$message_type = ''; // 'success' or 'error'

// --- Establish Database Connection ---
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Handle Deletion Request ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = intval($_GET['delete_id']); // Sanitize input

    if ($id_to_delete > 0) {
        // 1. Fetch the image filename before deleting the database record
        $sql_select_filename = "SELECT image_filename FROM products WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select_filename);
        $stmt_select->bind_param("i", $id_to_delete);
        $stmt_select->execute();
        $stmt_select->bind_result($image_filename_to_delete);
        $stmt_select->fetch();
        $stmt_select->close();

        // 2. Delete the record from the database
        $sql_delete_db = "DELETE FROM products WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete_db);
        $stmt_delete->bind_param("i", $id_to_delete);

        if ($stmt_delete->execute()) {
            // 3. If database deletion is successful, attempt to delete the file
            if ($image_filename_to_delete) {
                $file_path = $imageFolderPath . $image_filename_to_delete;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        $message = "Product and image deleted successfully!";
                        $message_type = 'success';
                    } else {
                        $message = "Product deleted from database, but failed to delete image file: " . htmlspecialchars($image_filename_to_delete);
                        $message_type = 'error';
                    }
                } else {
                    $message = "Product deleted from database, but image file not found on server: " . htmlspecialchars($image_filename_to_delete);
                    $message_type = 'error';
                }
            } else {
                $message = "Product deleted successfully (no associated image filename found or image filename was empty).";
                $message_type = 'success';
            }
        } else {
            $message = "Error deleting product from database: " . $stmt_delete->error;
            $message_type = 'error';
        }
        $stmt_delete->close();
    } else {
        $message = "Invalid product ID provided.";
        $message_type = 'error';
    }
}

// --- Fetch all products to display (after potential deletion) ---
$sql_fetch_all = "SELECT id, name, image_filename FROM products ORDER BY name ASC";
$result_fetch_all = $conn->query($sql_fetch_all);

$products_to_display = [];
if ($result_fetch_all->num_rows > 0) {
    while($row = $result_fetch_all->fetch_assoc()) {
        $products_to_display[] = $row;
    }
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jewellery Images - Delete</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f8f8;
            color: #333;
        }
        .product-tile {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }
        .product-image-thumbnail {
            width: 100%;
            height: 150px; /* Smaller height for administration view */
            object-fit: contain;
            background-color: #f0f0f0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <header class="bg-white shadow-sm py-4">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 text-center">Manage Jewellery Images</h1>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg
                <?php echo ($message_type === 'success') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>
                border <?php echo ($message_type === 'success') ? 'border-green-400' : 'border-red-400'; ?>">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <h2 class="text-xl sm:text-2xl font-semibold text-gray-700 mb-6 text-center">Select an image to delete:</h2>

        <?php if (empty($products_to_display)): ?>
            <div class="text-center text-base sm:text-lg text-gray-600 py-10">
                <p>No images found in the catalogue to manage.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($products_to_display as $product): ?>
                    <div class="product-tile bg-white rounded-2xl shadow-md">
                        <img
                            src="<?php echo htmlspecialchars($imageFolderPath . $product['image_filename']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="product-image-thumbnail"
                            onerror="this.onerror=null;this.src='https://placehold.co/400x150/E0E0E0/888888?text=Image+Not+Found';"
                        >
                        <div class="p-4 flex flex-col justify-between flex-grow">
                            <p class="text-gray-700 text-center font-medium mb-3"><?php echo htmlspecialchars($product['name']); ?></p>
                            <a href="?delete_id=<?php echo htmlspecialchars($product['id']); ?>"
                               onclick="return confirm('Are you sure you want to delete &quot;<?php echo htmlspecialchars($product['name']); ?>&quot;? This action cannot be undone.');"
                               class="block w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg text-center text-sm transition duration-300 ease-in-out">
                                Delete Image
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm sm:text-base">
            <p>&copy; <?php echo date('Y'); ?> Chandrakala Jewellers. All rights reserved.</p>
            <p class="text-xs sm:text-sm mt-2">Admin Panel for Image Management</p>
        </div>
    </footer>

</body>
</html>