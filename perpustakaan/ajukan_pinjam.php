<?php
session_start();
require_once __DIR__ . '/includes/auth_check.php';
cekMahasiswa();
require_once __DIR__ . '/config/db.php';

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

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $durasi  = max(1, min(30, (int)($_POST['durasi'] ?? 7)));
    $catatan = trim($_POST['catatan'] ?? '');

    if ($buku['stok'] < 1) {
        $_SESSION['error_form'] = 'Stok buku tidak tersedia.';
        header('Location: ajukan_pinjam.php?id='.$id); exit;
    }

    $cek1 = $pdo->prepare("SELECT id FROM pengajuan_peminjaman WHERE user_id=? AND buku_id=? AND status='menunggu'");
    $cek1->execute([$_SESSION['user_id'], $id]);
    $cek2 = $pdo->prepare("SELECT id FROM peminjaman WHERE user_id=? AND buku_id=? AND status='dipinjam'");
    $cek2->execute([$_SESSION['user_id'], $id]);

    if ($cek1->fetch()) {
        $_SESSION['error_form'] = 'Anda sudah mengajukan peminjaman buku ini.';
    } elseif ($cek2->fetch()) {
        $_SESSION['error_form'] = 'Anda sedang meminjam buku ini.';
    } else {
        $ins = $pdo->prepare("INSERT INTO pengajuan_peminjaman (user_id,buku_id,catatan) VALUES (?,?,?)");
        $catatan_full = "Durasi: $durasi hari" . ($catatan ? " | Catatan: $catatan" : '');
        $ins->execute([$_SESSION['user_id'], $id, $catatan_full]);
        $_SESSION['pesan'] = 'Pengajuan berhasil! Admin akan memproses permintaan Anda.';
        header('Location: mahasiswa/pengajuan.php'); exit;
    }
    header('Location: ajukan_pinjam.php?id='.$id); exit;
}

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user_data    = $user->fetch();
$foto_nav     = $user_data['foto'] ?? null;
$ada_foto_nav = $foto_nav && file_exists(__DIR__.'/admin/uploads/foto_profil/'.$foto_nav);
$ada_foto     = $buku['foto'] && file_exists(__DIR__.'/admin/uploads/foto_buku/'.$buku['foto']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Peminjaman — <?= htmlspecialchars($buku['judul']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navbar-blur{background:rgba(0,77,90,0.97);backdrop-filter:blur(12px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .5s ease both}
        .popup-notif{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;min-width:320px}
        @keyframes slideDown{from{opacity:0;transform:translate(-50%,-20px)}to{opacity:1;transform:translate(-50%,0)}}
        .slide-down{animation:slideDown .4s ease both}
        input[type=range]{accent-color:#006e82}
    </style>
</head>
<body class="bg-slate-100 font-sans min-h-screen">

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
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-white border-opacity-20 text-white text-sm">
                    <?php if ($ada_foto_nav): ?>
                        <img src="admin/uploads/foto_profil/<?= $foto_nav ?>"
                             class="w-6 h-6 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                             style="background:#6ab023">
                            <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <span class="hidden sm:inline"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                </div>
                <a href="auth/logout.php" class="text-red-300 hover:text-red-200 text-sm px-3 py-2 rounded-xl hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="pt-16" style="background:#004d5a">
    <div class="max-w-4xl mx-auto px-4 py-4">
        <nav class="flex items-center gap-2 text-xs flex-wrap" style="color:rgba(255,255,255,0.6)">
            <a href="beranda.php" class="hover:text-white transition">Beranda</a>
            <i class="fa-solid fa-chevron-right text-xs" style="color:rgba(255,255,255,0.3)"></i>
            <a href="buku_detail.php?id=<?= $id ?>" class="hover:text-white transition">
                <?= htmlspecialchars($buku['judul']) ?>
            </a>
            <i class="fa-solid fa-chevron-right text-xs" style="color:rgba(255,255,255,0.3)"></i>
            <span class="text-white">Ajukan Peminjaman</span>
        </nav>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="fade-up grid md:grid-cols-2 gap-8">

        <!-- Kiri: Info Buku -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 h-fit sticky top-24">
            <h3 class="font-bold text-slate-700 text-sm mb-4 flex items-center gap-2">
                <i class="fa-solid fa-book" style="color:#006e82"></i>
                Buku yang Akan Dipinjam
            </h3>
            <div class="flex gap-4 mb-4">
                <?php if ($ada_foto): ?>
                <img src="admin/uploads/foto_buku/<?= $buku['foto'] ?>"
                     class="w-20 h-28 object-cover rounded-xl border border-slate-100 flex-shrink-0 shadow-sm">
                <?php else: ?>
                <div class="w-20 h-28 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:linear-gradient(135deg,#e8f4f8,#d0eaf0)">
                    <i class="fa-solid fa-book text-2xl" style="color:#006e82;opacity:0.4"></i>
                </div>
                <?php endif; ?>
                <div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                          style="background:#e8f8e0;color:#3d7010;border:1px solid #a8d870">
                        <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                    </span>
                    <h4 class="font-bold text-slate-800 text-base leading-tight mt-2">
                        <?= htmlspecialchars($buku['judul']) ?>
                    </h4>
                    <p class="text-sm mt-1" style="color:#006e82"><?= htmlspecialchars($buku['penulis']) ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($buku['penerbit']) ?>, <?= $buku['tahun_terbit'] ?></p>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-4 space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Stok Tersedia</span>
                    <span class="font-semibold" style="color:#6ab023"><?= $buku['stok'] ?> eksemplar</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Denda Keterlambatan</span>
                    <span class="font-semibold text-slate-600">Rp 1.000 / hari</span>
                </div>
            </div>

            <!-- Preview Ringkasan -->
            <div class="mt-4 rounded-2xl p-4" style="background:#f0fafa">
                <p class="text-xs font-semibold text-slate-600 mb-3">Ringkasan Peminjaman</p>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Tanggal Pinjam</span>
                        <span class="font-semibold text-slate-700"><?= date('d/m/Y') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Durasi</span>
                        <span id="previewDurasi" class="font-semibold" style="color:#006e82">7 hari</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Estimasi Kembali</span>
                        <span id="previewKembali" class="font-semibold text-slate-700"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                        <span class="text-slate-400">Denda jika terlambat 1 hr</span>
                        <span class="font-semibold text-orange-500">Rp 1.000</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanan: Form -->
        <div>
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-4">
                <h2 class="font-bold text-slate-800 text-xl mb-1">Formulir Peminjaman</h2>
                <p class="text-slate-400 text-sm mb-6">
                    Sesuaikan durasi peminjaman sesuai kebutuhan Anda
                </p>

                <form method="POST" class="space-y-6">

                    <!-- Durasi Pinjam -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-3">
                            Durasi Peminjaman
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold text-white"
                                  style="background:#006e82" id="labelDurasi">7 hari</span>
                        </label>

                        <!-- Slider -->
                        <input type="range" name="durasi" id="sliderDurasi"
                               min="1" max="30" value="7" step="1"
                               class="w-full h-2 rounded-full outline-none cursor-pointer"
                               oninput="updateDurasi(this.value)">
                        <div class="flex justify-between text-xs text-slate-300 mt-1">
                            <span>1 hari</span>
                            <span>30 hari</span>
                        </div>

                        <!-- Pilihan cepat -->
                        <div class="flex gap-2 mt-3 flex-wrap">
                            <?php foreach ([3,7,14,21,30] as $d): ?>
                            <button type="button" onclick="setDurasi(<?= $d ?>)"
                                    class="durasi-btn px-3 py-1.5 rounded-xl text-xs font-medium border transition
                                           <?= $d===7?'border-teal-500 bg-teal-50 text-teal-700':'border-slate-200 text-slate-500 hover:border-teal-400 hover:text-teal-600' ?>">
                                <?= $d ?> hari
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Catatan Peminjaman
                            <span class="text-slate-400 font-normal text-xs ml-1">(opsional)</span>
                        </label>
                        <textarea name="catatan" rows="4"
                                  placeholder="Contoh: Dibutuhkan untuk tugas akhir, diperlukan sampai akhir bulan, dll."
                                  class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none
                                         text-slate-600 placeholder-slate-300 leading-relaxed"></textarea>
                    </div>

                    <!-- Info persetujuan -->
                    <div class="flex items-start gap-3 p-4 rounded-2xl"
                         style="background:linear-gradient(135deg,#e0f2f8,#e8f8e0)">
                        <i class="fa-solid fa-circle-info text-sm mt-0.5 flex-shrink-0" style="color:#006e82"></i>
                        <div class="text-xs text-slate-600 leading-relaxed">
                            Pengajuan akan dikirim ke petugas perpustakaan untuk diverifikasi.
                            Anda akan mendapat notifikasi status pengajuan di menu
                            <strong>Pengajuan Saya</strong>.
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="flex gap-3">
                        <a href="buku_detail.php?id=<?= $id ?>"
                           class="flex-1 py-3.5 rounded-2xl text-slate-600 font-semibold text-sm border
                                  border-slate-200 hover:bg-slate-50 transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            Kembali
                        </a>
                        <button type="submit"
                                class="flex-1 py-3.5 rounded-2xl text-white font-semibold text-sm transition
                                       hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2"
                                style="background:linear-gradient(135deg,#006e82,#008fa3)">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer style="background:#003d49" class="mt-8">
    <div class="max-w-7xl mx-auto px-4 py-6 text-center">
        <p class="text-xs" style="color:rgba(255,255,255,0.4)">
            &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
        </p>
    </div>
</footer>

<script>
function updateDurasi(val) {
    val = parseInt(val);
    document.getElementById('sliderDurasi').value = val;
    document.getElementById('labelDurasi').textContent = val + ' hari';
    document.getElementById('previewDurasi').textContent = val + ' hari';

    // Estimasi tanggal kembali
    const tgl = new Date();
    tgl.setDate(tgl.getDate() + val);
    document.getElementById('previewKembali').textContent =
        tgl.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'});

    // Update tombol pilihan cepat
    document.querySelectorAll('.durasi-btn').forEach(btn => {
        const d = parseInt(btn.textContent);
        if (d === val) {
            btn.className = 'durasi-btn px-3 py-1.5 rounded-xl text-xs font-medium border transition border-teal-500 bg-teal-50 text-teal-700';
        } else {
            btn.className = 'durasi-btn px-3 py-1.5 rounded-xl text-xs font-medium border transition border-slate-200 text-slate-500 hover:border-teal-400 hover:text-teal-600';
        }
    });
}

function setDurasi(val) {
    updateDurasi(val);
}

// Init
updateDurasi(7);
</script>
</body>
</html>