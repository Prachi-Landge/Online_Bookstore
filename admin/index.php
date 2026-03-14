<?php
session_start();
require '../db.php';
require '../auth.php';

requireAdmin();

$username = $_SESSION['username'] ?? 'Admin';

// Get statistics
$stats = [];

// Total books
$stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
$stats['books'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent orders
$stmt = $pdo->query("SELECT o.*, u.username 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC 
                     LIMIT 5");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookStore</title>
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
                <a href="users.php" class="text-gray-300 hover:text-white transition">Users</a>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <a href="../index.php" class="text-gray-300 hover:text-white transition">View Site</a>
                <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors">
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Books</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['books']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Users</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['users']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Orders</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo $stats['orders']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-gray-600 text-sm font-semibold mb-2">Total Revenue</h3>
                <p class="text-3xl font-bold text-orange-600">$<?php echo number_format($stats['revenue'], 2); ?></p>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Recent Orders</h2>
            <?php if (empty($recent_orders)): ?>
                <p class="text-gray-600">No orders yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-4">Order ID</th>
                                <th class="text-left py-2 px-4">User</th>
                                <th class="text-left py-2 px-4">Amount</th>
                                <th class="text-left py-2 px-4">Status</th>
                                <th class="text-left py-2 px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4">#<?php echo $order['id']; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td class="py-2 px-4">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-xs
                                            <?php 
                                            echo match($order['status']) {
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'shipped' => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                            ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="orders.php" class="text-blue-600 hover:underline">View All Orders →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
