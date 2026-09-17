<?php
require_once '../config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;

$product = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => 0,
    'image' => null
];

$error = '';

if ($editing) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        redirect('products.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = max(0, (int)($_POST['stock'] ?? 0));
    $imageName = $product['image'];

    if ($name === '') {
        $error = 'Product name is required.';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative.';
    }

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed. PHP upload error: ' . $_FILES['image']['error'];
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $error = 'Image must be 5MB or smaller.';
        } else {

            $tmp = $_FILES['image']['tmp_name'];

            if (!is_uploaded_file($tmp)) {
                $error = 'The uploaded file was not received correctly.';
            } else {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                ];

                if (!isset($allowed[$mime])) {
                    $error = 'Invalid image type. Use JPG, PNG, WEBP or GIF.';
                } else {

                    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' .
                                 DIRECTORY_SEPARATOR . 'uploads';

                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $error = 'Could not create the uploads folder.';
                        }
                    }

                    if (!$error && !is_writable($uploadDir)) {
                        $error = 'The uploads folder is not writable.';
                    }

                    if (!$error) {

                        $extension = $allowed[$mime];

                        $imageName =
                            'product_' .
                            date('YmdHis') . '_' .
                            bin2hex(random_bytes(8)) .
                            '.' . $extension;

                        $destination =
                            $uploadDir .
                            DIRECTORY_SEPARATOR .
                            $imageName;

                        if (!move_uploaded_file($tmp, $destination)) {
                            $error = 'PHP could not move the uploaded image to the uploads folder.';
                            $imageName = $product['image'];
                        } else {

                            // Remove old image after the new image has been saved.
                            if ($editing && !empty($product['image'])) {
                                $old = $uploadDir .
                                       DIRECTORY_SEPARATOR .
                                       basename($product['image']);

                                if (is_file($old)) {
                                    @unlink($old);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if (!$error) {

        if ($editing) {

            $stmt = $db->prepare(
                "UPDATE products
                 SET name=?, description=?, price=?, stock=?, image=?
                 WHERE id=?"
            );

            $stmt->bind_param(
                "ssdisi",
                $name,
                $description,
                $price,
                $stock,
                $imageName,
                $id
            );

            $stmt->execute();

            flash('success', 'Product updated successfully.');

        } else {

            $stmt = $db->prepare(
                "INSERT INTO products
                 (name, description, price, stock, image)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssdis",
                $name,
                $description,
                $price,
                $stock,
                $imageName
            );

            $stmt->execute();

            flash('success', 'Product added successfully.');
        }

        redirect('products.php');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $editing ? 'Edit' : 'Add' ?> Product | <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="form-page">
<div class="form-card">

<h1><?= $editing ? 'Edit' : 'Add' ?> Product</h1>

<?php if ($error): ?>
<div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="form-group">
<label>Product Name</label>
<input class="form-control" type="text" name="name"
       value="<?= e($product['name']) ?>" required>
</div>

<div class="form-group">
<label>Description</label>
<textarea class="form-control" name="description" rows="4"><?= e($product['description']) ?></textarea>
</div>

<div class="form-group">
<label>Price (₦)</label>
<input class="form-control" type="number" step="0.01"
       min="0" name="price"
       value="<?= e((string)$product['price']) ?>" required>
</div>

<div class="form-group">
<label>Stock Quantity</label>
<input class="form-control" type="number" min="0"
       name="stock" value="<?= (int)$product['stock'] ?>" required>
</div>

<div class="form-group">
<label>Product Image</label>
<input class="form-control"
       type="file"
       name="image"
       accept="image/jpeg,image/png,image/webp,image/gif">
<small>Maximum size: 5MB</small>
</div>

<?php if (!empty($product['image'])): ?>
<div style="margin:20px 0">
    <p><strong>Current image</strong></p>
    <img
        src="../<?= e(image_url($product['image'])) ?>"
        alt="<?= e($product['name']) ?>"
        style="width:180px;height:150px;object-fit:cover;border-radius:10px;border:1px solid #ddd"
    >
</div>
<?php endif; ?>

<button class="btn btn-primary" type="submit">
<?= $editing ? 'Update Product' : 'Add Product' ?>
</button>

<a class="btn btn-outline" href="products.php">Cancel</a>

</form>

</div>
</section>

<?php include '../includes/footer.php'; ?>

</body>
</html>
