<?php
session_start();
require '../db.php';
require '../auth.php';

requireAdmin();

// Handle add category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        header('Location: categories.php?added=1');
        exit;
    }
}

// Handle edit category
if (isset($_POST['edit_category'])) {
    $id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($id > 0 && !empty($name)) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        header('Location: categories.php?updated=1');
        exit;
    }
}

// Handle delete category
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: categories.php?deleted=1');
        exit;
    }
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
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
            <h1 class="text-3xl font-bold">Manage Categories</h1>
            <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')" 
                    class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue transition-colors">
                Add New Category
            </button>
        </div>

        <?php if (isset($_GET['added'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Category added successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Category updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Category deleted successfully!</div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo $cat['id']; ?></td>
                                <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($cat['description'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)" 
                                            class="text-blue-600 hover:underline mr-3">Edit</button>
                                    <a href="?delete=<?php echo $cat['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this category?')"
                                       class="text-red-600 hover:underline">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4">Add New Category</h2>
            <form method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-md"></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="submit" name="add_category" class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue">
                        Add Category
                    </button>
                    <button type="button" onclick="document.getElementById('addCategoryModal').classList.add('hidden')" 
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4">Edit Category</h2>
            <form method="post">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-2 border rounded-md"></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="submit" name="edit_category" class="bg-navy text-white px-6 py-2 rounded-md hover:bg-book-blue">
                        Update Category
                    </button>
                    <button type="button" onclick="document.getElementById('editCategoryModal').classList.add('hidden')" 
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCategory(cat) {
            document.getElementById('edit_category_id').value = cat.id;
            document.getElementById('edit_name').value = cat.name;
            document.getElementById('edit_description').value = cat.description || '';
            document.getElementById('editCategoryModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
