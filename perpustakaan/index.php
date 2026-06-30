<?php
session_start();
// Kalau sudah login, arahkan ke tempat yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: beranda.php');
    }
    exit;
}
// Belum login → redirect ke beranda (yang punya form login sendiri)
header('Location: beranda.php');
exit;