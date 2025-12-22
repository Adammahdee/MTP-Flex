<?php
require_once 'init.php';

$page_title = 'Find Duplicate Categories';
require_once 'assets/header.php';

$duplicates = [];
$error = '';

try {
    $stmt = $pdo->query("
        SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM categories
        GROUP BY name
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<div class="page-header">
    <h1><i class="fas fa-copy"></i> Find Duplicate Categories</h1>
</div>

<?php if ($error) echo "<div class='alert alert-danger'>".htmlspecialchars($error)."</div>"; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($duplicates)): ?>
            <div class="alert alert-success">No duplicate category names found. Your schema is clean!</div>
        <?php else: ?>
            <div class="alert alert-warning">The following category names are duplicated. It is recommended to consolidate them and delete the extras from the <a href="categories.php">Manage Categories</a> page.</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Duplicate Name</th>
                        <th>Count</th>
                        <th>Category IDs Involved</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($duplicates as $dup): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dup['name']) ?></strong></td>
                            <td><?= $dup['count'] ?></td>
                            <td><?= htmlspecialchars($dup['ids']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
