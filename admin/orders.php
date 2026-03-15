<?php
session_start();
require '../db.php';
require '../auth.php';

requireAdmin();

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($order_id > 0 && in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        header('Location: orders.php?updated=1');
        exit;
    }
}

// Fetch all orders
$stmt = $pdo->query("SELECT o.*, u.username 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC");
$orders = $pdo->query("SELECT o.*, u.username 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch order items for each order
foreach ($orders as &$order) {
    $items_stmt = $pdo->prepare("SELECT oi.*, b.title, b.author 
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
    <title>Manage Orders - Admin</title>
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
                    <span class="inline-block align-middle">Admin Panel</span>
                    <span class="ml-1 inline-block h-1 w-6 rounded-full bg-amber-400 align-middle"></span>
                </a>
                <a href="index.php" class="text-gray-300 hover:text-white transition">Dashboard</a>
                <a href="books.php" class="text-gray-300 hover:text-white transition">Books</a>
                <a href="categories.php" class="text-gray-300 hover:text-white transition">Categories</a>
                <a href="orders.php" class="text-gray-300 hover:text-white transition">Orders</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="../index.php" class="text-gray-300 hover:text-white transition">View Site</a>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">Manage Orders</h1>
        <p class="text-sm text-slate-300 mb-6">Review and update order statuses for your customers.</p>

        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-emerald-500/10 border border-emerald-400/60 text-emerald-200 px-4 py-3 rounded-xl mb-4 text-sm">Order status updated successfully.</div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.9)] p-12 text-center">
                <p class="text-slate-300 text-lg">No orders found.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b border-slate-800">
                            <div>
                                <h2 class="text-xl font-display font-semibold text-white">Order #<?php echo $order['id']; ?></h2>
                                <p class="text-slate-400 text-sm">Customer: <?php echo htmlspecialchars($order['username']); ?></p>
                                <p class="text-slate-400 text-sm">Date: <?php echo date('F d, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="text-right">
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" 
                                            class="px-3 py-2 border rounded-md text-sm
                                            <?php 
                                            echo match($order['status']) {
                                                'pending' => 'bg-yellow-500/15 text-yellow-200 border-yellow-400/60',
                                                'processing' => 'bg-blue-500/15 text-blue-200 border-blue-400/60',
                                                'shipped' => 'bg-purple-500/15 text-purple-200 border-purple-400/60',
                                                'delivered' => 'bg-green-500/15 text-green-200 border-green-400/60',
                                                'cancelled' => 'bg-red-500/15 text-red-200 border-red-400/60',
                                                default => 'bg-slate-700/60 text-slate-200 border-slate-500/80'
                                            };
                                            ?>">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                                <p class="text-xl font-semibold mt-2 text-amber-300">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['shipping_address'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-slate-400"><strong>Shipping Address:</strong></p>
                                <p class="text-slate-100 text-sm"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-2">
                            <h3 class="font-semibold text-slate-100 text-sm">Items</h3>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="flex justify-between p-3 bg-slate-900/70 rounded-xl border border-slate-800">
                                    <div>
                                        <p class="font-semibold text-slate-100 text-sm"><?php echo htmlspecialchars($item['title']); ?></p>
                                        <p class="text-xs text-slate-400"><?php echo htmlspecialchars($item['author']); ?> · Qty: <?php echo $item['quantity']; ?></p>
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
