<?php
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
cekMahasiswa();
require_once __DIR__ . '/../config/db.php';

$notif = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? '';

// Kirim pesan baru
if ($aksi === 'kirim') {
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan  = trim($_POST['pesan']  ?? '');

    if (empty($pesan)) {
        $_SESSION['error_form'] = 'Isi pesan tidak boleh kosong.';
        header('Location: pesan.php'); exit;
    }

    // Ambil data user
    $u = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $u->execute([$_SESSION['user_id']]);
    $user = $u->fetch();

    $ins = $pdo->prepare("INSERT INTO pesan_kontak
        (user_id, nama, email, subjek, pesan, status) VALUES (?,?,?,?,?,'belum_dibaca')");
    $ins->execute([
        $_SESSION['user_id'],
        $user['nama'],
        $user['email'],
        $subjek,
        $pesan
    ]);
    $_SESSION['pesan'] = 'Pesan berhasil dikirim ke admin perpustakaan.';
    header('Location: pesan.php'); exit;
}

// Tandai balasan sudah dibaca oleh mahasiswa
if ($aksi === 'baca_balasan') {
    $id = (int)$_POST['id'];
    $pdo->prepare("UPDATE pesan_kontak SET sudah_dibaca_mahasiswa=1
                   WHERE id=? AND user_id=?")->execute([$id, $_SESSION['user_id']]);
    header('Location: pesan.php'); exit;
}

// Ambil semua pesan milik mahasiswa ini
$pesanku = $pdo->prepare("SELECT * FROM pesan_kontak
                           WHERE user_id=? ORDER BY created_at DESC");
$pesanku->execute([$_SESSION['user_id']]);
$daftar_pesan = $pesanku->fetchAll();

// Hitung balasan belum dibaca
$belum_baca = $pdo->prepare("SELECT COUNT(*) FROM pesan_kontak
                              WHERE user_id=? AND status='dibalas' AND sudah_dibaca_mahasiswa=0");
$belum_baca->execute([$_SESSION['user_id']]);
$jml_belum_baca = (int)$belum_baca->fetchColumn();

$user_data    = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user_data->execute([$_SESSION['user_id']]);
$user_data    = $user_data->fetch();
$foto_nav     = $user_data['foto'] ?? null;
$ada_foto_nav = $foto_nav && file_exists(__DIR__.'/../admin/uploads/foto_profil/'.$foto_nav);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Saya — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        .popup-notif{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;min-width:320px}
        @keyframes slideDown{from{opacity:0;transform:translate(-50%,-20px)}to{opacity:1;transform:translate(-50%,0)}}
        .slide-down{animation:slideDown .4s ease both}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#006e82;border-radius:3px}
    </style>
</head>
<body class="bg-slate-100 font-sans min-h-screen">

<!-- Popup Notif -->
<?php if ($notif): ?>
<div id="pp" class="popup-notif slide-down">
    <div class="flex items-center gap-3 bg-white border border-green-200 shadow-xl rounded-2xl px-5 py-4">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#d1fae5">
            <i class="fa-solid fa-circle-check text-green-500"></i>
        </div>
        <p class="text-slate-700 text-sm font-medium flex-1"><?= htmlspecialchars($notif) ?></p>
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
            <a href="../beranda.php" class="flex items-center gap-3">
                <img src="../assets/logo.jpg" alt="Logo"
                     class="w-9 h-9 rounded-full object-cover border-2 border-white border-opacity-30">
                <div class="hidden sm:block">
                    <p class="text-white font-bold text-sm leading-tight">Perpustakaan</p>
                    <p class="text-xs leading-tight" style="color:#a8d870">Universitas Harapan Medan</p>
                </div>
            </a>
            <div class="hidden md:flex items-center gap-1">
                <a href="../beranda.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Beranda</a>
                <a href="../koleksi.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Koleksi Buku</a>
                <a href="../tentang.php" class="text-white text-sm px-4 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Tentang</a>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative" id="umw">
                    <button onclick="document.getElementById('umd').classList.toggle('hidden')"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white
                                   border-opacity-20 text-white hover:bg-white hover:bg-opacity-10 transition text-sm">
                        <?php if ($ada_foto_nav): ?>
                            <img src="../admin/uploads/foto_profil/<?= $foto_nav ?>"
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
                            <p class="text-slate-400 text-xs">Mahasiswa</p>
                        </div>
                        <a href="riwayat.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                            <i class="fa-solid fa-clock-rotate-left text-slate-400 w-4 text-center"></i>Riwayat Pinjam</a>
                        <a href="pengajuan.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                            <i class="fa-solid fa-inbox text-slate-400 w-4 text-center"></i>Pengajuan Saya</a>
                        <a href="pesan.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium hover:bg-blue-50 transition"
                           style="color:#006e82">
                            <div class="relative w-4 text-center">
                                <i class="fa-solid fa-envelope text-xs" style="color:#006e82"></i>
                                <?php if ($jml_belum_baca > 0): ?>
                                <span class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 bg-red-500 text-white
                                             text-xs rounded-full flex items-center justify-center font-bold"
                                      style="font-size:8px"><?= $jml_belum_baca ?></span>
                                <?php endif; ?>
                            </div>
                            Pesan Saya
                            <?php if ($jml_belum_baca > 0): ?>
                            <span class="ml-auto bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded-full font-bold">
                                <?= $jml_belum_baca ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="../profil_mahasiswa.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                            <i class="fa-solid fa-circle-user text-slate-400 w-4 text-center"></i>Pengaturan Profil</a>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="pt-16" style="background:#004d5a">
    <div class="max-w-4xl mx-auto px-4 py-4">
        <nav class="flex items-center gap-2 text-xs" style="color:rgba(255,255,255,0.6)">
            <a href="../beranda.php" class="hover:text-white transition">Beranda</a>
            <i class="fa-solid fa-chevron-right text-xs" style="color:rgba(255,255,255,0.3)"></i>
            <span class="text-white">Pesan Saya</span>
        </nav>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Pesan Saya</h1>
            <p class="text-slate-400 text-sm mt-1">
                Kirim pesan ke admin dan lihat balasannya di sini
            </p>
        </div>
        <?php if ($jml_belum_baca > 0): ?>
        <span class="bg-red-100 text-red-600 text-sm font-semibold px-3 py-1.5 rounded-full">
            <?= $jml_belum_baca ?> balasan baru
        </span>
        <?php endif; ?>
    </div>

    <div class="grid md:grid-cols-5 gap-6">

        <!-- Form Kirim Pesan -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sticky top-24">
                <h3 class="font-bold text-slate-700 text-sm mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-xs" style="color:#006e82"></i>
                    Kirim Pesan Baru
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="aksi" value="kirim">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Subjek</label>
                        <select name="subjek"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-teal-500 bg-white text-slate-600">
                            <option value="">-- Pilih Subjek --</option>
                            <option>Pertanyaan Peminjaman</option>
                            <option>Masalah Akun</option>
                            <option>Laporan Kendala</option>
                            <option>Usulan Buku</option>
                            <option>Saran & Masukan</option>
                            <option>Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">
                            Pesan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="pesan" rows="5" required
                                  placeholder="Tulis pesan Anda di sini..."
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none
                                         text-slate-600 placeholder-slate-300 leading-relaxed"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full py-3 rounded-xl text-white font-semibold text-sm transition
                                   hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center gap-2"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        Kirim Pesan
                    </button>
                </form>

                <!-- Info -->
                <div class="mt-4 p-3 rounded-xl text-xs text-slate-500 leading-relaxed"
                     style="background:#f0fafa">
                    <i class="fa-solid fa-circle-info mr-1" style="color:#006e82"></i>
                    Admin perpustakaan akan membalas pesan Anda. Balasan akan muncul di halaman ini.
                </div>
            </div>
        </div>

        <!-- Daftar Pesan & Balasan -->
        <div class="md:col-span-3 space-y-4">
            <?php if (empty($daftar_pesan)): ?>
            <div class="bg-white rounded-2xl border border-slate-100 py-16 text-center shadow-sm">
                <i class="fa-solid fa-envelope text-slate-300 text-4xl mb-4 block"></i>
                <p class="text-slate-500 font-medium">Belum ada pesan</p>
                <p class="text-slate-400 text-sm mt-1">Kirim pesan pertama Anda ke admin</p>
            </div>
            <?php else: ?>
            <?php foreach ($daftar_pesan as $p):
                $ada_balasan_baru = $p['status']==='dibalas' && !$p['sudah_dibaca_mahasiswa'];
            ?>
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden
                        <?= $ada_balasan_baru ? 'border-teal-300' : 'border-slate-100' ?>">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"
                     style="<?= $ada_balasan_baru ? 'background:#f0fafa' : '' ?>">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-slate-700 text-sm">
                                <?= htmlspecialchars($p['subjek'] ?: 'Tidak ada subjek') ?>
                            </p>
                            <?php if ($ada_balasan_baru): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                  style="background:#d1fae5;color:#065f46">
                                <i class="fa-solid fa-reply mr-1 text-xs"></i>Balasan Baru!
                            </span>
                            <?php elseif ($p['status']==='dibalas'): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  style="background:#f1f5f9;color:#64748b">
                                Dibalas
                            </span>
                            <?php elseif ($p['status']==='belum_dibaca'): ?>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  style="background:#e0f2f8;color:#006e82">
                                Menunggu Balasan
                            </span>
                            <?php else: ?>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  style="background:#f8fafc;color:#94a3b8">
                                Sudah Dibaca Admin
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-slate-300 text-xs mt-0.5">
                            <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                        </p>
                    </div>
                </div>

                <!-- Pesan Mahasiswa -->
                <div class="px-5 py-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                             style="background:#6ab023">
                            <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold text-slate-500 mb-1">Pesan Anda</p>
                            <div class="bg-slate-50 rounded-xl p-3 text-sm text-slate-600 leading-relaxed">
                                <?= nl2br(htmlspecialchars($p['pesan'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balasan Admin -->
                <?php if ($p['balasan']): ?>
                <div class="px-5 pb-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                             style="background:#006e82">
                            <i class="fa-solid fa-shield-halved text-white text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-xs font-semibold" style="color:#006e82">Admin Perpustakaan</p>
                                <p class="text-xs text-slate-300">
                                    <?= date('d M Y, H:i', strtotime($p['waktu_balas'])) ?>
                                </p>
                            </div>
                            <div class="rounded-xl p-3 text-sm leading-relaxed"
                                 style="background:#e0f2f8;color:#334155">
                                <?= nl2br(htmlspecialchars($p['balasan'])) ?>
                            </div>
                            <!-- Tandai sudah dibaca -->
                            <?php if ($ada_balasan_baru): ?>
                            <form method="POST" class="mt-2">
                                <input type="hidden" name="aksi" value="baca_balasan">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit"
                                        class="text-xs px-3 py-1.5 rounded-lg border transition
                                               text-slate-500 hover:bg-slate-50"
                                        style="border-color:#94a3b8">
                                    <i class="fa-solid fa-check mr-1"></i>Tandai Sudah Dibaca
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="px-5 pb-4">
                    <div class="flex items-center gap-2 text-xs text-slate-400 bg-slate-50 rounded-xl px-3 py-2.5">
                        <i class="fa-regular fa-clock"></i>
                        Menunggu balasan dari admin perpustakaan...
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer style="background:#003d49" class="mt-12">
    <div class="max-w-7xl mx-auto px-4 py-6 text-center">
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