<?php
session_start();
require_once __DIR__ . '/config/db.php';

$sudah_login  = isset($_SESSION['user_id']);
$is_mahasiswa = $sudah_login && $_SESSION['role'] === 'mahasiswa';
$is_admin     = $sudah_login && $_SESSION['role'] === 'admin';

if ($is_admin) { header('Location: admin/dashboard.php'); exit; }

$user_data = null;
if ($is_mahasiswa) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
}

// Ambil 12 buku terbaru
$bukus     = $pdo->query("SELECT b.*, k.nama_kategori FROM buku b
                           LEFT JOIN kategori k ON b.kategori_id=k.id
                           ORDER BY b.id DESC LIMIT 12")->fetchAll();

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

// Proses pengajuan pinjam
$aksi = $_POST['aksi'] ?? '';
if ($aksi === 'ajukan' && $is_mahasiswa) {
    $buku_id = (int)$_POST['buku_id'];
    $catatan = trim($_POST['catatan'] ?? '');

    $stok = $pdo->prepare("SELECT stok,judul FROM buku WHERE id=?");
    $stok->execute([$buku_id]);
    $buku_cek = $stok->fetch();

    if (!$buku_cek || $buku_cek['stok'] < 1) {
        $_SESSION['error_form'] = 'Stok buku sedang tidak tersedia.';
    } else {
        $cek1 = $pdo->prepare("SELECT id FROM pengajuan_peminjaman WHERE user_id=? AND buku_id=? AND status='menunggu'");
        $cek1->execute([$_SESSION['user_id'], $buku_id]);
        $cek2 = $pdo->prepare("SELECT id FROM peminjaman WHERE user_id=? AND buku_id=? AND status='dipinjam'");
        $cek2->execute([$_SESSION['user_id'], $buku_id]);

        if ($cek1->fetch()) {
            $_SESSION['error_form'] = 'Anda sudah mengajukan peminjaman buku ini.';
        } elseif ($cek2->fetch()) {
            $_SESSION['error_form'] = 'Anda sedang meminjam buku ini.';
        } else {
            $ins = $pdo->prepare("INSERT INTO pengajuan_peminjaman (user_id,buku_id,catatan) VALUES (?,?,?)");
            $ins->execute([$_SESSION['user_id'], $buku_id, $catatan]);
            $_SESSION['pesan'] = 'Pengajuan peminjaman "' . $buku_cek['judul'] . '" berhasil dikirim!';
        }
    }
    header('Location: beranda.php'); exit;
}

$foto_nav     = $user_data['foto'] ?? null;
$ada_foto_nav = $foto_nav && file_exists(__DIR__ . '/admin/uploads/foto_profil/' . $foto_nav);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan Universitas Harapan Medan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root{--teal:#006e82;--green:#6ab023;--dark:#004d5a}
        *{scroll-behavior:smooth}
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        .hero-gradient{background:linear-gradient(135deg,#004d5a 0%,#006e82 50%,#6ab023 100%)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
        @keyframes scrollLeft{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
        @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
        .anim-1{animation:fadeInUp .6s ease .1s both}
        .anim-2{animation:fadeInUp .6s ease .2s both}
        .anim-3{animation:fadeInUp .6s ease .3s both}
        .anim-4{animation:fadeInUp .6s ease .4s both}
        .anim-float{animation:float 3s ease-in-out infinite}
        .slide-down{animation:slideDown .4s ease both}
        .gradient-text{background:linear-gradient(135deg,#6ab023,#a8d870);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .badge-kat{background:linear-gradient(135deg,#e8f8e0,#d4f0b8);color:#3d7010;border:1px solid #a8d870}
        /* Carousel */
        /* Carousel — smooth & tidak monoton */
.carousel-wrap{
    overflow:hidden;
    -webkit-mask:linear-gradient(90deg,transparent,black 10%,black 90%,transparent);
    mask:linear-gradient(90deg,transparent,black 10%,black 90%,transparent);
}
.carousel-track{
    display:flex;
    gap:1rem;
    will-change:transform;
}
.book-card{
    flex:0 0 180px;
    transition:all .35s cubic-bezier(.4,0,.2,1);
    cursor:pointer;
}
.book-card:hover{
    transform:translateY(-10px) scale(1.04) rotate(-1deg);
    box-shadow:0 24px 48px rgba(0,110,130,.2);
    z-index:10;
    position:relative;
}
.book-hover-overlay{opacity:0;transition:opacity .3s}
.book-card:hover .book-hover-overlay{opacity:1}
        /* Book card */
        .book-card{flex:0 0 180px;transition:all .3s cubic-bezier(.4,0,.2,1);cursor:pointer}
        .book-card:hover{transform:translateY(-8px) scale(1.03);box-shadow:0 20px 40px rgba(0,110,130,.2)}
        .book-hover-overlay{opacity:0;transition:opacity .3s ease}
        .book-card:hover .book-hover-overlay{opacity:1}
        /* Modal */
        .modal-bd{backdrop-filter:blur(6px);background:rgba(0,0,0,0.65)}
        #searchOverlay{backdrop-filter:blur(8px);background:rgba(0,40,48,0.92)}
        /* Popup notif */
        .popup-notif{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;min-width:320px;max-width:90vw}
        /* Modal detail buku */
        .detail-table tr td:first-child{color:#64748b;padding:8px 0;font-size:13px;width:130px;vertical-align:top}
        .detail-table tr td:last-child{color:#1e293b;padding:8px 0;font-size:13px;font-weight:500}
        .detail-table tr{border-bottom:1px solid #f1f5f9}
        .detail-table tr:last-child{border-bottom:none}
        /* Scrollbar */
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#006e82;border-radius:3px}
    </style>
</head>
<body class="bg-slate-50 font-sans">

<!-- ═══════════ POPUP NOTIFIKASI ═══════════ -->
<?php if ($pesan): ?>
<div id="popupPesan" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-green-200 shadow-xl rounded-2xl px-5 py-4">
        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
             style="background:#d1fae5">
            <i class="fa-solid fa-circle-check text-green-500"></i>
        </div>
        <p class="text-slate-700 text-sm font-medium flex-1"><?= htmlspecialchars($pesan) ?></p>
        <button onclick="document.getElementById('popupPesan').remove()"
                class="text-slate-300 hover:text-slate-500 transition ml-2">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
<script>setTimeout(()=>document.getElementById('popupPesan')?.remove(), 5000)</script>
<?php endif; ?>

<?php if ($error): ?>
<div id="popupError" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-red-200 shadow-xl rounded-2xl px-5 py-4">
        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
             style="background:#fee2e2">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
        </div>
        <p class="text-slate-700 text-sm font-medium flex-1"><?= htmlspecialchars($error) ?></p>
        <button onclick="document.getElementById('popupError').remove()"
                class="text-slate-300 hover:text-slate-500 transition ml-2">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
<script>setTimeout(()=>document.getElementById('popupError')?.remove(), 5000)</script>
<?php endif; ?>

<!-- ═══════════ NAVBAR ═══════════ -->
<nav class="navbar-blur fixed top-0 left-0 right-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <a href="beranda.php" class="flex items-center gap-3">
                <img src="assets/logo.jpg" alt="Logo"
                     class="w-9 h-9 rounded-full object-cover border-2 border-white border-opacity-30">
                <div class="hidden sm:block">
                    <p class="text-white font-bold text-sm leading-tight">Perpustakaan</p>
                    <p class="text-xs leading-tight" style="color:#a8d870">Universitas Harapan Medan</p>
                </div>
            </a>

            <!-- Menu Tengah -->
            <div class="hidden md:flex items-center gap-1">
                <a href="beranda.php#beranda"
                   class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">
                    Beranda
                </a>
                <a href="koleksi.php"
                   class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">
                    Koleksi Buku
                </a>
                <a href="tentang.php"
                   class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">
                    Tentang
                </a>
                <a href="kontak.php"
                   class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">
                    Kontak
                </a>
            </div>

            <!-- Kanan -->
            <div class="flex items-center gap-2">
                <!-- Search -->
                 <div id="searchOverlay" class="hidden fixed inset-0 z-50 flex items-start justify-center pt-24 px-4">
    <div class="w-full max-w-2xl">
        <form action="koleksi.php" method="GET">
            <div class="flex items-center bg-white rounded-2xl shadow-2xl overflow-hidden">
                <i class="fa-solid fa-search text-slate-400 ml-5"></i>
                <input type="text" name="cari" id="inputSearch"
                       placeholder="Cari judul buku, penulis..."
                       class="flex-1 px-4 py-4 text-slate-700 text-lg outline-none bg-transparent">
                <button type="submit"
                        class="px-6 py-4 text-white font-semibold text-sm"
                        style="background:#006e82">Cari</button>
                <button type="button" onclick="tutupSearch()"
                        class="px-4 py-4 text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </form>
        <p class="text-center text-white text-opacity-60 text-sm mt-4">
            Tekan <kbd class="bg-white bg-opacity-20 px-2 py-0.5 rounded text-xs">Enter</kbd>
            untuk mencari atau <kbd class="bg-white bg-opacity-20 px-2 py-0.5 rounded text-xs">Esc</kbd>
            untuk menutup
        </p>
    </div>
</div>
                <button onclick="bukaSearch()"
                        class="w-9 h-9 flex items-center justify-center rounded-xl text-white
                               hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fa-solid fa-search"></i>
                </button>
                

                <?php if ($is_mahasiswa): ?>
                <!-- User Menu -->
                <div class="relative" id="userMenuWrap">
                    <button onclick="toggleUserMenu()"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white
                                   border-opacity-20 text-white hover:bg-white hover:bg-opacity-10 transition text-sm">
                        <?php if ($ada_foto_nav): ?>
                            <img src="admin/uploads/foto_profil/<?= $foto_nav ?>"
                                 class="w-6 h-6 rounded-full object-cover border border-white border-opacity-30">
                        <?php else: ?>
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                 style="background:#6ab023">
                                <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:inline max-w-28 truncate">
                            <?= htmlspecialchars($_SESSION['nama']) ?>
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>

                    <div id="userMenuDropdown"
                         class="hidden absolute right-0 mt-2 bg-white rounded-2xl shadow-xl
                                border border-slate-100 py-2 w-56 z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="font-bold text-slate-800 text-sm">
                                <?= htmlspecialchars($_SESSION['nama']) ?>
                            </p>
                            <p class="text-slate-400 text-xs mt-0.5">Mahasiswa</p>
                        </div>
                        <a href="mahasiswa/riwayat.php"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600
                                  hover:bg-slate-50 transition">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400 w-4 text-center"></i>
                            Riwayat Pinjam
                        </a>
                        <a href="mahasiswa/pesan.php"
   class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
    <i class="fa-solid fa-envelope text-slate-400 w-4 text-center"></i>
    Kirim Pesan
</a>
                        <a href="mahasiswa/pengajuan.php"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600
                                  hover:bg-slate-50 transition">
                            <i class="fa-solid fa-inbox text-slate-400 w-4 text-center"></i>
                            Pengajuan Saya
                        </a>
                        <a href="profil_mahasiswa.php"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600
                                  hover:bg-slate-50 transition">
                            <i class="fa-solid fa-circle-user text-slate-400 w-4 text-center"></i>
                            Pengaturan Profil
                        </a>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <a href="auth/logout.php"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500
                                      hover:bg-red-50 transition">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <button onclick="bukaLogin()"
                        class="flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-xl
                               border border-white border-opacity-30 text-white
                               hover:bg-white hover:text-teal-800 transition-all">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span class="hidden sm:inline">Login</span>
                </button>
                <?php endif; ?>

                <button onclick="toggleMobileMenu()"
                        class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl text-white
                               hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-white border-opacity-10">
        <div class="px-4 py-3 space-y-1">
            <a href="beranda.php#beranda" onclick="toggleMobileMenu()"
               class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Beranda</a>
            <a href="beranda.php#koleksi" onclick="toggleMobileMenu()"
               class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Koleksi Buku</a>
            <a href="beranda.php#tentang" onclick="toggleMobileMenu()"
               class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Tentang</a>
            <a href="beranda.php#kontak" onclick="toggleMobileMenu()"
               class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Kontak</a>
            <?php if ($is_mahasiswa): ?>
            <a href="mahasiswa/riwayat.php" class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Riwayat Pinjam</a>
            <a href="mahasiswa/pengajuan.php" class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Pengajuan Saya</a>
            <a href="profil_mahasiswa.php" class="block text-white text-sm px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10">Profil</a>
            <?php else: ?>
            <button onclick="bukaLogin();toggleMobileMenu()"
                    class="w-full text-left text-white text-sm px-3 py-2 rounded-lg bg-white bg-opacity-10 font-medium">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>Login
            </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ═══════════ SEARCH OVERLAY ═══════════ -->
<div id="searchOverlay" class="hidden fixed inset-0 z-50"
     style="background:rgba(0,0,0,0.5);backdrop-filter:blur(8px)">
    <div class="flex flex-col items-center pt-32 px-4">
        <form action="koleksi.php" method="GET" class="w-full max-w-2xl">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="cari" id="inputSearch"
                       placeholder="Cari judul buku, penulis, atau kategori..."
                       class="w-full pl-14 pr-36 py-5 rounded-2xl text-slate-700 text-lg
                              outline-none shadow-2xl border-0 bg-white"
                       style="font-size:16px">
                <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-2">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-white font-semibold text-sm transition hover:opacity-90"
                            style="background:#006e82">
                        Cari
                    </button>
                    <button type="button" onclick="tutupSearch()"
                            class="w-9 h-9 flex items-center justify-center rounded-xl
                                   text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>
        </form>
        <p class="text-white text-opacity-50 text-sm mt-4" style="color:rgba(255,255,255,0.5)">
            Tekan <kbd class="bg-white bg-opacity-20 text-white px-2 py-0.5 rounded text-xs font-mono">Esc</kbd>
            untuk menutup
        </p>
    </div>
</div>

<!-- ═══════════ MODAL LOGIN ═══════════ -->
<?php if (!$sudah_login): ?>
<div id="modalLogin" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-bd">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-5 text-center" style="background:linear-gradient(135deg,#004d5a,#006e82)">
            <img src="assets/logo.jpg" alt="Logo"
                 class="w-14 h-14 rounded-full object-cover mx-auto mb-3 border-2 border-white border-opacity-30">
            <h2 class="text-white font-bold text-lg">Masuk ke Perpustakaan</h2>
            <p class="text-white text-xs mt-1" style="opacity:.7">Universitas Harapan Medan</p>
        </div>
        <form action="auth/login_beranda.php" method="POST" class="px-6 py-6 space-y-4">
            <?php if ($error): ?>
            <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl px-3 py-2.5 text-xs">
                <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                <div class="flex items-center border border-slate-200 rounded-xl px-3 py-2.5 gap-2 focus-within:ring-2 focus-within:ring-teal-500 focus-within:border-transparent">
                    <i class="fa-solid fa-envelope text-slate-300 text-sm"></i>
                    <input type="email" name="email" required placeholder="contoh@email.com"
                           class="flex-1 text-sm text-slate-700 outline-none bg-transparent">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Password</label>
                <div class="flex items-center border border-slate-200 rounded-xl px-3 py-2.5 gap-2 focus-within:ring-2 focus-within:ring-teal-500 focus-within:border-transparent">
                    <i class="fa-solid fa-lock text-slate-300 text-sm"></i>
                    <input type="password" name="password" id="passLogin" required placeholder="••••••••"
                           class="flex-1 text-sm text-slate-700 outline-none bg-transparent">
                    <button type="button" onclick="togglePassLogin()" class="text-slate-300 hover:text-slate-500">
                        <i id="eyeLogin" class="fa-solid fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <button type="submit"
                    class="w-full py-3 rounded-xl text-white font-semibold text-sm transition hover:shadow-lg hover:-translate-y-0.5"
                    style="background:linear-gradient(135deg,#006e82,#008fa3)">
                <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
            </button>
            <button type="button" onclick="tutupLogin()"
                    class="w-full py-2.5 rounded-xl text-slate-400 text-sm hover:bg-slate-50 transition">
                Batal
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════ HERO ═══════════ -->
<section id="beranda" class="hero-gradient relative min-h-screen flex items-center overflow-hidden pt-16">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full opacity-10"
             style="background:radial-gradient(circle,#6ab023,transparent)"></div>
        <div class="absolute bottom-20 -left-20 w-80 h-80 rounded-full opacity-10"
             style="background:radial-gradient(circle,#a8d870,transparent)"></div>
        <div class="absolute inset-0 opacity-5"
             style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:40px 40px"></div>
    </div>
                
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-20 w-full">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div>
            <div class="anim-1 inline-flex items-center gap-2 bg-white bg-opacity-15 border border-white
                        border-opacity-20 text-white text-xs font-medium px-3 py-1.5 rounded-full mb-6">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Sistem Perpustakaan Digital
            </div>
            <h1 class="anim-2 text-white font-bold text-4xl lg:text-5xl xl:text-6xl leading-tight mb-6">
                Temukan Buku
                <span class="gradient-text block">Impian Anda</span>
                di Sini
            </h1>
            <p class="anim-3 text-lg leading-relaxed mb-8" style="color:rgba(255,255,255,0.85)">
                Perpustakaan Universitas Harapan Medan hadir dengan ribuan koleksi buku
                ilmiah, fiksi, dan referensi untuk mendukung perjalanan akademik Anda.
            </p>
                
            <!-- Search Bar -->
            <div class="anim-4">
                <form action="koleksi.php" method="GET" class="flex gap-2">
                    <div class="flex-1 flex items-center bg-white rounded-2xl px-4 py-3 gap-3 shadow-lg">
                        <i class="fa-solid fa-search text-slate-400"></i>
                        <input type="text" name="cari" placeholder="Cari judul buku, penulis..."
                               class="flex-1 text-slate-700 text-sm bg-transparent outline-none">
                    </div>
                    
                    <button type="submit"
                            class="px-6 py-3 rounded-2xl font-semibold text-white text-sm shadow-lg
                                   hover:shadow-xl hover:-translate-y-0.5 transition-all"
                            style="background:linear-gradient(135deg,#6ab023,#4d8a0f)">
                        Cari
                    </button>
                </form>
            </div>
        </div>
    </div>
   </div><!-- tutup kolom kiri -->

    <!-- Kolom Kanan: Logo -->
    <div class="hidden lg:flex items-center justify-center">
        <div class="relative flex items-center justify-center" style="width:320px;height:320px">
            <!-- Cincin dekorasi -->
            <div class="absolute rounded-full border border-white border-opacity-15"
                 style="width:320px;height:320px;animation:spin 25s linear infinite"></div>
            <div class="absolute rounded-full border border-white border-opacity-10"
                 style="width:270px;height:270px;animation:spin 18s linear infinite reverse"></div>
            <div class="absolute rounded-full"
                 style="width:220px;height:220px;background:rgba(255,255,255,0.06)"></div>
            <!-- Logo -->
            <div class="relative z-10" style="animation:float 3s ease-in-out infinite">
                <div class="rounded-full overflow-hidden shadow-2xl border-4 border-white border-opacity-20"
                     style="width:200px;height:200px;background:rgba(255,255,255,0.1)">
                    <img src="assets/logo.jpg" alt="Logo UHM"
                         class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>

    </div><!-- tutup grid -->        
    <svg class="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 60" fill="none">
        <path d="M0 60L48 50C96 40 192 20 288 15C384 10 480 20 576 25C672 30 768 30 864 25C960 20 1056 10 1152 10C1248 10 1344 20 1392 25L1440 30V60H0Z" fill="#f0fafa"/>
    </svg>

    
</section>

<!-- ═══════════ KOLEKSI BUKU (CAROUSEL) ═══════════ -->
<section id="koleksi" class="py-16" style="background:#f0fafa">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="text-center mb-10">
            <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3"
                  style="background:#e8f8e0;color:#4d8a0f">📚 Koleksi Kami</span>
            <h2 class="text-3xl font-bold text-slate-800 mb-3">
                Temukan Buku yang
                <span style="color:#006e82"> Anda Butuhkan</span>
            </h2>
            <p class="text-slate-500 text-sm">
                Klik buku untuk melihat detail
                <?= $is_mahasiswa ? 'dan mengajukan peminjaman' : '— Login untuk meminjam' ?>
            </p>
        </div>

        <!-- Carousel -->
        <div class="carousel-wrap">
            <div class="carousel-track" id="carouselTrack">
                <?php
                // Duplikasi untuk efek loop mulus
                $semua = array_merge($bukus, $bukus);
                foreach ($semua as $buku):
                    $ada_foto = $buku['foto'] &&
                        file_exists(__DIR__ . '/admin/uploads/foto_buku/' . $buku['foto']);
                ?>
                <div class="book-card rounded-2xl overflow-hidden bg-white shadow-sm border border-slate-100"
                     onclick="lihatDetail(<?= htmlspecialchars(json_encode($buku)) ?>)">
                    <!-- Cover -->
                    <div class="relative overflow-hidden" style="height:220px">
                        <?php if ($ada_foto): ?>
                            <img src="admin/uploads/foto_buku/<?= $buku['foto'] ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center gap-2"
                                 style="background:linear-gradient(135deg,#e8f4f8,#d0eaf0)">
                                <i class="fa-solid fa-book text-4xl" style="color:#006e82;opacity:0.3"></i>
                                <p class="text-xs text-slate-400 text-center px-3 leading-tight">
                                    <?= mb_strimwidth(htmlspecialchars($buku['judul']),0,40,'...') ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Badge stok -->
                        <div class="absolute top-2 right-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white"
                                  style="background:<?= $buku['stok']>0 ? '#6ab023' : '#ef4444' ?>">
                                <?= $buku['stok']>0 ? 'Tersedia' : 'Habis' ?>
                            </span>
                        </div>

                        <!-- Hover overlay — "Lihat Buku" -->
                        <div class="book-hover-overlay absolute inset-0 flex items-center justify-center"
                             style="background:rgba(0,78,90,0.78)">
                            <div class="text-white text-center">
                                <i class="fa-solid fa-eye text-2xl mb-2"></i>
                                <p class="text-sm font-semibold">Lihat Buku</p>
                            </div>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-3">
                        <span class="badge-kat text-xs px-2 py-0.5 rounded-full font-medium">
                            <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                        </span>
                        <h3 class="text-slate-800 font-semibold text-xs leading-tight line-clamp-2 mt-1.5">
                            <?= htmlspecialchars($buku['judul']) ?>
                        </h3>
                        <p class="text-slate-400 text-xs mt-0.5 truncate">
                            <?= htmlspecialchars($buku['penulis']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kontrol Carousel Manual -->
        <div class="flex justify-center gap-3 mt-6">
            <button onclick="geserKiri()"
                    class="w-10 h-10 rounded-full border border-slate-200 bg-white shadow-sm
                           flex items-center justify-center text-slate-500 hover:border-teal-500
                           hover:text-teal-600 transition">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </button>
            <button onclick="geserKanan()"
                    class="w-10 h-10 rounded-full border border-slate-200 bg-white shadow-sm
                           flex items-center justify-center text-slate-500 hover:border-teal-500
                           hover:text-teal-600 transition">
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </button>
        </div>
    </div>
</section>

<!-- ═══════════ TENTANG ═══════════ -->
<section id="tentang" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-4"
                      style="background:#e0f2f8;color:#006e82">🏛️ Tentang Perpustakaan</span>
                <h2 class="text-3xl font-bold text-slate-800 mb-4">
                    Pusat Ilmu Pengetahuan
                    <span style="color:#006e82"> Universitas Harapan Medan</span>
                </h2>
                <p class="text-slate-500 leading-relaxed mb-6">
                    Perpustakaan Universitas Harapan Medan adalah pusat sumber daya akademik
                    yang menyediakan akses ke ribuan koleksi buku, jurnal, dan referensi
                    untuk mendukung kegiatan belajar mengajar.
                </p>
                <div class="space-y-4">
                    <?php
                    $infos = [
                        ['fa-clock','Jam Operasional','Senin – Jumat: 08.00 – 17.00 WIB'],
                        ['fa-location-dot','Lokasi','Jl. HM Joni No.70C, Medan, Sumatera Utara'],
                        ['fa-phone','Kontak','(061) 1234-5678 | perpustakaan@harapan.ac.id'],
                    ];
                    foreach ($infos as $f):
                    ?>
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:#e0f2f8">
                            <i class="fa-solid <?= $f[0] ?> text-sm" style="color:#006e82"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700 text-sm"><?= $f[1] ?></p>
                            <p class="text-slate-400 text-sm"><?= $f[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <?php
                $features = [
                    ['fa-search','Cari Buku','Temukan buku berdasarkan judul, penulis, atau kategori','#006e82'],
                    ['fa-mobile-screen','Akses Online','Ajukan peminjaman langsung dari website kapan saja','#6ab023'],
                    ['fa-bell','Notifikasi','Status pengajuan dan pengingat batas pengembalian','#e07b1a'],
                    ['fa-shield-halved','Data Aman','Informasi pribadi Anda tersimpan dengan aman','#004d5a'],
                ];
                foreach ($features as $f):
                ?>
                <div class="bg-slate-50 rounded-2xl p-5 hover:bg-white hover:shadow-md transition-all border border-slate-100">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3"
                         style="background:<?= $f[3] ?>15">
                        <i class="fa-solid <?= $f[0] ?>" style="color:<?= $f[3] ?>"></i>
                    </div>
                    <p class="font-semibold text-slate-700 text-sm mb-1"><?= $f[1] ?></p>
                    <p class="text-slate-400 text-xs leading-relaxed"><?= $f[2] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ CTA ═══════════ -->
<?php if (!$is_mahasiswa): ?>
<section class="py-16 relative overflow-hidden" style="background:linear-gradient(135deg,#004d5a,#006e82)">
    <div class="absolute inset-0 pointer-events-none opacity-10"
         style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:30px 30px"></div>
    <div class="max-w-3xl mx-auto px-4 text-center relative z-10">
        <div class="anim-float inline-block mb-6">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto"
                 style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3)">
                <i class="fa-solid fa-book-open text-white text-2xl"></i>
            </div>
        </div>
        <h2 class="text-white font-bold text-3xl lg:text-4xl mb-4">Mulai Peminjaman Hari Ini</h2>
        <p class="mb-8 max-w-lg mx-auto" style="color:rgba(255,255,255,0.8)">
            Login dengan akun mahasiswa untuk mengajukan peminjaman buku langsung dari website
        </p>
        <button onclick="bukaLogin()"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl font-semibold text-sm
                       bg-white hover:bg-slate-50 hover:shadow-xl hover:-translate-y-0.5 transition-all"
                style="color:#006e82">
            <i class="fa-solid fa-right-to-bracket"></i>
            Login Sekarang
        </button>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════ FOOTER ═══════════ -->
<footer id="kontak" style="background:#003d49">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <img src="assets/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <p class="text-white font-bold text-sm">Perpustakaan</p>
                        <p class="text-xs" style="color:#6ab023">Universitas Harapan Medan</p>
                    </div>
                </div>
                <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,0.6)">
                    Pusat sumber daya ilmu pengetahuan untuk civitas akademika UHM.
                </p>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-4">Navigasi</p>
                <ul class="space-y-2">
                    <?php foreach (['Beranda'=>'#beranda','Koleksi Buku'=>'#koleksi','Tentang'=>'#tentang','Kontak'=>'#kontak'] as $l=>$h): ?>
                    <li><a href="<?= $h ?>" class="text-sm hover:text-green-400 transition" style="color:rgba(255,255,255,0.6)"><?= $l ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold text-sm mb-4">Kontak</p>
                <div class="space-y-2">
                    <?php
                    $kontaks=[['fa-location-dot','Jl. HM Joni No.70C, Medan'],['fa-phone','(061) 1234-5678'],['fa-envelope','perpustakaan@harapan.ac.id'],['fa-clock','Senin – Jumat, 08.00 – 17.00']];
                    foreach($kontaks as $k):
                    ?>
                    <div class="flex items-start gap-2">
                        <i class="fa-solid <?= $k[0] ?> text-xs mt-1 flex-shrink-0" style="color:#6ab023"></i>
                        <p class="text-xs" style="color:rgba(255,255,255,0.6)"><?= $k[1] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="border-t pt-6 text-center" style="border-color:rgba(255,255,255,0.1)">
            <p class="text-xs" style="color:rgba(255,255,255,0.4)">
                &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<!-- ═══════════ MODAL DETAIL BUKU ═══════════ -->
<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-bd"
     onclick="tutupDetail()">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-screen overflow-y-auto"
         onclick="event.stopPropagation()">

        <div class="flex flex-col md:flex-row">
            <!-- Cover Kiri -->
            <div class="md:w-56 flex-shrink-0">
                <div id="detailCover"
                     class="w-full h-64 md:h-full min-h-56 flex items-center justify-center overflow-hidden"
                     style="background:linear-gradient(135deg,#006e82,#008fa3)">
                </div>
            </div>

            <!-- Info Kanan -->
            <div class="flex-1 p-6 flex flex-col">
                <div class="flex items-start justify-between mb-1">
                    <div class="flex-1">
                        <h2 id="detailJudul" class="text-slate-800 font-bold text-xl leading-tight mb-1"></h2>
                        <p id="detailPenulis" class="text-sm mb-3" style="color:#006e82"></p>
                        <span id="detailKategori" class="badge-kat text-xs px-2 py-0.5 rounded-full font-medium"></span>
                    </div>
                    <button onclick="tutupDetail()" class="text-slate-300 hover:text-slate-500 ml-3 flex-shrink-0">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <!-- Tabel Detail -->
                <table class="detail-table w-full mt-4 flex-1">
                    <tr>
                        <td>Penerbit</td>
                        <td id="detailPenerbit"></td>
                    </tr>
                    <tr>
                        <td>Tahun Terbit</td>
                        <td id="detailTahun"></td>
                    </tr>
                    <tr>
                        <td>ISBN</td>
                        <td id="detailIsbn" class="font-mono"></td>
                    </tr>
                    <tr>
                        <td>Kategori</td>
                        <td id="detailKategori2" style="color:#006e82"></td>
                    </tr>
                    <tr>
                        <td>Stok Tersedia</td>
                        <td id="detailStok" class="font-bold"></td>
                    </tr>
                </table>

                <!-- Status -->
                <div class="mt-4 flex items-center gap-2">
                    <span id="detailDotStok" class="w-2.5 h-2.5 rounded-full inline-block"></span>
                    <span id="detailStatusLabel" class="text-sm font-medium"></span>
                </div>
            </div>
        </div>

        <!-- Footer Tombol -->
        <div class="border-t border-slate-100 px-6 py-4 flex items-center gap-3">
            <button onclick="bagikanBuku()"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-slate-500 text-sm
                           hover:bg-slate-50 border border-slate-200 transition">
                <i class="fa-solid fa-share-nodes"></i>
                Bagikan buku ini
            </button>
            <div class="flex-1"></div>
            <?php if ($is_mahasiswa): ?>
            <button id="btnAjukanDetail" onclick="ajukanPinjam()"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold
                           transition hover:shadow-lg hover:-translate-y-0.5"
                    style="background:linear-gradient(135deg,#006e82,#008fa3)">
                <i class="fa-solid fa-plus text-xs"></i>
                Pinjam buku ini
            </button>
            <?php else: ?>
            <button onclick="tutupDetail();bukaLogin()"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold
                           transition hover:shadow-lg"
                    style="background:linear-gradient(135deg,#006e82,#008fa3)">
                <i class="fa-solid fa-right-to-bracket text-xs"></i>
                Login untuk Pinjam
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Form Ajukan Tersembunyi -->
<?php if ($is_mahasiswa): ?>
<form id="formAjukan" method="POST" class="hidden">
    <input type="hidden" name="aksi" value="ajukan">
    <input type="hidden" name="buku_id" id="ajukanBukuId">
    <input type="hidden" name="catatan" value="">
</form>
<?php endif; ?>

<script>
// ── Carousel ──────────────────────────────────────────────────
// ── Carousel Smooth ──────────────────────────────────────────
const track = document.getElementById('carouselTrack');
let pos     = 0;
let paused  = false;
let speed   = 0.6;
let raf;

track.addEventListener('mouseenter', () => { paused = true; speed = 0; });
track.addEventListener('mouseleave', () => { paused = false; speed = 0.6; });

function animate() {
    if (!paused) {
        pos += speed;
        track.style.transform = `translateX(-${pos}px)`;
        // Reset saat setengah
        const half = track.scrollWidth / 2;
        if (pos >= half) pos = 0;
    }
    raf = requestAnimationFrame(animate);
}
animate();

function geserKiri() {
    pos = Math.max(0, pos - 560);
    track.style.transition = 'transform .5s ease';
    track.style.transform  = `translateX(-${pos}px)`;
    setTimeout(() => track.style.transition = '', 500);
}
function geserKanan() {
    pos += 560;
    track.style.transition = 'transform .5s ease';
    track.style.transform  = `translateX(-${pos}px)`;
    setTimeout(() => track.style.transition = '', 500);
}
// Auto scroll
let autoScroll = setInterval(() => {
    if (carPaused) return;
    track.style.transition = 'transform .8s linear';
    carPos += 1;
    track.style.transform = `translateX(-${carPos}px)`;
    // Reset saat sudah setengah (duplikasi)
    const half = track.scrollWidth / 2;
    if (carPos >= half) { carPos = 0; track.style.transition = 'none'; track.style.transform = 'translateX(0)'; }
}, 20);

// ── Search ────────────────────────────────────────────────────
function bukaSearch() {
    document.getElementById('searchOverlay').classList.remove('hidden');
    // Langsung fokus ke input
    setTimeout(() => {
        const input = document.getElementById('inputSearch');
        if (input) input.focus();
    }, 100);
}
function tutupSearch() {
    document.getElementById('searchOverlay').classList.add('hidden');
}
// Klik backdrop tutup search
document.getElementById('searchOverlay').addEventListener('click', function(e) {
    if (e.target === this) tutupSearch();
});

// ── Login ─────────────────────────────────────────────────────
function bukaLogin() { document.getElementById('modalLogin')?.classList.remove('hidden'); }
function tutupLogin(){ document.getElementById('modalLogin')?.classList.add('hidden'); }
document.getElementById('modalLogin')?.addEventListener('click', function(e){ if(e.target===this) tutupLogin(); });

// ── User Menu ─────────────────────────────────────────────────
function toggleUserMenu() { document.getElementById('userMenuDropdown')?.classList.toggle('hidden'); }
document.addEventListener('click', e => {
    if (!e.target.closest('#userMenuWrap')) document.getElementById('userMenuDropdown')?.classList.add('hidden');
});

// ── Mobile Menu ───────────────────────────────────────────────
function toggleMobileMenu() { document.getElementById('mobileMenu').classList.toggle('hidden'); }

// ── Password Toggle ───────────────────────────────────────────
function togglePassLogin() {
    const i = document.getElementById('passLogin');
    const e = document.getElementById('eyeLogin');
    i.type = i.type==='password' ? 'text' : 'password';
    e.className = i.type==='text' ? 'fa-solid fa-eye-slash text-sm' : 'fa-solid fa-eye text-sm';
}

// ── Detail Buku ───────────────────────────────────────────────
let bukuAktif = null;
function lihatDetail(data) {
    window.location.href = 'buku_detail.php?id=' + data.id;
}

function tutupDetail() { document.getElementById('modalDetail').classList.add('hidden'); bukuAktif=null; }

function ajukanPinjam() {
    if (!bukuAktif || bukuAktif.stok < 1) return;
    document.getElementById('ajukanBukuId').value = bukuAktif.id;
    document.getElementById('formAjukan').submit();
}

function bagikanBuku() {
    if (navigator.share && bukuAktif) {
        navigator.share({ title: bukuAktif.judul, text: 'Lihat buku ini di Perpustakaan UHM', url: window.location.href });
    } else {
        navigator.clipboard?.writeText(window.location.href);
        alert('Link berhasil disalin!');
    }
}

// ESC
document.addEventListener('keydown', e => {
    if (e.key==='Escape') { tutupDetail(); tutupSearch(); tutupLogin(); }
});

// Navbar scroll
window.addEventListener('scroll', () => {
    document.querySelector('nav').style.boxShadow = window.scrollY>50 ? '0 4px 20px rgba(0,0,0,0.3)' : '';
});

// Buka login otomatis jika error
<?php if ($error && !$sudah_login): ?>
window.addEventListener('load', () => bukaLogin());
<?php endif; ?>
</script>
</body>
</html>