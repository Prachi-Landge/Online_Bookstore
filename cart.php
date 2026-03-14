<?php
session_start();
require 'db.php';
require 'auth.php';

// Require authentication
requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch cart items from the database
$stmt = $pdo->prepare("SELECT c.*, b.* FROM cart c 
                       JOIN books b ON c.book_id = b.id 
                       WHERE c.user_id = ?
                       ORDER BY c.id DESC");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = array_reduce($cartItems, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

// Handle quantity updates
if (isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    if ($cart_id > 0 && $quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$quantity, $cart_id, $user_id]);
    }
    header('Location: cart.php');
    exit;
}

// Handle remove item
if (isset($_POST['remove_item'])) {
    $cart_id = intval($_POST['cart_id'] ?? 0);
    
    if ($cart_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    }
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
    <nav class="bg-navy p-4 sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white font-bold text-xl">BookStore</a>
                <a href="index.php" class="text-gray-300 hover:text-white transition">Books</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <a href="cart.php" class="text-white relative">
                    <img src="./cart.png" alt="Cart" class="h-9 w-9 hover:opacity-80 transition">
                </a>
                <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                    Logout
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
                                     class="w-32 h-40 object-cover rounded shadow-md"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                
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
                                                   class="w-20 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <button type="submit" 
                                                    name="update_quantity" 
                                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                                                Update
                                            </button>
                                            <button type="submit" 
                                                    name="remove_item" 
                                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
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
                        <a href="checkout.php" class="block w-full bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors text-center">
                            Proceed to Checkout
                        </a>
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
