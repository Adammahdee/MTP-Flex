<?php
// This header is for the PUBLIC user-facing store.
// It assumes a session has been started and cart count calculated by the calling page.

$cart_item_count = $cart_item_count ?? 0; // Null coalesce for safety
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

    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">MTP Flex Store</a>
            <nav class="header-nav">
                <a href="index.php">Home</a>
                <a href="store.php">Shop</a>
                <a href="store.php?category=1">Men</a>
                <a href="store.php?category=2">Women</a>
                <a href="store.php?category=3">Kids</a>
                <a href="store.php?category=4">Accessories</a>
            </nav>
            <div class="header-icons">
                <a href="#"><i class="fas fa-search"></i></a>
                <a href="cart.php"><i class="fas fa-shopping-cart"></i> (<?= $cart_item_count ?>)</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">My Account</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
