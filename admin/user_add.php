<?php
// Centralized initialization
require_once 'init.php';
require_once 'assets/header.php';

$name = $email = $phone_number = $address = $is_admin = '';
$message = $message_type = '';

// --- PHP LOGIC: Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $is_admin = (isset($_POST['is_admin']) && $_POST['is_admin'] == 1) ? 1 : 0;

    if (empty($name) || !$email || empty($password)) {
        $message = "Name, valid Email, and Password are required.";
        $message_type = 'danger';
    } else if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = 'danger';
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Insert user into the database
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, phone_number, address, is_admin)
                VALUES (:name, :email, :password, :phone_number, :address, :is_admin)
            ");
            
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashed_password,
                'phone_number' => $phone_number,
                'address' => $address,
                'is_admin' => $is_admin
            ]);

            $message = "User '{$name}' created successfully! Role: " . ($is_admin ? 'Admin' : 'Customer');
            $message_type = 'success';
            
            // Clear form fields on success
            $name = $email = $phone_number = $address = '';

        } catch (PDOException $e) {
            // Check for unique constraint violation (Email must be unique)
            if ($e->getCode() == '23000') {
                 $message = "Database Error: A user with this email already exists.";
            } else {
                 $message = "Database Error: Failed to add user. " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}
?>

    <div class="page-header">
        <h1><i class="fas fa-user-plus"></i> Add New User</h1>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid <?= $message_type === 'success' ? '#16a34a' : ($message_type === 'danger' ? '#dc2626' : '#ffc107') ?>; background: <?= $message_type === 'success' ? '#dcfce7' : ($message_type === 'danger' ? '#f8d7da' : '#fffbe7') ?>; color: #333;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="user_add.php" method="POST">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?= htmlspecialchars($phone_number) ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($address) ?></textarea>
                    </div>

                    <div class="form-group">
                        <input type="checkbox" id="is_admin" name="is_admin" value="1" <?= $is_admin == 1 ? 'checked' : '' ?>>
                        <label for="is_admin" style="display: inline; font-weight: normal;">Grant Administrator Privileges</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User Account</button>
                <a href="users.php" class="btn info" style="margin-left: 10px;"><i class="fas fa-arrow-left"></i> Back to User List</a>
            </div>
        </form>
    </div>

<?php
require_once "assets/footer.php";
?>