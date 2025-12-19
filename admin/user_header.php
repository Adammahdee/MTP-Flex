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
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="/MTP Flex/shop/index.php" class="logo">MTP Flex Store</a>
            <nav class="header-nav">
                <a href="/MTP Flex/shop/index.php">Home</a>
                <a href="/MTP Flex/shop/index.php">Shop</a>
            </nav>
            <div class="header-icons">
                <a href="#"><i class="fas fa-search"></i></a>
                <a href="/MTP Flex/shop/cart.php"><i class="fas fa-shopping-cart"></i> (<?= $cart_item_count ?>)</a>
                <?php // Logic for USER accounts only. No admin links should be shown on the public site.
                if (isset($_SESSION['user_id
