<?php
// Centralized initialization
require_once 'init.php';

// --- PHP LOGIC: Fetch Users ---
$users = [];
$error_message = null;

// Helper function to determine the badge style based on role
function get_role_badge($is_admin) {
    if ($is_admin == 1) {
        return '<span class="badge primary">Admin</span>';
    }
    return '<span class="badge info">Customer</span>';
}

try {
    // Attempt to fetch all users. Assumes 'users' table exists.
    $stmt = $pdo->prepare("SELECT id, name, email, created_at, is_admin FROM users ORDER BY created_at DESC");
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If the table 'users' does not exist
    $error_message = "The 'users' table is missing from your database. Please run the SQL script to create it.";
}

?>

<?php require_once 'assets/header.php'; ?>
    <div class="page-header">
        <h1><i class="fas fa-users"></i> User Management</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="card" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px;">
            <p><strong>Database Error:</strong> <?= $error_message ?></p>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="row" style="justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; padding: 0 10px;">Registered Users (<?= count($users) ?>)</h3>
            <div style="padding: 0 10px;">
                <a href="user_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New User</a>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= get_role_badge($user['is_admin']) ?></td>
                            <td><?= date("Y-m-d", strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="user_delete.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">
                            <?php if (!$error_message): ?>
                                No users registered yet.
                            <?php else: ?>
                                Cannot display user list due to database error above.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
// Include the structural closing components (Footer)
require_once "assets/footer.php";
?>