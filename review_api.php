<?php
session_start();
require 'db.php';
require 'auth.php';

header('Content-Type: application/json');

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($book_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Check if user already reviewed this book
    $check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND book_id = ?");
    $check->execute([$user_id, $book_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $update = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
        $update->execute([$rating, $comment, $existing['id']]);
    } else {
        // Keep INSERT minimal to match most schemas (id, user_id, book_id, rating, comment)
        $insert = $pdo->prepare("INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insert->execute([$user_id, $book_id, $rating, $comment]);
    }

    // Return updated aggregate rating
    $agg = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE book_id = ?");
    $agg->execute([$book_id]);
    $stats = $agg->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Review saved',
        'avg_rating' => round((float)$stats['avg_rating'], 1),
        'review_count' => (int)$stats['review_count'],
        'book_id' => $book_id,
    ]);
} catch (Exception $e) {
    // For production you might want to log this instead of exposing it
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

