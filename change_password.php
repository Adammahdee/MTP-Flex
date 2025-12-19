<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=change_password");
    exit;
}

// Redirect admins away from this user-specific page
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: admin/dashboard.php");
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "The new passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "The new password must be at least 6 characters long.";
    } else {
        try {
            // Get the current hashed password from the database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();

            // Verify the current password
            if ($user && password_verify($current_password, $user['password'])) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $update_stmt->execute(['password' => $hashed_password, 'id' => $user_id]);

                $success = "Your password has been changed successfully!";
            } else {
                $error = "Your current password is not correct.";
            }
        } catch (PDOException $e) {
            $error = "A database error occurred. Please try again later.";
            // In a production environment, you would log this error: error_log($e->getMessage());
        }
    }
}

$page_title = 'Change Password';
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
    .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--accent-color); text-decoration: none; }
</style>

<div class="container">
    <div class="form-container">
        <h2>Change Your Password</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="change_password.php" method="POST">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn-primary">Update Password</button>
        </form>

        <a href="profile.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    </div>
</div>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>
