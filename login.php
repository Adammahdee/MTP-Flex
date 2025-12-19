<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: profile.php");
    }
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            // Check for a user in the database
            $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login successful, regenerate session ID to prevent fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'];

                // Redirect based on user role
                if ($user['is_admin'] == 1) {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: profile.php");
                }
                exit;
            } else {
                // Use a generic error message to prevent account enumeration
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "A system error occurred. Please try again later.";
            // In a production environment, you would log this error: error_log($e->getMessage());
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MTP Flex</title>
    <style>
        .form-container { max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: var(--shadow); }
        .form-container h2 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        .btn-primary { width: 100%; padding: 0.8rem; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: bold; border: 1px solid transparent; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .register-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--accent-color); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Login to Your Account</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
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

            <a href="register.php" class="register-link">Don't have an account? Register</a>
        </div>
    </div>
</body>
</html>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>