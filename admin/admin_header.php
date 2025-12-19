<?php
// This header is for the ADMIN panel.
// It assumes a session has been started and validated by admin_guard.php

$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin Panel') ?> - MTP Flex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #f8f9fa;
            --sidebar-bg: #212529;
            --sidebar-link: #adb5bd;
            --sidebar-link-hover: #fff;
            --sidebar-link-active: #fff;
        }
        body {
            background-color: var(--admin-bg);
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: var(--sidebar-bg);
            padding: 20px;
            color: white;
        }
        .sidebar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2rem;
            color: white;
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link {
            color: var(--sidebar-link);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background-color 0.2s, color 0.2s;
        }
        .sidebar .nav-link:hover {
            background-color: #343a40;
            color: var(--sidebar-link-hover);
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: var(--sidebar-link-active);
            font-weight: bold;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .page-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .card {
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="../dashboard.php" class="logo">MTP Flex Admin</a>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="../products/"><i class="fas fa-box"></i> Products</a></li>
            <li class="nav-item"><a class="nav-link" href="../products/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="../orders/"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="../users/"><i class="fas fa-users"></i> Users</a></li>
        </ul>
        <div class="mt-auto" style="position: absolute; bottom: 20px; width: calc(100% - 40px);">
            <hr style="color: #6c757d;">
            <a class="nav-link" href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout (<?= htmlspecialchars($admin_name) ?>)</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <main>
