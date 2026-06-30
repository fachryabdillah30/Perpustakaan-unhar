<?php
session_start();
require_once __DIR__ . '/config/db.php';

$sudah_login  = isset($_SESSION['user_id']);
$is_mahasiswa = $sudah_login && $_SESSION['role'] === 'mahasiswa';
$is_admin     = $sudah_login && $_SESSION['role'] === 'admin';
if ($is_admin) { header('Location: admin/dashboard.php'); exit; }

$cari     = trim($_GET['cari'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);
$sort     = $_GET['sort'] ?? 'terbaru';

$where  = [];
$params = [];
if ($cari) {
    $where[]  = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)";
    $params[] = "%$cari%"; $params[] = "%$cari%"; $params[] = "%$cari%";
}
if ($kategori) { $where[] = "b.kategori_id=?"; $params[] = $kategori; }

$order = match($sort) {
    'az'     => 'b.judul ASC',
    'za'     => 'b.judul DESC',
    'stok'   => 'b.stok DESC',
    default  => 'b.id DESC'
};

$sql = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id";
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= " ORDER BY $order";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bukus = $stmt->fetchAll();
$kategoris = $pdo->query("SELECT k.*, COUNT(b.id) as jml FROM kategori k LEFT JOIN buku b ON k.id=b.kategori_id GROUP BY k.id ORDER BY k.nama_kategori")->fetchAll();

$user_data    = null;
$foto_nav     = null;
$ada_foto_nav = false;
if ($is_mahasiswa) {
    $u = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $u->execute([$_SESSION['user_id']]);
    $user_data    = $u->fetch();
    $foto_nav     = $user_data['foto'] ?? null;
    $ada_foto_nav = $foto_nav && file_exists(__DIR__.'/admin/uploads/foto_profil/'.$foto_nav);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Buku — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        .badge-kat{background:linear-gradient(135deg,#e8f8e0,#d4f0b8);color:#3d7010;border:1px solid #a8d870}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease both}
        .book-card{transition:all .3s cubic-bezier(.4,0,.2,1)}
        .book-card:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 16px 32px rgba(0,110,130,.15)}
        .hover-ov{opacity:0;transition:opacity .3s}
        .book-card:hover .hover-ov{opacity:1}
        .hero-koleksi{background:linear-gradient(135deg,#004d5a,#006e82)}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#006e82;border-radius:3px}
    </style>
</head>
<body class="bg-slate-50 font-sans">

<!-- NAVBAR -->
<nav class="navbar-blur fixed top-0 left-0 right-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="beranda.php" class="flex items-center gap-3">
                <img src="assets/logo.jpg" alt="Logo"
                     class="w-9 h-9 rounded-full object-cover border-2 border-white border-opacity-30">
                <div class="hidden sm:block">
                    <p class="text-white font-bold text-sm leading-tight">Perpustakaan</p>
                    <p class="text-xs leading-tight" style="color:#a8d870">Universitas Harapan Medan</p>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-1">
                <a href="beranda.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Beranda</a>
                <a href="koleksi.php" class="text-white text-sm px-4 py-2 rounded-lg bg-white bg-opacity-15">Koleksi Buku</a>
                <a href="tentang.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Tentang</a>
                <a href="kontak.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Kontak</a>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($is_mahasiswa): ?>
                <div class="relative" id="umw">
                    <button onclick="document.getElementById('umd').classList.toggle('hidden')"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white border-opacity-20 text-white hover:bg-white hover:bg-opacity-10 transition text-sm">
                        <?php if ($ada_foto_nav): ?>
                            <img src="admin/uploads/foto_profil/<?= $foto_nav ?>" class="w-6 h-6 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" style="background:#6ab023">
                                <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:inline max-w-28 truncate"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>
                    <div id="umd" class="hidden absolute right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 w-56 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($_SESSION['nama']) ?></p>
                            <p class="text-slate-400 text-xs">Mahasiswa</p>
                        </div>
                        <a href="mahasiswa/riwayat.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-clock-rotate-left w-4 text-center text-slate-400"></i>Riwayat Pinjam</a>
                        <a href="kontak.php"
   class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
    <i class="fa-solid fa-envelope text-slate-400 w-4 text-center"></i>
    Kirim Pesan
</a>
                        <a href="mahasiswa/pengajuan.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-inbox w-4 text-center text-slate-400"></i>Pengajuan Saya</a>
                        <a href="profil_mahasiswa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-circle-user w-4 text-center text-slate-400"></i>Pengaturan Profil</a>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <a href="auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50"><i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Logout</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <a href="beranda.php" class="flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-xl border border-white border-opacity-30 text-white hover:bg-white hover:text-teal-800 transition-all">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i><span class="hidden sm:inline">Login</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- HERO KOLEKSI -->
<div class="pt-16 hero-koleksi">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
        <h1 class="text-white font-bold text-3xl lg:text-4xl mb-3">Koleksi Buku</h1>
        <p class="mb-6 max-w-xl" style="color:rgba(255,255,255,0.75)">
            Jelajahi <?= $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn() ?> koleksi buku
            dari <?= count($kategoris) ?> kategori ilmu pengetahuan
        </p>
        <!-- Search Bar -->
        <form method="GET" class="flex gap-2 max-w-xl">
            <?php if ($kategori): ?>
            <input type="hidden" name="kategori" value="<?= $kategori ?>">
            <?php endif; ?>
            <div class="flex-1 flex items-center bg-white rounded-2xl px-4 py-3 gap-3 shadow-lg">
                <i class="fa-solid fa-search text-slate-400"></i>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                       placeholder="Cari judul, penulis, penerbit..."
                       class="flex-1 text-slate-700 text-sm bg-transparent outline-none">
                <?php if ($cari): ?>
                <a href="koleksi.php<?= $kategori?'?kategori='.$kategori:'' ?>" class="text-slate-300 hover:text-slate-500">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </div>
            <button type="submit"
                    class="px-6 py-3 rounded-2xl font-semibold text-white text-sm shadow-lg hover:-translate-y-0.5 transition"
                    style="background:linear-gradient(135deg,#6ab023,#4d8a0f)">
                Cari
            </button>
        </form>
    </div>
</div>

<!-- KONTEN -->
<div class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">

        <!-- Sidebar Kategori -->
        <aside class="lg:w-56 flex-shrink-0">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden sticky top-24">
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="font-bold text-slate-700 text-sm">Kategori</p>
                </div>
                <ul>
                    <li>
                        <a href="koleksi.php<?= $cari?'?cari='.$cari:'' ?><?= $sort!='terbaru'?'&sort='.$sort:'' ?>"
                           class="flex items-center justify-between px-4 py-3 text-sm transition hover:bg-slate-50
                                  <?= !$kategori?'font-semibold':'text-slate-500' ?>"
                           style="<?= !$kategori?'color:#006e82':'' ?>">
                            <span>Semua Buku</span>
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:#e0f2f8;color:#006e82">
                                <?= $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn() ?>
                            </span>
                        </a>
                    </li>
                    <?php foreach ($kategoris as $kat): ?>
                    <li>
                        <a href="koleksi.php?kategori=<?= $kat['id'] ?><?= $cari?'&cari='.$cari:'' ?><?= $sort!='terbaru'?'&sort='.$sort:'' ?>"
                           class="flex items-center justify-between px-4 py-3 text-sm transition hover:bg-slate-50
                                  <?= $kategori==$kat['id']?'font-semibold':'text-slate-500' ?>"
                           style="<?= $kategori==$kat['id']?'color:#006e82':'' ?>">
                            <span><?= htmlspecialchars($kat['nama_kategori']) ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  style="background:<?= $kategori==$kat['id']?'#e0f2f8':'#f8fafc' ?>;
                                         color:<?= $kategori==$kat['id']?'#006e82':'#94a3b8' ?>">
                                <?= $kat['jml'] ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- Grid Buku -->
        <div class="flex-1">
            <!-- Header hasil -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <p class="text-slate-800 font-semibold">
                        <?= count($bukus) ?> buku ditemukan
                        <?= $cari ? '— pencarian: "<strong>'.htmlspecialchars($cari).'</strong>"' : '' ?>
                        <?= $kategori ? '— kategori: <strong>'.htmlspecialchars(array_column($kategoris,'nama_kategori','id')[$kategori]??'-').'</strong>' : '' ?>
                    </p>
                </div>
                <!-- Sortir -->
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-sm">Urutkan:</span>
                    <form method="GET" id="sortForm">
                        <?php if ($cari): ?><input type="hidden" name="cari" value="<?= htmlspecialchars($cari) ?>"><?php endif; ?>
                        <?php if ($kategori): ?><input type="hidden" name="kategori" value="<?= $kategori ?>"><?php endif; ?>
                        <select name="sort" onchange="document.getElementById('sortForm').submit()"
                                class="border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-600
                                       focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white">
                            <option value="terbaru" <?= $sort==='terbaru'?'selected':'' ?>>Terbaru</option>
                            <option value="az" <?= $sort==='az'?'selected':'' ?>>A–Z</option>
                            <option value="za" <?= $sort==='za'?'selected':'' ?>>Z–A</option>
                            <option value="stok" <?= $sort==='stok'?'selected':'' ?>>Stok Terbanyak</option>
                        </select>
                    </form>
                </div>
            </div>

            <?php if (empty($bukus)): ?>
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-100">
                <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                     style="background:#e8f8e0">
                    <i class="fa-solid fa-book text-3xl" style="color:#6ab023"></i>
                </div>
                <p class="text-slate-600 font-medium">Buku tidak ditemukan</p>
                <a href="koleksi.php" class="inline-block mt-4 text-sm px-4 py-2 rounded-xl text-white"
                   style="background:#006e82">Reset Filter</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php foreach ($bukus as $b):
                    $af = $b['foto'] && file_exists(__DIR__.'/admin/uploads/foto_buku/'.$b['foto']);
                ?>
                <a href="buku_detail.php?id=<?= $b['id'] ?>"
                   class="fade-up book-card bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 flex flex-col">
                    <div class="relative overflow-hidden" style="height:220px">
                        <?php if ($af): ?>
                            <img src="admin/uploads/foto_buku/<?= $b['foto'] ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center gap-2"
                                 style="background:linear-gradient(135deg,#e8f4f8,#d0eaf0)">
                                <i class="fa-solid fa-book text-4xl" style="color:#006e82;opacity:0.3"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-2 right-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                                  style="background:<?= $b['stok']>0?'#6ab023':'#ef4444' ?>">
                                <?= $b['stok']>0?'Tersedia':'Habis' ?>
                            </span>
                        </div>
                        <div class="hover-ov absolute inset-0 flex items-center justify-center"
                             style="background:rgba(0,78,90,0.78)">
                            <div class="text-white text-center">
                                <i class="fa-solid fa-eye text-2xl mb-1"></i>
                                <p class="text-xs font-semibold">Lihat Buku</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 flex-1 flex flex-col">
                        <span class="badge-kat text-xs px-2 py-0.5 rounded-full font-medium mb-1.5 w-fit">
                            <?= htmlspecialchars($b['nama_kategori'] ?? '-') ?>
                        </span>
                        <h3 class="text-slate-800 font-semibold text-xs leading-tight line-clamp-2 flex-1">
                            <?= htmlspecialchars($b['judul']) ?>
                        </h3>
                        <p class="text-slate-400 text-xs mt-1 truncate"><?= htmlspecialchars($b['penulis']) ?></p>
                        <p class="text-slate-300 text-xs"><?= $b['tahun_terbit'] ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer style="background:#003d49" class="mt-16">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
        <p class="text-xs" style="color:rgba(255,255,255,0.4)">
            &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
        </p>
    </div>
</footer>

<script>
document.addEventListener('click', e => {
    if (!e.target.closest('#umw')) document.getElementById('umd')?.classList.add('hidden');
});
</script>
</body>
</html>