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
    <div class="relative bg-gradient-to-r from-book-blue to-navy py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl md:text-5xl text-white font-bold mb-6 text-center">Welcome to BookStore</h1>
            <form method="GET" action="index.php" class="max-w-2xl mx-auto" id="searchForm">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           id="searchInput"
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search books by title or author..." 
                           class="w-full px-6 py-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-800"
                           autocomplete="off"
                    >
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue transition-colors">
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
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar - Categories -->
            <aside class="w-full md:w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h2 class="text-xl font-bold mb-4">Categories</h2>
                    <ul class="space-y-2">
                        <li>
                            <a href="index.php" class="block px-3 py-2 rounded hover:bg-gray-100 transition <?php echo $category_id == 0 ? 'bg-book-blue text-white' : 'text-gray-700'; ?>">
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?category=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="block px-3 py-2 rounded hover:bg-gray-100 transition <?php echo $category_id == $cat['id'] ? 'bg-book-blue text-white' : 'text-gray-700'; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- Books Grid -->
            <main class="flex-1">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800" id="pageTitle">
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
                    <p class="text-gray-600 mt-1" id="bookCount">Showing <?php echo count($books); ?> of <?php echo $total_books; ?> books</p>
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="booksGrid">
                            <?php 
                            // Check wishlist status for each book
                            foreach ($books as $book): 
                                $wishlist_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
                                $wishlist_stmt->execute([$user_id, $book['id']]);
                                $in_wishlist = $wishlist_stmt->fetch() !== false;
                            ?>
                                <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                                    <a href="book.php?id=<?php echo $book['id']; ?>">
                                        <div class="relative">
                                            <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                                 class="h-64 w-full object-cover"
                                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/300x400?text=No+Cover';">
                                            <?php if ($book['category_name']): ?>
                                                <div class="absolute top-2 right-2">
                                                    <span class="bg-book-blue text-white text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <!-- Wishlist Heart Icon -->
                                            <button onclick="toggleWishlist(event, <?php echo $book['id']; ?>)" 
                                                    class="absolute top-2 left-2 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition-colors wishlist-btn"
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
                                    <div class="p-4">
                                        <a href="book.php?id=<?php echo $book['id']; ?>">
                                            <h3 class="font-bold text-lg mb-1 text-gray-800 line-clamp-2 hover:text-blue-600"><?php echo htmlspecialchars($book['title']); ?></h3>
                                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                                        </a>
                                        
                                        <!-- Rating Stars -->
                                        <div class="flex items-center mb-2">
                                            <?php 
                                            $avg_rating = round($book['avg_rating'], 1);
                                            $full_stars = floor($avg_rating);
                                            $has_half = ($avg_rating - $full_stars) >= 0.5;
                                            ?>
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $full_stars): ?>
                                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    <?php elseif ($i == $full_stars + 1 && $has_half): ?>
                                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545L10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0z"/></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600"><?php echo $avg_rating > 0 ? $avg_rating : 'No ratings'; ?> (<?php echo $book['review_count']; ?>)</span>
                                        </div>
                                        
                                        <p class="text-blue-600 font-bold text-xl mb-3">$<?php echo number_format($book['price'], 2); ?></p>
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" 
                                                    name="add_to_cart" 
                                                    class="w-full bg-navy text-white py-2 rounded-md hover:bg-book-blue transition-colors font-semibold">
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
    </script>
</body>
</html>
