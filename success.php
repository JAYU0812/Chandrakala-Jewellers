<?php
// --- START DEBUGGING: Enable Error Reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING ---

require 'db.php'; // Database connection

// Get payment data from URL (you can pass more data as needed)
// Using null coalescing operator for safer access and default values
$payment_id = isset($_GET['payment_id']) ? htmlspecialchars($_GET['payment_id']) : '';
$product_ids_str = isset($_GET['product_ids']) ? htmlspecialchars($_GET['product_ids']) : ''; // Expecting comma-separated IDs
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
$user_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Guest';
$user_mobile = isset($_GET['mobile']) ? htmlspecialchars($_GET['mobile']) : '';

$product_names = [];
$product_ids_array = [];

if (!empty($product_ids_str)) {
    $product_ids_array = array_map('intval', explode(',', $product_ids_str));
    
    // Fetch Product Names for display
    // Using prepared statement for multiple IDs
    $placeholders = implode(',', array_fill(0, count($product_ids_array), '?'));
    $stmt = $conn->prepare("SELECT name FROM jewellery_items WHERE id IN ($placeholders)");
    $types = str_repeat('i', count($product_ids_array)); // 'i' for integer
    $stmt->bind_param($types, ...$product_ids_array);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $product_names[] = htmlspecialchars($row['name']);
    }
    $stmt->close();
}

$product_display_name = !empty($product_names) ? implode(', ', $product_names) : 'Items';

// Save Payment to Database
// This logic assumes a single payment entry for a collection of products.
// If you need individual entries per product, this needs to be adjusted.
if (!empty($payment_id) && !empty($product_ids_array) && $amount > 0) {
    // Convert product_ids_array to a JSON string for storage, or iterate to insert each.
    // For simplicity, storing as a comma-separated string or JSON string.
    $product_ids_json = json_encode($product_ids_array);

    // Check if payment_id already exists to prevent duplicate entries on refresh
    $check_stmt = $conn->prepare("SELECT id FROM payments WHERE payment_id = ?");
    $check_stmt->bind_param("s", $payment_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows == 0) {
        $insert_stmt = $conn->prepare("INSERT INTO payments (payment_id, product_ids, amount, user_name, user_mobile) VALUES (?, ?, ?, ?, ?)");
        // 's' for product_ids (as JSON string), 'd' for amount, 's' for name, 's' for mobile
        $insert_stmt->bind_param("ssdds", $payment_id, $product_ids_json, $amount, $user_name, $user_mobile);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - ChandraKala Jewellers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* bg-gray-100 */
            color: #1f2937; /* text-gray-800 */
        }
        .tick-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex; /* Use flex to center tick */
            align-items: center;
            justify-content: center;
            border: 5px solid #4CAF50;
            position: relative;
            margin-bottom: 20px;
            animation: pop 0.4s ease forwards;
        }
        .tick-circle::after {
            content: '';
            position: absolute;
            /* Adjust top/left for better centering of the tick within the circle */
            top: 45%; 
            left: 50%;
            width: 25px;
            height: 50px;
            border-right: 5px solid #4CAF50;
            border-bottom: 5px solid #4CAF50;
            transform: translate(-50%, -50%) rotate(45deg) scale(0);
            transform-origin: center center;
            animation: tick 0.6s 0.3s ease forwards;
        }
        @keyframes tick {
            to { transform: translate(-50%, -50%) rotate(45deg) scale(1); }
        }
        @keyframes pop {
            0% { transform: scale(0); }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6">

    <div class="bg-white rounded-xl shadow-2xl p-8 sm:p-12 text-center max-w-md w-full">
        <div class="tick-circle mx-auto"></div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-green-600 mb-4">Payment Successful!</h1>
        <p class="text-lg text-gray-700 mb-2">Thank you for your purchase of:</p>
        <p class="text-xl font-semibold text-gray-900 mb-4"><strong><?= $product_display_name ?></strong></p>
        
        <?php if (!empty($payment_id)): ?>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <p class="text-sm text-gray-600 mb-1">Payment ID:</p>
                <p class="text-lg font-bold text-gray-800 break-words"><strong><?= $payment_id ?></strong></p>
            </div>
        <?php endif; ?>

        <?php if ($amount > 0): ?>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <p class="text-sm text-gray-600 mb-1">Paid Amount:</p>
                <p class="text-lg font-bold text-gray-800">₹<?= number_format($amount, 2) ?></p>
            </div>
        <?php endif; ?>

        <a href="gallery.php" class="inline-block bg-yellow-500 text-white font-bold py-3 px-8 rounded-full shadow-lg hover:bg-yellow-600 transition-all duration-300 transform hover:scale-105 mt-6">Continue Shopping</a>
    </div>

    <script>
        // Clear the cart from localStorage after successful payment
        localStorage.removeItem('cart');

        // Custom Alert Modal Functions (reused from other pages for consistency, though not directly used here)
        function showCustomAlert(message, isConfirm = false, onConfirm = null) {
            let alertModal = document.getElementById('customAlertModal');
            let alertMessage = document.getElementById('customAlertMessage');
            let alertButtons = alertModal.querySelector('.alert-buttons'); 

            if (!alertModal) { // Create modal if it doesn't exist
                alertModal = document.createElement('div');
                alertModal.id = 'customAlertModal';
                alertModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101] hidden';
                alertModal.innerHTML = `
                    <div class="bg-white p-6 rounded-lg shadow-xl text-center max-w-sm mx-4">
                        <p id="customAlertMessage" class="text-lg font-semibold text-gray-800 mb-4"></p>
                        <div class="alert-buttons flex justify-center gap-4 mt-4"></div>
                    </div>
                `;
                document.body.appendChild(alertModal);
                alertMessage = document.getElementById('customAlertMessage');
                alertButtons = alertModal.querySelector('.alert-buttons');
            }

            alertButtons.innerHTML = ''; // Clear previous buttons
            alertMessage.innerText = message;

            if (isConfirm) {
                const confirmBtn = document.createElement('button');
                confirmBtn.className = 'bg-red-500 text-white px-5 py-2 rounded-md hover:bg-red-600 transition-colors duration-200';
                confirmBtn.innerText = 'Yes';
                confirmBtn.onclick = () => {
                    closeCustomAlert();
                    if (onConfirm) onConfirm();
                };
                alertButtons.appendChild(confirmBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'bg-gray-300 text-gray-800 px-5 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200';
                cancelBtn.innerText = 'No';
                cancelBtn.onclick = closeCustomAlert;
                alertButtons.appendChild(cancelBtn);
            } else {
                const okBtn = document.createElement('button');
                okBtn.className = 'bg-blue-500 text-white px-5 py-2 rounded-md hover:bg-blue-600 transition-colors duration-200';
                okBtn.innerText = 'OK';
                okBtn.onclick = closeCustomAlert;
                alertButtons.appendChild(okBtn);
            }

            alertModal.classList.remove('hidden');
        }

        function closeCustomAlert() {
            document.getElementById('customAlertModal').classList.add('hidden');
        }
    </script>

</body>
</html>
