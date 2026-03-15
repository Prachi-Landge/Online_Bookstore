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

    <!-- Cart Section -->
    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">Shopping Cart</h1>
        <p class="text-sm text-slate-300 mb-6">Review the books you’ve added before checking out.</p>
        
        <?php if (empty($cartItems)): ?>
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.9)] p-8 text-center">
                <p class="text-slate-300">Your cart is empty.</p>
                <a href="index.php" class="inline-block mt-4 px-6 py-2 bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 rounded-xl hover:-translate-y-[1px] transition-all shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Cart Items -->
                <div class="md:col-span-2 space-y-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_18px_50px_rgba(15,23,42,0.9)] p-6">
                            <div class="flex items-start gap-4">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="w-28 h-40 object-cover rounded-xl shadow-[0_16px_45px_rgba(15,23,42,0.9)]"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="font-semibold text-base md:text-lg text-white"><?php echo htmlspecialchars($item['title']); ?></h3>
                                            <p class="text-slate-300 text-sm"><?php echo htmlspecialchars($item['author']); ?></p>
                                            <p class="text-amber-300 font-semibold mt-2">$<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        
                                        <form method="post" class="flex items-center gap-2">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" 
                                                   name="quantity" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   class="w-20 px-3 py-2 border border-slate-700 bg-slate-900 rounded-md text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400 text-sm">
                                            <button type="submit" 
                                                    name="update_quantity" 
                                                    class="px-4 py-2 bg-sky-500 text-slate-950 rounded-xl hover:bg-sky-400 transition-colors text-xs font-semibold uppercase tracking-[0.16em]">
                                                Update
                                            </button>
                                            <button type="submit" 
                                                    name="remove_item" 
                                                    class="px-4 py-2 bg-red-500/10 text-red-300 border border-red-400/60 rounded-xl hover:bg-red-500/20 transition-colors text-xs font-semibold uppercase tracking-[0.16em]">
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
                    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6 sticky top-20">
                        <h2 class="text-xl font-display font-semibold mb-4 text-white">Cart Summary</h2>
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-slate-300 text-sm">Subtotal</span>
                                <span class="text-slate-100 text-sm">$<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-300 text-sm">Shipping</span>
                                <span class="text-emerald-300 text-sm font-medium">Free</span>
                            </div>
                            <div class="border-t border-slate-700 pt-2 font-semibold flex justify-between">
                                <span class="text-slate-100 text-sm">Total</span>
                                <span class="text-amber-300 text-lg">$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                        <a href="checkout.php" class="block w-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 py-2.5 rounded-xl hover:-translate-y-[1px] transition-all text-center text-sm font-semibold uppercase tracking-[0.18em] shadow-lg shadow-amber-500/25 hover:shadow-amber-400/40">
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
