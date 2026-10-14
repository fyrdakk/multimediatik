<?php
session_start();
require_once '../include/koneksi.php';

if (isset($_SESSION['id'])) {
    header("Location: home.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: index.php?error=kosong");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, email, password, status
    FROM db_login
    WHERE email = ?
    LIMIT 1"
);

if (!$stmt) {
    error_log("Login query gagal: " . mysqli_error($conn));
    header("Location: index.php?error=sistem");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (
    $admin &&
    $admin['status'] === 'aktif' &&
    password_verify($password, $admin['password'])
) {
    session_regenerate_id(true);

    $_SESSION['id'] = $admin['id'];
    $_SESSION['email'] = $admin['email'];

    header("Location: home.php");
    exit;
}

header("Location: index.php?error=login");
exit;
?>
