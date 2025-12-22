<?php
// Start session to access cart data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path is relative from /store.php to /config/db.php
require_once "config/db.php";

// Calculate total items in cart
$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    $cart_item_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

$products = [];
$categories = [];
$error = '';
$search_term = trim($_GET['search'] ?? ''); // Get search term from URL
$current_category = $_GET['category'] ?? ''; // Get category ID from URL if present

try {
    // 1. Fetch Categories for the sidebar/filter
    $stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY name ASC");
    $all_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build Tree
    $categories = []; // Top level
    $cats_map = [];
    foreach ($all_cats as $c) {
        $c['children'] = [];
        $cats_map[$c['id']] = $c;
    }
    foreach ($cats_map as $id => &$c) {
        if ($c['parent_id'] && isset($cats_map[$c['parent_id']])) {
            $cats_map[$c['parent_id']]['children'][] = &$c;
        } else {
            $categories[] = &$c;
        }
    }

    // 2. Build Query for Products
    $sql = "
        SELECT 
            p.id, p.name, p.description, p.price, p.image_path,
            c.name AS category_name
        FROM 
            products p
        LEFT JOIN 
            categories c ON p.category_id = c.id
        WHERE 
            p.status = 'Available'
    ";
    $params = [];

    // Apply Search Filter if provided
    if (!empty($search_term)) {
        $sql .= " AND p.name LIKE :search_term";
        $params['search_term'] = '%' . $search_term . '%';
    }

    // Apply Category Filter if selected
    if (!empty($current_category) && is_numeric($current_category)) {
        $sql .= " AND p.category_id = :category_id";
        $params['category_id'] = $current_category;
    }


    $sql .= " ORDER BY p.name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors (e.g., tables missing)
    $error = "Could not load store items. Please ensure the database tables are set up.";
}
?>

<?php 
$page_title = 'Shop - MTP Flex Store';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php'; 
?>
    <style>
        /* --- Store Layout --- */
        .store-wrapper {
            display: flex;
            gap: 2rem;
            padding: 2rem 0;
        }
        .store-sidebar { flex: 0 0 260px; }
        .store-content { flex: 1; }

        /* --- Sidebar Filters --- */
        .filter-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        .filter-card h3 { margin: 0 0 1rem 0; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; }
        .filter-card input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
        }
        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .category-list li a {
            display: block;
            padding: 0.5rem 0;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .category-list li a:hover { color: var(--accent-color); }
        .category-list li a.active { color: var(--primary-color); font-weight: 600; }

        /* --- Product Grid --- */
        .page-title { font-size: 1.8rem; margin-bottom: 1.5rem; }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        .product-card a { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
        .product-card .image-wrap {
            width: 100%;
            height: 280px;
        }
        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-info h4 { margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 500; }
        .product-info .price { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); margin-top: auto; }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
        }
    </style>

    <div class="container store-wrapper" style="padding-top: 2rem; padding-bottom: 2rem;">
        
        <aside class="sidebar">
            <div class="filter-card">
                <form action="store.php" method="GET">
                    <h3>Search</h3>
                    <input type="text" name="search" placeholder="e.g., 'T-Shirt'" value="<?= htmlspecialchars($search_term) ?>">
                    <?php if (!empty($current_category)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($current_category) ?>">
                    <?php endif; ?>
                </form>
            </div>

            <div class="filter-card">
                <h3>Categories</h3>
                <ul class="category-list">
                    <li>
                        <a href="store.php<?= !empty($search_term) ? '?search=' . htmlspecialchars($search_term) : '' ?>" class="<?= empty($current_category) ? 'active' : '' ?>">
                            All Products
                        </a>
                    </li>
                    <?php 
                    function render_sidebar_cats($cats, $current, $search) {
                        foreach ($cats as $category) {
                            $params = ['category' => $category['id']];
                            if (!empty($search)) { $params['search'] = $search; }
                            $active = ($current == $category['id']) ? 'active' : '';
                            echo '<li>';
                            echo '<a href="store.php?' . http_build_query($params) . '" class="' . $active . '">' . htmlspecialchars($category['name']) . '</a>';
                            if (!empty($category['children'])) {
                                echo '<ul class="category-list" style="padding-left: 15px;">';
                                render_sidebar_cats($category['children'], $current, $search);
                                echo '</ul>';
                            }
                            echo '</li>';
                        }
                    }
                    render_sidebar_cats($categories, $current_category, $search_term);
                    ?>
                </ul>
            </div>
        </aside>

        <main class="store-content">
            <h2 class="page-title">
                <?php if (!empty($search_term)): ?>
                    Searching for "<?= htmlspecialchars($search_term) ?>"
                <?php else: ?>
                    All Products
                <?php endif; ?>
            </h2>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php elseif (count($products) > 0): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <a href="product_detail.php?id=<?= $product['id'] ?>">
                                <div class="image-wrap">
                                    <img src="<?= htmlspecialchars($product['image_path'] ?? 'assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                </div>
                                <div class="product-info">
                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                    <p class="price">₦<?= number_format($product['price'], 2) ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No products found matching your criteria.</p>
            <?php endif; ?>
        </main>
    </div>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php'; ?>