<?php
session_start();
require 'db.php';
require 'auth.php';

requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch cart items
$stmt = $pdo->prepare("SELECT c.*, b.* FROM cart c 
                       JOIN books b ON c.book_id = b.id 
                       WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Calculate total
$total = array_reduce($cartItems, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

// Handle checkout
if (isset($_POST['process_checkout'])) {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    
    if (empty($shipping_address)) {
        $error = "Please provide a shipping address.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $total, $shipping_address]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items and clear cart
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['book_id'], $item['quantity'], $item['price']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            header('Location: orders.php?order_placed=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Checkout failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BookStore</title>
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

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Shipping Information</h2>
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Shipping Address</label>
                            <textarea name="shipping_address" rows="4" required
                                      class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Enter your complete shipping address"></textarea>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" name="process_checkout" 
                                    class="w-full bg-navy text-white py-3 rounded-md hover:bg-book-blue transition-colors font-semibold text-lg">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-20">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    <div class="space-y-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-start gap-3 pb-4 border-b">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="w-16 h-20 object-cover rounded"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-sm"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-gray-600 text-xs">Qty: <?php echo $item['quantity']; ?></p>
                                    <p class="text-blue-600 font-semibold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-2 border-t pt-4">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
