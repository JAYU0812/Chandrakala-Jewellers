<?php
// catalogue.php

// --- Database Configuration ---
// IMPORTANT: Replace with your actual database credentials.
// For InfinityFree, these details are usually found in your cPanel.
$dbHost = 'sql101.infinityfree.com'; // Often 'localhost' for shared hosting
$dbUser = 'if0_38635607'; // e.g., 'epiz_XXXXXXX'
$dbPass = 'AYhfHKGaRAkxs0'; // Your database password
$dbName = 'if0_38635607_cjdb';   // e.g., 'epiz_XXXXXXX_yourdbname'

// --- Image Folder Path ---
// This is the folder where your jewellery images are stored.
// Assuming 'catalogue' folder is inside 'htdocs' and 'catalogue.php' is also in 'htdocs'
// or in a subfolder where 'catalogue' is a sibling.
$imageFolderPath = './catalogue/'; // Adjust if your images are in a different location relative to this script.

// --- Establish Database Connection ---
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Fetch Images from Database ---
// Assuming you have a table named 'products' with columns:
// 'id' (INT, PRIMARY KEY, AUTO_INCREMENT)
// 'name' (VARCHAR, e.g., 'Diamond Necklace')
// 'description' (TEXT, e.g., 'Exquisite diamond necklace...')
// 'image_filename' (VARCHAR, e.g., 'diamond_necklace_01.jpg')
$sql = "SELECT id, name, description, image_filename FROM products ORDER BY name ASC";
$result = $conn->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
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
    <title>Jewellery Catalogue - Elegance & Sparkle</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts for Inter (general text) and Playfair Display (headings) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Custom styles for the jewellery catalogue */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f8f8; /* Light background for elegance */
            color: #333;
        }
        h1, h2 {
            font-family: 'Playfair Display', serif; /* Elegant font for headings */
            color: #4a4a4a;
        }
        /* Custom hover effect for product tiles */
        .product-tile {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            border-radius: 1rem; /* Rounded corners for all elements */
            overflow: hidden; /* Ensure image respects rounded corners */
        }
        .product-tile:hover {
            transform: translateY(-8px) scale(1.03); /* Lift and slightly enlarge on hover */
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15); /* Stronger shadow on hover */
        }
        .product-image {
            width: 100%;
            height: 250px; /* Fixed height for consistent gallery appearance */
            object-fit: contain; /* Ensures full image is visible, preserving aspect ratio */
            background-color: #f0f0f0; /* Background color to fill empty space if aspect ratio differs */
            border-radius: 1rem; /* Rounded corners for the image itself */
        }
        /* Adjust image height for smaller screens */
        @media (max-width: 639px) { /* max-sm */
            .product-image {
                height: 200px; /* Slightly shorter for mobile */
            }
        }
        /* Responsive adjustments for grid */
        .grid-cols-responsive {
            /* Default for extra small to small screens */
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Adjusted min-width for smaller mobile devices */
        }
        @media (min-width: 640px) { /* sm breakpoint */
            .grid-cols-responsive {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        @media (min-width: 768px) { /* md breakpoint */
            .grid-cols-responsive {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
        @media (min-width: 1024px) { /* lg breakpoint */
            .grid-cols-responsive {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        /* Mobile Navigation Specific Styles */
        .mobile-nav-menu {
            transition: max-height 0.3s ease-out;
            max-height: 0;
            overflow: hidden;
        }
        .mobile-nav-menu.open {
            max-height: 200px; /* Adjust based on content height */
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Header Section -->
    <header class="bg-white shadow-sm py-4 lg:py-6">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <!-- Responsive Heading for Brand Name -->
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">CHANDRAKALA JEWELLERS</h1>
            
            <!-- Desktop Navigation -->
            <nav class="hidden md:block">
                <ul class="flex space-x-4 lg:space-x-6">
                    <li><a href="index1.php" class="text-gray-600 hover:text-gray-900 font-medium text-base lg:text-lg">Home</a></li>
                    <li><a href="https://chandrakalajewellers.wuaze.com/index1.php#about" class="text-gray-600 hover:text-gray-900 font-medium text-base lg:text-lg">About</a></li>
                    <li><a href="Catalogue.php" class="text-gray-900 font-bold border-b-2 border-a08060 pb-1 text-base lg:text-lg">Catalogue</a></li>
                    <li><a href="https://chandrakalajewellers.wuaze.com/index1.php#contact" class="text-gray-600 hover:text-gray-900 font-medium text-base lg:text-lg">Contact</a></li>
                </ul>
            </nav>

            <!-- Mobile Hamburger Icon -->
            <button id="mobile-menu-button" class="md:hidden p-2 rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-label="Toggle navigation">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation Menu (hidden by default) -->
        <nav id="mobile-menu" class="md:hidden bg-white mobile-nav-menu">
            <ul class="flex flex-col items-center py-4 space-y-4">
                <li><a href="index1.php" class="text-gray-600 hover:text-gray-900 font-medium text-lg">Home</a></li>
                <li><a href="https://chandrakalajewellers.wuaze.com/index1.php#about" class="text-gray-600 hover:text-gray-900 font-medium text-lg">About</a></li>
                <li><a href="Catalogue.php" class="text-gray-900 font-bold border-b-2 border-a08060 pb-1 text-lg">Catalogue</a></li>
                <li><a href="https://chandrakalajewellers.wuaze.com/index1.php#contact" class="text-gray-600 hover:text-gray-900 font-medium text-lg">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content Section -->
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        <!-- Responsive Main Heading -->
        <h2 class="text-2xl sm:text-3xl md:text-4xl text-center mb-8 md:mb-10 font-bold">Our Exquisite Jewellery Collection</h2>

        <?php if (empty($products)): ?>
            <div class="text-center text-base sm:text-lg text-gray-600 py-10">
                <p>No jewellery products found in the catalogue yet.</p>
                <p>Please add products to your database and ensure images are in the '<?php echo htmlspecialchars($imageFolderPath); ?>' folder.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8 grid-cols-responsive">
                <?php foreach ($products as $product): ?>
                    <div class="product-tile bg-white rounded-2xl shadow-lg hover:shadow-xl flex flex-col">
                        <img
                            src="<?php echo htmlspecialchars($imageFolderPath . $product['image_filename']); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            class="product-image"
                            onerror="this.onerror=null;this.src='https://placehold.co/400x250/E0E0E0/888888?text=Image+Not+Found';"
                        >
                        <!-- Product name and description are intentionally removed for a gallery-only view -->
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Section -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm sm:text-base">
            <p>&copy; <?php echo date('Y'); ?> Chandrakala Jewellers. All rights reserved.</p>
            <p class="text-xs sm:text-sm mt-2">Designed with elegance and passion.</p>
        </div>
    </footer>

    <script>
        // JavaScript for mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
        });
    </script>
</body>
</html>
