<?php
require_once 'init.php';

// Attempt to load Faker, otherwise use simple fallback
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
    $faker = Faker\Factory::create();
} else {
    // Simple fallback if Composer/Faker is not installed
    class SimpleFaker {
        function word() { $w = ['Shirt','Pant','Shoe','Hat','Watch','Bag','Scarf']; return $w[array_rand($w)]; }
        function sentence() { return "This is a generated product description."; }
        function numberBetween($min, $max) { return rand($min, $max); }
        function randomFloat($d, $min, $max) { return rand($min*100, $max*100)/100; }
        function randomElement($arr) { return $arr[array_rand($arr)]; }
        function colorName() { return $this->randomElement(['Red','Blue','Green','Black','White']); }
    }
    $faker = new SimpleFaker();
}

$message = "";

if (isset($_POST['seed'])) {
    try {
        $pdo->beginTransaction();

        // 1. Create Tags
        $tags = ['Summer', 'Winter', 'Sale', 'New Arrival', 'Best Seller', 'Eco-Friendly', 'Limited Edition'];
        $tag_ids = [];
        foreach ($tags as $t) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
            $stmt->execute([$t]);
            // Get ID (either new or existing)
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$t]);
            $tag_ids[] = $stmt->fetchColumn();
        }

        // 2. Create Products
        $cats_stmt = $pdo->query("SELECT id FROM categories");
        $category_ids = $cats_stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($category_ids)) die("Please seed categories first.");

        $count = 1000;
        $stmt_prod = $pdo->prepare("INSERT INTO products (name, description, price, stock, status, category_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_var = $pdo->prepare("INSERT INTO product_variants (product_id, sku, stock, price) VALUES (?, ?, ?, ?)");
        $stmt_attr = $pdo->prepare("INSERT INTO variant_attributes (variant_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
        $stmt_tag = $pdo->prepare("INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (?, ?)");

        for ($i = 0; $i < $count; $i++) {
            $name = ucfirst($faker->word()) . " " . ucfirst($faker->word());
            $price = $faker->randomFloat(2, 10, 500);
            $cat_id = $faker->randomElement($category_ids);
            
            // Insert Product
            $stmt_prod->execute([
                $name, 
                $faker->sentence(), 
                $price, 
                0, // Stock will be sum of variants
                'Available', 
                $cat_id
            ]);
            $product_id = $pdo->lastInsertId();

            // Create Variants (Size/Color)
            $sizes = ['S', 'M', 'L', 'XL'];
            $colors = [$faker->colorName(), $faker->colorName()];
            $total_stock = 0;

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    // 50% chance a variant exists
                    if ($faker->numberBetween(0, 1)) {
                        $stock = $faker->numberBetween(0, 50); // Some will be 0 (low stock)
                        $sku = strtoupper(substr($name, 0, 3)) . "-{$size}-" . strtoupper(substr($color, 0, 3)) . "-" . rand(100,999);
                        
                        $stmt_var->execute([$product_id, $sku, $stock, null]);
                        $var_id = $pdo->lastInsertId();

                        $stmt_attr->execute([$var_id, 'Size', $size]);
                        $stmt_attr->execute([$var_id, 'Color', $color]);

                        $total_stock += $stock;
                    }
                }
            }

            // Update parent stock
            $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$total_stock, $product_id]);

            // Assign Random Tags
            $num_tags = rand(1, 3);
            for ($t=0; $t<$num_tags; $t++) {
                $stmt_tag->execute([$product_id, $faker->randomElement($tag_ids)]);
            }
        }

        $pdo->commit();
        $message = "Successfully seeded $count products with variants and tags.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

$page_title = 'Seed Products';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-database"></i> Seed Database</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p>This will generate <strong>1000 products</strong> with:</p>
        <ul>
            <li>Random Categories</li>
            <li>Variants (Size/Color) with separate stock</li>
            <li>Tags (Summer, Sale, etc.)</li>
        </ul>
        <form method="POST">
            <button type="submit" name="seed" class="btn btn-danger btn-lg">Generate Seed Data</button>
        </form>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
