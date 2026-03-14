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
                <a href="index.php" class="text-white font-bold text-xl">Admin Panel</a>
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

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Manage Orders</h1>

        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Order status updated successfully!</div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-600 text-lg">No orders found.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b">
                            <div>
                                <h2 class="text-xl font-bold">Order #<?php echo $order['id']; ?></h2>
                                <p class="text-gray-600 text-sm">Customer: <?php echo htmlspecialchars($order['username']); ?></p>
                                <p class="text-gray-600 text-sm">Date: <?php echo date('F d, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="text-right">
                                <form method="post" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" 
                                            class="px-3 py-2 border rounded-md
                                            <?php 
                                            echo match($order['status']) {
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'shipped' => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                                <p class="text-xl font-bold mt-2">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['shipping_address'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600"><strong>Shipping Address:</strong></p>
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-2">
                            <h3 class="font-semibold">Items:</h3>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="flex justify-between p-3 bg-gray-50 rounded">
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($item['title']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['author']); ?> - Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <p class="font-semibold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
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
