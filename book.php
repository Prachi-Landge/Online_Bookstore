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
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative container mx-auto mt-4" role="alert">
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline"> Book added to cart.</span>
    </div>
    <?php endif; ?>

    <!-- Book Details -->
    <div class="container mx-auto px-4 py-8">
        <a href="index.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Books</a>
        
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Book Cover -->
                <div>
                    <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="w-full rounded-lg shadow-md"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/500x700?text=No+Cover';">
                </div>
                
                <!-- Book Info -->
                <div>
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="text-xl text-gray-600 mb-4">by <?php echo htmlspecialchars($book['author']); ?></p>
                    
                    <?php if ($book['category_name']): ?>
                        <span class="inline-block bg-book-blue text-white px-3 py-1 rounded-full text-sm mb-4">
                            <?php echo htmlspecialchars($book['category_name']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <!-- Rating -->
                    <div class="flex items-center mb-4">
                        <?php 
                        $avg_rating = round($book['avg_rating'], 1);
                        $full_stars = floor($avg_rating);
                        $has_half = ($avg_rating - $full_stars) >= 0.5;
                        ?>
                        <div class="flex text-yellow-400 mr-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $full_stars): ?>
                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                <?php elseif ($i == $full_stars + 1 && $has_half): ?>
                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20"><path d="M10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545L10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0z"/></svg>
                                <?php else: ?>
                                    <svg class="w-6 h-6 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-lg font-semibold"><?php echo $avg_rating > 0 ? $avg_rating : 'No ratings'; ?></span>
                        <span class="text-gray-600 ml-2">(<?php echo $book['review_count']; ?> reviews)</span>
                    </div>
                    
                    <p class="text-3xl font-bold text-blue-600 mb-6">$<?php echo number_format($book['price'], 2); ?></p>
                    
                    <p class="text-gray-700 mb-6 leading-relaxed">
                        <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                    </p>
                    
                    <div class="flex gap-4">
                        <form method="post" class="flex-1">
                            <button type="submit" name="add_to_cart" 
                                    class="w-full bg-navy text-white py-3 rounded-md hover:bg-book-blue transition-colors font-semibold text-lg">
                                Add to Cart
                            </button>
                        </form>
                        
                        <form method="post">
                            <button type="submit" name="toggle_wishlist" 
                                    class="px-6 py-3 rounded-md border-2 transition-colors flex items-center justify-center <?php echo $in_wishlist ? 'bg-red-100 border-red-500 text-red-700 hover:bg-red-200' : 'bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200'; ?>">
                                <svg class="w-6 h-6 <?php echo $in_wishlist ? 'fill-current' : ''; ?>" 
                                     fill="<?php echo $in_wishlist ? 'currentColor' : 'none'; ?>" 
                                     stroke="currentColor" 
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6">Reviews</h2>
            
            <!-- Add Review Form -->
            <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold mb-4">Write a Review</h3>
                <form method="post">
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Rating</label>
                        <select name="rating" required class="w-full md:w-48 px-4 py-2 border rounded-md">
                            <option value="">Select Rating</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Comment</label>
                        <textarea name="comment" rows="4" 
                                  class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Write your review here..."></textarea>
                    </div>
                    <button type="submit" name="submit_review" 
                            class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue transition-colors">
                        Submit Review
                    </button>
                </form>
            </div>
            
            <!-- Reviews List -->
            <?php if (empty($reviews)): ?>
                <p class="text-gray-600">No reviews yet. Be the first to review!</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b pb-4 last:border-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span class="font-semibold"><?php echo htmlspecialchars($review['username']); ?></span>
                                    <div class="flex text-yellow-400 ml-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php else: ?>
                                                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
