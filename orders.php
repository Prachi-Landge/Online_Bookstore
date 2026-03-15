<?php
session_start();
require 'db.php';
require 'auth.php';

requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order items for each order
foreach ($orders as &$order) {
    $items_stmt = $pdo->prepare("SELECT oi.*, b.title, b.author, b.cover 
                                 FROM order_items oi 
                                 JOIN books b ON oi.book_id = b.id 
                                 WHERE oi.order_id = ?");
    $items_stmt->execute([$order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BookStore</title>
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

    <?php if (isset($_GET['order_placed'])): ?>
    <div class="bg-emerald-500/10 border border-emerald-400/60 text-emerald-200 px-4 py-3 rounded-xl shadow-md container mx-auto mt-4 text-sm flex items-center justify-between gap-3" role="alert">
        <span><strong class="font-semibold">Order placed.</strong> We’re processing it now.</span>
        <a href="index.php" class="inline-flex items-center text-emerald-100 text-xs uppercase tracking-[0.18em] border border-emerald-300/40 rounded-full px-3 py-1 hover:bg-emerald-400/10 transition-colors">Continue browsing</a>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">My Orders</h1>
        <p class="text-sm text-slate-300 mb-6">Track your recent purchases and order details.</p>
        
        <?php if (empty($orders)): ?>
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.9)] p-12 text-center">
                <p class="text-slate-300 text-lg mb-4">You have no orders yet.</p>
                <a href="index.php" class="inline-block px-6 py-2 bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 rounded-xl hover:-translate-y-[1px] transition-all shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b border-slate-800">
                            <div>
                                <h2 class="text-xl font-display font-semibold text-white">Order #<?php echo $order['id']; ?></h2>
                                <p class="text-slate-400 text-sm">Placed on <?php echo date('F d, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                                    <?php 
                                    echo match($order['status']) {
                                        'pending' => 'bg-yellow-500/15 text-yellow-200 border border-yellow-400/60',
                                        'processing' => 'bg-blue-500/15 text-blue-200 border border-blue-400/60',
                                        'shipped' => 'bg-purple-500/15 text-purple-200 border border-purple-400/60',
                                        'delivered' => 'bg-green-500/15 text-green-200 border border-green-400/60',
                                        'cancelled' => 'bg-red-500/15 text-red-200 border border-red-400/60',
                                        default => 'bg-slate-700/60 text-slate-200 border border-slate-500/80'
                                    };
                                    ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <p class="text-xl font-semibold mt-2 text-amber-300">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['shipping_address'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-slate-400"><strong>Shipping Address:</strong></p>
                                <p class="text-slate-100 text-sm"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-3">
                            <h3 class="font-semibold text-slate-100 text-sm">Items</h3>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="flex items-start gap-4 p-3 bg-slate-900/70 rounded-xl border border-slate-800">
                                    <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="w-16 h-20 object-cover rounded-lg"
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-slate-100 text-sm"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <p class="text-xs text-slate-400"><?php echo htmlspecialchars($item['author']); ?></p>
                                        <p class="text-xs text-slate-400">Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <p class="font-semibold text-amber-300 text-sm">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
