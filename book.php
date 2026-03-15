<?php
session_start();
require 'db.php';
require 'auth.php';

requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch book details with category and ratings
$stmt = $pdo->prepare("SELECT b.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count
                       FROM books b
                       LEFT JOIN categories c ON b.category_id = c.id
                       LEFT JOIN reviews r ON b.id = r.book_id
                       WHERE b.id = ?
                       GROUP BY b.id");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('Location: index.php');
    exit;
}

// Check if book is in wishlist
$wishlist_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
$wishlist_stmt->execute([$user_id, $book_id]);
$in_wishlist = $wishlist_stmt->fetch() !== false;

// Fetch reviews
$reviews_stmt = $pdo->prepare("SELECT r.*, u.username 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.id 
                               WHERE r.book_id = ? 
                               ORDER BY r.created_at DESC");
$reviews_stmt->execute([$book_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user_id, $book_id]);
    $existing_item = $stmt->fetch();
    
    if ($existing_item) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$existing_item['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $book_id]);
    }
    
    header('Location: book.php?id=' . $book_id . '&added=1');
    exit;
}

// Handle wishlist toggle
if (isset($_POST['toggle_wishlist'])) {
    if ($in_wishlist) {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
        $stmt->execute([$user_id, $book_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $book_id]);
    }
    header('Location: book.php?id=' . $book_id);
    exit;
}

// Handle review submission
if (isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating >= 1 && $rating <= 5) {
        // Check if user already reviewed
        $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?");
        $check_stmt->execute([$user_id, $book_id]);
        
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$rating, $comment, $user_id, $book_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $book_id, $rating, $comment]);
        }
        
        header('Location: book.php?id=' . $book_id);
        exit;
    }
}

// Re-check wishlist status after potential changes
$wishlist_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
$wishlist_stmt->execute([$user_id, $book_id]);
$in_wishlist = $wishlist_stmt->fetch() !== false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - BookStore</title>
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
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="text-gray-300 hover:text-white transition">Admin</a>
                <?php endif; ?>
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

    <?php if (isset($_GET['added'])): ?>
    <div class="bg-emerald-500/10 border border-emerald-400/60 text-emerald-200 px-4 py-3 rounded-xl shadow-md container mx-auto mt-4 text-sm flex items-center justify-between gap-3" role="alert">
        <span><strong class="font-semibold">Added to cart.</strong> You can adjust quantity from your cart anytime.</span>
        <a href="cart.php" class="inline-flex items-center text-emerald-100 text-xs uppercase tracking-[0.18em] border border-emerald-300/40 rounded-full px-3 py-1 hover:bg-emerald-400/10 transition-colors">View cart</a>
    </div>
    <?php endif; ?>

    <!-- Book Details -->
    <div class="container mx-auto px-4 py-10">
        <a href="index.php" class="inline-flex items-center text-slate-300 hover:text-white mb-4 text-sm">
            ← <span class="ml-1 underline-offset-4 hover:underline">Back to Books</span>
        </a>
        
        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-[0_24px_70px_rgba(15,23,42,0.95)] p-6 md:p-8">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Book Cover -->
                <div>
                    <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="w-full rounded-2xl shadow-[0_22px_65px_rgba(15,23,42,0.9)]"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/500x700?text=No+Cover';">
                </div>
                
                <!-- Book Info -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-amber-300 mb-1">Book detail</p>
                        <h1 class="text-3xl md:text-4xl font-display font-semibold mb-1 text-white"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <p class="text-base text-slate-300 mb-1">by <span class="font-medium text-slate-100"><?php echo htmlspecialchars($book['author']); ?></span></p>
                    </div>
                    
                    <?php if ($book['category_name']): ?>
                        <span class="inline-flex items-center gap-1 bg-slate-900/80 border border-amber-300/40 text-amber-200 px-3 py-1 rounded-full text-xs font-medium mb-1">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <?php echo htmlspecialchars($book['category_name']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <!-- Rating -->
                    <div class="flex items-center mb-2">
                        <?php 
                        $avg_rating = round($book['avg_rating'], 1);
                        $full_stars = floor($avg_rating);
                        $has_half = ($avg_rating - $full_stars) >= 0.5;
                        ?>
                        <div class="flex text-amber-400 mr-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $full_stars): ?>
                                    <svg class="w-6 h-6 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                <?php elseif ($i == $full_stars + 1 && $has_half): ?>
                                    <svg class="w-6 h-6 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545L10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0z"/></svg>
                                <?php else: ?>
                                    <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm font-medium text-slate-200"><?php echo $avg_rating > 0 ? $avg_rating : 'No ratings yet'; ?></span>
                        <span class="text-xs text-slate-400 ml-2">(<?php echo $book['review_count']; ?> review<?php echo $book['review_count'] == 1 ? '' : 's'; ?>)</span>
                    </div>
                    
                    <p class="text-3xl font-semibold text-amber-300 mb-4">$<?php echo number_format($book['price'], 2); ?></p>
                    
                    <p class="text-slate-200 mb-6 leading-relaxed text-sm md:text-base">
                        <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                    </p>
                    
                    <div class="flex gap-4">
                        <form method="post" class="flex-1">
                            <button type="submit" name="add_to_cart" 
                                    class="w-full bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 py-3 rounded-xl hover:-translate-y-[1px] transition-all font-semibold text-lg shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40">
                                Add to Cart
                            </button>
                        </form>
                        
                        <form method="post">
                            <button type="submit" name="toggle_wishlist" 
                                    class="px-6 py-3 rounded-xl border transition-colors flex items-center justify-center gap-2 <?php echo $in_wishlist ? 'bg-red-500/10 border-red-400 text-red-300 hover:bg-red-500/20' : 'bg-slate-900 border-slate-700 text-slate-200 hover:bg-slate-800'; ?>">
                                <svg class="w-6 h-6 <?php echo $in_wishlist ? 'fill-current text-red-400' : 'text-slate-400'; ?>" 
                                     fill="<?php echo $in_wishlist ? 'currentColor' : 'none'; ?>" 
                                     stroke="currentColor" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span class="text-sm font-medium"><?php echo $in_wishlist ? 'In wishlist' : 'Add to wishlist'; ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="mt-8 bg-slate-950/80 border border-slate-800 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.95)] p-6 md:p-8">
            <h2 class="text-2xl font-display font-semibold mb-6 text-white">Reviews</h2>
            
            <!-- Add Review Form -->
            <div class="mb-8 p-4 bg-slate-900/70 border border-slate-700 rounded-xl">
                <h3 class="font-semibold mb-4 text-slate-100">Write a Review</h3>
                <form method="post">
                    <div class="mb-4">
                        <label class="block text-slate-300 mb-2 text-sm">Rating</label>
                        <select name="rating" required class="w-full md:w-48 px-4 py-2 border border-slate-700 bg-slate-900 rounded-md text-sm text-slate-100">
                            <option value="">Select Rating</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-slate-300 mb-2 text-sm">Comment</label>
                        <textarea name="comment" rows="4" 
                                  class="w-full px-4 py-2 border border-slate-700 bg-slate-900 rounded-md text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                  placeholder="Write your review here..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" 
                            class="bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 px-6 py-2 rounded-xl text-xs font-semibold uppercase tracking-[0.18em] hover:-translate-y-[1px] transition-all shadow-lg shadow-amber-500/25 hover:shadow-amber-400/40">
                        Submit Review
                    </button>
                </form>
            </div>
            
            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <p class="text-slate-400 text-sm">No reviews yet. Be the first to share your thoughts.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-slate-800 pb-4 last:border-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span class="font-semibold text-slate-100 text-sm"><?php echo htmlspecialchars($review['username']); ?></span>
                                    <div class="flex text-amber-400 ml-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <svg class="w-4 h-4 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php else: ?>
                                                <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="text-slate-200 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
