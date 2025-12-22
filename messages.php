<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$messages = [];
$total_pages = 1;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10; // Messages per page
$offset = ($page - 1) * $limit;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($delete_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
                $stmt->execute([$delete_id, $user_id]);
                $success = "Message deleted successfully.";
            } catch (PDOException $e) {
                $error = "Failed to delete message.";
            }
        }
    } elseif (isset($_POST['mark_read_id'])) {
        $read_id = filter_input(INPUT_POST, 'mark_read_id', FILTER_VALIDATE_INT);
        if ($read_id) {
            try {
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$read_id, $user_id]);
                $success = "Message marked as read.";
            } catch (PDOException $e) {
                $error = "Failed to update message status.";
            }
        }
    }
}

// Fetch Messages
try {
    // Get total count for pagination
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $total_messages = $count_stmt->fetchColumn();
    $total_pages = ceil($total_messages / $limit);

    // We fetch all columns. The column containing the text might be 'message' or 'body'
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist yet, just show empty
    if ($e->getCode() !== '42S02') {
        $error = "Database Error: " . $e->getMessage();
    }
}

$page_title = 'My Messages';
require_once __DIR__ . '/store_header.php';
?>

<style>
    .message-card {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
        background: white;
    }
    .message-card.unread {
        border-left: 4px solid var(--accent-color);
        background-color: #f8fafc;
    }
    .message-card:hover {
        box-shadow: var(--shadow);
    }
    details > summary {
        list-style: none;
        cursor: pointer;
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    details > summary::-webkit-details-marker { display: none; }
    details[open] > summary {
        border-bottom: 1px solid var(--border-color);
        background-color: #f9fafb;
        border-radius: 8px 8px 0 0;
    }
    .message-body {
        padding: 1.5rem;
        color: var(--text-primary);
    }
    .badge-new {
        background-color: #ef4444;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 4px;
        margin-right: 8px;
        vertical-align: middle;
    }
    .pagination { display: flex; padding-left: 0; list-style: none; justify-content: center; margin-top: 1.5rem; }
    .page-link { position: relative; display: block; color: var(--accent-color); text-decoration: none; background-color: #fff; border: 1px solid #dee2e6; transition: all 0.2s; padding: .5rem .75rem; margin: 0 2px; border-radius: 4px; }
    .page-item.active .page-link { z-index: 3; color: #fff; background-color: var(--accent-color); border-color: var(--accent-color); }
    .page-item.disabled .page-link { color: #6c757d; pointer-events: none; background-color: #f8f9fa; border-color: #dee2e6; }
    .page-link:hover { background-color: #e9ecef; }
</style>

<div class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4"><i class="fas fa-envelope"></i> My Messages</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($messages)): ?>
                <div class="alert alert-info">
                    You have no messages in your inbox.
                </div>
            <?php else: ?>
                <div class="messages-list">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            // COMPATIBILITY FIX: Check for 'message' column first, then fallback to 'body'
                            // This resolves "Undefined array key 'body'" errors if the schema changed.
                            $content = $msg['message'] ?? $msg['body'] ?? '';
                            $subject = $msg['subject'] ?? '(No Subject)';
                            $date = date('M d, Y h:i A', strtotime($msg['created_at']));
                            $is_read = !empty($msg['is_read']);
                        ?>
                        <div class="message-card <?= !$is_read ? 'unread' : '' ?>">
                            <details <?= !$is_read ? 'open' : '' ?>>
                                <summary>
                                    <div class="d-flex align-items-center">
                                        <?php if (!$is_read): ?>
                                            <span class="badge-new">NEW</span>
                                        <?php else: ?>
                                            <i class="fas fa-envelope-open text-muted me-2"></i>
                                        <?php endif; ?>
                                        <span class="<?= !$is_read ? 'fw-bold' : '' ?>"><?= htmlspecialchars($subject) ?></span>
                                    </div>
                                    <small class="text-muted"><?= $date ?></small>
                                </summary>
                                <div class="message-body">
                                    <p style="white-space: pre-wrap; line-height: 1.6;"><?= htmlspecialchars($content) ?></p>
                                    
                                    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                                        <?php if (!$is_read): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="mark_read_id" value="<?= $msg['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" style="display: inline;">
                                            <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Messages pagination">
                        <ul class="pagination">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="profile.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/store_footer.php'; ?>
