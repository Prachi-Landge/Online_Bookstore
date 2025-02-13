<?php
session_start();
require 'db.php';

$user_id = 1; // Replace with actual user ID from session when you implement user authentication

// Fetch cart items from the database
$stmt = $pdo->prepare("SELECT c.*, b.* FROM cart c 
                       JOIN books b ON c.book_id = b.id 
                       WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = array_reduce($cartItems, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = $_POST['quantity'];
    
    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $user_id]);
    }
    header('Location: cart.php');
    exit;
}

// Handle remove item
if (isset($_POST['remove_item'])) {
    $cart_id = $_POST['cart_id'];
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - BookStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
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
                <a href="index.php" class="text-white font-semibold">Books</a>
                <a href="#" class="text-gray-300 hover:text-white">Categories</a>
                <a href="#" class="text-gray-300 hover:text-white">Bookstore</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" 
                           placeholder="Search Books..." 
                           class="bg-book-blue text-white placeholder-gray-400 px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto">
                </div>
                <a href="#" class="text-gray-300 hover:text-white">Login</a>
                <a href="#" class="text-gray-300 hover:text-white">Sign Up</a>
                <a href="cart.php" class="text-white">
                    <img src="./cart.png" alt="Cart" class="h-9 w-9">
                </a>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600">Your cart is empty</p>
                <a href="index.php" class="inline-block mt-4 px-6 py-2 bg-navy text-white rounded-md hover:bg-book-blue transition-colors">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Cart Items -->
                <div class="md:col-span-2 space-y-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-start gap-4">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="w-32 h-40 object-cover rounded"
                                     onerror="this.onerror=null; this.src='/path/to/default-cover.jpg';">
                                
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($item['title']); ?></h3>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($item['author']); ?></p>
                                            <p class="text-blue-600 font-bold mt-2">$<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        
                                        <form method="post" class="flex items-center gap-2">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   class="w-16 px-2 py-1 border rounded">
                                            <button type="submit" 
                                                    name="update_quantity" 
                                                    class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                                Update
                                            </button>
                                            <button type="submit" 
                                                    name="remove_item" 
                                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-20">
                        <h2 class="text-xl font-bold mb-4">Cart Summary</h2>
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <div class="border-t pt-2 font-bold flex justify-between">
                                <span>Total</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                        <button class="w-full bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors">
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Quantity update confirmation
        document.querySelectorAll('input[name="quantity"]').forEach(input => {
            input.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    </script>
</body>
</html>
