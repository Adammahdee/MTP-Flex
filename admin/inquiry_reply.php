<?php
require_once 'init.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: inquiries.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM contact_inquiries WHERE id = ?");
$stmt->execute([$id]);
$inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inquiry) {
    header("Location: inquiries.php");
    exit;
}

// Mark inquiry as read when viewed
if (isset($inquiry['is_read']) && $inquiry['is_read'] == 0) {
    $pdo->prepare("UPDATE contact_inquiries SET is_read = 1 WHERE id = ?")->execute([$id]);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_message = trim($_POST['reply_message'] ?? '');
    
    if (empty($reply_message)) {
        $error = "Reply message cannot be empty.";
    } else {
        if (!empty($inquiry['user_id'])) {
            // User is registered, send to internal inbox
            try {
                // 1. Ensure the messages table exists with a minimal valid schema.
                $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Fix: Handle legacy 'body' column causing "Field 'body' doesn't have a default value" error
                try {
                    // Attempt to rename 'body' to 'message' (preserves data if only 'body' exists)
                    $pdo->exec("ALTER TABLE messages CHANGE COLUMN body message TEXT NOT NULL");
                } catch (PDOException $e) {
                    // If rename failed (e.g., 'message' already exists), drop 'body' to resolve conflict
                    try {
                        $pdo->exec("ALTER TABLE messages DROP COLUMN body");
                    } catch (PDOException $e2) { /* Ignore if body doesn't exist */ }
                }

                // 2. Self-healing: Add columns if they don't exist.
                // This is safer than one big CREATE statement if the table exists in a partial state.
                $columns_to_add = [
                    'subject' => 'VARCHAR(255) NOT NULL',
                    'message' => 'TEXT NOT NULL',
                    'is_read' => 'TINYINT(1) DEFAULT 0'
                ];

                foreach ($columns_to_add as $column => $type) {
                    try {
                        $pdo->exec("ALTER TABLE messages ADD COLUMN {$column} {$type}");
                    } catch (PDOException $e) {
                        // Error for 'Duplicate column name' is SQLSTATE 42S21.
                        // We can safely ignore this error and continue.
                        if ($e->getCode() !== '42S21') {
                            throw $e; // Re-throw other errors
                        }
                    }
                }

                // 3. Now, it's safe to insert.
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message) VALUES (?, ?, ?)");
                $subject = "Re: " . $inquiry['subject'];
                $stmt->execute([$inquiry['user_id'], $subject, $reply_message]);
                $success = "Reply sent successfully to the user's inbox.";
            } catch (PDOException $e) {
                $error = "Failed to send message: " . $e->getMessage();
            }
        } else {
            // User is a guest
            $success = "This was a guest inquiry. In a live environment, an email would be sent to <strong>" . htmlspecialchars($inquiry['email']) . "</strong>.";
        }
    }
}

$page_title = 'Reply to Inquiry';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-reply"></i> Reply to Inquiry #<?= $inquiry['id'] ?></h1>
    <a href="inquiries.php" class="btn btn-outline-secondary">Back to List</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Message Details</strong></div>
            <div class="card-body">
                <p><strong>From:</strong> <?= htmlspecialchars($inquiry['name']) ?> (<?= htmlspecialchars($inquiry['email']) ?>)</p>
                <p><strong>Subject:</strong> <?= htmlspecialchars($inquiry['subject']) ?></p>
                <p><strong>Date:</strong> <?= date('F j, Y, g:i a', strtotime($inquiry['created_at'])) ?></p>
                <hr>
                <p style="white-space: pre-wrap;"><?= htmlspecialchars($inquiry['message']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Send Reply</strong></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Reply Message</label>
                        <textarea name="reply_message" class="form-control" rows="6" required placeholder="Type your reply here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reply</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
