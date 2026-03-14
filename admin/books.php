<?php
session_start();
require '../db.php';
require '../auth.php';

requireAdmin();

$username = $_SESSION['username'] ?? 'Admin';

// Handle add book
if (isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $cover = trim($_POST['cover'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    
    if (!empty($title) && !empty($author) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO books (title, author, category_id, price, cover, description, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $author, $category_id, $price, $cover, $description, $stock]);
        header('Location: books.php?added=1');
        exit;
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle edit book
if (isset($_POST['edit_book'])) {
    $id = intval($_POST['book_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $cover = trim($_POST['cover'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    
    if ($id > 0 && !empty($title) && !empty($author) && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, category_id = ?, price = ?, cover = ?, description = ?, stock = ? WHERE id = ?");
        $stmt->execute([$title, $author, $category_id, $price, $cover, $description, $stock, $id]);
        header('Location: books.php?updated=1');
        exit;
    }
}

// Handle delete book
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: books.php?deleted=1');
        exit;
    }
}

// Fetch books
$stmt = $pdo->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.id DESC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Admin</title>
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Manage Books</h1>
            <button onclick="document.getElementById('addBookModal').classList.remove('hidden')" 
                    class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue transition-colors">
                Add New Book
            </button>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Book added successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Book updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Book deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Books Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cover</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <img src="<?php echo htmlspecialchars($book['cover']); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                         class="w-16 h-20 object-cover rounded"
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/100x150?text=No+Cover';">
                                </td>
                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($book['title']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($book['author']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">$<?php echo number_format($book['price'], 2); ?></td>
                                <td class="px-6 py-4"><?php echo $book['stock']; ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="editBook(<?php echo htmlspecialchars(json_encode($book)); ?>)" 
                                            class="text-blue-600 hover:underline mr-3">Edit</button>
                                    <a href="?delete=<?php echo $book['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this book?')"
                                       class="text-red-600 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div id="addBookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4">Add New Book</h2>
            <form method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Author *</label>
                    <input type="text" name="author" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Category *</label>
                    <select name="category_id" required class="w-full px-4 py-2 border rounded-md">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Price *</label>
                    <input type="number" name="price" step="0.01" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Cover URL</label>
                    <input type="url" name="cover" class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded-md"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Stock</label>
                    <input type="number" name="stock" value="100" class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="flex gap-4">
                    <button type="submit" name="add_book" class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue">
                        Add Book
                    </button>
                    <button type="button" onclick="document.getElementById('addBookModal').classList.add('hidden')" 
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold mb-4">Edit Book</h2>
            <form method="post">
                <input type="hidden" name="book_id" id="edit_book_id">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" id="edit_title" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Author *</label>
                    <input type="text" name="author" id="edit_author" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Category *</label>
                    <select name="category_id" id="edit_category_id" required class="w-full px-4 py-2 border rounded-md">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Price *</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Cover URL</label>
                    <input type="url" name="cover" id="edit_cover" class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="4" class="w-full px-4 py-2 border rounded-md"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Stock</label>
                    <input type="number" name="stock" id="edit_stock" class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="flex gap-4">
                    <button type="submit" name="edit_book" class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue">
                        Update Book
                    </button>
                    <button type="button" onclick="document.getElementById('editBookModal').classList.add('hidden')" 
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editBook(book) {
            document.getElementById('edit_book_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_category_id').value = book.category_id || '';
            document.getElementById('edit_price').value = book.price;
            document.getElementById('edit_cover').value = book.cover || '';
            document.getElementById('edit_description').value = book.description || '';
            document.getElementById('edit_stock').value = book.stock || 100;
            document.getElementById('editBookModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
