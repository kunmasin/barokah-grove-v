<?php
require_once 'config.php';
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shop | <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'includes/header.php'; ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">OUR STORE</span>
                <h2>All Kitchen and Houshold Items</h2>
            </div>
        </div>
        <div class="product-grid">
<?php foreach($products as $p): ?>
<article class="product-card">
<?php if($p['image']): ?>
    <img src="<?= e(image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>">
    <?php else: ?>
        <div class="product-placeholder">GADGET</div><?php endif; ?>
    <div class="product-body">
    <h3><?= e($p['name']) ?></h3>
    <p><?= e($p['description']) ?></p>
    <div class="product-bottom">
        <strong><?= money($p['price']) ?></strong>
<?php if($p['stock']>0): ?>
    <form action="cart.php" method="post">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn-small">Add to cart</button>
    </form>
<?php else: ?>
    <span>Out of stock</span>
<?php endif; ?>
</div></div></article>
<?php endforeach; ?>
</div></div></section>
<?php include 'includes/footer.php'; ?><script src="assets/js/app.js"></script></body></html>