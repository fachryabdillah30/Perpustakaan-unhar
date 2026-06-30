<?php
// auth/logout.php — Menghapus session dan keluar
session_start();

// Hapus semua data session — "gelang" dicabut
session_destroy();

// Kembali ke halaman login
header('Location: ../index.php');
exit;