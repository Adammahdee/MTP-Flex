<?php
require_once 'init.php';

$page_title = 'Seed Accessories';
require_once 'assets/header.php';

$accessories_name = 'Accessories';
$sub_categories = [
    'Jewelry' => 'Necklaces, rings, earrings',
    'Bags' => 'Handbags, totes, clutches',
    'Headwear' => 'Hats, scarves',
    'Belts' => 'Fashion belts',
    'Sunglasses' => 'Eyewear',
    'Watches' => 'Wristwatches',
    'Hair Accessories' => 'Ties, clips'
];

$messages = [];

// Handle Structure Seeding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_structure'])) {
    try {
        // 1. Get or Create Parent 'Accessories'
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id IS NULL");
        $stmt->execute([$accessories_name]);
        $parent_id = $stmt->fetchColumn();

        if (!$parent_id) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, NULL)");
            $stmt->execute([$accessories_name, 'Main Accessories Category']);
            $parent_id = $pdo->lastInsertId();
            $messages[] = "<div class='alert alert-success'>Created parent category: <strong>$accessories_name</strong></div>";
        } else {
            $messages[] = "<div class='alert alert-info'>Parent category <strong>$accessories_name</strong> already exists.</div>";
        }

        // 2. Create Sub-categories
        foreach ($sub_categories as $name => $desc) {
            // Check if exists under this parent
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
            $stmt->execute([$name, $parent_id]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc, $parent_id]);
                $messages[] = "<div class='alert alert-success'>Created sub-category: <strong>$name</strong> ($desc)</div>";
            } else {
                $messages[] = "<div class='alert alert-warning'>Sub-category <strong>$name</strong> already exists.</div>";
            }
        }
    } catch (PDOException $e) {
        $messages[] = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Handle Product Seeding
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_products'])) {
    $products_data = [
        'Jewelry' => [
            ['Gold Layered Necklace', 12500, 'Elegant gold layered necklace suitable for any occasion.', 'https://images.unsplash.com/photo-1599643478518-17488fbbcd75?auto=format&fit=crop&w=500&q=60'],
            ['Silver Stud Earrings', 5000, 'Classic silver studs, perfect for daily wear.', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=500&q=60'],
            ['Diamond Simulant Ring', 15000, 'Sparkling ring that catches the light beautifully.', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=500&q=60']
        ],
        'Bags' => [
            ['Leather Tote Bag', 45000, 'Spacious leather tote for work or travel.', 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=500&q=60'],
            ['Evening Clutch', 25000, 'Stylish clutch for evening events.', 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?auto=format&fit=crop&w=500&q=60'],
            ['Crossbody Bag', 30000, 'Convenient crossbody bag for hands-free carrying.', 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=500&q=60']
        ],
        'Watches' => [
            ['Classic Leather Watch', 35000, 'Timeless design with a genuine leather strap.', 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=500&q=60'],
            ['Minimalist Gold Watch', 40000, 'Sleek gold-tone watch with a minimalist dial.', 'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?auto=format&fit=crop&w=500&q=60']
        ],
        'Sunglasses' => [
            ['Aviator Sunglasses', 10000, 'Classic aviator style with UV protection.', 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=500&q=60'],
            ['Cat Eye Sunglasses', 12000, 'Trendy cat-eye frames for a bold look.', 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=500&q=60']
        ]
    ];

    try {
        $count = 0;
        $stmt_insert = $pdo->prepare("INSERT INTO products (name, description, price, stock, category_id, status, image_path) VALUES (?, ?, ?, ?, ?, 'Available', ?)");
        
        foreach ($products_data as $cat_name => $items) {
            // Find category ID
            $stmt_cat = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt_cat->execute([$cat_name]);
            $cat_id = $stmt_cat->fetchColumn();

            if ($cat_id) {
                foreach ($items as $item) {
                    $stmt_insert->execute([$item[0], $item[2], $item[1], 50, $cat_id, $item[3]]);
                    $count++;
                }
            }
        }
        $messages[] = "<div class='alert alert-success'>Successfully added <strong>$count</strong> dummy products to accessory categories.</div>";
    } catch (PDOException $e) {
        $messages[] = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-magic"></i> Seed Accessories</h1>
</div>

<div class="card">
    <div class="card-body">
        <?php foreach ($messages as $msg) echo $msg; ?>
        
        <p>Use the buttons below to populate your database with accessory categories and dummy products.</p>
        
        <form method="POST" class="d-flex gap-3">
            <button type="submit" name="seed_structure" class="btn btn-info"><i class="fas fa-folder-tree"></i> Seed Categories Structure</button>
            <button type="submit" name="seed_products" class="btn btn-success"><i class="fas fa-box-open"></i> Seed Dummy Products</button>
        </form>

        <div class="mt-3">
            <a href="categories.php" class="btn btn-primary">Go to Categories</a>
        </div>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>
