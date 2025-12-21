<?php
require_once 'init.php';

// The category structure from the user
$category_tree = [
    'Women’s' => [
        'Tops' => ['Blouses', 'T-shirts', 'Crop tops'],
        'Bottoms' => ['Skirts', 'Trousers', 'Jeans'],
        'Dresses' => ['Casual', 'Evening', 'Formal'],
        'Outerwear' => ['Jackets', 'Coats', 'Blazers'],
        'Activewear' => ['Leggings', 'Sports bras', 'Yoga sets'],
        'Shoes' => ['Heels', 'Flats', 'Sneakers', 'Boots'],
    ],
    'Men’s' => [
        'Tops' => ['Shirts', 'Polos', 'T-shirts'],
        'Bottoms' => ['Jeans', 'Chinos', 'Shorts'],
        'Outerwear' => ['Jackets', 'Coats', 'Blazers'],
        'Formalwear' => ['Suits', 'Dress shirts', 'Ties'],
        'Activewear' => ['Tracksuits', 'Gym wear'],
        'Shoes' => ['Dress shoes', 'Sneakers', 'Boots'],
    ],
    'Unisex' => [
        'Basics' => ['Hoodies', 'Sweatshirts', 'Tees'],
        'Activewear' => ['Joggers', 'Performance wear'],
        'Outerwear' => ['Parkas', 'Bomber jackets'],
        'Footwear' => ['Sneakers', 'Sandals'],
    ],
    'Kid’s' => [
        'Boys’ Clothing' => [],
        'Girls’ Clothing' => [],
        'Babywear' => [],
        'Shoes' => [],
    ],
    'Accessories' => [],
    'Seasonal / Occasion' => [
        'Winter Wear' => ['Scarves', 'Gloves', 'Thermal layers'],
        'Summer Wear' => [],
        'Rainy Season' => [],
        'Festive & Party Wear' => [],
        'Travel Essentials' => [],
    ],
];

function seed_categories($pdo, $categories, $parent_id = null) {
    $count = 0;
    foreach ($categories as $name => $sub_categories) {
        if (is_int($name)) {
            $name = $sub_categories;
            $sub_categories = [];
        }

        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id <=> ?");
        $stmt->execute([$name, $parent_id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $insert_stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
            $insert_stmt->execute([$name, $parent_id]);
            $new_id = $pdo->lastInsertId();
            $count++;
            echo "<li>Added '{$name}'</li>";
        } else {
            $new_id = $existing['id'];
        }

        if (!empty($sub_categories)) {
            $count += seed_categories($pdo, $sub_categories, $new_id);
        }
    }
    return $count;
}

$page_title = 'Seed Categories';
require_once 'assets/header.php';
?>

<div class="page-header"><h1><i class="fas fa-seedling"></i> Seed Product Categories</h1></div>

<div class="card">
    <div class="card-body">
        <p>This script will populate the database with a default set of product categories and sub-categories. It will not create duplicates if categories already exist.</p>
        <hr>
        <h4>Processing Log:</h4>
        <ul>
        <?php
        $total_added = 0;
        // --- Schema Migration for Seeder ---
        // The seeder needs to ensure the schema is correct to handle hierarchical data with non-unique names.
        
        // 1. Add parent_id column if it doesn't exist
        try { $pdo->exec("ALTER TABLE categories ADD COLUMN parent_id INT NULL"); } catch (PDOException $e) { /* Ignore */ }

        // 2. Drop the old, problematic unique index on `name` if it exists
        try {
            // The error "for key 'categories.name'" indicates the index is likely named 'name'.
            $pdo->exec("ALTER TABLE categories DROP INDEX name");
            echo "<li>Notice: Dropped old unique index on `name` column.</li>";
        } catch (PDOException $e) { /* Ignore if index 'name' doesn't exist */ }

        // 3. Add the new, correct composite unique index on (name, parent_id)
        try { $pdo->exec("ALTER TABLE categories ADD UNIQUE `uq_category_name_parent` (name, parent_id)"); } catch (PDOException $e) { /* Ignore if index already exists */ }

        // 4. Add foreign key constraint
        try { $pdo->exec("ALTER TABLE categories ADD CONSTRAINT fk_parent_category FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE"); } catch (PDOException $e) { /* Ignore if constraint already exists */ }
        echo "<li>Database schema verified for hierarchical categories.</li>";
        // --- End Schema Migration ---

        $total_added = seed_categories($pdo, $category_tree);
        ?>
        </ul>
        <hr>
        <div class="alert alert-success">
            <strong>Seeding Complete.</strong> <?= $total_added ?> new categories were added.
        </div>
        <a href="categories.php" class="btn btn-primary">View Categories</a>
    </div>
</div>

<?php
require_once 'assets/footer.php';
?>
