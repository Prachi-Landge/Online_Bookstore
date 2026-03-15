<?php
session_start();
require 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check user in database
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            
            // Redirect admin to admin panel
            if (($user['role'] ?? 'user') === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BookStore</title>
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
<body class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-gray-100 font-sans flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <a href="index.php" class="inline-flex items-center justify-center gap-2 text-white font-display text-3xl tracking-wide">
                <span>BookStore</span>
                <span class="inline-block h-1 w-8 rounded-full bg-amber-400"></span>
            </a>
            <p class="mt-2 text-sm text-slate-300">Sign in to continue discovering your next favorite read.</p>
        </div>
        <div class="bg-slate-950/80 border border-slate-800 p-6 rounded-2xl shadow-[0_24px_70px_rgba(15,23,42,0.95)]">
            <h1 class="text-2xl font-display font-semibold mb-4 text-white">Login</h1>
            <?php if ($error): ?>
                <div class="bg-rose-500/10 border border-rose-400/60 text-rose-100 px-4 py-3 rounded-xl mb-4 text-sm" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-4">
                    <label for="username" class="block text-slate-300 text-sm mb-1">Username</label>
                    <input type="text" name="username" id="username" class="w-full px-4 py-2 border border-slate-700 bg-slate-900 rounded-md text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-slate-300 text-sm mb-1">Password</label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-2 border border-slate-700 bg-slate-900 rounded-md text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-400" required>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-amber-400 text-slate-950 py-2.5 rounded-xl hover:-translate-y-[1px] transition-all text-sm font-semibold uppercase tracking-[0.18em] shadow-lg shadow-amber-500/25 hover:shadow-amber-400/40">Login</button>
            </form>
            <p class="mt-4 text-center text-sm text-slate-300">
                Don't have an account? <a href="register.php" class="text-amber-300 hover:text-amber-200 underline underline-offset-4">Register here</a>.
            </p>
        </div>
    </div>
</body>
</html>