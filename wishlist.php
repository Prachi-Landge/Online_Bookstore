<?php
session_start();
require 'db.php';
require 'auth.php';

requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Handle remove from wishlist
if (isset($_POST['remove_wishlist'])) {
    $book_id = intval($_POST['book_id'] ?? 0);
    if ($book_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
        header('Location: wishlist.php?removed=1');
        exit;
    }
}

// Fetch wishlist items
$stmt = $pdo->prepare("SELECT w.*, b.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating
                       FROM wishlist w
                       JOIN books b ON w.book_id = b.id
                       LEFT JOIN categories c ON b.category_id = c.id
                       LEFT JOIN reviews r ON b.id = r.book_id
                       WHERE w.user_id = ?
                       GROUP BY b.id
                       ORDER BY w.created_at DESC");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - BookStore</title>
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

    <?php if (isset($_GET['removed'])): ?>
    <div class="bg-emerald-500/10 border border-emerald-400/60 text-emerald-200 px-4 py-3 rounded-xl shadow-md container mx-auto mt-4 text-sm" role="alert">
        <strong class="font-semibold">Removed.</strong>
        <span class="ml-1"> Book removed from wishlist.</span>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-2 text-white">My Wishlist</h1>
        <p class="text-sm text-slate-300 mb-6">Save books you’re interested in and come back anytime.</p>
        
        <?php if (empty($wishlist_items)): ?>
            <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.9)] p-12 text-center">
                <p class="text-slate-300 text-lg mb-4">Your wishlist is empty</p>
                <a href="index.php" class="inline-block px-6 py-2 bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 rounded-xl hover:-translate-y-[1px] transition-all shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40">
                    Browse Books
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-7">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="group bg-slate-950/60 border border-slate-800/80 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.85)] overflow-hidden transition-all duration-500 hover:shadow-[0_30px_90px_rgba(15,23,42,0.95)] hover:-translate-y-2 hover:border-amber-400/60 relative">
                        <a href="book.php?id=<?php echo $item['id']; ?>">
                            <div class="relative overflow-hidden">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="h-64 w-full object-cover scale-105 transform transition-transform duration-700 group-hover:scale-110"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                <?php if ($item['category_name']): ?>
                                    <div class="absolute top-2 right-2">
                                        <span class="bg-slate-950/80 backdrop-blur text-xs px-2.5 py-1 rounded-full text-amber-300 border border-amber-300/40 shadow-md">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-100 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent transition-opacity duration-500"></div>
                            </div>
                        </a>
                        <div class="p-4 space-y-3">
                            <a href="book.php?id=<?php echo $item['id']; ?>">
                                <h3 class="font-semibold text-base md:text-lg mb-1 text-white line-clamp-2 group-hover:text-amber-300 transition-colors"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="text-slate-300 text-sm mb-1"><?php echo htmlspecialchars($item['author']); ?></p>
                            </a>
                            
                            <!-- Rating -->
                            <div class="flex items-center gap-2 mb-1">
                                <?php 
                                $avg_rating = round($item['avg_rating'], 1);
                                $full_stars = floor($avg_rating);
                                ?>
                                <div class="flex text-amber-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $full_stars): ?>
                                            <svg class="w-4 h-4 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs font-medium text-slate-300"><?php echo $avg_rating > 0 ? $avg_rating : 'No ratings yet'; ?></span>
                            </div>
                            
                            <p class="text-amber-300 font-semibold text-lg mb-3">$<?php echo number_format($item['price'], 2); ?></p>
                            
                            <div class="flex gap-2">
                                <a href="book.php?id=<?php echo $item['id']; ?>" 
                                   class="flex-1 text-center bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 py-2 rounded-xl hover:-translate-y-[1px] transition-all font-semibold shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40 text-sm">
                                    View Details
                                </a>
                                <form method="post" class="inline">
                                    <input type="hidden" name="book_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_wishlist" 
                                            class="px-4 py-2 bg-red-500/10 text-red-300 border border-red-400/60 rounded-xl hover:bg-red-500/20 transition-colors text-xs font-semibold uppercase tracking-[0.16em]">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
