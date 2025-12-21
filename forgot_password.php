<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // In a real production environment, you would:
        // 1. Generate a unique token.
        // 2. Store the token and expiry in the database for this user.
        // 3. Send an email with a link like reset_password.php?token=xyz
        
        // For this demonstration, we will just display a success message.
        $success = "If an account exists for " . htmlspecialchars($email) . ", you will receive password reset instructions shortly.";
    }
}

$page_title = 'Forgot Password';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<style>
    .form-container { max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: var(--shadow); }
    .form-container h2 { text-align: center; margin-bottom: 1.5rem; }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); }
    .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
    .btn-primary { width: 100%; padding: 0.8rem; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; }
    .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: bold; border: 1px solid transparent; }
    .alert-success { background-color: #dcfce7; color: #166534; border-color: #a7f3d0; }
    .alert-danger { background-color: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--text-secondary); text-decoration: none; }
    .back-link:hover { color: var(--primary-color); }
</style>

<div class="container">
    <div class="form-container">
        <h2>Reset Password</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;">Enter your email address and we'll send you a link to reset your password.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            
            <button type="submit" class="btn-primary">Send Reset Link</button>
        </form>

        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</div>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>
