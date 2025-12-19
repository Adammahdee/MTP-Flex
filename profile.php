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

require_once __DIR__ . "/config/db.php";

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = '';
$orders = [];
$error = $success = '';

try {
    // Fetch current user details for the form
    $user_stmt = $pdo->prepare("SELECT name, email, phone_number, address FROM users WHERE id = :user_id AND is_admin = 0");
    $user_stmt->execute(['user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Security Fallback: If the user ID in the session does not correspond to a valid, non-admin user,
    // destroy the session and force a re-login.
    if (!$user_data) {
        session_unset();
        session_destroy();
        header("Location: login.php?message=" . urlencode("Invalid session. Please log in again."));
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error, maybe show a message
}

// Re-use the header from the homepage for consistency
$page_title = 'My Profile';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<style>
    /* Modern Dashboard CSS */
    :root {
        --dashboard-bg: #f9fafb;
        --sidebar-width: 280px;
        --card-radius: 12px;
        --transition: all 0.2s ease;
    }

    body { background-color: var(--dashboard-bg); }

    .dashboard-wrapper {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        padding: 3rem 0;
    }

    @media (min-width: 992px) {
        .dashboard-wrapper {
            flex-direction: row;
            align-items: flex-start;
        }
    }

    /* Sidebar Navigation */
    .dashboard-sidebar {
        flex: 0 0 100%;
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        position: sticky;
        top: 100px;
    }

    @media (min-width: 992px) {
        .dashboard-sidebar { flex: 0 0 var(--sidebar-width); }
    }

    .user-brief {
        padding: 2rem;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(135deg, var(--primary-color), #374151);
        color: white;
    }

    .user-avatar-placeholder {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem;
        font-weight: bold;
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
    }

    .user-brief h3 { margin: 0; font-size: 1.1rem; font-weight: 600; }
    .user-brief p { margin: 0.25rem 0 0; font-size: 0.85rem; opacity: 0.8; }

    .sidebar-nav { padding: 0.5rem 0; }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 0.85rem 1.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
        border-left: 3px solid transparent;
        font-size: 0.95rem;
    }

    .nav-item i { width: 24px; margin-right: 10px; text-align: center; }
    .nav-item:hover { background-color: var(--bg-light); color: var(--primary-color); }
    .nav-item.active { background-color: #eff6ff; color: var(--accent-color); border-left-color: var(--accent-color); }
    .nav-item.logout { color: #ef4444; margin-top: 0.5rem; border-top: 1px solid var(--border-color); }
    .nav-item.logout:hover { background-color: #fef2f2; }

    /* Main Content */
    .dashboard-content { flex: 1; width: 100%; display: flex; flex-direction: column; gap: 2rem; }

    .dashboard-card {
        background: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        padding: 2rem;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .card-title { font-size: 1.2rem; font-weight: 700; color: var(--primary-color); margin: 0; display: flex; align-items: center; gap: 0.75rem; }

    /* Info Grid */
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; }
    .info-item label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 0.4rem; font-weight: 600; }
    .info-item p { margin: 0; font-size: 1rem; color: var(--text-primary); font-weight: 500; }

    /* Order Table */
    .table-responsive { overflow-x: auto; }
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .modern-table th { background-color: #f8fafc; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
    .modern-table td { padding: 1rem; border-bottom: 1px solid var(--border-color); color: var(--text-primary); font-size: 0.9rem; }
    .modern-table tr:last-child td { border-bottom: none; }

    .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; line-height: 1; }
    .status-pending { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .status-completed { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
    .status-cancelled { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-secondary); }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; color: #d1d5db; }
</style>

<main class="container">
    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-top: 2rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top: 2rem;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-brief">
                <div class="user-avatar-placeholder"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                <h3><?= htmlspecialchars($user_name) ?></h3>
                <p>Member</p>
            </div>
            <nav class="sidebar-nav">
                <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i> My Profile</a>
                <a href="edit_profile.php" class="nav-item"><i class="fas fa-edit"></i> Edit Details</a>
                <a href="change_password.php" class="nav-item"><i class="fas fa-lock"></i> Security</a>
                <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Content -->
        <div class="dashboard-content">
            <!-- Account Details Card -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-id-card"></i> Account Information</h2>
                    <a href="edit_profile.php" class="btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.85rem; border-radius: 6px; text-decoration: none; color: var(--text-primary); border: 1px solid var(--border-color);">Edit</a>
                </div>
                <div class="info-grid">
                    <div class="info-item"><label>Full Name</label><p><?= htmlspecialchars($user_data['name'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Email Address</label><p><?= htmlspecialchars($user_data['email'] ?? 'N/A') ?></p></div>
                    <div class="info-item"><label>Phone Number</label><p><?= htmlspecialchars($user_data['phone_number'] ?? 'Not provided') ?></p></div>
                    <div class="info-item" style="grid-column: 1 / -1;"><label>Shipping Address</label><p><?= nl2br(htmlspecialchars($user_data['address'] ?? 'Not provided')) ?></p></div>
                </div>
            </section>

            <!-- Order History Card -->
            <section class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-shopping-bag"></i> Recent Orders</h2>
                </div>
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-basket"></i>
                        <p>You haven't placed any orders yet.</p>
                        <a href="store.php" class="btn-primary" style="display: inline-block; width: auto; margin-top: 1rem; padding: 0.6rem 1.5rem;">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date Placed</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($order['id']) ?></strong></td>
                                        <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
                                        <td style="font-weight: 600;">₦<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = 'status-badge';
                                                if ($order['status'] === 'Pending') $statusClass .= ' status-pending';
                                                elseif ($order['status'] === 'Completed') $statusClass .= ' status-completed';
                                                else $statusClass .= ' status-cancelled';
                                            ?>
                                            <span class="<?= $statusClass ?>"><?= htmlspecialchars($order['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>

























































































































































body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--bg-light);
    color: var(--text-primary);
    margin: 0;
    line-height: 1.6;
}

.footer {
    background-color: var(--primary-color);
    color: var(--text-secondary);
    padding: 3rem 0;
    text-align: center;
    margin-top: 2rem;
}
.footer a { color: white; margin: 0 10px; text-decoration: none; }
