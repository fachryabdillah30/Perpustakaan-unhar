<?php
session_start();
require_once __DIR__ . '/config/db.php';

$sudah_login  = isset($_SESSION['user_id']);
$is_mahasiswa = $sudah_login && $_SESSION['role'] === 'mahasiswa';
$is_admin     = $sudah_login && $_SESSION['role'] === 'admin';

if ($is_admin) { header('Location: admin/dashboard.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: beranda.php'); exit; }

$stmt = $pdo->prepare("SELECT b.*, k.nama_kategori FROM buku b
                        LEFT JOIN kategori k ON b.kategori_id=k.id WHERE b.id=?");
$stmt->execute([$id]);
$buku = $stmt->fetch();
if (!$buku) { header('Location: beranda.php'); exit; }

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

// Rekomendasi
$rek = $pdo->prepare("SELECT b.*, k.nama_kategori FROM buku b
                       LEFT JOIN kategori k ON b.kategori_id=k.id
                       WHERE b.kategori_id=? AND b.id!=? ORDER BY RAND() LIMIT 6");
$rek->execute([$buku['kategori_id'], $id]);
$rekomendasi = $rek->fetchAll();

if (count($rekomendasi) < 6) {
    $ids = array_merge(array_column($rekomendasi,'id'), [$id]);
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $r2  = $pdo->prepare("SELECT b.*, k.nama_kategori FROM buku b
                           LEFT JOIN kategori k ON b.kategori_id=k.id
                           WHERE b.id NOT IN ($ph) ORDER BY RAND() LIMIT ".(6-count($rekomendasi)));
    $r2->execute($ids);
    $rekomendasi = array_merge($rekomendasi, $r2->fetchAll());
}

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
$ada_foto = $buku['foto'] && file_exists(__DIR__.'/admin/uploads/foto_buku/'.$buku['foto']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($buku['judul']) ?> — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        .badge-kat{background:linear-gradient(135deg,#e8f8e0,#d4f0b8);color:#3d7010;border:1px solid #a8d870}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .5s ease both}
        .book-card{transition:all .3s cubic-bezier(.4,0,.2,1)}
        .book-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 20px 40px rgba(0,110,130,.15)}
        .hover-ov{opacity:0;transition:opacity .3s}
        .book-card:hover .hover-ov{opacity:1}
        .info-row{display:flex;border-bottom:1px solid #f1f5f9;padding:14px 0}
        .info-row:last-child{border-bottom:none}
        .info-label{color:#94a3b8;font-size:13px;width:150px;flex-shrink:0;padding-top:1px}
        .info-value{color:#1e293b;font-size:13px;font-weight:500;flex:1}
        .popup-notif{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;min-width:320px;max-width:90vw}
        @keyframes slideDown{from{opacity:0;transform:translate(-50%,-20px)}to{opacity:1;transform:translate(-50%,0)}}
        .slide-down{animation:slideDown .4s ease both}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#006e82;border-radius:3px}
    </style>
</head>
<body class="bg-slate-50 font-sans">

<!-- Popup -->
<?php if ($pesan): ?>
<div id="pp" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-green-200 shadow-xl rounded-2xl px-5 py-4">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#d1fae5">
            <i class="fa-solid fa-circle-check text-green-500"></i>
        </div>
        <p class="text-slate-700 text-sm font-medium flex-1"><?= htmlspecialchars($pesan) ?></p>
        <button onclick="document.getElementById('pp').remove()" class="text-slate-300 hover:text-slate-500 ml-2">
            <i class="fa-solid fa-xmark"></i></button>
    </div>
</div>
<script>setTimeout(()=>document.getElementById('pp')?.remove(),5000)</script>
<?php endif; ?>

<?php if ($error): ?>
<div id="pe" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-red-200 shadow-xl rounded-2xl px-5 py-4">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#fee2e2">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        </div>
        <p class="text-slate-700 text-sm font-medium flex-1"><?= htmlspecialchars($error) ?></p>
        <button onclick="document.getElementById('pe').remove()" class="text-slate-300 hover:text-slate-500 ml-2">
            <i class="fa-solid fa-xmark"></i></button>
    </div>
</div>
<script>setTimeout(()=>document.getElementById('pe')?.remove(),5000)</script>
<?php endif; ?>

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
                <a href="koleksi.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Koleksi Buku</a>
                <a href="tentang.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Tentang</a>
                <a href="kontak.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Kontak</a>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($is_mahasiswa): ?>
                <div class="relative" id="umw">
                    <button onclick="document.getElementById('umd').classList.toggle('hidden')"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white border-opacity-20
                                   text-white hover:bg-white hover:bg-opacity-10 transition text-sm">
                        <?php if ($ada_foto_nav): ?>
                            <img src="admin/uploads/foto_profil/<?= $foto_nav ?>"
                                 class="w-6 h-6 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                 style="background:#6ab023">
                                <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:inline max-w-28 truncate"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>
                    <div id="umd" class="hidden absolute right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 w-56 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($_SESSION['nama']) ?></p>
                            <p class="text-slate-400 text-xs mt-0.5">Mahasiswa</p>
                        </div>
                        <a href="mahasiswa/riwayat.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400 w-4 text-center"></i>Riwayat Pinjam</a>
                        <a href="mahasiswa/pengajuan.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-inbox text-slate-400 w-4 text-center"></i>Pengajuan Saya</a>
                        <a href="profil_mahasiswa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-circle-user text-slate-400 w-4 text-center"></i>Pengaturan Profil</a>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <a href="auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Logout</a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <a href="beranda.php"
                   class="flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-xl border border-white border-opacity-30 text-white hover:bg-white hover:text-teal-800 transition-all">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span class="hidden sm:inline">Login</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="pt-16" style="background:#004d5a">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-4">
        <nav class="flex items-center gap-2 text-xs flex-wrap" style="color:rgba(255,255,255,0.6)">
            <a href="beranda.php" class="hover:text-white transition">Beranda</a>
            <i class="fa-solid fa-chevron-right text-xs" style="color:rgba(255,255,255,0.3)"></i>
            <a href="koleksi.php" class="hover:text-white transition">Koleksi Buku</a>
            <i class="fa-solid fa-chevron-right text-xs" style="color:rgba(255,255,255,0.3)"></i>
            <span class="text-white truncate max-w-xs"><?= htmlspecialchars($buku['judul']) ?></span>
        </nav>
    </div>
</div>

<!-- KONTEN UTAMA -->
<main class="max-w-7xl mx-auto px-4 lg:px-8 py-10">
    <div class="fade-up grid lg:grid-cols-3 gap-8 mb-12">

        <!-- Kolom Kiri: Cover -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-4">
                <!-- Cover -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-slate-100">
                    <div class="relative overflow-hidden cursor-zoom-in" style="height:400px"
                         onclick="document.getElementById('zoomModal').classList.remove('hidden')">
                        <?php if ($ada_foto): ?>
                            <img src="admin/uploads/foto_buku/<?= $buku['foto'] ?>"
                                 class="w-full h-full object-contain p-4 hover:scale-105 transition-transform duration-500">
                            <div class="absolute bottom-3 right-3 bg-black bg-opacity-40 text-white
                                        text-xs px-2 py-1 rounded-lg">
                                <i class="fa-solid fa-magnifying-glass-plus mr-1"></i>Zoom
                            </div>
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center gap-4 p-8"
                                 style="background:linear-gradient(135deg,#e8f4f8,#d0eaf0)">
                                <i class="fa-solid fa-book text-7xl" style="color:#006e82;opacity:0.25"></i>
                                <p class="text-slate-400 text-sm text-center">
                                    <?= htmlspecialchars($buku['judul']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Stok -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0"
                              style="background:<?= $buku['stok']>0?'#6ab023':'#ef4444' ?>"></span>
                        <span class="font-semibold text-slate-700 text-sm">
                            <?= $buku['stok']>0?'Tersedia di Perpustakaan':'Stok Habis' ?>
                        </span>
                    </div>
                    <p class="text-slate-400 text-xs pl-6"><?= $buku['stok'] ?> eksemplar</p>
                </div>

                <!-- Tombol Pinjam / Login -->
                <?php if ($is_mahasiswa): ?>
                    <?php if ($buku['stok'] > 0): ?>
                    <a href="ajukan_pinjam.php?id=<?= $buku['id'] ?>"
                       class="w-full py-4 rounded-2xl text-white font-semibold text-sm transition
                              hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2"
                       style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Ajukan Peminjaman
                    </a>
                    <?php else: ?>
                    <div class="w-full py-4 rounded-2xl text-white font-semibold text-sm flex items-center
                                justify-center gap-2 opacity-60 cursor-not-allowed"
                         style="background:#94a3b8">
                        <i class="fa-solid fa-ban text-xs"></i>
                        Stok Tidak Tersedia
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <a href="beranda.php"
                   class="w-full py-4 rounded-2xl text-white font-semibold text-sm transition
                          hover:shadow-xl flex items-center justify-center gap-2"
                   style="background:linear-gradient(135deg,#006e82,#008fa3)">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    Login untuk Meminjam
                </a>
                <?php endif; ?>

                <!-- Bagikan -->
                <button onclick="bagikan()"
                        class="w-full py-3 rounded-2xl text-slate-500 font-medium text-sm border border-slate-200
                               hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-share-nodes text-xs"></i>
                    Bagikan Buku Ini
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Info -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Header -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="badge-kat text-xs px-3 py-1 rounded-full font-semibold">
                        <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                    </span>
                    <span class="text-xs px-3 py-1 rounded-full font-semibold"
                          style="background:<?= $buku['stok']>0?'#d1fae5':'#fee2e2' ?>;
                                 color:<?= $buku['stok']>0?'#065f46':'#991b1b' ?>">
                        <i class="fa-solid <?= $buku['stok']>0?'fa-circle-check':'fa-circle-xmark' ?> mr-1 text-xs"></i>
                        <?= $buku['stok']>0?'Tersedia':'Stok Habis' ?>
                    </span>
                </div>
                <h1 class="text-slate-800 font-bold text-3xl leading-tight mb-3">
                    <?= htmlspecialchars($buku['judul']) ?>
                </h1>
                <p class="text-xl mb-6 font-medium" style="color:#006e82">
                    <?= htmlspecialchars($buku['penulis']) ?>
                </p>

                <!-- Tabel Info -->
                <div>
                    <?php
                    $rows = [
                        ['Penerbit',     htmlspecialchars($buku['penerbit'])],
                        ['Tahun Terbit', $buku['tahun_terbit']],
                        ['ISBN',         htmlspecialchars($buku['isbn'] ?? '-')],
                        ['Kategori',     htmlspecialchars($buku['nama_kategori'] ?? '-')],
                        ['Stok',         $buku['stok'] . ' eksemplar'],
                    ];
                    foreach ($rows as $r):
                    ?>
                    <div class="info-row">
                        <span class="info-label"><?= $r[0] ?></span>
                        <span class="info-value <?= $r[0]==='Stok'?($buku['stok']>0?'text-green-600':'text-red-500'):'' ?>">
                            <?= $r[1] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 text-lg mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-align-left text-sm" style="color:#006e82"></i>
                    Tentang Buku Ini
                </h3>
                <?php if (!empty($buku['deskripsi'])): ?>
                    <p class="text-slate-600 leading-relaxed text-sm">
                        <?= nl2br(htmlspecialchars($buku['deskripsi'])) ?>
                    </p>
                <?php else: ?>
                    <p class="text-slate-400 italic text-sm">
                        Deskripsi buku belum tersedia. Admin dapat menambahkan deskripsi melalui halaman manajemen buku.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- REKOMENDASI -->
    <?php if (!empty($rekomendasi)): ?>
    <div class="fade-up">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Buku Serupa</h2>
                <p class="text-slate-400 text-sm mt-1">Rekomendasi buku yang mungkin Anda minati</p>
            </div>
            <a href="koleksi.php" class="text-sm font-medium px-4 py-2 rounded-xl border transition hover:bg-teal-50"
               style="color:#006e82;border-color:#006e82">
                Lihat Semua <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($rekomendasi as $r):
                $af = $r['foto'] && file_exists(__DIR__.'/admin/uploads/foto_buku/'.$r['foto']);
            ?>
            <a href="buku_detail.php?id=<?= $r['id'] ?>"
               class="book-card bg-white rounded-2xl overflow-hidden shadow-sm border border-slate-100 flex flex-col">
                <div class="relative overflow-hidden" style="height:200px">
                    <?php if ($af): ?>
                        <img src="admin/uploads/foto_buku/<?= $r['foto'] ?>"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center"
                             style="background:linear-gradient(135deg,#e8f4f8,#d0eaf0)">
                            <i class="fa-solid fa-book text-3xl" style="color:#006e82;opacity:0.3"></i>
                        </div>
                    <?php endif; ?>
                    <div class="absolute top-2 right-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                              style="background:<?= $r['stok']>0?'#6ab023':'#ef4444' ?>">
                            <?= $r['stok']>0?'Tersedia':'Habis' ?>
                        </span>
                    </div>
                    <div class="hover-ov absolute inset-0 flex items-center justify-center"
                         style="background:rgba(0,78,90,0.78)">
                        <div class="text-white text-center">
                            <i class="fa-solid fa-eye text-xl mb-1"></i>
                            <p class="text-xs font-semibold">Lihat Buku</p>
                        </div>
                    </div>
                </div>
                <div class="p-3 flex-1 flex flex-col">
                    <span class="badge-kat text-xs px-2 py-0.5 rounded-full font-medium mb-1.5 w-fit">
                        <?= htmlspecialchars($r['nama_kategori'] ?? '-') ?>
                    </span>
                    <h3 class="text-slate-800 font-semibold text-xs leading-tight line-clamp-2 flex-1">
                        <?= htmlspecialchars($r['judul']) ?>
                    </h3>
                    <p class="text-slate-400 text-xs mt-1 truncate"><?= htmlspecialchars($r['penulis']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<footer style="background:#003d49" class="mt-16">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
        <p class="text-xs" style="color:rgba(255,255,255,0.4)">
            &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
        </p>
    </div>
</footer>

<!-- Zoom Modal -->
<div id="zoomModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.85);backdrop-filter:blur(6px)"
     onclick="document.getElementById('zoomModal').classList.add('hidden')">
    <?php if ($ada_foto): ?>
    <img src="admin/uploads/foto_buku/<?= $buku['foto'] ?>"
         class="max-h-screen max-w-4xl object-contain rounded-2xl shadow-2xl">
    <?php endif; ?>
</div>

<script>
document.addEventListener('click', e => {
    if (!e.target.closest('#umw')) document.getElementById('umd')?.classList.add('hidden');
});
function bagikan() {
    if (navigator.share) {
        navigator.share({title:'<?= addslashes($buku['judul']) ?>',url:window.location.href});
    } else {
        navigator.clipboard?.writeText(window.location.href);
        alert('Link disalin!');
    }
}
document.addEventListener('keydown', e => {
    if (e.key==='Escape') document.getElementById('zoomModal').classList.add('hidden');
});
</script>
</body>
</html>