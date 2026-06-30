<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email dan password wajib diisi.';
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Email atau password salah.';
    header('Location: ../index.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['nama']    = $user['nama'];
$_SESSION['role']    = $user['role'];

if ($user['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../mahasiswa/dashboard.php');
}
exit;