<?php
require_once 'config.php';
if (is_logged_in()) redirect('index.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = 'Enter valid details. Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email); $stmt->execute();
        if ($stmt->get_result()->num_rows) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users(full_name,email,phone,password) VALUES(?,?,?,?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $hash);
            $stmt->execute();
            flash('success', 'Registration successful. You can now log in.');
            redirect('login.php');
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Register | <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'includes/header.php'; ?>
<section class="form-page"><div class="form-card"><h1>Create an account</h1><p>Join Barakah Grove Ventures and start shopping.</p>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<div class="form-group"><label>Full name</label><input class="form-control" name="full_name" required></div>
<div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
<div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
<div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
<div class="form-group"><label>Confirm password</label><input class="form-control" type="password" name="confirm_password" required></div>
<button class="btn btn-primary" type="submit">Create Account</button>
</form><p>Already registered? <a href="login.php" style="color:var(--primary)">Login</a></p></div></section>
<?php include 'includes/footer.php'; ?></body></html>