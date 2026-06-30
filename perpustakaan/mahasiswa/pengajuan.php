<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekMahasiswa();
require_once __DIR__ . '/../config/db.php';

$pengajuans = $pdo->prepare("
    SELECT pp.*, b.judul, b.penulis, b.foto as foto_buku
    FROM pengajuan_peminjaman pp
    JOIN buku b ON pp.buku_id=b.id
    WHERE pp.user_id=? ORDER BY pp.created_at DESC");
$pengajuans->execute([$_SESSION['user_id']]);
$data = $pengajuans->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Saya — Perpustakaan UHM</title>
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
        <p class="text-white font-bold text-sm">Pengajuan Saya</p>
        <a href="../auth/logout.php" class="text-red-300 hover:text-red-200 text-sm transition">
            <i class="fa-solid fa-right-from-bracket mr-1"></i>Logout
        </a>
    </div>
</nav>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Pengajuan Saya</h1>
        <p class="text-slate-400 text-sm mt-1">Status pengajuan peminjaman buku Anda</p>
    </div>

    <?php if (empty($data)): ?>
    <div class="text-center py-16 bg-white rounded-2xl border border-slate-100">
        <i class="fa-solid fa-inbox text-slate-300 text-5xl mb-4"></i>
        <p class="text-slate-500 font-medium">Belum ada pengajuan</p>
        <a href="../beranda.php#koleksi"
           class="inline-block mt-4 px-5 py-2.5 rounded-xl text-white text-sm font-medium transition"
           style="background:linear-gradient(135deg,#006e82,#008fa3)">
            Jelajahi Koleksi Buku
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($data as $pj):
            $ada_foto = $pj['foto_buku'] && file_exists(__DIR__.'/../admin/uploads/foto_buku/'.$pj['foto_buku']);
            $alasan   = $pj['alasan_tolak'] ?? null;
        ?>
        <div class="flex items-center gap-4 bg-white rounded-2xl p-4 shadow-sm border border-slate-100 hover:shadow-md transition">
            <?php if ($ada_foto): ?>
            <img src="../admin/uploads/foto_buku/<?= $pj['foto_buku'] ?>"
                 class="w-12 h-16 object-cover rounded-xl border border-slate-200 flex-shrink-0">
            <?php else: ?>
            <div class="w-12 h-16 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#e0f2f8">
                <i class="fa-solid fa-book text-sm" style="color:#006e82"></i>
            </div>
            <?php endif; ?>

            <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-700 text-sm truncate"><?= htmlspecialchars($pj['judul']) ?></p>
                <p class="text-slate-400 text-xs"><?= htmlspecialchars($pj['penulis']) ?></p>
                <p class="text-slate-300 text-xs mt-0.5">
                    Diajukan: <?= date('d M Y H:i', strtotime($pj['created_at'])) ?>
                </p>
                <?php if ($alasan): ?>
                <p class="text-red-400 text-xs mt-0.5 italic">
                    Alasan penolakan: <?= htmlspecialchars($alasan) ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="flex-shrink-0">
                <?php if ($pj['status']==='menunggu'): ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#fef3c7;color:#92400e">
                    <i class="fa-solid fa-clock mr-1"></i>Menunggu
                </span>
                <?php elseif ($pj['status']==='disetujui'): ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#d1fae5;color:#065f46">
                    <i class="fa-solid fa-check mr-1"></i>Disetujui
                </span>
                <?php else: ?>
                <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#fee2e2;color:#991b1b">
                    <i class="fa-solid fa-xmark mr-1"></i>Ditolak
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