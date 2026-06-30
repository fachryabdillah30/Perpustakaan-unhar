<?php
session_start();
require_once __DIR__ . '/config/db.php';

$sudah_login  = isset($_SESSION['user_id']);
$is_mahasiswa = $sudah_login && $_SESSION['role'] === 'mahasiswa';
$is_admin     = $sudah_login && $_SESSION['role'] === 'admin';
if ($is_admin) { header('Location: admin/dashboard.php'); exit; }

// Jika mahasiswa, redirect ke halaman pesan mahasiswa
if ($is_mahasiswa) {
    header('Location: mahasiswa/pesan.php'); exit;
}

$pesan_kirim = '';
$error_kirim = '';

// Proses kirim pesan — hanya jika sudah login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_pengirim'])) {
    if (!$sudah_login) {
        $error_kirim = 'Anda harus login terlebih dahulu untuk mengirim pesan.';
    } else {
        // tidak akan sampai sini karena mahasiswa sudah di-redirect,
        // tapi jaga-jaga
        $error_kirim = 'Silakan gunakan halaman Pesan Saya di akun Anda.';
    }
}

$user_data    = null;
$foto_nav     = null;
$ada_foto_nav = false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        .hero-gradient{background:linear-gradient(135deg,#004d5a,#006e82)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .5s ease both}
        .popup-notif{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;min-width:340px;max-width:90vw}
        @keyframes slideDown{from{opacity:0;transform:translate(-50%,-20px)}to{opacity:1;transform:translate(-50%,0)}}
        .slide-down{animation:slideDown .4s ease both}
    </style>
</head>
<body class="bg-slate-50 font-sans">

<!-- Popup Error -->
<?php if ($error_kirim): ?>
<div id="pe" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-orange-200 shadow-2xl rounded-2xl px-5 py-4">
        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
             style="background:#fff7ed">
            <i class="fa-solid fa-lock text-orange-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-slate-800 text-sm font-semibold">Login Diperlukan</p>
            <p class="text-slate-500 text-xs mt-0.5"><?= htmlspecialchars($error_kirim) ?></p>
        </div>
        <a href="beranda.php" class="text-xs px-3 py-1.5 rounded-lg text-white font-medium flex-shrink-0 transition hover:opacity-90"
           style="background:#006e82">Login</a>
    </div>
</div>
<script>setTimeout(()=>document.getElementById('pe')?.remove(),6000)</script>
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
                <a href="kontak.php" class="text-white text-sm px-4 py-2 rounded-lg bg-white bg-opacity-15">Kontak</a>
            </div>
            <div class="flex items-center gap-2">
                <!-- Tidak ada login di halaman ini karena mahasiswa di-redirect -->
                <a href="beranda.php"
                   class="flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-xl
                          border border-white border-opacity-30 text-white hover:bg-white hover:text-teal-800 transition-all">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span class="hidden sm:inline">Login</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="pt-16 hero-gradient">
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-14 text-center">
        <div class="fade-up inline-flex items-center gap-2 bg-white bg-opacity-15 border border-white border-opacity-20
                    text-white text-xs font-medium px-3 py-1.5 rounded-full mb-5">
            <i class="fa-solid fa-envelope"></i> Hubungi Kami
        </div>
        <h1 class="fade-up text-white font-bold text-4xl mb-3">Ada Pertanyaan?</h1>
        <p class="fade-up max-w-xl mx-auto" style="color:rgba(255,255,255,0.8)">
            Tim perpustakaan kami siap membantu Anda melalui berbagai saluran yang tersedia.
        </p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 lg:px-8 py-12">
    <div class="grid lg:grid-cols-2 gap-10">

        <!-- Info Kontak -->
        <div class="space-y-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Informasi Kontak</h2>
                <p class="text-slate-400 text-sm">Kunjungi kami langsung atau hubungi melalui saluran di bawah ini</p>
            </div>
            <?php
            $kontak_info = [
                ['fa-location-dot','Alamat','Jl. HM Joni No.70C, Medan, Sumatera Utara 20217','#006e82','Gedung Perpustakaan, Lantai 1-2, Universitas Harapan Medan'],
                ['fa-phone','Telepon','(061) 1234-5678','#6ab023','Senin – Jumat, 08.00 – 17.00 WIB'],
                ['fa-envelope','Email','perpustakaan@harapan.ac.id','#e07b1a','Respons dalam 1-2 hari kerja'],
                ['fa-clock','Jam Buka','Senin – Jumat: 08.00 – 17.00 | Sabtu: 08.00 – 13.00','#004d5a','Minggu & Hari Libur: Tutup'],
            ];
            foreach ($kontak_info as $k):
            ?>
            <div class="fade-up flex items-start gap-4 bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:<?= $k[3] ?>15">
                    <i class="fa-solid <?= $k[0] ?> text-lg" style="color:<?= $k[3] ?>"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-700 text-sm"><?= $k[1] ?></p>
                    <p class="text-slate-600 text-sm mt-0.5"><?= $k[2] ?></p>
                    <p class="text-slate-400 text-xs mt-0.5"><?= $k[4] ?></p>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Sosmed -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                <p class="font-bold text-slate-700 text-sm mb-4">Ikuti Kami</p>
                <div class="flex gap-3">
                    <?php
                    $sosmed=[['fa-facebook','#1877f2'],['fa-instagram','#e1306c'],['fa-twitter','#1da1f2'],['fa-youtube','#ff0000']];
                    foreach($sosmed as $s):
                    ?>
                    <a href="#" class="w-10 h-10 rounded-xl flex items-center justify-center transition hover:-translate-y-1 hover:shadow-md"
                       style="background:<?= $s[1] ?>15">
                        <i class="fa-brands <?= $s[0] ?>" style="color:<?= $s[1] ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Form Kirim Pesan -->
        <div class="fade-up">
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 text-xl mb-2">Kirim Pesan</h3>

                <!-- NOTIFIKASI jika belum login -->
                <?php if (!$sudah_login): ?>
                <div class="flex items-start gap-3 p-4 rounded-2xl border mb-6"
                     style="background:#fff7ed;border-color:#fed7aa">
                    <i class="fa-solid fa-lock text-orange-400 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="text-orange-800 font-semibold text-sm">Login Diperlukan</p>
                        <p class="text-orange-600 text-xs mt-1 leading-relaxed">
                            Anda harus memiliki akun dan login terlebih dahulu untuk mengirim pesan ke admin perpustakaan.
                        </p>
                        <a href="beranda.php"
                           class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold px-3 py-1.5
                                  rounded-lg text-white transition hover:opacity-90"
                           style="background:#006e82">
                            <i class="fa-solid fa-right-to-bracket text-xs"></i>
                            Login Sekarang
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-slate-400 text-sm mb-6">Silakan isi form di bawah ini</p>
                <?php endif; ?>

                <form method="POST" class="space-y-4"
                      onsubmit="<?= !$sudah_login ? 'return tampilkanErrorLogin()' : '' ?>">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Nama Lengkap</label>
                            <input type="text" name="nama_pengirim" required placeholder="Nama Anda"
                                   <?= !$sudah_login ? 'disabled' : '' ?>
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-teal-500
                                          <?= !$sudah_login ? 'bg-slate-50 cursor-not-allowed opacity-60' : '' ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                            <input type="email" name="email_pengirim" required placeholder="email@contoh.com"
                                   <?= !$sudah_login ? 'disabled' : '' ?>
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-teal-500
                                          <?= !$sudah_login ? 'bg-slate-50 cursor-not-allowed opacity-60' : '' ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Subjek</label>
                        <select name="subjek"
                                <?= !$sudah_login ? 'disabled' : '' ?>
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white text-slate-600
                                       <?= !$sudah_login ? 'cursor-not-allowed opacity-60' : '' ?>">
                            <option value="">-- Pilih Subjek --</option>
                            <option>Pertanyaan Peminjaman Buku</option>
                            <option>Masalah Akun</option>
                            <option>Saran & Masukan</option>
                            <option>Usulan Koleksi Buku</option>
                            <option>Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Pesan</label>
                        <textarea name="pesan_kontak" rows="5"
                                  <?= !$sudah_login ? 'disabled' : 'required' ?>
                                  placeholder="<?= !$sudah_login ? 'Login terlebih dahulu untuk menulis pesan...' : 'Tuliskan pesan Anda di sini...' ?>"
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none
                                         text-slate-600 placeholder-slate-300
                                         <?= !$sudah_login ? 'bg-slate-50 cursor-not-allowed opacity-60' : '' ?>"></textarea>
                    </div>

                    <?php if (!$sudah_login): ?>
                    <!-- Tombol disabled dengan overlay klik -->
                    <button type="button" onclick="tampilkanErrorLogin()"
                            class="w-full py-3.5 rounded-2xl font-semibold text-sm flex items-center
                                   justify-center gap-2 cursor-not-allowed opacity-60"
                            style="background:#94a3b8;color:white">
                        <i class="fa-solid fa-lock text-xs"></i>
                        Login untuk Mengirim Pesan
                    </button>
                    <?php else: ?>
                    <button type="submit"
                            class="w-full py-3.5 rounded-2xl text-white font-semibold text-sm transition
                                   hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        Kirim Pesan
                    </button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- FAQ -->
            <div class="mt-6 bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-circle-question" style="color:#006e82"></i>
                    Pertanyaan Umum
                </h3>
                <div class="space-y-2">
                    <?php
                    $faq=[
                        ['Berapa lama batas waktu peminjaman?','Batas waktu peminjaman default adalah 7 hari. Petugas dapat menyesuaikan durasi. Denda keterlambatan Rp 1.000/hari.'],
                        ['Bagaimana cara mengajukan peminjaman?','Login dengan akun mahasiswa, cari buku, klik buku lalu klik "Ajukan Peminjaman" dan isi formulir.'],
                        ['Berapa maksimal buku yang bisa dipinjam?','Setiap mahasiswa dapat meminjam maksimal 3 buku secara bersamaan.'],
                    ];
                    foreach($faq as $i=>$f):
                    ?>
                    <div class="border border-slate-100 rounded-xl overflow-hidden">
                        <button onclick="toggleFaq(<?= $i ?>)"
                                class="w-full text-left px-4 py-3 text-sm font-medium text-slate-700
                                       hover:bg-slate-50 transition flex items-center justify-between">
                            <?= $f[0] ?>
                            <i id="fi<?= $i ?>" class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform flex-shrink-0 ml-2"></i>
                        </button>
                        <div id="faq<?= $i ?>" class="hidden px-4 py-3 text-xs text-slate-500 leading-relaxed border-t border-slate-100">
                            <?= $f[1] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer style="background:#003d49" class="mt-12">
    <div class="max-w-7xl mx-auto px-4 py-8 text-center">
        <p class="text-xs" style="color:rgba(255,255,255,0.4)">
            &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
        </p>
    </div>
</footer>

<!-- Modal notifikasi login -->
<div id="modalLoginPerlu" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5);backdrop-filter:blur(4px)"
     onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center" onclick="event.stopPropagation()">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
             style="background:#fff7ed">
            <i class="fa-solid fa-lock text-orange-400 text-2xl"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-lg mb-2">Login Diperlukan</h3>
        <p class="text-slate-500 text-sm mb-6 leading-relaxed">
            Anda harus memiliki akun mahasiswa dan login terlebih dahulu untuk mengirim pesan ke admin perpustakaan.
        </p>
        <div class="flex gap-2">
            <button onclick="document.getElementById('modalLoginPerlu').classList.add('hidden')"
                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-500 text-sm hover:bg-slate-50 transition">
                Tutup
            </button>
            <a href="beranda.php"
               class="flex-1 py-2.5 rounded-xl text-white font-semibold text-sm transition hover:shadow-lg flex items-center justify-center gap-1.5"
               style="background:linear-gradient(135deg,#006e82,#008fa3)">
                <i class="fa-solid fa-right-to-bracket text-xs"></i>
                Login
            </a>
        </div>
    </div>
</div>

<script>
function toggleFaq(i) {
    const el   = document.getElementById('faq'+i);
    const icon = document.getElementById('fi'+i);
    el.classList.toggle('hidden');
    icon.style.transform = el.classList.contains('hidden') ? '' : 'rotate(180deg)';
}
function tampilkanErrorLogin() {
    document.getElementById('modalLoginPerlu').classList.remove('hidden');
    return false;
}
</script>
</body>
</html>