<?php
// This header is for the PUBLIC user-facing store.
// It assumes a session has been started and cart count calculated by the calling page.

$cart_item_count = $cart_item_count ?? 0; // Null coalesce for safety

// Fetch categories for navigation
$nav_categories = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY name ASC");
        $all_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cats_map = [];
        foreach ($all_cats as $cat) {
            $cat['children'] = [];
            $cats_map[$cat['id']] = $cat;
        }
        
        foreach ($cats_map as $id => &$cat) {
            if ($cat['parent_id'] && isset($cats_map[$cat['parent_id']])) {
                $cats_map[$cat['parent_id']]['children'][] = &$cat;
            } elseif (!$cat['parent_id']) {
                $nav_categories[] = &$cat;
            }
        }
    } catch (PDOException $e) { /* Ignore errors, menu will be empty */ }
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
        .header-nav { display: flex; gap: 2rem; align-items: center; }
        .nav-item { position: relative; }
        .nav-link { color: var(--text-primary); text-decoration: none; font-weight: 500; transition: color 0.2s; display: block; padding: 0.5rem 0; }
        .nav-link:hover, .nav-link.active { color: var(--accent-color); }
        
        /* Dropdown */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: var(--card-bg);
            min-width: 200px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem 0;
            z-index: 1000;
        }
        .nav-item:hover .dropdown-menu { display: block; }
        .dropdown-item { display: block; padding: 0.5rem 1rem; color: var(--text-primary); text-decoration: none; font-size: 0.9rem; }
        .dropdown-item:hover { background-color: var(--bg-light); color: var(--accent-color); }
        
        .header-icons { display: flex; align-items: center; gap: 1.5rem; }
        .header-icons a { color: var(--text-primary); text-decoration: none; font-size: 1.2rem; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">MTP Flex Store</a>
            <nav class="header-nav">
                <div class="nav-item"><a href="index.php" class="nav-link">Home</a></div>
                <div class="nav-item"><a href="store.php" class="nav-link">Shop All</a></div>
                <?php foreach ($nav_categories as $cat): ?>
                    <div class="nav-item">
                        <a href="store.php?category=<?= $cat['id'] ?>" class="nav-link"><?= htmlspecialchars($cat['name']) ?></a>
                        <?php if (!empty($cat['children'])): ?>
                            <div class="dropdown-menu">
                                <?php foreach ($cat['children'] as $child): ?>
                                    <a href="store.php?category=<?= $child['id'] ?>" class="dropdown-item"><?= htmlspecialchars($child['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
            <div class="header-icons">
                <a href="../store.php"><i class="fas fa-search"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> (<?= $cart_item_count ?>)</a>
                <?php // Differentiate between Guest, Customer, and Admin
                if (isset($_SESSION['user_id'])):
                    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                        <a href="admin/dashboard.php" title="Admin Panel"><i class="fas fa-user-shield"></i></a>
                    <?php else: ?>
                    <a href="profile.php" title="My Profile"><i class="fas fa-user-circle"></i></a>
                    <?php endif; ?>
                    <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" title="Login / Register"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
