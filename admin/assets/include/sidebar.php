<div class="sidebar">
    <div class="sidebar-header">
        <h2>Navigation</h2> 
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= is_active('dashboard.php') ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        
        <li class="menu-separator">Inventory</li> 
        <li>
            <a href="products.php" class="<?= is_active('products.php') ?>">
                <i class="fas fa-box"></i> Products
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?= is_active('categories.php') ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
        </li>
        
        <li class="menu-separator">Sales & Users</li> 
        <li>
            <a href="orders.php" class="<?= is_active('orders.php') ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
        </li>
        <li>
            <a href="users.php" class="<?= is_active('users.php') ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        </div>
</div>