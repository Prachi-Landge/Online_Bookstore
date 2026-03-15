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
    <!-- Modern Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                        display: ['Playfair Display', 'serif']
                    },
                    colors: {
                        'navy': '#1B2838',
                        'book-blue': '#2A475E'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-gray-100 font-sans">
    <!-- Navigation -->
    <nav class="backdrop-blur bg-navy/90 border-b border-white/5 p-4 sticky top-0 z-50 shadow-lg">
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white font-display text-2xl tracking-wide">
                    <span class="inline-block align-middle">BookStore</span>
                    <span class="ml-1 inline-block h-1 w-6 rounded-full bg-amber-400 align-middle"></span>
                </a>
                <a href="index.php" class="text-gray-300 hover:text-white transition">Books</a>
                <a href="wishlist.php" class="text-gray-300 hover:text-white transition">Wishlist</a>
                <a href="orders.php" class="text-gray-300 hover:text-white transition">My Orders</a>
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

    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">Checkout</h1>
        <p class="text-sm text-slate-300 mb-6">Confirm your shipping details and place your order.</p>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="md:col-span-2">
                <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6">
                    <h2 class="text-xl font-display font-semibold mb-4 text-white">Shipping Information</h2>
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-slate-300 mb-2 text-sm">Shipping Address</label>
                            <textarea name="shipping_address" rows="4" required
                                      class="w-full px-4 py-2 border border-slate-700 bg-slate-900 rounded-md text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                      placeholder="Enter your complete shipping address"></textarea>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" name="process_checkout" 
                                    class="w-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 py-3 rounded-xl hover:-translate-y-[1px] transition-all font-semibold text-lg shadow-lg shadow-amber-500/25 hover:shadow-amber-400/40">
                                Place Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="md:col-span-1">
                <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6 sticky top-20">
                    <h2 class="text-xl font-display font-semibold mb-4 text-white">Order Summary</h2>
                    <div class="space-y-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-start gap-3 pb-4 border-b border-slate-800">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="w-16 h-20 object-cover rounded-xl shadow-[0_16px_45px_rgba(15,23,42,0.9)]"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-sm text-slate-100"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-slate-400 text-xs">Qty: <?php echo $item['quantity']; ?></p>
                                    <p class="text-amber-300 font-semibold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="space-y-2 border-t pt-4">
                        <div class="flex justify-between">
                            <span class="text-slate-300 text-sm">Subtotal</span>
                            <span class="text-slate-100 text-sm">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-300 text-sm">Shipping</span>
                            <span class="text-emerald-300 text-sm font-medium">Free</span>
                        </div>
                        <div class="flex justify-between font-semibold text-lg border-t border-slate-700 pt-2">
                            <span class="text-slate-100 text-sm">Total</span>
                            <span class="text-amber-300 text-lg">$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
