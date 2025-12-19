<?php
// Admin-specific login page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If an admin is already logged in, redirect to the dashboard.
if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: dashboard.php");
    exit;
}

// We need the connection function, but we will instantiate the admin connection ourselves.
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            // IMPORTANT: Get the high-privilege admin database connection
            // Prepare a statement to find an ADMIN user by email
            $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = :email AND is_admin = 1 LIMIT 1");
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful, regenerate session ID to prevent fixation
                session_regenerate_id(true);

                // Set admin-specific session variables
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['is_admin'] = 1;

                // Redirect to the admin dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                // Use a generic error message to prevent account enumeration
                $error = "Invalid credentials or not an administrator account.";
            }
        } catch (PDOException $e) {
            $error = "A system error occurred. Please try again later.";
            if (defined('DEBUG') && DEBUG) { $error .= " (Debug: " . $e->getMessage() . ")"; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MTP Flex</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #1f2937; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #4b5563; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        .btn-primary { width: 100%; padding: 12px; background-color: #d9534f; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; transition: background-color 0.3s; }
        .btn-primary:hover { background-color: #c9302c; }
        .alert-danger { background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Administrator Access</h2>
        
        <?php if ($error): ?>
            <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
    </div>
</body>
</html>
