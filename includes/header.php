<header class="site-header">
<div class="container nav">
    <a class="brand" href="<?= is_admin() ? '../admin/index.php' : 'index.php' ?>">
        <!--<span class="brand-mark">BG</span> -->
        <span style="color: #ffd500;">BARAKAH GROVE VENTURES</span>
    </a>
    <nav>
        <?php if (is_admin()): ?>
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/index.php">Dashboard</a>
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/products.php">Products</a>
            <!--<a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/orders.php">Orders</a>-->
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/invoice.php">Invoices</a>
            <!--<a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/users.php">Users</a>-->
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/admin/transaction_history.php">Transactions</a>
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/logout.php">Logout</a>
        <?php else: ?>
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/index.php">Home</a>
            <a style="color: #ffd500;" href="<?= BASE_URL ?>/shop.php">Shop</a>
            <?php if (is_logged_in()): ?>
                <a style="color: #ffd500;" href="<?= BASE_URL ?>/my_orders.php">My Orders</a>
                <a style="color: #ffd500;" href="<?= BASE_URL ?>/logout.php">Logout</a>
            <?php else: ?>
                <a style="color: #ffd500;" href="<?= BASE_URL ?>/login.php">Login</a>
                <a class="nav-cta" style="color: #ffd500;" href="<?= BASE_URL ?>/register.php">Register</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</div>
</header>