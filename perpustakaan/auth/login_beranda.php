<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../beranda.php'); exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    $_SESSION['error_form'] = 'Email dan password wajib diisi.';
    header('Location: ../beranda.php'); exit;
}

require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error_form'] = 'Email atau password salah.';
    header('Location: ../beranda.php'); exit;
}

// Admin yang login dari beranda → redirect ke admin
if ($user['role'] === 'admin') {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['foto']    = $user['foto'] ?? null;
    header('Location: ../admin/dashboard.php'); exit;
}

// Mahasiswa → tetap di beranda
$_SESSION['user_id'] = $user['id'];
$_SESSION['nama']    = $user['nama'];
$_SESSION['role']    = $user['role'];
$_SESSION['foto']    = $user['foto'] ?? null;
$_SESSION['pesan']   = 'Selamat datang kembali, ' . $user['nama'] . '!';

header('Location: ../beranda.php'); exit;