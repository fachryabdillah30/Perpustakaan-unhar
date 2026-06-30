<?php
// mahasiswa/dashboard.php
require_once __DIR__ . '/../includes/auth_check.php';
cekMahasiswa(); // Hanya mahasiswa yang boleh masuk
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Mahasiswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">

        <div class="bg-white rounded-2xl shadow p-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Mahasiswa</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>!
                    </p>
                </div>
                <a href="../auth/logout.php"
                   class="bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded-lg transition">
                    Logout
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-purple-50 border border-purple-100 rounded-xl p-5">
                    <p class="text-purple-600 text-sm font-medium">Role Anda</p>
                    <p class="text-2xl font-bold text-purple-800 mt-1">Mahasiswa</p>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-xl p-5">
                    <p class="text-green-600 text-sm font-medium">Status</p>
                    <p class="text-2xl font-bold text-green-800 mt-1">Aktif</p>
                </div>
            </div>

            <p class="text-gray-400 text-sm mt-8 text-center">
                Fitur pencarian buku akan ditambahkan di Fase 4 ✨
            </p>
        </div>

    </div>
</body>
</html>