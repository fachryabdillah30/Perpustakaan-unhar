<?php
session_start();
require_once __DIR__ . '/config/db.php';
$sudah_login  = isset($_SESSION['user_id']);
$is_mahasiswa = $sudah_login && $_SESSION['role'] === 'mahasiswa';
$is_admin     = $sudah_login && $_SESSION['role'] === 'admin';
if ($is_admin) { header('Location: admin/dashboard.php'); exit; }

$total_buku     = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$total_anggota  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mahasiswa'")->fetchColumn();
$total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        .hero-gradient{background:linear-gradient(135deg,#004d5a,#006e82,#6ab023)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .5s ease both}
        .fade-up:nth-child(2){animation-delay:.1s}
        .fade-up:nth-child(3){animation-delay:.2s}
        .fade-up:nth-child(4){animation-delay:.3s}
    </style>
</head>
<body class="bg-slate-50 font-sans">

<!-- NAVBAR -->
<nav class="navbar-blur fixed top-0 left-0 right-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="beranda.php" class="flex items-center gap-3">
                <img src="assets/logo.jpg" alt="Logo" class="w-9 h-9 rounded-full object-cover border-2 border-white border-opacity-30">
                <div class="hidden sm:block">
                    <p class="text-white font-bold text-sm leading-tight">Perpustakaan</p>
                    <p class="text-xs leading-tight" style="color:#a8d870">Universitas Harapan Medan</p>
                </div>
            </a>
            <div     class="hidden md:flex items-center gap-1">
                <a href="beranda.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Beranda</a>
                <a href="koleksi.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Koleksi Buku</a>
                <a href="tentang.php" class="text-white text-sm px-4 py-2 rounded-lg bg-white bg-opacity-15">Tentang</a>
                <a href="kontak.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Kontak</a>
            </div>
            <div class="flex items-center gap-2">
               <?php if ($is_mahasiswa): ?>
<div class="relative" id="umw">
    <button onclick="document.getElementById('umd').classList.toggle('hidden')"
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white
                   border-opacity-20 text-white hover:bg-white hover:bg-opacity-10 transition text-sm">
        <?php
        $foto_nav     = $_SESSION['foto'] ?? null;
        $ada_foto_nav = $foto_nav && file_exists(__DIR__.'/admin/uploads/foto_profil/'.$foto_nav);
        if ($ada_foto_nav): ?>
            <img src="admin/uploads/foto_profil/<?= $foto_nav ?>"
                 class="w-6 h-6 rounded-full object-cover border border-white border-opacity-30">
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
            <p class="text-slate-400 text-xs">Mahasiswa</p>
        </div>
        <a href="mahasiswa/riwayat.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
            <i class="fa-solid fa-clock-rotate-left text-slate-400 w-4 text-center"></i>Riwayat Pinjam</a>
        <a href="kontak.php"
   class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
    <i class="fa-solid fa-envelope text-slate-400 w-4 text-center"></i>
    Kirim Pesan
</a>
        <a href="mahasiswa/pengajuan.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
            <i class="fa-solid fa-inbox text-slate-400 w-4 text-center"></i>Pengajuan Saya</a>
        <a href="profil_mahasiswa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
            <i class="fa-solid fa-circle-user text-slate-400 w-4 text-center"></i>Pengaturan Profil</a>
        <div class="border-t border-slate-100 mt-1 pt-1">
            <a href="auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition">
                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Logout</a>
        </div>
    </div>
</div>
<script>
document.addEventListener('click', e => {
    if (!e.target.closest('#umw')) document.getElementById('umd')?.classList.add('hidden');
});
</script>
<?php else: ?>
<a href="beranda.php" class="flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-xl border border-white border-opacity-30 text-white hover:bg-white hover:text-teal-800 transition-all">
    <i class="fa-solid fa-right-to-bracket text-xs"></i><span class="hidden sm:inline">Login</span>
</a>
<?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="pt-16 hero-gradient">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-16 text-center">
        <div class="fade-up inline-flex items-center gap-2 bg-white bg-opacity-15 border border-white border-opacity-20
                    text-white text-xs font-medium px-3 py-1.5 rounded-full mb-6">
            <i class="fa-solid fa-building-columns"></i> Tentang Kami
        </div>
        <h1 class="fade-up text-white font-bold text-4xl lg:text-5xl mb-4">
            Perpustakaan Universitas<br>
            <span style="color:#a8d870">Harapan Medan</span>
        </h1>
        <p class="fade-up text-lg max-w-2xl mx-auto" style="color:rgba(255,255,255,0.8)">
            Pusat sumber daya ilmu pengetahuan yang mendukung keunggulan akademik
            civitas akademika Universitas Harapan Medan
        </p>
    </div>
</div>

<!-- STATISTIK -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-3 gap-6 max-w-3xl mx-auto text-center">
            <?php
            $stats = [
                [$total_buku,    'Koleksi Buku',  'fa-book',  '#006e82'],
                [$total_anggota, 'Anggota Aktif', 'fa-users', '#6ab023'],
                [$total_kategori,'Kategori',      'fa-tags',  '#e07b1a'],
            ];
            foreach ($stats as $s):
            ?>
            <div class="fade-up">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3"
                     style="background:<?= $s[3] ?>15">
                    <i class="fa-solid <?= $s[2] ?> text-xl" style="color:<?= $s[3] ?>"></i>
                </div>
                <p class="text-4xl font-bold text-slate-800 mb-1"><?= $s[0] ?>+</p>
                <p class="text-slate-400 text-sm"><?= $s[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- VISI MISI -->
<section class="py-16" style="background:#f0fafa">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <div class="fade-up bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5"
                     style="background:#e0f2f8">
                    <i class="fa-solid fa-eye text-xl" style="color:#006e82"></i>
                </div>
                <h2 class="text-slate-800 font-bold text-2xl mb-4">Visi</h2>
                <p class="text-slate-500 leading-relaxed">
                    Menjadi perpustakaan digital terdepan yang mendukung keunggulan akademik dan
                    penelitian di Universitas Harapan Medan, serta menjadi pusat informasi
                    terpercaya bagi seluruh civitas akademika.
                </p>
            </div>
            <div class="fade-up bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5"
                     style="background:#e8f8e0">
                    <i class="fa-solid fa-bullseye text-xl" style="color:#6ab023"></i>
                </div>
                <h2 class="text-slate-800 font-bold text-2xl mb-4">Misi</h2>
                <ul class="space-y-3 text-slate-500 text-sm">
                    <?php
                    $misi = [
                        'Menyediakan koleksi buku dan referensi ilmiah yang lengkap dan mutakhir',
                        'Memberikan layanan perpustakaan yang mudah diakses secara digital',
                        'Mendukung kegiatan penelitian dan pengabdian masyarakat',
                        'Meningkatkan minat baca dan literasi di lingkungan kampus',
                        'Mengembangkan sistem informasi perpustakaan yang modern dan efisien',
                    ];
                    foreach ($misi as $m):
                    ?>
                    <li class="flex items-start gap-3">
                        <i class="fa-solid fa-check-circle text-xs mt-1 flex-shrink-0" style="color:#6ab023"></i>
                        <?= $m ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Layanan -->
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-slate-800 mb-3">Layanan Kami</h2>
            <p class="text-slate-400">Berbagai layanan untuk mendukung kegiatan akademik Anda</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $layanan = [
                ['fa-book-open','Peminjaman Buku','Pinjam buku secara online langsung dari website','#006e82'],
                ['fa-search','Pencarian Koleksi','Temukan buku dengan fitur pencarian dan filter kategori','#6ab023'],
                ['fa-rotate-left','Pengembalian','Sistem pengembalian dengan kalkulasi denda otomatis','#e07b1a'],
                ['fa-chart-bar','Laporan','Laporan lengkap riwayat peminjaman dan statistik','#004d5a'],
            ];
            foreach ($layanan as $l):
            ?>
            <div class="fade-up bg-white rounded-2xl p-6 text-center shadow-sm border border-slate-100
                        hover:shadow-md hover:-translate-y-1 transition-all">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-3"
                     style="background:<?= $l[3] ?>15">
                    <i class="fa-solid <?= $l[0] ?> text-lg" style="color:<?= $l[3] ?>"></i>
                </div>
                <p class="font-semibold text-slate-700 text-sm mb-2"><?= $l[1] ?></p>
                <p class="text-slate-400 text-xs leading-relaxed"><?= $l[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- JAM & LOKASI -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100">
                <h3 class="font-bold text-slate-800 text-xl mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-clock" style="color:#006e82"></i>
                    Jam Operasional
                </h3>
                <div class="space-y-3">
                    <?php
                    $jam = [
                        ['Senin – Jumat','08.00 – 17.00 WIB','#6ab023'],
                        ['Sabtu','08.00 – 13.00 WIB','#e07b1a'],
                        ['Minggu','Tutup','#ef4444'],
                    ];
                    foreach ($jam as $j):
                    ?>
                    <div class="flex items-center justify-between py-3 border-b border-slate-200 last:border-0">
                        <span class="text-slate-600 text-sm font-medium"><?= $j[0] ?></span>
                        <span class="text-sm font-semibold" style="color:<?= $j[2] ?>"><?= $j[1] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-100">
                <h3 class="font-bold text-slate-800 text-xl mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-location-dot" style="color:#006e82"></i>
                    Lokasi & Kontak
                </h3>
                <div class="space-y-4">
                    <?php
                    $info = [
                        ['fa-map-marker-alt','Alamat','Jl. HM Joni No.70C, Medan, Sumatera Utara 20217','#006e82'],
                        ['fa-phone','Telepon','(061) 1234-5678','#6ab023'],
                        ['fa-envelope','Email','perpustakaan@harapan.ac.id','#e07b1a'],
                        ['fa-globe','Website','www.harapan.ac.id','#004d5a'],
                    ];
                    foreach ($info as $i):
                    ?>
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:<?= $i[3] ?>15">
                            <i class="fa-solid <?= $i[0] ?> text-sm" style="color:<?= $i[3] ?>"></i>
                        </div>
                        <div>
                            <p class="text-slate-400 text-xs"><?= $i[1] ?></p>
                            <p class="text-slate-700 text-sm font-medium"><?= $i[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<footer style="background:#003d49" class="mt-0">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
        <p class="text-xs" style="color:rgba(255,255,255,0.4)">
            &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
        </p>
    </div>
</footer>
</body>
</html>