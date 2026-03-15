<?php
session_start();
require 'db.php';
require 'auth.php';

// Require authentication
requireAuth();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Fetch categories for sidebar
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query for books with search and category filter
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_id > 0) {
    $where_conditions[] = "b.category_id = ?";
    $params[] = $category_id;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM books b $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_books = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_books / $per_page);

// Fetch books with average ratings
// Note: LIMIT and OFFSET cannot be parameterized in MySQL, so we use intval to ensure they're safe integers
$limit = intval($per_page);
$offset = intval($offset);
$sql = "SELECT b.*, c.name as category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN reviews r ON b.id = r.book_id
        $where_clause
        GROUP BY b.id
        ORDER BY b.id DESC
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $book_id = intval($_POST['book_id'] ?? 0);
    
    if ($book_id > 0) {
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
        
        header('Location: index.php?' . http_build_query($_GET) . '&added=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore</title>
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
                <span class="text-gray-300 hidden md:inline">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
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

    <!-- Hero Section with Search -->
    <div class="relative overflow-hidden bg-gradient-to-r from-book-blue to-navy py-16">
        <div class="pointer-events-none absolute -left-20 top-10 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-10 bottom-0 h-72 w-72 rounded-full bg-sky-400/10 blur-3xl"></div>
        <div class="container mx-auto px-4">
            <p class="text-center text-sm uppercase tracking-[0.25em] text-amber-300 mb-3">Your next favorite read</p>
            <h1 class="text-4xl md:text-5xl lg:text-6xl text-white font-display font-semibold mb-4 text-center leading-tight">
                Discover stories that stay with you
            </h1>
            <p class="max-w-2xl mx-auto text-center text-blue-100 mb-8 text-sm md:text-base">
                Browse curated titles, reviews and ratings from real readers, all in a single modern bookstore experience.
            </p>
            <form method="GET" action="index.php" class="max-w-2xl mx-auto" id="searchForm">
                <div class="relative group">
                    <input type="text" 
                           name="search" 
                           id="searchInput"
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search books by title or author..." 
                           class="w-full px-6 py-4 rounded-2xl bg-white/95 border border-white/20 shadow-[0_18px_45px_rgba(15,23,42,0.45)] focus:outline-none focus:ring-2 focus:ring-amber-400/90 text-gray-900 placeholder:text-slate-400 transition-all"
                           autocomplete="off"
                    >
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 px-6 py-2 rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-amber-400/40 hover:-translate-y-[1px] transition-all">
                        Search
                    </button>
                </div>
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar - Categories -->
            <aside class="w-full md:w-64 flex-shrink-0">
                <div class="sticky top-24 rounded-2xl border border-slate-800/80 bg-slate-950/70 p-5 shadow-[0_18px_45px_rgba(15,23,42,0.9)]">
                    <div class="flex items-center justify-between mb-4 gap-2">
                        <div>
                            <!-- <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Browse</p> -->
                            <h2 class="text-lg font-display font-semibold text-white">Categories</h2>
                        </div>
                        <!-- <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-amber-500/10 text-amber-300 text-xs border border-amber-400/40">
                            <?php echo count($categories); ?>
                        </span> -->
                    </div>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-0 rounded-2xl border border-white/5"></div>
                        <ul class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1 custom-scroll">
                            <li>
                                <a href="index.php" 
                                   class="group flex items-center justify-between rounded-xl px-3 py-2 text-sm <?php echo $category_id == 0 ? 'bg-amber-500/15 text-amber-200 border border-amber-400/50' : 'text-slate-300 hover:text-white hover:bg-slate-900/80 border border-transparent'; ?> transition-all">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full <?php echo $category_id == 0 ? 'bg-emerald-400 animate-pulse' : 'bg-slate-600 group-hover:bg-amber-300'; ?>"></span>
                                        All Categories
                                    </span>
                                    <!-- <span class="text-[10px] uppercase tracking-[0.18em] text-slate-400 group-hover:text-amber-200">View</span> -->
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <?php $isActive = $category_id == $cat['id']; ?>
                                <li>
                                    <a href="index.php?category=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="group flex items-center justify-between rounded-xl px-3 py-2 text-sm <?php echo $isActive ? 'bg-amber-500/15 text-amber-200 border border-amber-400/60 shadow-[0_14px_35px_rgba(251,191,36,0.18)]' : 'text-slate-300 hover:text-white hover:bg-slate-900/80 border border-transparent'; ?> transition-all">
                                        <span class="flex items-center gap-2">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full <?php echo $isActive ? 'bg-emerald-400 animate-pulse' : 'bg-slate-600 group-hover:bg-amber-300'; ?>"></span>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </span>
                                        <!-- <span class="text-[10px] uppercase tracking-[0.18em] text-slate-500 group-hover:text-amber-200">Open</span> -->
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Books Grid -->
            <main class="flex-1">
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                    <h2 class="text-2xl md:text-3xl font-display font-semibold text-white" id="pageTitle">
                        <?php 
                        if (!empty($search)) {
                            echo "Search Results for: " . htmlspecialchars($search);
                        } elseif ($category_id > 0) {
                            $cat_name = $categories[array_search($category_id, array_column($categories, 'id'))]['name'] ?? 'Category';
                            echo htmlspecialchars($cat_name) . " Books";
                        } else {
                            echo "All Books";
                        }
                        ?>
                    </h2>
                    <p class="text-sm text-slate-300 mt-1" id="bookCount">Showing <?php echo count($books); ?> of <?php echo $total_books; ?> books</p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/40 bg-amber-500/10 px-4 py-1 text-xs uppercase tracking-[0.2em] text-amber-200">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live reviews & ratings
                    </div>
                </div>

                <div id="booksContainer">
                    <?php if (empty($books)): ?>
                        <div class="bg-white rounded-lg shadow-md p-12 text-center">
                            <p class="text-gray-600 text-lg">No books found.</p>
                            <a href="index.php" class="inline-block mt-4 px-6 py-2 bg-navy text-white rounded-md hover:bg-book-blue transition-colors">
                                View All Books
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-7" id="booksGrid">
                            <?php 
                            // Check wishlist status for each book
                            foreach ($books as $book): 
                                $wishlist_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
                                $wishlist_stmt->execute([$user_id, $book['id']]);
                                $in_wishlist = $wishlist_stmt->fetch() !== false;
                            ?>
                                <div class="group bg-slate-950/60 border border-slate-800/80 rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.85)] overflow-hidden transition-all duration-500 hover:shadow-[0_30px_90px_rgba(15,23,42,0.95)] hover:-translate-y-2 hover:border-amber-400/60 relative">
                                    <a href="book.php?id=<?php echo $book['id']; ?>">
                                        <div class="relative overflow-hidden">
                                            <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                 class="h-64 w-full object-cover scale-105 transform transition-transform duration-700 group-hover:scale-110"
                                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                            <?php if ($book['category_name']): ?>
                                                <div class="absolute top-2 right-2">
                                                    <span class="bg-slate-950/80 backdrop-blur text-xs px-2.5 py-1 rounded-full text-amber-300 border border-amber-300/40 shadow-md">
                                                        <?php echo htmlspecialchars($book['category_name']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-100 bg-gradient-to-t from-slate-950/80 via-slate-950/40 to-transparent transition-opacity duration-500"></div>
                                            <!-- Wishlist Heart Icon -->
                                            <button onclick="toggleWishlist(event, <?php echo $book['id']; ?>)" 
                                                    class="absolute top-2 left-2 bg-slate-950/80 backdrop-blur rounded-full p-2 shadow-md hover:bg-red-50/10 transition-colors wishlist-btn border border-white/15"
                                                    data-book-id="<?php echo $book['id']; ?>"
                                                    data-in-wishlist="<?php echo $in_wishlist ? 'true' : 'false'; ?>">
                                                <svg class="w-6 h-6 <?php echo $in_wishlist ? 'text-red-500 fill-current' : 'text-gray-400'; ?>" 
                                                     fill="<?php echo $in_wishlist ? 'currentColor' : 'none'; ?>" 
                                                     stroke="currentColor" 
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </a>
                                    <div class="p-4 space-y-3">
                                        <a href="book.php?id=<?php echo $book['id']; ?>">
                                            <h3 class="font-semibold text-base md:text-lg mb-1 text-white line-clamp-2 group-hover:text-amber-300 transition-colors"><?php echo htmlspecialchars($book['title']); ?></h3>
                                            <p class="text-slate-300 text-sm mb-1"><?php echo htmlspecialchars($book['author']); ?></p>
                                        </a>
                                        
                                        <!-- Rating Stars -->
                                        <div class="flex items-center gap-2 mb-1">
                                            <?php 
                                            $avg_rating = round($book['avg_rating'], 1);
                                            $full_stars = floor($avg_rating);
                                            $has_half = ($avg_rating - $full_stars) >= 0.5;
                                            ?>
                                            <div class="flex text-amber-400">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $full_stars): ?>
                                                        <svg class="w-4 h-4 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    <?php elseif ($i == $full_stars + 1 && $has_half): ?>
                                                        <svg class="w-4 h-4 fill-current drop-shadow-sm" viewBox="0 0 20 20"><path d="M10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545L10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0z"/></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-xs font-medium text-slate-300">
                                                <?php echo $avg_rating > 0 ? $avg_rating : 'No ratings yet'; ?> 
                                                <span class="text-slate-400">· <?php echo $book['review_count']; ?> review<?php echo $book['review_count'] == 1 ? '' : 's'; ?></span>
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between gap-3 pt-1">
                                            <p class="text-amber-300 font-semibold text-lg">$<?php echo number_format($book['price'], 2); ?></p>
                                            <button type="button"
                                                    onclick="openReviewModal(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')"
                                                    class="inline-flex items-center gap-1 rounded-full border border-amber-400/60 bg-amber-500/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-200 hover:bg-amber-500/20 hover:-translate-y-[1px] transition-all">
                                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                                                Rate &amp; Review
                                            </button>
                                        </div>
                                        <form method="post" class="mt-3">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" 
                                                    name="add_to_cart" 
                                                    class="w-full bg-gradient-to-r from-sky-500 to-sky-400 text-slate-950 py-2.5 rounded-xl font-semibold tracking-wide shadow-lg shadow-sky-500/25 hover:shadow-sky-400/40 hover:-translate-y-[1px] transition-all">
                                                Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                    <!-- Pagination -->
                    <div id="paginationContainer">
                        <?php if ($total_pages > 1): ?>
                            <div class="mt-8 flex justify-center items-center gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="px-4 py-2 bg-white border rounded-md hover:bg-gray-100 transition">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="px-4 py-2 <?php echo $i == $page ? 'bg-navy text-white' : 'bg-white border'; ?> rounded-md hover:bg-gray-100 transition">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="px-4 py-2 bg-white border rounded-md hover:bg-gray-100 transition">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="relative w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 shadow-[0_24px_70px_rgba(15,23,42,0.95)] p-6">
            <button type="button" onclick="closeReviewModal()" class="absolute right-4 top-4 text-slate-400 hover:text-slate-200 transition-colors">
                ✕
            </button>
            <div class="mb-4">
                <p class="text-xs uppercase tracking-[0.22em] text-amber-300 mb-1">Quick review</p>
                <h3 class="font-display text-xl text-white" id="reviewBookTitle">Rate this book</h3>
            </div>
            <form id="reviewForm" class="space-y-4">
                <input type="hidden" id="reviewBookId" name="book_id">
                <div>
                    <p class="text-xs font-medium text-slate-300 mb-1.5">Your rating</p>
                    <div class="flex items-center gap-1.5" id="ratingStars">
                        <!-- Stars injected by JS -->
                    </div>
                    <p id="ratingHint" class="mt-1 text-[11px] text-slate-400 italic">Tap a star to rate from 1–5.</p>
                </div>
                <div>
                    <label for="reviewComment" class="block text-xs font-medium text-slate-300 mb-1.5">Share a short review (optional)</label>
                    <textarea id="reviewComment" name="comment" rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400 resize-none" placeholder="What did you like or dislike about this book?"></textarea>
                </div>
                <div class="flex items-center justify-between gap-3 pt-2">
                    <p id="reviewStatus" class="text-xs text-slate-400"></p>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-950 shadow-lg shadow-amber-500/30 hover:shadow-amber-400/40 hover:-translate-y-[1px] transition-all disabled:opacity-60 disabled:hover:translate-y-0">
                        <span id="reviewSubmitText">Submit review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const categoryId = <?php echo $category_id; ?>;

        // Instant search while typing
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // Debounce: wait 300ms after user stops typing
            searchTimeout = setTimeout(() => {
                if (searchTerm.length > 0 || categoryId > 0) {
                    performSearch(searchTerm);
                } else {
                    // Reload page if search is empty and no category filter
                    window.location.href = 'index.php';
                }
            }, 300);
        });

        function performSearch(searchTerm) {
            const url = `search_api.php?search=${encodeURIComponent(searchTerm)}${categoryId > 0 ? '&category=' + categoryId : ''}`;
            
            fetch(url)
                .then(response => response.json())
                .then(books => {
                    displayBooks(books, searchTerm);
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }

        function displayBooks(books, searchTerm) {
            const container = document.getElementById('booksContainer');
            const titleElement = document.getElementById('pageTitle');
            const countElement = document.getElementById('bookCount');
            
            if (books.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <p class="text-gray-600 text-lg">No books found.</p>
                        <a href="index.php" class="inline-block mt-4 px-6 py-2 bg-navy text-white rounded-md hover:bg-book-blue transition-colors">
                            View All Books
                        </a>
                    </div>
                `;
                titleElement.textContent = searchTerm ? `Search Results for: ${searchTerm}` : 'No Results';
                countElement.textContent = 'Showing 0 books';
                return;
            }

            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="booksGrid">';
            
            books.forEach(book => {
                const avgRating = parseFloat(book.avg_rating) || 0;
                const fullStars = Math.floor(avgRating);
                const hasHalf = (avgRating - fullStars) >= 0.5;
                const in_wishlist = book.in_wishlist || false;
                
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= fullStars) {
                        starsHtml += '<svg class="w-4 h-4 fill-current text-yellow-400" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>';
                    } else if (i === fullStars + 1 && hasHalf) {
                        starsHtml += '<svg class="w-4 h-4 fill-current text-yellow-400" viewBox="0 0 20 20"><path d="M10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545L10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0z"/></svg>';
                    } else {
                        starsHtml += '<svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>';
                    }
                }
                
                html += `
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <a href="book.php?id=${book.id}">
                            <div class="relative">
                                <img src="${book.cover || 'https://via.placeholder.com/300x400?text=No+Cover'}" 
                                     alt="${book.title}" 
                                     class="h-64 w-full object-cover"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                ${book.category_name ? `<div class="absolute top-2 right-2"><span class="bg-book-blue text-white text-xs px-2 py-1 rounded">${book.category_name}</span></div>` : ''}
                                <button onclick="toggleWishlist(event, ${book.id})" 
                                        class="absolute top-2 left-2 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition-colors wishlist-btn"
                                        data-book-id="${book.id}"
                                        data-in-wishlist="${in_wishlist}">
                                    <svg class="w-6 h-6 ${in_wishlist ? 'text-red-500 fill-current' : 'text-gray-400'}" 
                                         fill="${in_wishlist ? 'currentColor' : 'none'}" 
                                         stroke="currentColor" 
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </div>
                        </a>
                        <div class="p-4">
                            <a href="book.php?id=${book.id}">
                                <h3 class="font-bold text-lg mb-1 text-gray-800 line-clamp-2 hover:text-blue-600">${book.title}</h3>
                                <p class="text-gray-600 text-sm mb-2">${book.author}</p>
                            </a>
                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">${starsHtml}</div>
                                <span class="ml-2 text-sm text-gray-600">${avgRating > 0 ? avgRating.toFixed(1) : 'No ratings'} (${book.review_count || 0})</span>
                            </div>
                            <p class="text-blue-600 font-bold text-xl mb-3">$${parseFloat(book.price).toFixed(2)}</p>
                            <form method="post" class="mt-2">
                                <input type="hidden" name="book_id" value="${book.id}">
                                <button type="submit" name="add_to_cart" class="w-full bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors font-semibold">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            titleElement.textContent = searchTerm ? `Search Results for: ${searchTerm}` : 'All Books';
            countElement.textContent = `Showing ${books.length} books`;
            
            // Hide pagination during instant search
            document.getElementById('paginationContainer').style.display = 'none';
        }

        function toggleWishlist(event, bookId) {
            event.preventDefault();
            event.stopPropagation();
            
            const btn = event.currentTarget;
            const inWishlist = btn.getAttribute('data-in-wishlist') === 'true';
            const action = inWishlist ? 'remove' : 'add';
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('book_id', bookId);
            
            fetch('wishlist_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const svg = btn.querySelector('svg');
                    if (data.in_wishlist) {
                        svg.classList.remove('text-gray-400');
                        svg.classList.add('text-red-500', 'fill-current');
                        svg.setAttribute('fill', 'currentColor');
                        btn.setAttribute('data-in-wishlist', 'true');
                    } else {
                        svg.classList.remove('text-red-500', 'fill-current');
                        svg.classList.add('text-gray-400');
                        svg.setAttribute('fill', 'none');
                        btn.setAttribute('data-in-wishlist', 'false');
                    }
                }
            })
            .catch(error => {
                console.error('Wishlist error:', error);
            });
        }

        // Review modal logic
        let selectedRating = 0;

        function buildRatingStars() {
            const container = document.getElementById('ratingStars');
            container.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.dataset.value = i.toString();
                btn.className = 'rating-star text-2xl text-slate-600 transition-transform hover:-translate-y-[1px]';
                btn.innerHTML = '★';
                btn.addEventListener('click', () => setRating(i));
                container.appendChild(btn);
            }
        }

        function setRating(value) {
            selectedRating = value;
            const stars = document.querySelectorAll('#ratingStars .rating-star');
            stars.forEach((star, idx) => {
                if (idx < value) {
                    star.classList.remove('text-slate-600');
                    star.classList.add('text-amber-400', 'drop-shadow');
                } else {
                    star.classList.remove('text-amber-400', 'drop-shadow');
                    star.classList.add('text-slate-600');
                }
            });
            const hints = ['Terrible', 'Poor', 'Okay', 'Good', 'Loved it'];
            const hintText = document.getElementById('ratingHint');
            hintText.textContent = selectedRating > 0 ? `${hints[selectedRating - 1]} · Tap again to adjust.` : 'Tap a star to rate from 1–5.';
        }

        function openReviewModal(bookId, title) {
            const modal = document.getElementById('reviewModal');
            document.getElementById('reviewBookId').value = bookId;
            document.getElementById('reviewBookTitle').textContent = title || 'Rate this book';
            document.getElementById('reviewComment').value = '';
            document.getElementById('reviewStatus').textContent = '';
            selectedRating = 0;
            buildRatingStars();
            setRating(0);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeReviewModal() {
            const modal = document.getElementById('reviewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal when clicking backdrop
        document.getElementById('reviewModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                closeReviewModal();
            }
        });

        // Handle review form submit
        document.getElementById('reviewForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const statusEl = document.getElementById('reviewStatus');
            const submitText = document.getElementById('reviewSubmitText');

            if (selectedRating === 0) {
                statusEl.textContent = 'Please select a rating before submitting.';
                statusEl.className = 'text-xs text-amber-300';
                return;
            }

            const formData = new FormData();
            formData.append('book_id', document.getElementById('reviewBookId').value);
            formData.append('rating', selectedRating.toString());
            formData.append('comment', document.getElementById('reviewComment').value.trim());

            submitText.textContent = 'Saving...';
            statusEl.textContent = '';

            fetch('review_api.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        statusEl.textContent = 'Thank you! Your review has been saved.';
                        statusEl.className = 'text-xs text-emerald-300';
                        setTimeout(() => {
                            closeReviewModal();
                            // Optimistically update rating text if present on the card
                            if (data.book_id && typeof data.avg_rating !== 'undefined') {
                                const cards = document.querySelectorAll('#booksGrid > div');
                                cards.forEach(card => {
                                    const hiddenInput = card.querySelector('input[name="book_id"]');
                                    if (hiddenInput && parseInt(hiddenInput.value, 10) === data.book_id) {
                                        const ratingSpan = card.querySelector('span.text-xs.font-medium.text-slate-300');
                                        if (ratingSpan) {
                                            ratingSpan.innerHTML = `${data.avg_rating || 'No ratings yet'} <span class="text-slate-400">· ${data.review_count} review${data.review_count === 1 ? '' : 's'}</span>`;
                                        }
                                    }
                                });
                            }
                        }, 600);
                    } else {
                        statusEl.textContent = data.message || 'Unable to save review right now.';
                        statusEl.className = 'text-xs text-rose-300';
                    }
                })
                .catch(() => {
                    statusEl.textContent = 'Something went wrong. Please try again.';
                    statusEl.className = 'text-xs text-rose-300';
                })
                .finally(() => {
                    submitText.textContent = 'Submit review';
                });
        });
    </script>
</body>
</html>
