<?php
require_once '../config.php'; require_admin();
if(isset($_GET['status'],$_GET['id'])){
    $id=(int)$_GET['id'];$status=$_GET['status'];
    $allowed=['pending','paid','completed','cancelled'];
    if(in_array($status,$allowed,true)){ $stmt=$db->prepare("UPDATE orders SET status=? WHERE id=?");$stmt->bind_param("si",$status,$id);$stmt->execute(); }
    redirect('orders.php');
}
$orders=$db->query("SELECT o.*,u.full_name,u.email FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Orders | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <section class="admin-wrap">
        <div class="container">
            <h2>Orders & Invoices</h2>
            <div class="table-wrap">
                <table class="admin-table">
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($orders as $o): ?>
                        <tr>
                            <td><?= e($o['invoice_no']) ?></td>
                            <td><?= e($o['full_name']) ?><br><small><?= e($o['email']) ?></small></td>
                            <td><?= money($o['total']) ?></td>
                            <td><?= e(ucfirst($o['status'])) ?></td>
                            <td><?= e($o['created_at']) ?></td>
                            <td class="actions">
                                <a class="btn btn-small" href="<?= BASE_URL ?>/admin/invoice.php?id=<?= (int)$o['id'] ?>">Invoice</a>
                                <a class="btn btn-warning btn-small" href="<?= BASE_URL ?>/admin/orders.php?id=<?= (int)$o['id'] ?>&status=completed">Complete</a>
                                <a class="btn btn-danger btn-small" href="<?= BASE_URL ?>/admin/orders.php?id=<?= (int)$o['id'] ?>&status=cancelled">Cancel</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </section>
    <?php include '../includes/footer.php'; ?>
</body>
</html>