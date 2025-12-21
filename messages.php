<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=messages");
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$user_id = $_SESSION['user_id'];
$messages = [];
$error = '';
$filter = $_GET['filter'] ?? 'all';

try {
    // Build query based on filter
    $sql = "SELECT * FROM messages WHERE user_id = :user_id";
    if ($filter === 'read') {
        $sql .= " AND is_read = 1";
    } elseif ($filter === 'unread') {
        $sql .= " AND is_read = 0";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // After fetching, mark displayed unread messages as read (unless we are only viewing 'read' messages)
    if ($filter !== 'read') {
        $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE user_id = :user_id AND is_read = 0");
        $updateStmt->execute(['user_id' => $user_id]);
    }

} catch (PDOException $e) {
    // Gracefully handle if the messages table doesn't exist yet
    if ($e->getCode() === '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
        // Table doesn't exist, create it automatically
        try {
            $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_inquiries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                CONSTRAINT fk_contact_inquiries_users
                FOREIGN KEY (user_id)
                REFERENCES users(id)
                ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    body TEXT NOT NULL,
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $messages = []; // Table is new, so no messages yet
        } catch (PDOException $ex) {
            $error = "Failed to initialize messages system: " . $ex->getMessage();
        }
    } elseif ($e->getCode() === '42S22' || strpos($e->getMessage(), "Unknown column") !== false) {
        // Column missing, attempt to add it
        try {
            $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
            // Refresh page to retry query
            header("Location: messages.php?filter=" . urlencode($filter));
            exit;
        } catch (PDOException $ex) {
             $error = "Database Error: Failed to update table schema. " . $ex->getMessage();
        }
    } else {
        $error = "Database Error: " . $e->getMessage();
    }
}

$page_title = 'My Messages';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<style>
    .messages-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .messages-container h2 {
        font-size: 1.8rem;
        margin-bottom: 2rem;
        color: var(--primary-color);
    }
    .message-item {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        transition: box-shadow 0.2s;
    }
    .message-item:hover {
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    }
    .message-item.unread {
        border-left: 4px solid var(--accent-color);
    }
    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background-color: #f9fafb;
    }
    .message-header strong { font-size: 1.1rem; color: var(--text-primary); }
    .message-header span { font-size: 0.85rem; color: var(--text-secondary); }
    .message-body {
        padding: 1.5rem;
        line-height: 1.7;
        color: var(--text-secondary);
    }
    .empty-state { text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 12px; }
    
    .filter-bar { margin-bottom: 2rem; display: flex; gap: 0.5rem; }
    .filter-btn { 
        padding: 0.5rem 1rem; 
        border-radius: 50px; 
        text-decoration: none; 
        font-size: 0.9rem; 
        font-weight: 500; 
        color: var(--text-secondary); 
        background-color: #e5e7eb; 
        transition: all 0.2s;
    }
    .filter-btn:hover { background-color: #d1d5db; color: var(--text-primary); }
    .filter-btn.active { background-color: var(--primary-color); color: white; }
</style>

<div class="container messages-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="margin: 0;">My Messages</h2>
    </div>

    <div class="filter-bar">
        <a href="messages.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="messages.php?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">Unread</a>
        <a href="messages.php?filter=read" class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">Read</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (empty($messages)): ?>
        <div class="empty-state"><p>You have no messages at the moment.</p></div>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
            <div class="message-item <?= $message['is_read'] == 0 ? 'unread' : '' ?>">
                <div class="message-header">
                    <strong><?= htmlspecialchars($message['subject']) ?></strong>
                    <span><?= date("M d, Y, g:i a", strtotime($message['created_at'])) ?></span>
                </div>
                <div class="message-body">
                    <?= nl2br(htmlspecialchars($message['body'])) ?>
                    <div style="margin-top: 1.5rem;">
                        <a href="contact_us.php?subject=Re: <?= urlencode($message['subject']) ?>" class="btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-size: 0.9rem; color: white; display: inline-block;"><i class="fas fa-reply"></i> Reply</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php';
?>
