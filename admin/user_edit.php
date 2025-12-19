<?php
// Centralized initialization
require_once 'init.php';
require_once 'assets/header.php';

$user = null;
$message = $message_type = '';

// Check if a user ID is provided in the URL
$user_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: users.php");
    exit;
}

try {
    // 1. Fetch Existing User Data
    $stmt = $pdo->prepare("SELECT id, name, email, phone_number, address, is_admin FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "Error: User not found.";
        $message_type = 'danger';
        goto end_of_logic; 
    }
    
} catch (PDOException $e) {
    $message = "Database Error: Could not load user data. Ensure 'users' table exists.";
    $message_type = 'danger';
    goto end_of_logic;
}

// 2. Handle Form Submission (Update Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message_type !== 'danger') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] == 1) ? 1 : 0;
    
    if (empty($name) || !$email) {
        $message = "Name and valid Email are required.";
        $message_type = 'danger';
    } else if (!empty($password) && strlen($password) < 6) {
        $message = "If changing, the password must be at least 6 characters long.";
        $message_type = 'danger';
    } else {
        $sql = "UPDATE users SET name = :name, email = :email, phone_number = :phone_number, address = :address, is_admin = :is_admin";
        $params = [
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone_number,
            'address' => $address,
            'is_admin' => $is_admin,
            'id' => $user_id
        ];

        // Include password update only if a new password was provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = :password";
            $params['password'] = $hashed_password;
        }

        $sql .= " WHERE id = :id";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $message = "User '{$name}' updated successfully!";
            $message_type = 'success';
            
            // Re-fetch data to update the form fields with the new values
            $stmt = $pdo->prepare("SELECT id, name, email, phone_number, address, is_admin FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
             // Check for unique constraint violation
            if ($e->getCode() == '23000') {
                 $message = "Database Error: A user with this email already exists.";
            } else {
                 $message = "Database Error: Failed to update user. " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}

end_of_logic:
?>

    <div class="page-header">
        <h1><i class="fas fa-user-edit"></i> Edit User: <?= htmlspecialchars($user['name'] ?? 'N/A') ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid <?= $message_type === 'success' ? '#16a34a' : ($message_type === 'danger' ? '#dc2626' : '#ffc107') ?>; background: <?= $message_type === 'success' ? '#dcfce7' : ($message_type === 'danger' ? '#f8d7da' : '#fffbe7') ?>; color: #333;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
        <div class="card">
            <form action="user_edit.php?id=<?= $user_id ?>" method="POST">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Change Password (Leave blank to keep current)</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="New Password">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                        </div>

                        <div class="form-group mt-4">
                            <input type="checkbox" id="is_admin" name="is_admin" value="1" <?= $user['is_admin'] == 1 ? 'checked' : '' ?>>
                            <label for="is_admin" style="display: inline; font-weight: normal;">Grant Administrator Privileges</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-sync-alt"></i> Update User Details</button>
                    <a href="users.php" class="btn info" style="margin-left: 10px;"><i class="fas fa-arrow-left"></i> Back to User List</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php
require_once "assets/footer.php";
?>