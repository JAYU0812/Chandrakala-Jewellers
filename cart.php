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

// Fetch latest rates for price calculation if needed (though cart items should already have final prices)
// For robustness, let's keep it, but it might not be strictly necessary if get_cart_items.php handles all pricing.
$rate_query = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1");
$rate = $rate_query->fetch_assoc() ?? [];
$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;

// Price Calculation Function (copied from index1.php for consistency, though get_cart_items.php should provide final prices)
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
    $final_calculated_price = $price + ($price * ($making / 100));
    return round($final_calculated_price); // Round to the nearest whole number
}

// Metal Name Map for Display
function get_metal_name($metal) {
    $map = [
        'gold' => 'Gold',
        'silver' => 'Silver',
        'silver_925' => '925 Silver'
    ];
    return $map[$metal] ?? 'Unknown';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - ChandraKala Jewellers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* bg-gray-100 */
            color: #1f2937; /* text-gray-800 */
        }
        /* Custom styles for buttons to ensure consistent look */
        .btn-primary {
            background-color: #f59e0b; /* yellow-500 */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600; /* font-semibold */
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary:hover {
            background-color: #d97706; /* yellow-600 */
            transform: translateY(-1px);
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
</head>
<body class="min-h-screen flex flex-col">

    <header class="bg-white shadow-md py-4 px-6 md:px-8 lg:px-12 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center">
            <img src="logo.png" alt="Jewellery Store Logo" class="h-10 w-auto mr-3">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900">Your Shopping Cart</h1>
        </div>
        <nav>
            <a href="index1.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200 py-1 px-3">Home</a>
            <a href="gallery.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200 py-1 px-3">Shop</a>
        </nav>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 flex-grow">
        <div class="cart-container bg-white rounded-lg shadow-xl p-6 sm:p-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-gray-900 mb-6">Your Items</h2>

            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table id="cart-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metal</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remove</th>
                        </tr>
                    </thead>
                    <tbody id="cart-body" class="bg-white divide-y divide-gray-200">
                        <!-- Cart Items Will Appear Here -->
                    </tbody>
                </table>
            </div>

            <div id="loading-message" class="text-center text-gray-600 mt-8 text-lg hidden">
                Loading cart items...
            </div>

            <div id="empty-cart" class="empty-message text-center mt-8 text-xl text-gray-500 hidden">
                Your cart is empty 🛒
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8">
                <button class="btn-primary bg-gray-300 text-gray-800 hover:bg-gray-400" onclick="window.location.href='gallery.php'">Continue Shopping</button>
                <button class="btn-primary" onclick="proceedToCheckout()">Proceed to Checkout</button>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white text-center py-6 mt-10">
        <p>&copy; 2024 ChandraKala Jewellers. All rights reserved.</p>
    </footer>

    <!-- Custom Alert Modal Structure (reused from index1.php) -->
    <div id="customAlertModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101] hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl text-center max-w-sm mx-4">
            <p id="customAlertMessage" class="text-lg font-semibold text-gray-800 mb-4"></p>
            <button onclick="closeCustomAlert()" class="bg-blue-500 text-white px-5 py-2 rounded-md hover:bg-blue-600 transition-colors duration-200">OK</button>
        </div>
    </div>

<script>
    // Custom Alert Modal Functions (reused from index1.php)
    function showCustomAlert(message) {
        let alertModal = document.getElementById('customAlertModal');
        let alertMessage = document.getElementById('customAlertMessage');
        alertMessage.innerText = message;
        alertModal.classList.remove('hidden');
    }

    function closeCustomAlert() {
        document.getElementById('customAlertModal').classList.add('hidden');
    }

    // Function to load cart items
    async function loadCartItems() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartTable = document.getElementById('cart-table');
        const emptyCartMessage = document.getElementById('empty-cart');
        const loadingMessage = document.getElementById('loading-message');
        const tbody = document.getElementById('cart-body');
        
        tbody.innerHTML = ''; // Clear existing items
        loadingMessage.classList.remove('hidden'); // Show loading message

        if (cart.length === 0) {
            cartTable.classList.add('hidden');
            emptyCartMessage.classList.remove('hidden');
            loadingMessage.classList.add('hidden'); // Hide loading message
            return;
        }

        try {
            const response = await fetch('get_cart_items.php?ids=' + cart.join(','));
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.length === 0) {
                cartTable.classList.add('hidden');
                emptyCartMessage.classList.remove('hidden');
            } else {
                cartTable.classList.remove('hidden');
                emptyCartMessage.classList.add('hidden');
                data.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            <img src="uploads/${item.image}" alt="${item.name}" class="w-16 h-16 object-cover rounded-md mx-auto">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${parseFloat(item.weight).toFixed(2)}g</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${item.metal_type.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">₹ ${Math.round(parseFloat(item.final_price))}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="btn-red" onclick="removeFromCart(${item.id})">Remove</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (error) {
            console.error('Error fetching cart items:', error);
            showCustomAlert('Failed to load cart items. Please try again.');
            cartTable.classList.add('hidden');
            emptyCartMessage.classList.remove('hidden');
            emptyCartMessage.innerText = 'Failed to load cart items. Please refresh the page.';
        } finally {
            loadingMessage.classList.add('hidden'); // Always hide loading message
        }
    }

    function removeFromCart(id) {
        showCustomAlert('Are you sure you want to remove this item from your cart?', true, () => {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart = cart.filter(item => item !== id);
            localStorage.setItem('cart', JSON.stringify(cart));
            loadCartItems(); // Reload cart items after removal
        });
    }

    // Modified showCustomAlert to include a confirmation callback
    function showCustomAlert(message, isConfirm = false, onConfirm = null) {
        let alertModal = document.getElementById('customAlertModal');
        let alertMessage = document.getElementById('customAlertMessage');
        let alertButtons = alertModal.querySelector('.alert-buttons'); // Get the buttons container

        if (!alertButtons) { // Create buttons container if it doesn't exist
            alertButtons = document.createElement('div');
            alertButtons.className = 'alert-buttons flex justify-center gap-4 mt-4';
            alertModal.querySelector('div').appendChild(alertButtons); // Append to the modal content div
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


    function proceedToCheckout() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart.length === 0) {
            showCustomAlert('Your cart is empty!');
        } else {
            window.location.href = 'checkout.php';
        }
    }

    // Load cart items on page load
    window.onload = loadCartItems;
</script>

</body>
</html>
