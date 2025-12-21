<?php
// This header is for the PUBLIC user-facing store.
// It assumes a session has been started and cart count calculated by the calling page.

$cart_item_count = $cart_item_count ?? 0; // Null coalesce for safety

$unread_count = 0;
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unread_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Table might not exist yet, ignore error
    }
}

// Fetch Categories for Navigation
$nav_categories = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id ASC, name ASC");
        $all_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cat_map = [];
        foreach ($all_cats as $c) {
            $c['children'] = [];
            $cat_map[$c['id']] = $c;
        }
        foreach ($cat_map as $id => &$c) {
            if ($c['parent_id'] && isset($cat_map[$c['parent_id']])) {
                $cat_map[$c['parent_id']]['children'][] = &$c;
            } else {
                $nav_categories[] = &$c;
            }
        }
    } catch (PDOException $e) {
        // Ignore error if table doesn't exist yet
    }
}

// Helper function to render category dropdowns recursively
function render_nav_dropdown_items($children) {
    foreach ($children as $child) {
        if (!empty($child['children'])) {
            echo '<div class="nav-dropdown-submenu">';
            echo '<a href="store.php?category=' . $child['id'] . '" class="has-children">' . htmlspecialchars($child['name']) . '</a>';
            echo '<div class="nav-dropdown-menu">';
            render_nav_dropdown_items($child['children']);
            echo '</div>';
            echo '</div>';
        } else {
            echo '<a href="store.php?category=' . $child['id'] . '">' . htmlspecialchars($child['name']) . '</a>';
        }
    }
}

// Custom navigation structure
$apparel_group = ['name' => 'Apparel', 'children' => []];
$kids_group = null;
$seasonal_group = null;
$accessories_group = null;
$shop_all_cats = $nav_categories; // Keep a copy for the 'Shop' dropdown

// Re-organize the fetched categories
if (!empty($nav_categories)) {
    foreach($nav_categories as $category) {
        if (in_array($category['name'], ["Men’s", "Women’s", "Unisex"])) {
            $apparel_group['children'][] = $category;
        } elseif ($category['name'] === "Kid’s") {
            $kids_group = $category;
        } elseif ($category['name'] === "Seasonal / Occasion") {
            $seasonal_group = $category;
            $seasonal_group['name'] = 'Seasonal'; // Shorten name for display
        } elseif (strtolower($category['name']) === 'accessories') {
            $accessories_group = $category;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'MTP Flex Store') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #111827; /* Deep Blue/Black */
            --accent-color: #3b82f6;
            --bg-light: #f4f6f9;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.07);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-primary);
            margin: 0;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        /* --- Header --- */
        .header {
            background-color: var(--card-bg);
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .logo { font-size: 1.5rem; font-weight: 700; color: var(--primary-color); text-decoration: none; }
        .header-nav { display: flex; gap: 2rem; }
        .header-nav a { color: var(--text-primary); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .header-nav a:hover, .header-nav a.active { color: var(--accent-color); }
        .header-icons { display: flex; align-items: center; gap: 1.5rem; }
        .header-icons a { color: var(--text-primary); text-decoration: none; font-size: 1.2rem; }

        /* --- General Form & UI Styles for User Pages --- */
        .form-container { max-width: 600px; margin: 2rem auto; padding: 2rem; background: var(--card-bg); border-radius: 12px; box-shadow: var(--shadow); }
        .form-container h2 { text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        textarea.form-control { min-height: 100px; }
        .btn-primary { width: 100%; padding: 0.8rem; background-color: var(--primary-color); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; text-align: center; text-decoration: none; display: inline-block; }
        .btn-secondary { background-color: var(--text-secondary); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: bold; border: 1px solid transparent; }
        .alert-success { background-color: #dcfce7; color: #166534; border-color: #a7f3d0; }
        .alert-danger { background-color: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .text-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--accent-color); text-decoration: none; }

        /* Dropdown Menu */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--card-bg);
            min-width: 200px;
            box-shadow: var(--shadow);
            border-radius: 8px;
            z-index: 1000;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .dropdown-content a {
            color: var(--text-primary);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .dropdown-content a:hover { background-color: var(--bg-light); color: var(--accent-color); }
        .dropdown:hover .dropdown-content { display: block; }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 5px;
            font-size: 0.65rem;
            font-weight: bold;
            border: 2px solid var(--card-bg);
        }

        /* Navigation Dropdowns */
        .nav-item { position: relative; display: flex; align-items: center; height: 100%; }
        .nav-item > a { display: flex; align-items: center; gap: 5px; color: var(--text-primary); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .nav-item > a:hover { color: var(--accent-color); }
        
        .nav-dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: var(--card-bg);
            min-width: 220px;
            box-shadow: var(--shadow);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            z-index: 1000;
            padding: 0.5rem 0;
        }
        
        .nav-item:hover > .nav-dropdown-menu { display: block; }
        
        .nav-dropdown-menu a {
            display: block;
            padding: 0.6rem 1.2rem;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .nav-dropdown-menu a:hover { background-color: var(--bg-light); color: var(--accent-color); }

        /* Nested Dropdowns */
        .nav-dropdown-submenu { position: relative; }
        .nav-dropdown-submenu > .nav-dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -5px;
        }
        .nav-dropdown-submenu:hover > .nav-dropdown-menu { display: block; }
        
        /* Arrow indicators */
        .has-children::after {
            content: '\f078'; /* fa-chevron-down */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        .nav-dropdown-menu .has-children::after {
            content: '\f054'; /* fa-chevron-right */
            float: right;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">MTP Flex Store</a>
            <nav class="header-nav">
                <a href="index.php">Home</a>

                <!-- Shop Dropdown -->
                <div class="nav-item">
                    <a href="store.php" class="has-children">Shop</a>
                    <div class="nav-dropdown-menu">
                        <a href="store.php">All Products</a>
                        <?php if (!empty($shop_all_cats)): ?>
                            <?php foreach ($shop_all_cats as $category): ?>
                                <a href="store.php?category=<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Accessories Link -->
                <?php if ($accessories_group): ?>
                    <a href="store.php?category=<?= $accessories_group['id'] ?>"><?= htmlspecialchars($accessories_group['name']) ?></a>
                <?php endif; ?>

                <!-- Apparel Dropdown -->
                <?php if (!empty($apparel_group['children'])): ?>
                    <div class="nav-item">
                        <a href="#" class="has-children"><?= $apparel_group['name'] ?></a>
                        <div class="nav-dropdown-menu"><?php render_nav_dropdown_items($apparel_group['children']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Kid's Dropdown -->
                <?php if ($kids_group): ?>
                    <div class="nav-item">
                        <a href="store.php?category=<?= $kids_group['id'] ?>" class="has-children"><?= htmlspecialchars($kids_group['name']) ?></a>
                        <div class="nav-dropdown-menu"><?php if (!empty($kids_group['children'])) render_nav_dropdown_items($kids_group['children']); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Seasonal Dropdown -->
                <?php if ($seasonal_group): ?>
                    <div class="nav-item">
                        <a href="store.php?category=<?= $seasonal_group['id'] ?>" class="has-children"><?= htmlspecialchars($seasonal_group['name']) ?></a>
                        <div class="nav-dropdown-menu"><?php if (!empty($seasonal_group['children'])) render_nav_dropdown_items($seasonal_group['children']); ?></div>
                    </div>
                <?php endif; ?>
            </nav>
            <div class="header-icons">
                <a href="#"><i class="fas fa-search"></i></a>
                <a href="cart.php"><i class="fas fa-cart-shopping"></i> (<?= $cart_item_count ?>)</a>
                <div class="dropdown">
                    <a href="#" class="dropbtn" style="position: relative;">
                        <i class="fas fa-user-circle"></i>
                        <?php if (isset($_SESSION['user_id']) && $unread_count > 0): ?>
                            <span class="notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-content">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="profile.php">My Profile</a>
                            <a href="profile.php">My Orders</a>
                            <a href="wishlist.php">Wishlist</a>
                            <a href="messages.php">My Messages</a>
                            <a href="logout.php">Sign Out</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                            <a href="register.php">Register</a>
                            <a href="profile.php">Orders</a>
                            <a href="wishlist.php">Wishlist</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
