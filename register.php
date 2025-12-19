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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "The passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "The password must be at least 6 characters long.";
    } else {
        try {
            // Check if username or email already exists
            // We check them separately to provide a slightly better user experience if needed,
            // but return a generic error to prevent user enumeration.
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE name = :name OR email = :email LIMIT 1");
            $stmt->execute(['name' => $name, 'email' => $email]);
            if ($stmt->fetch()) {
                // SECURITY: Use a generic message to prevent attackers from discovering
                // which one (username or email) is already taken (user enumeration).
                // You could log the specific reason for your own internal diagnostics.
                // error_log("Registration failed: duplicate username or email for: " . $email);
                $error = "An account with that name or email already exists.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database
                // The `is_admin` column will default to 0 (or its default value in the DB schema).
                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, 0)");
                $insert_stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed_password]);

                $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
            }
        } catch (PDOException $e) {
            $error = "A database error occurred. Please try again later.";
            // In a production environment, you would log this error: error_log($e->getMessage());
        }
    }
}

$page_title = 'Register Account';
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
    .login-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--accent-color); text-decoration: none; }
</style>

<div class="container">
    <div class="form-container">
        <h2>Create Your Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success /* Allow link in success message */ ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn-primary">Register</button>
        </form>

        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>
</div>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>
