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
                <a href="orders.php" class="text-gray-300 hover:text-white transition">Orders</a>
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
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative container mx-auto mt-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"> Your order has been placed successfully.</span>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-600 text-lg mb-4">You have no orders yet.</p>
                <a href="index.php" class="inline-block px-6 py-2 bg-navy text-white rounded-md hover:bg-book-blue transition-colors">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start mb-4 pb-4 border-b">
                            <div>
                                <h2 class="text-xl font-bold">Order #<?php echo $order['id']; ?></h2>
                                <p class="text-gray-600 text-sm">Placed on <?php echo date('F d, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
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
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <p class="text-xl font-bold mt-2">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['shipping_address'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-600"><strong>Shipping Address:</strong></p>
                                <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-3">
                            <h3 class="font-semibold">Items:</h3>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="flex items-start gap-4 p-3 bg-gray-50 rounded">
                                    <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                         class="w-16 h-20 object-cover rounded"
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                    <div class="flex-1">
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['author']); ?></p>
                                        <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?></p>
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
