<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect non-logged-in users to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=profile");
    exit;
}

// If an admin lands on this page, redirect them to the admin dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: ../admin/dashboard.php");
    exit;
}

require_once __DIR__ . "/../config/db.php";

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = '';
$orders = [];
$error = $success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name'] ?? '');
    $new_email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (empty($new_name) || !$new_email) {
        $error = "Name and a valid email are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            $stmt->execute(['name' => $new_name, 'email' => $new_email, 'id' => $user_id]);
            
            // Update session variables to reflect change immediately
            $_SESSION['user_name'] = $new_name;
            $user_name = $new_name; // Update for current page render

            $success = "Profile updated successfully!";
        } catch (PDOException $e) {
            // Check for duplicate email error
            if ($e->errorInfo[1] == 1062) {
                $error = "This email address is already in use by another account.";
            } else {
                $error = "Database error: Could not update profile.";
            }
        }
    }
}

try {
    // Fetch current user details for the form
    $user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :user_id");
    $user_stmt->execute(['user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $user_email = $user_data['email'] ?? '';

    $stmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error, maybe show a message
}

// Re-use the header from the homepage for consistency
$page_title = 'My Profile';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'user_header.php';
?>

<style>
.profile-container { max-width: 900px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: var(--shadow); }
.profile-header { border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 2rem; }
.profile-header h2 { font-size: 1.8rem; margin: 0; }
.order-table { width: 100%; border-collapse: collapse; }
.profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start; }

/* Form & Feedback Styles */
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; }
.form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
.btn-primary { padding: 0.75rem 1.5rem; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
.alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: bold; border: 1px solid transparent; }
.alert-success { background-color: #dcfce7; color: #166534; border-color: #a7f3d0; }
.alert-danger { background-color: #fee2e2; color: #991b1b; border-color: #fecaca; }
.no-orders { text-align: center; padding: 2rem; color: var(--text-secondary); }
.no-orders {
    text-align: center;
    padding: 3rem;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    color: var(--text-secondary);
}

/* Order Table Styles */
.order-table th, .order-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
.order-table th { font-weight: 600; color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; }
.status-badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
.status-pending { background-color: #fef3c7; color: #92400e; }
.status-completed { background-color: #dcfce7; color: #166534; }
</style>

<main>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Welcome, <?= htmlspecialchars($user_name) ?>!</h2>
            <p>Here you can view your order history and manage your account details.</p>
        </div>

        <h3>Your Orders</h3>
        <?php if (empty($orders)): ?>
            <p>You have not placed any orders yet.</p>
        <?php else: ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($order['id']) ?></td>
                            <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
                            <td>₦<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <?php if ($order['status'] === 'Pending'): ?>
                                    <span class="status-badge status-pending">Pending</span>
                                <?php elseif ($order['status'] === 'Completed'): ?>
                                    <span class="status-badge status-completed">Completed</span>
                                <?php else: ?>
                                    <span class="status-badge"><?= htmlspecialchars($order['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php
require_once __DIR__ . '/../assets/store_footer.php';
?>