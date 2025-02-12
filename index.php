<?php
session_start(); // Start the session to manage cart data
require 'db.php'; // Include the database connection

// Fetch all books from the database
$stmt = $pdo->query("SELECT * FROM books");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize the cart in the session if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $book_id = $_POST['book_id'];
    if (!in_array($book_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $book_id; // Add book ID to the cart
    }
    header('Location: index.php'); // Refresh the page
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Tailwind configuration
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#1B2838',
                        'book-blue': '#2A475E'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-navy p-4 sticky top-0 z-50">
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-6">
                <a href="#" class="text-white font-semibold">Books</a>
                <a href="#" class="text-gray-300 hover:text-white">Categories</a>
                <a href="#" class="text-gray-300 hover:text-white">Bookstore</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" 
                           id="searchInput"
                           placeholder="Search Books..." 
                           class="bg-book-blue text-white placeholder-gray-400 px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto"
                    >
                </div>
                <a href="#" class="text-gray-300 hover:text-white">Login</a>
                <a href="#" class="text-gray-300 hover:text-white">Sign Up</a>
                <a href="cart.php" class="text-white"> <!-- Link to cart.php -->
                    <img src="./cart.png" alt="Cart" class="h-9 w-9">
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-book-blue to-navy py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl text-white font-bold mb-6">Welcome to BookStore</h1>
            <div class="relative max-w-xl">
                <input type="text" 
                       placeholder="Search for books..." 
                       class="w-full px-4 py-3 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Book Categories -->
    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Book Categories</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($books as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:scale-105">
                    <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="h-48 mx-auto p-1">
                    <div class="p-4">
                        <div class="text-xs text-blue-600 font-semibold mb-2"><?php echo htmlspecialchars($book['category']); ?></div>
                        <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="text-blue-600 font-bold mt-2">$<?php echo number_format($book['price'], 2); ?></p>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <button type="submit" 
                                    name="add_to_cart" 
                                    class="w-full bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Book Details -->
    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Book Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php foreach (array_slice($books, 0, 5) as $book): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="h-48 p-1 mx-auto">
                    <div class="p-3">
                        <h3 class="font-semibold text-sm truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="text-gray-600 text-xs"><?php echo htmlspecialchars($book['author']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const bookElements = document.querySelectorAll('.grid > div');
            
            bookElements.forEach(element => {
                const title = element.querySelector('h3').textContent.toLowerCase();
                const author = element.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm)) {
                    element.style.display = '';
                } else {
                    element.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>