<?php
require_once '../config.php'; require_admin();
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];$stmt=$db->prepare("DELETE FROM products WHERE id=?");$stmt->bind_param("i",$id);$stmt->execute();flash('success','Product deleted.');redirect('products.php');
}
$products=$db->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);$success=flash('success');
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Products | <?= e(SITE_NAME) ?></title>
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
        <?php include '../includes/header.php'; ?>
        <section class="admin-wrap">
            <div class="container">
                <div class="section-head">
                    <h2>Products</h2>
                    <a class="btn" href="product_form.php">+ Add Product</a>
                </div>
                <?php if($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if($p['image']): ?>
                                        <img class="thumb" src="../<?= e(image_url($p['image'])) ?>">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= e($p['name']) ?></td>
                                <td><?= money($p['price']) ?></td>
                                <td><?= (int)$p['stock'] ?></td>
                                <td class="actions">
                                    <a class="btn btn-small" href="product_form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                                    <a class="btn btn-danger btn-small" data-confirm="Delete this product?" href="products.php?delete=<?= (int)$p['id'] ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </section>
        <script src="../assets/js/app.js"></script>
        <?php include '../includes/footer.php'; ?>
    </body>
</html>