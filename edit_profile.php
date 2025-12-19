<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";
$page_title = 'Edit Profile';
require_once __DIR__ . "/store_header.php";

$user_id = $_SESSION['user_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $phone   = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    if ($name === "") {
        $error = "Name is required.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET name = ?, phone_number = ?, address = ?
            WHERE id = ? AND is_admin = 0
        ");

        if ($stmt->execute([$name, $phone, $address, $user_id])) {
            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

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
</style>

<main class="container">
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin-top: 2rem;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-top: 2rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-brief">
                <div class="user-avatar-placeholder"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <h3><?= htmlspecialchars($user['name']) ?></h3>
                <p>Member</p>
            </div>
            <nav class="sidebar-nav">
                <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> My Profile</a>
                <a href="edit_profile.php" class="nav-item active"><i class="fas fa-edit"></i> Edit Details</a>
                <a href="change_password.php" class="nav-item"><i class="fas fa-lock"></i> Security</a>
                <a href="logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Content -->
        <div class="dashboard-content">
            <section class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-edit"></i> Edit Profile Details</h2>
                </div>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email (cannot be changed)</label>
                        <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background-color: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="4"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary" style="width: auto; padding-left: 2rem; padding-right: 2rem;">Save Changes</button>
                        <a href="profile.php" class="btn-secondary" style="padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; color: var(--text-primary); border: 1px solid var(--border-color);">Cancel</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>

<?php include 'store_footer.php'; ?>
