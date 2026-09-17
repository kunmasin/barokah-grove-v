<?php
require_once 'config.php';
$products = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="badge">QUALITY KITCHEN AND HOUSEHOLD ITEMS • GREAT VALUE</span>
            <h1>Upgrade your world with the right Kitchen and Household Items.</h1>
            <p>Welcome to Barakah Grove Ventures — your trusted store for quality kitchen and household items at great value.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="shop.php">Shop Now</a>
                <a class="btn btn-outline" href="#products">Explore Products</a>
            </div>
        </div>
        <div class="hero-card">
            <h3>Kitchen and household items made simple.</h3>
            <p>Browse products, add to cart, checkout and get your invoice instantly.</p>
        </div>
    </div>
</section>

<section id="products" class="section">
<div class="container">
    <div class="section-head">
        <div><span class="eyebrow">FEATURED</span><h2>Popular kitchen and household items</h2></div>
        <a href="shop.php">View all →</a>
    </div>
    <div class="product-grid">
    <?php foreach ($products as $product): ?>
        <article class="product-card">
            <?php if ($product['image']): ?>
                <img src="<?= e(image_url($product['image'])) ?>" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <div class="product-placeholder">KITCHEN AND HOUSEHOLD ITEM</div>
            <?php endif; ?>
            <div class="product-body">
                <h3><?= e($product['name']) ?></h3>
                <p><?= e($product['description']) ?></p>
                <div class="product-bottom">
                    <strong><?= money($product['price']) ?></strong>
                    <a class="btn btn-small" href="shop.php">Buy</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</div>
</section>

<section class="section muted">
<div class="container feature-grid">
    <div><span class="feature-number">01</span><h3>Quality products</h3><p>We focus on useful kitchen and household items at competitive prices.</p></div>
    <div><span class="feature-number">02</span><h3>Simple shopping</h3><p>Choose your products and complete your purchase in minutes.</p></div>
    <div><span class="feature-number">03</span><h3>Instant invoice</h3><p>Generate and print a professional invoice after checkout.</p></div>
</div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>