<?php
require_once 'config.php';
$files = [];

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..' || $file === 'index.html') continue;
        if (is_file($dir . DIRECTORY_SEPARATOR . $file)) {
            $files[] = $file;
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Barakah Grove Image Test</title>
<style>
body{font-family:Arial;padding:30px;background:#f5f7f6}
.card{background:white;padding:20px;margin:15px 0;border-radius:10px}
img{width:180px;height:140px;object-fit:cover;border:1px solid #ddd}
.ok{color:green}.bad{color:red}
</style>
</head>
<body>
<h1>Image Upload Diagnostic</h1>

<p>
Uploads directory:
<strong><?= is_dir($dir) ? 'FOUND' : 'NOT FOUND' ?></strong>
</p>

<p>
Directory writable:
<strong><?= is_writable($dir) ? 'YES' : 'NO' ?></strong>
</p>

<p>
Images found:
<strong><?= count($files) ?></strong>
</p>

<?php foreach ($files as $file): ?>
<div class="card">
    <p><?= e($file) ?></p>
    <img src="image.php?file=<?= rawurlencode($file) ?>" alt="">
    <p>
        <a href="image.php?file=<?= rawurlencode($file) ?>" target="_blank">
            Open image directly
        </a>
    </p>
</div>
<?php endforeach; ?>

</body>
</html>
