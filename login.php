<?php
require_once 'config.php';
if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'index.php');
$error = ''; $success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s",$email); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password,$user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']=$user['id']; $_SESSION['full_name']=$user['full_name']; $_SESSION['role']=$user['role'];
        redirect($user['role']==='admin' ? 'admin/index.php' : 'index.php');
    }
    $error='Invalid email or password.';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'includes/header.php'; ?>
<section class="form-page"><div class="form-card"><h1>Welcome back</h1><p>Login to your Barakah Grove account.</p>
<?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
<div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
<button class="btn btn-primary" type="submit">Login</button>
</form><p>New customer? <a href="register.php" style="color:var(--primary)">Create an account</a></p></div></section>
<?php include 'includes/footer.php'; ?></body></html>