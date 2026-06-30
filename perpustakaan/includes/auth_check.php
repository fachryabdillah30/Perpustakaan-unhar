<?php
// includes/auth_check.php
// File penjaga pintu — di-include di awal setiap halaman terproteksi

// Kalau session belum dimulai, mulai sekarang
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk cek apakah user sudah login
function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        // Belum login — tendang ke halaman login
        header('Location: ' . getBaseUrl() . 'index.php');
        exit;
    }
}

// Fungsi untuk cek role — hanya admin yang boleh masuk
function cekAdmin() {
    cekLogin(); // Cek login dulu
    if ($_SESSION['role'] !== 'admin') {
        // Sudah login tapi bukan admin — tendang ke dashboard mahasiswa
        header('Location: ' . getBaseUrl() . 'mahasiswa/dashboard.php');
        exit;
    }
}

// Fungsi untuk cek role — hanya mahasiswa yang boleh masuk
function cekMahasiswa() {
    cekLogin(); // Cek login dulu
    if ($_SESSION['role'] !== 'mahasiswa') {
        // Sudah login tapi bukan mahasiswa — tendang ke dashboard admin
        header('Location: ' . getBaseUrl() . 'admin/dashboard.php');
        exit;
    }
}

// Helper untuk mendapatkan base URL proyek secara otomatis
function getBaseUrl() {
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Naik ke root folder perpustakaan/
    $parts = explode('/', trim($base, '/'));
    return '/' . $parts[0] . '/';
}