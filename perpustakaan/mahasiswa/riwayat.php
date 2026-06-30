<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekMahasiswa();
require_once __DIR__ . '/../config/db.php';

$riwayat = $pdo->prepare("
    SELECT p.*, b.judul, b.penulis, b.foto as foto_buku,
           CASE WHEN p.status='dipinjam' AND p.batas_kembali < CURDATE()
                THEN DATEDIFF(CURDATE(), p.batas_kembali) ELSE 0 END as hari_terlambat
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id
    WHERE p.user_id=? ORDER BY p.id DESC");
$riwayat->execute([$_SESSION['user_id']]);
$data = $riwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pinjam — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-slate-100 font-sans min-h-screen">
<nav style="background:#004d5a" class="shadow-lg sticky top-0 z-10">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="../beranda.php" class="flex items-center gap-2 text-white hover:opacity-80 transition">
            <i class="fa-solid fa-arrow-left text-sm"></i>
            <span class="text-sm font-medium">Kembali ke Beranda</span>
        </a>
        <p class="text-white font-bold text-sm">Riwayat Peminjaman</p>
        <a href="../auth/logout.php" class="text-red-300 hover:text-red-200 text-sm transition">
            <i class="fa-solid fa-right-from-bracket mr-1"></i>Logout
        </a>
    </div>
</nav>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Riwayat Peminjaman</h1>
        <p class="text-slate-400 text-sm mt-1">Semua buku yang pernah dan sedang Anda pinjam</p>
    </div>

    <?php if (empty($data)): ?>
    <div class="text-center py-16 bg-white rounded-2xl border border-slate-100">
        <i class="fa-solid fa-book-open text-slate-300 text-5xl mb-4"></i>
        <p class="text-slate-500 font-medium">Belum ada riwayat peminjaman</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($data as $r):
            $terlambat = $r['hari_terlambat'] > 0 && $r['status'] === 'dipinjam';
            $ada_foto  = $r['foto_buku'] && file_exists(__DIR__.'/../admin/uploads/foto_buku/'.$r['foto_buku']);
        ?>
        <div class="flex items-center gap-4 bg-white rounded-2xl p-4 shadow-sm border
                    <?= $terlambat ? 'border-red-200' : 'border-slate-100' ?>">
            <?php if ($ada_foto): ?>
            <img src="../admin/uploads/foto_buku/<?= $r['foto_buku'] ?>"
                 class="w-12 h-16 object-cover rounded-xl border border-slate-200 flex-shrink-0">
            <?php else: ?>
            <div class="w-12 h-16 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:#e0f2f8">
                <i class="fa-solid fa-book text-sm" style="color:#006e82"></i>
            </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-700 text-sm truncate"><?= htmlspecialchars($r['judul']) ?></p>
                <p class="text-slate-400 text-xs"><?= htmlspecialchars($r['penulis']) ?></p>
                <div class="flex flex-wrap gap-x-3 mt-1 text-xs text-slate-400">
                    <span>Pinjam: <?= date('d/m/Y', strtotime($r['tanggal_pinjam'])) ?></span>
                    <span class="<?= $terlambat ? 'text-red-500 font-semibold' : '' ?>">
                        Batas: <?= date('d/m/Y', strtotime($r['batas_kembali'])) ?>
                    </span>
                    <?php if ($terlambat): ?>
                    <span class="text-red-500 font-semibold">
                        Terlambat <?= $r['hari_terlambat'] ?> hari —
                        Denda Rp <?= number_format($r['hari_terlambat']*1000,0,',','.') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-shrink-0">
                <?php if ($r['status']==='dipinjam' && !$terlambat): ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#dbeafe;color:#1e40af">
                    <i class="fa-solid fa-book-open mr-1"></i>Dipinjam
                </span>
                <?php elseif ($terlambat): ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#fee2e2;color:#991b1b">
                    <i class="fa-solid fa-clock mr-1"></i>Terlambat
                </span>
                <?php else: ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#d1fae5;color:#065f46">
                    <i class="fa-solid fa-check mr-1"></i>Kembali
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>