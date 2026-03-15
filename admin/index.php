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

    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">Dashboard</h1>
        <p class="text-sm text-slate-300 mb-8">Overview of your bookstore performance, orders and users.</p>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_18px_50px_rgba(15,23,42,0.9)] p-6">
                <h3 class="text-slate-400 text-xs font-semibold mb-1 uppercase tracking-[0.18em]">Total Books</h3>
                <p class="text-3xl font-semibold text-sky-400"><?php echo $stats['books']; ?></p>
            </div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_18px_50px_rgba(15,23,42,0.9)] p-6">
                <h3 class="text-slate-400 text-xs font-semibold mb-1 uppercase tracking-[0.18em]">Total Users</h3>
                <p class="text-3xl font-semibold text-emerald-400"><?php echo $stats['users']; ?></p>
            </div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_18px_50px_rgba(15,23,42,0.9)] p-6">
                <h3 class="text-slate-400 text-xs font-semibold mb-1 uppercase tracking-[0.18em]">Total Orders</h3>
                <p class="text-3xl font-semibold text-purple-400"><?php echo $stats['orders']; ?></p>
            </div>
            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_18px_50px_rgba(15,23,42,0.9)] p-6">
                <h3 class="text-slate-400 text-xs font-semibold mb-1 uppercase tracking-[0.18em]">Total Revenue</h3>
                <p class="text-3xl font-semibold text-amber-300">$<?php echo number_format($stats['revenue'], 2); ?></p>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6">
            <h2 class="text-xl font-display font-semibold mb-4 text-white">Recent Orders</h2>
            <?php if (empty($recent_orders)): ?>
                <p class="text-slate-400 text-sm">No orders yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-4 text-xs text-slate-400 uppercase tracking-[0.16em]">Order ID</th>
                                <th class="text-left py-2 px-4 text-xs text-slate-400 uppercase tracking-[0.16em]">User</th>
                                <th class="text-left py-2 px-4 text-xs text-slate-400 uppercase tracking-[0.16em]">Amount</th>
                                <th class="text-left py-2 px-4 text-xs text-slate-400 uppercase tracking-[0.16em]">Status</th>
                                <th class="text-left py-2 px-4 text-xs text-slate-400 uppercase tracking-[0.16em]">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr class="border-b border-slate-800 hover:bg-slate-900/60">
                                    <td class="py-2 px-4 text-sm text-slate-100">#<?php echo $order['id']; ?></td>
                                    <td class="py-2 px-4 text-sm text-slate-200"><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td class="py-2 px-4 text-sm text-slate-200">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs
                                            <?php 
                                            echo match($order['status']) {
                                                'pending' => 'bg-yellow-500/15 text-yellow-200 border border-yellow-400/60',
                                                'processing' => 'bg-blue-500/15 text-blue-200 border border-blue-400/60',
                                                'shipped' => 'bg-purple-500/15 text-purple-200 border border-purple-400/60',
                                                'delivered' => 'bg-green-500/15 text-green-200 border border-green-400/60',
                                                default => 'bg-slate-700/60 text-slate-200 border border-slate-500/80'
                                            };
                                            ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 text-sm text-slate-400"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <a href="orders.php" class="text-sky-400 hover:text-sky-300 text-sm underline underline-offset-4">View All Orders →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
