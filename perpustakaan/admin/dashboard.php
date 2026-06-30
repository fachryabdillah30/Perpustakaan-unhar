<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$total_anggota   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mahasiswa'")->fetchColumn();
$total_buku      = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$total_transaksi = $pdo->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn();
$total_terlambat = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND batas_kembali < CURDATE()")->fetchColumn();

$terlambat = $pdo->query("
    SELECT u.nama, u.nim, b.judul, p.tanggal_pinjam, p.batas_kembali,
           DATEDIFF(CURDATE(), p.batas_kembali) as hari_terlambat
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b ON p.buku_id = b.id
    WHERE p.status = 'dipinjam' AND p.batas_kembali < CURDATE()
    ORDER BY hari_terlambat DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(20px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .card-anim { animation: fadeInUp 0.5s ease forwards; opacity:0; }
        .card-anim:nth-child(1){animation-delay:.1s}
        .card-anim:nth-child(2){animation-delay:.2s}
        .card-anim:nth-child(3){animation-delay:.3s}
        .card-anim:nth-child(4){animation-delay:.4s}
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .fade-in { animation: fadeIn 0.6s ease 0.5s forwards; opacity:0; }
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:3px}
    </style>
</head>
<body class="bg-slate-100 font-sans">

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen flex flex-col">

    <!-- TOPBAR -->
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between px-4 lg:px-8 py-3">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-slate-800 font-semibold">Dashboard</h2>
                    <p class="text-slate-400 text-xs"><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="hidden sm:block text-sm text-slate-500">
                    Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
                </span>
                <a href="../auth/logout.php"
                   class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-sm px-3 py-1.5 rounded-lg transition">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 lg:p-8">
        <div class="mb-6">
            <h3 class="text-slate-800 text-xl font-bold">Selamat Datang! 👋</h3>
            <p class="text-slate-500 text-sm">Berikut ringkasan data perpustakaan hari ini.</p>
        </div>

        <!-- Kartu Statistik -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <?php
            $stats = [
                ['label'=>'Data Mahasiswa','sub'=>'Total mahasiswa','nilai'=>$total_anggota,  'icon'=>'fa-users',      'warna'=>'blue',   'link'=>'mahasiswa.php'],
                ['label'=>'Data Buku',     'sub'=>'Total koleksi',  'nilai'=>$total_buku,     'icon'=>'fa-book',       'warna'=>'emerald','link'=>'buku.php'],
                ['label'=>'Peminjaman',    'sub'=>'Total transaksi','nilai'=>$total_transaksi,'icon'=>'fa-right-left', 'warna'=>'purple', 'link'=>'peminjaman.php'],
                ['label'=>'Terlambat',     'sub'=>'Perlu perhatian','nilai'=>$total_terlambat,'icon'=>'fa-clock',      'warna'=>'red',    'link'=>'peminjaman.php'],
            ];
            foreach ($stats as $s):
            ?>
            <div class="card-anim bg-white rounded-2xl p-5 shadow-sm border border-slate-100
                        hover:shadow-md hover:-translate-y-1 transition-all duration-200 cursor-pointer"
                 onclick="window.location='<?= $s['link'] ?>'">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-<?= $s['warna'] ?>-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid <?= $s['icon'] ?> text-<?= $s['warna'] ?>-500"></i>
                    </div>
                    <span class="text-xs text-<?= $s['warna'] ?>-500 bg-<?= $s['warna'] ?>-50 px-2 py-0.5 rounded-full font-medium">
                        <?= $s['label'] ?>
                    </span>
                </div>
                <p class="text-3xl font-bold text-slate-800"><?= $s['nilai'] ?></p>
                <p class="text-slate-400 text-xs mt-1"><?= $s['sub'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabel Keterlambatan -->
        <div class="fade-in bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h4 class="text-slate-800 font-semibold">Daftar Keterlambatan</h4>
                    <p class="text-slate-400 text-xs mt-0.5">Anggota yang melewati batas waktu pengembalian</p>
                </div>
                <?php if ($total_terlambat > 0): ?>
                <span class="bg-red-100 text-red-600 text-xs font-semibold px-3 py-1 rounded-full pulse-red">
                    <?= $total_terlambat ?> keterlambatan
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($terlambat)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-circle-check text-green-400 text-2xl"></i>
                </div>
                <p class="text-slate-600 font-medium">Semua buku dikembalikan tepat waktu</p>
                <p class="text-slate-400 text-sm mt-1">Tidak ada keterlambatan saat ini</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-6 py-3">No</th>
                            <th class="text-left px-6 py-3">Nama</th>
                            <th class="text-left px-6 py-3 hidden md:table-cell">NIM</th>
                            <th class="text-left px-6 py-3 hidden lg:table-cell">Judul Buku</th>
                            <th class="text-left px-6 py-3">Batas Kembali</th>
                            <th class="text-left px-6 py-3">Terlambat</th>
                            <th class="text-left px-6 py-3">Denda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($terlambat as $i => $row): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-400"><?= $i+1 ?></td>
                            <td class="px-6 py-4 font-medium text-slate-700"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="px-6 py-4 text-slate-500 hidden md:table-cell"><?= $row['nim'] ?? '-' ?></td>
                            <td class="px-6 py-4 text-slate-600 hidden lg:table-cell"><?= htmlspecialchars($row['judul']) ?></td>
                            <td class="px-6 py-4 text-slate-500"><?= date('d/m/Y', strtotime($row['batas_kembali'])) ?></td>
                            <td class="px-6 py-4">
                                <span class="bg-red-100 text-red-600 text-xs font-semibold px-2.5 py-1 rounded-full">
                                    <?= $row['hari_terlambat'] ?> hari
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-red-600">
                                Rp <?= number_format($row['hari_terlambat'] * 1000, 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>
</body>
</html>