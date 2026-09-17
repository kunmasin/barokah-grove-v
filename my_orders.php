<?php
require_once 'config.php'; require_login();
$stmt=$db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");$stmt->bind_param("i",$_SESSION['user_id']);$stmt->execute();$orders=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>My Orders | <?= e(SITE_NAME) ?></title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <?php include 'includes/header.php'; ?>
        <section class="section">
            <div class="container">
                <h1>My Orders</h1>
                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>Invoice</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                        <?php foreach($orders as $o): ?>
                            <tr>
                                <td><?= e($o['invoice_no']) ?></td>
                                <td><?= money($o['total']) ?></td>
                                <td><?= e(ucfirst($o['status'])) ?></td>
                                <td><?= e($o['created_at']) ?></td>
                                <td><a class="btn btn-small" href="invoice.php?id=<?= (int)$o['id'] ?>">Invoice</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </section>
        <?php include 'includes/footer.php'; ?>
    </body>
</html>