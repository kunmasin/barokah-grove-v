<?php require_once '../config.php'; require_admin();
$products=(int)$db->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$orders=(int)$db->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$customers=(int)$db->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$sales=(float)$db->query("SELECT COALESCE(SUM(total),0) s FROM orders WHERE status IN ('paid','completed')")->fetch_assoc()['s'];
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Admin Dashboard | <?= e(SITE_NAME) ?></title>
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
        <?php include '../includes/header.php'; ?>
        <section class="admin-wrap">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">ADMINISTRATION</span>
                        <h2>Dashboard</h2>
                    </div>
                    <a class="btn" href="<?= BASE_URL ?>/shop.php">View Store</a>
                </div>
                <div class="admin-grid">
                    <div class="stat">
                        <span>Products</span>
                        <strong><?= $products ?></strong>
                    </div>
                    <!-- <div class="stat">
                        <span>Orders</span>
                        <strong><?= $orders ?></strong>
                    </div> -->
                    <!-- <div class="stat">
                        <span>Customers</span> 
                        <strong><?= $customers ?></strong>
                    </div> -->
                    <div class="stat">
                        <span>Total sales</span>
                        <strong><?= money($sales) ?></strong>
                    </div>
                </div>
                <div style="margin-top:25px">
                    <a class="btn" href="products.php">Manage Products</a>
                    <a class="btn btn-outline" href="orders.php">View Orders</a>
                </div>
            </div>
        </section>
        <?php include '../includes/footer.php'; ?>
    </body>
</html>