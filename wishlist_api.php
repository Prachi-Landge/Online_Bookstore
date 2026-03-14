<?php
session_start();
require 'db.php';
require 'auth.php';

requireAuth();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $book_id = intval($_POST['book_id'] ?? 0);
    
    if ($book_id > 0) {
        if ($action === 'add') {
            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $book_id]);
            }
            echo json_encode(['success' => true, 'in_wishlist' => true]);
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
            echo json_encode(['success' => true, 'in_wishlist' => false]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid book ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
