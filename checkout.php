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

// Fetch latest rates for price calculation (if needed for any on-the-fly calculation, though cart items should have final prices)
$rate_query = $conn->query("SELECT * FROM rates ORDER BY updated_at DESC LIMIT 1");
$rate = $rate_query->fetch_assoc() ?? [];
$gold_rate = $rate['gold_rate'] ?? 0;
$silver_rate = $rate['silver_rate'] ?? 0;
$silver_925_rate = $rate['silver_925_rate'] ?? 0;
$making_charges = $rate['making_charges_percent'] ?? 0;

// Price Calculation Function (copied for consistency, though final prices are usually from get_cart_items.php)
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
    <title>Checkout - ChandraKala Jewellers</title>
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
        .btn-green {
            background-color: #22c55e; /* green-500 */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem; /* rounded-md */
            font-weight: 600; /* font-semibold */
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-green:hover {
            background-color: #16a34a; /* green-600 */
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <header class="bg-white shadow-md py-4 px-6 md:px-8 lg:px-12 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center">
            <img src="logo.png" alt="Jewellery Store Logo" class="h-10 w-auto mr-3">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900">Checkout</h1>
        </div>
        <nav>
            <a href="index1.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200 py-1 px-3">Home</a>
            <a href="gallery.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200 py-1 px-3">Shop</a>
            <a href="cart.php" class="text-gray-700 hover:text-blue-600 transition-colors duration-200 py-1 px-3">Cart</a>
        </nav>
    </header>

    <main class="container mx-auto p-4 sm:p-6 lg:p-8 flex-grow flex flex-col lg:flex-row gap-8">
        <!-- Order Summary Section -->
        <div class="lg:w-2/3 bg-white rounded-lg shadow-xl p-6 sm:p-8 order-2 lg:order-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Order Summary</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200 mb-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        </tr>
                    </thead>
                    <tbody id="cart-summary-body" class="bg-white divide-y divide-gray-200">
                        <!-- Cart items will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="loading-summary" class="text-center text-gray-600 mt-4 text-lg hidden">
                Loading order summary...
            </div>
            <div id="empty-summary" class="text-center mt-4 text-xl text-gray-500 hidden">
                Your cart is empty. Please add items to proceed.
            </div>
            <div class="flex justify-end items-center mt-6 pt-4 border-t border-gray-200">
                <span class="text-xl font-bold text-gray-900 mr-4">Total:</span>
                <span id="total-price" class="text-2xl font-extrabold text-green-700">₹ 0</span>
            </div>
        </div>

        <!-- Checkout Form Section -->
        <div class="lg:w-1/3 bg-white rounded-lg shadow-xl p-6 sm:p-8 order-1 lg:order-2">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Shipping Details</h2>
            <form action="success.php" method="post" id="checkout-form">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-semibold mb-2">Your Name</label>
                    <input type="text" id="name" name="name" placeholder="Full Name" class="p-3 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div class="mb-4">
                    <label for="address" class="block text-gray-700 text-sm font-semibold mb-2">Address</label>
                    <textarea id="address" name="address" placeholder="Shipping Address" rows="3" class="p-3 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required></textarea>
                </div>
                <div class="mb-6">
                    <label for="phone" class="block text-gray-700 text-sm font-semibold mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="e.g., +91 9876543210" class="p-3 border border-gray-300 rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required pattern="[0-9]{10,15}" title="Phone number must be 10-15 digits">
                </div>
                <button type="submit" class="btn-green w-full">Proceed to Payment</button>
            </form>
        </div>
    </main>

    <footer class="bg-gray-800 text-white text-center py-6 mt-10">
        <p>&copy; 2024 ChandraKala Jewellers. All rights reserved.</p>
    </footer>

    <!-- Custom Alert Modal Structure -->
    <div id="customAlertModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[101] hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl text-center max-w-sm mx-4">
            <p id="customAlertMessage" class="text-lg font-semibold text-gray-800 mb-4"></p>
            <div class="alert-buttons flex justify-center gap-4 mt-4">
                <button onclick="closeCustomAlert()" class="bg-blue-500 text-white px-5 py-2 rounded-md hover:bg-blue-600 transition-colors duration-200">OK</button>
            </div>
        </div>
    </div>

<script>
    // Custom Alert Modal Functions (reused from other pages)
    function showCustomAlert(message, isConfirm = false, onConfirm = null) {
        let alertModal = document.getElementById('customAlertModal');
        let alertMessage = document.getElementById('customAlertMessage');
        let alertButtons = alertModal.querySelector('.alert-buttons'); 

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

    // Function to load cart items and display summary
    async function loadOrderSummary() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartSummaryBody = document.getElementById('cart-summary-body');
        const loadingSummary = document.getElementById('loading-summary');
        const emptySummary = document.getElementById('empty-summary');
        const totalPriceElement = document.getElementById('total-price');
        const checkoutForm = document.getElementById('checkout-form');

        cartSummaryBody.innerHTML = ''; // Clear existing items
        loadingSummary.classList.remove('hidden'); // Show loading message
        checkoutForm.querySelector('button[type="submit"]').disabled = true; // Disable submit button initially

        if (cart.length === 0) {
            emptySummary.classList.remove('hidden');
            loadingSummary.classList.add('hidden');
            totalPriceElement.innerText = '₹ 0';
            return;
        }

        try {
            const response = await fetch('get_cart_items.php?ids=' + cart.join(','));
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            let total = 0;
            if (data.length === 0) {
                emptySummary.classList.remove('hidden');
            } else {
                emptySummary.classList.add('hidden');
                data.forEach(item => {
                    const finalPrice = Math.round(parseFloat(item.final_price));
                    total += finalPrice;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            <img src="uploads/${item.image}" alt="${item.name}" class="w-12 h-12 object-cover rounded-md mx-auto">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">${parseFloat(item.weight).toFixed(2)}g</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">₹ ${finalPrice}</td>
                    `;
                    cartSummaryBody.appendChild(tr);
                });
                checkoutForm.querySelector('button[type="submit"]').disabled = false; // Enable submit button
            }
            totalPriceElement.innerText = `₹ ${total}`;

        } catch (error) {
            console.error('Error fetching cart items for summary:', error);
            showCustomAlert('Failed to load order summary. Please try again.');
            emptySummary.classList.remove('hidden');
            emptySummary.innerText = 'Failed to load order summary. Please refresh the page.';
        } finally {
            loadingSummary.classList.add('hidden'); // Always hide loading message
        }
    }

    // Load order summary on page load
    window.onload = loadOrderSummary;

    // Prevent form submission if cart is empty
    document.getElementById('checkout-form').addEventListener('submit', function(event) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart.length === 0) {
            event.preventDefault(); // Stop form submission
            showCustomAlert('Your cart is empty. Please add items before proceeding to payment.');
        }
    });

</script>

</body>
</html>
