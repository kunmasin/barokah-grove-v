<?php
declare(strict_types=1);
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'barakah_grove');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Barakah Grove Ventures');
define('CURRENCY', '₦');

define('BASE_URL', 'http://localhost/barakah_grove_ventures');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (Throwable $e) {
    die('Database connection failed. Check config.php and make sure MySQL is running.');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void {
    if (!is_admin()) {
        redirect('../login.php');
    }
}

function money(float|int|string $amount): string {
    return CURRENCY . number_format((float)$amount, 2);
}

function image_url(?string $filename): string {
    if (!$filename) {
        return '';
    }
    return 'image.php?file=' . rawurlencode(basename($filename));
}

function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}
?>