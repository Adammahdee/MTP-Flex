<?php
require_once 'init.php';
$page_title = 'Fix Accessories Structure';
require_once 'assets/header.php';

$messages = [];

try {
    $pdo->beginTransaction();

    // 1. Find or Create the Master Top-Level "Accessories" Category
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Accessories' AND parent_id IS NULL LIMIT 1");
    $stmt->execute();
    $master_id = $stmt->fetchColumn();

    if (!$master_id) {
        $pdo->exec("INSERT INTO categories (name, description, parent_id) VALUES ('Accessories', 'Main Accessories Category', NULL)");
        $master_id = $pdo->lastInsertId();
        $messages[] = "<div class='alert alert-success'>Created new top-level 'Accessories' category (ID: $master_id).</div>";
    } else {
        $messages[] = "<div class='alert alert-info'>Found existing top-level 'Accessories' category (ID: $master_id).</div>";
    }

    // 1.5 Force-Link Known Sub-Categories to Master
    // This fixes issues where sub-categories exist but are orphaned or linked to wrong parents
    $known_subs = ['Jewelry', 'Bags', 'Headwear', 'Belts', 'Sunglasses', 'Watches', 'Hair Accessories'];
    foreach ($known_subs as $sub_name) {
        $stmt = $pdo->prepare("UPDATE categories SET parent_id = ? WHERE name LIKE ? AND id != ?");
        $stmt->execute([$master_id, $sub_name, $master_id]);
        if ($stmt->rowCount() > 0) {
            $messages[] = "<div class='alert alert-success'>Linked sub-category '<strong>$sub_name</strong>' to Accessories.</div>";
        }
    }

    // 2. Find all other "Accessories" categories (duplicates or sub-categories)
    $stmt = $pdo->prepare("SELECT id, parent_id FROM categories WHERE name = 'Accessories' AND id != ?");
    $stmt->execute([$master_id]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicates)) {
        $messages[] = "<div class='alert alert-success'>No duplicate 'Accessories' categories found.</div>";
    } else {
        foreach ($duplicates as $dup) {
            $dup_id = $dup['id'];
            
            // Move children of this duplicate to the master
            $update_children = $pdo->prepare("UPDATE categories SET parent_id = ? WHERE parent_id = ?");
            $update_children->execute([$master_id, $dup_id]);
            $moved_count = $update_children->rowCount();
            
            if ($moved_count > 0) {
                $messages[] = "<div class='alert alert-warning'>Moved $moved_count sub-categories from duplicate Accessories (ID: $dup_id) to Master (ID: $master_id).</div>";
            }

            // Move products of this duplicate to the master
            $update_products = $pdo->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
            $update_products->execute([$master_id, $dup_id]);
            $moved_prods = $update_products->rowCount();

            if ($moved_prods > 0) {
                $messages[] = "<div class='alert alert-warning'>Moved $moved_prods products from duplicate Accessories (ID: $dup_id) to Master (ID: $master_id).</div>";
            }

            // Delete the duplicate
            $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $del->execute([$dup_id]);
            $messages[] = "<div class='alert alert-danger'>Deleted duplicate Accessories category (ID: $dup_id).</div>";
        }
    }

    $pdo->commit();
    $messages[] = "<div class='alert alert-success'><strong>Success!</strong> Accessories structure has been consolidated. Check your store menu now.</div>";

} catch (Exception $e) {
    $pdo->rollBack();
    $messages[] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<div class="page-header">
    <h1><i class="fas fa-tools"></i> Fix Accessories Structure</h1>
</div>

<div class="card">
    <div class="card-body">
        <?php foreach ($messages as $msg) echo $msg; ?>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
