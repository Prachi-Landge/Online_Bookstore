<?php
session_start();
require 'db.php';
require 'auth.php';

// Require authentication
requireAuth();

$user_id = $_SESSION['user_id'];

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Build query
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

// Fetch books with average ratings (limit to 20 for instant results)
$sql = "SELECT b.*, c.name as category_name,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN reviews r ON b.id = r.book_id
        $where_clause
        GROUP BY b.id
        ORDER BY b.id DESC
        LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check wishlist status for each book
foreach ($books as &$book) {
    $wishlist_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $wishlist_stmt->execute([$user_id, $book['id']]);
    $book['in_wishlist'] = $wishlist_stmt->fetch() !== false;
}
unset($book);

header('Content-Type: application/json');
echo json_encode($books);
?>
