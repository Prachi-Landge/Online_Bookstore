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
                <a href="wishlist.php" class="text-gray-300 hover:text-white transition">Wishlist</a>
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
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative container mx-auto mt-4" role="alert">
        <strong class="font-bold">Removed!</strong>
        <span class="block sm:inline"> Book removed from wishlist.</span>
    </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">My Wishlist</h1>
        
        <?php if (empty($wishlist_items)): ?>
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <p class="text-gray-600 text-lg mb-4">Your wishlist is empty</p>
                <a href="index.php" class="inline-block px-6 py-2 bg-navy text-white rounded-md hover:bg-book-blue transition-colors">
                    Browse Books
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <a href="book.php?id=<?php echo $item['id']; ?>">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($item['cover']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="h-64 w-full object-cover"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                <?php if ($item['category_name']): ?>
                                    <div class="absolute top-2 right-2">
                                        <span class="bg-book-blue text-white text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="p-4">
                            <a href="book.php?id=<?php echo $item['id']; ?>">
                                <h3 class="font-bold text-lg mb-1 text-gray-800 line-clamp-2 hover:text-blue-600"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($item['author']); ?></p>
                            </a>
                            
                            <!-- Rating -->
                            <div class="flex items-center mb-2">
                                <?php 
                                $avg_rating = round($item['avg_rating'], 1);
                                $full_stars = floor($avg_rating);
                                ?>
                                <div class="flex text-yellow-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $full_stars): ?>
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="ml-2 text-sm text-gray-600"><?php echo $avg_rating > 0 ? $avg_rating : 'No ratings'; ?></span>
                            </div>
                            
                            <p class="text-blue-600 font-bold text-xl mb-3">$<?php echo number_format($item['price'], 2); ?></p>
                            
                            <div class="flex gap-2">
                                <a href="book.php?id=<?php echo $item['id']; ?>" 
                                   class="flex-1 text-center bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors font-semibold">
                                    View Details
                                </a>
                                <form method="post" class="inline">
                                    <input type="hidden" name="book_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_wishlist" 
                                            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
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
