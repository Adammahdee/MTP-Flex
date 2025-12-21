<?php
require_once 'init.php';

$inquiries = [];
$error = '';

try {
    $stmt = $pdo->query("SELECT * FROM contact_inquiries ORDER BY created_at DESC");
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist if no inquiries have been made yet
    if ($e->getCode() !== '42S02') {
        $error = "Database error: " . $e->getMessage();
    }
}

$page_title = 'Customer Inquiries';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-envelope"></i> Customer Inquiries</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr><td colspan="5" class="text-center">No inquiries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $inquiry): ?>
                        <tr>
                            <td>#<?= $inquiry['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($inquiry['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($inquiry['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($inquiry['subject']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($inquiry['created_at'])) ?></td>
                            <td>
                                <a href="inquiry_reply.php?id=<?= $inquiry['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-reply"></i> View & Reply</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
