<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? '';

// ── UPDATE PROFIL ─────────────────────────────────────────────
if ($aksi === 'update_profil') {
    $nama  = trim($_POST['nama']);
    $email = trim($_POST['email']);

    if (empty($nama) || empty($email)) {
        $_SESSION['error_form'] = 'Nama dan email wajib diisi.';
    } else {
        // Cek email duplikat (kecuali milik sendiri)
        $cek = $pdo->prepare("SELECT id FROM users WHERE email=? AND id != ?");
        $cek->execute([$email, $_SESSION['user_id']]);
        if ($cek->fetch()) {
            $_SESSION['error_form'] = 'Email sudah digunakan akun lain.';
        } else {
            // Upload foto profil
            $foto_lama = $_SESSION['foto'] ?? null;
            $foto_nama = $foto_lama;

            if (!empty($_FILES['foto']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) {
                    $_SESSION['error_form'] = 'Format foto harus JPG, PNG, atau WEBP.';
                    header('Location: profil.php'); exit;
                }
                if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                    $_SESSION['error_form'] = 'Ukuran foto maksimal 2MB.';
                    header('Location: profil.php'); exit;
                }
                // Hapus foto lama
                if ($foto_lama && file_exists(__DIR__ . '/uploads/foto_profil/' . $foto_lama)) {
                    unlink(__DIR__ . '/uploads/foto_profil/' . $foto_lama);
                }
                $foto_nama = 'admin_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'],
                    __DIR__ . '/uploads/foto_profil/' . $foto_nama);
            }

            $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, foto=? WHERE id=?");
            $stmt->execute([$nama, $email, $foto_nama, $_SESSION['user_id']]);

            // Update session
            $_SESSION['nama'] = $nama;
            $_SESSION['foto'] = $foto_nama;
            session_write_close();
            session_start();

            $_SESSION['pesan'] = 'Profil berhasil diperbarui.';
        }
    }
    header('Location: profil.php'); exit;
}

// ── UPDATE PASSWORD ───────────────────────────────────────────
if ($aksi === 'update_password') {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi    = $_POST['konfirmasi'];

    // Ambil password saat ini dari database
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($password_lama, $user['password'])) {
        $_SESSION['error_form'] = 'Password lama tidak sesuai.';
    } elseif (strlen($password_baru) < 6) {
        $_SESSION['error_form'] = 'Password baru minimal 6 karakter.';
    } elseif ($password_baru !== $konfirmasi) {
        $_SESSION['error_form'] = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password_baru, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $_SESSION['user_id']]);
        $_SESSION['pesan'] = 'Password berhasil diperbarui.';
    }
    header('Location: profil.php'); exit;
}

// ── HAPUS FOTO ────────────────────────────────────────────────
if (($_GET['aksi'] ?? '') === 'hapus_foto') {
    $foto = $_SESSION['foto'] ?? null;
    if ($foto && file_exists(__DIR__ . '/uploads/foto_profil/' . $foto)) {
        unlink(__DIR__ . '/uploads/foto_profil/' . $foto);
    }
    $pdo->prepare("UPDATE users SET foto=NULL WHERE id=?")->execute([$_SESSION['user_id']]);
    $_SESSION['foto'] = null;
    $_SESSION['pesan'] = 'Foto profil berhasil dihapus.';
    header('Location: profil.php'); exit;
}

// ── AMBIL DATA USER ───────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Statistik aktivitas admin
$total_mahasiswa  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mahasiswa'")->fetchColumn();
$total_buku       = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$total_peminjaman = $pdo->query("SELECT COUNT(*) FROM peminjaman")->fetchColumn();
$total_terlambat  = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND batas_kembali < CURDATE()")->fetchColumn();

$foto_ada = $user['foto'] && file_exists(__DIR__ . '/uploads/foto_profil/' . $user['foto']);
$foto_src = $foto_ada ? 'uploads/foto_profil/' . $user['foto'] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}
        .menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        .modal-backdrop{backdrop-filter:blur(4px)}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:3px}
        .tab-btn.aktif { background:#2563eb; color:white; }
        .tab-panel { display:none; }
        .tab-panel.aktif { display:block; }
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
                    <h2 class="text-slate-800 font-semibold">Profil Saya</h2>
                    <p class="text-slate-400 text-xs"><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <a href="../auth/logout.php"
               class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-sm px-3 py-1.5 rounded-lg transition">
                <i class="fa-solid fa-right-from-bracket text-xs"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <main class="flex-1 p-4 lg:p-8">

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
        <div id="notif" class="fade-up flex items-center gap-3 bg-green-50 border border-green-200
                               text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            <?= htmlspecialchars($pesan) ?>
            <button onclick="document.getElementById('notif').remove()"
                    class="ml-auto text-green-400 hover:text-green-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div id="notif-err" class="fade-up flex items-center gap-3 bg-red-50 border border-red-200
                                   text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('notif-err').remove()"
                    class="ml-auto text-red-400 hover:text-red-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto">

            <!-- KARTU PROFIL ATAS -->
            <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">

                    <!-- Foto Profil -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="relative">
                            <div id="fotoContainer"
                                 class="w-28 h-28 rounded-full overflow-hidden border-4 border-slate-100 shadow-md bg-blue-100 flex items-center justify-center">
                                <?php if ($foto_src): ?>
                                    <img id="fotoImg" src="<?= $foto_src ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-blue-500 font-bold text-4xl">
                                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <!-- Tombol ganti foto -->
                            <label class="absolute bottom-0 right-0 w-8 h-8 bg-blue-600 hover:bg-blue-700
                                          rounded-full flex items-center justify-center cursor-pointer
                                          shadow-md transition">
                                <i class="fa-solid fa-camera text-white text-xs"></i>
                                <input type="file" id="inputFotoLangsung" accept="image/*"
                                       class="hidden" onchange="uploadFotoLangsung(this)">
                            </label>
                        </div>
                        <?php if ($foto_src): ?>
                        <a href="?aksi=hapus_foto"
                           onclick="return confirm('Hapus foto profil?')"
                           class="text-xs text-red-400 hover:text-red-600 transition">
                            <i class="fa-solid fa-trash mr-1"></i>Hapus Foto
                        </a>
                        <?php endif; ?>
                        <p class="text-slate-400 text-xs text-center">Klik ikon kamera<br>untuk ganti foto</p>
                    </div>

                    <!-- Info Profil -->
                    <div class="flex-1 text-center sm:text-left">
                        <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                            <h3 class="text-slate-800 font-bold text-xl">
                                <?= htmlspecialchars($user['nama']) ?>
                            </h3>
                            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full capitalize">
                                <?= $user['role'] ?>
                            </span>
                        </div>
                        <p class="text-slate-400 text-sm mb-4"><?= htmlspecialchars($user['email']) ?></p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <?php
                            $info_stats = [
                                ['label'=>'Mahasiswa', 'nilai'=>$total_mahasiswa,  'warna'=>'blue'],
                                ['label'=>'Buku',      'nilai'=>$total_buku,       'warna'=>'emerald'],
                                ['label'=>'Peminjaman','nilai'=>$total_peminjaman, 'warna'=>'purple'],
                                ['label'=>'Terlambat', 'nilai'=>$total_terlambat,  'warna'=>'red'],
                            ];
                            foreach ($info_stats as $s):
                            ?>
                            <div class="bg-<?= $s['warna'] ?>-50 rounded-xl p-3 text-center">
                                <p class="text-2xl font-bold text-<?= $s['warna'] ?>-700"><?= $s['nilai'] ?></p>
                                <p class="text-<?= $s['warna'] ?>-500 text-xs mt-0.5"><?= $s['label'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-slate-400 text-xs mt-3">
                            <i class="fa-solid fa-calendar mr-1"></i>
                            Bergabung sejak <?= date('d F Y', strtotime($user['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB PENGATURAN -->
            <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

                <!-- Tab Header -->
                <div class="flex border-b border-slate-100 p-1 gap-1 bg-slate-50">
                    <button onclick="gantiTab('profil')" id="tab-profil"
                            class="tab-btn aktif flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition">
                        <i class="fa-solid fa-user text-xs"></i>
                        Edit Profil
                    </button>
                    <button onclick="gantiTab('password')" id="tab-password"
                            class="tab-btn flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                                   text-slate-500 hover:bg-white transition">
                        <i class="fa-solid fa-lock text-xs"></i>
                        Ubah Password
                    </button>
                    <button onclick="gantiTab('foto')" id="tab-foto"
                            class="tab-btn flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                                   text-slate-500 hover:bg-white transition">
                        <i class="fa-solid fa-image text-xs"></i>
                        Foto Profil
                    </button>
                </div>

                <!-- ═══ TAB EDIT PROFIL ═══ -->
                <div id="panel-profil" class="tab-panel aktif px-6 py-6">
                    <h4 class="text-slate-700 font-semibold mb-4">Informasi Akun</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="aksi" value="update_profil">
                        <input type="file" name="foto" id="fotoHidden" class="hidden">
                        <div class="space-y-4 max-w-md">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="nama"
                                       value="<?= htmlspecialchars($user['nama']) ?>" required
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($user['email']) ?>" required
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                                <input type="text" value="Administrator" disabled
                                       class="w-full border border-slate-100 rounded-lg px-3 py-2.5 text-sm
                                              bg-slate-50 text-slate-400 cursor-not-allowed">
                                <p class="text-slate-400 text-xs mt-1">Role tidak dapat diubah</p>
                            </div>
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg
                                           text-sm font-medium transition">
                                <i class="fa-solid fa-save mr-1.5"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ═══ TAB UBAH PASSWORD ═══ -->
                <div id="panel-password" class="tab-panel px-6 py-6">
                    <h4 class="text-slate-700 font-semibold mb-1">Ubah Password</h4>
                    <p class="text-slate-400 text-sm mb-4">
                        Pastikan password baru minimal 6 karakter dan mudah diingat.
                    </p>
                    <form method="POST" class="space-y-4 max-w-md">
                        <input type="hidden" name="aksi" value="update_password">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Password Lama <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="password_lama" id="passLama" required
                                       placeholder="Masukkan password saat ini"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                                <button type="button" onclick="togglePass('passLama','eyeLama')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i id="eyeLama" class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Password Baru <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="password_baru" id="passBaru" required
                                       placeholder="Min. 6 karakter"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                                       oninput="cekKekuatan(this.value)">
                                <button type="button" onclick="togglePass('passBaru','eyeBaru')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i id="eyeBaru" class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                            <!-- Indikator kekuatan password -->
                            <div class="flex gap-1 mt-2">
                                <div id="k1" class="h-1 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="k2" class="h-1 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="k3" class="h-1 flex-1 rounded bg-slate-200 transition-colors"></div>
                                <div id="k4" class="h-1 flex-1 rounded bg-slate-200 transition-colors"></div>
                            </div>
                            <p id="kekuatanLabel" class="text-xs mt-1 text-slate-400"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Konfirmasi Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" name="konfirmasi" id="passKonfirm" required
                                       placeholder="Ulangi password baru"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                                       oninput="cekKonfirmasi()">
                                <button type="button" onclick="togglePass('passKonfirm','eyeKonfirm')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i id="eyeKonfirm" class="fa-solid fa-eye text-sm"></i>
                                </button>
                            </div>
                            <p id="konfirmasiLabel" class="text-xs mt-1"></p>
                        </div>
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg
                                       text-sm font-medium transition">
                            <i class="fa-solid fa-lock mr-1.5"></i>
                            Perbarui Password
                        </button>
                    </form>
                </div>

                <!-- ═══ TAB FOTO PROFIL ═══ -->
                <div id="panel-foto" class="tab-panel px-6 py-6">
                    <h4 class="text-slate-700 font-semibold mb-1">Foto Profil</h4>
                    <p class="text-slate-400 text-sm mb-6">
                        Upload foto profil Anda. Format JPG, PNG, atau WEBP. Maksimal 2MB.
                    </p>
                    <form method="POST" enctype="multipart/form-data" id="formFoto">
                        <input type="hidden" name="aksi" value="update_profil">
                        <!-- Nama tidak berubah -->
                        <input type="hidden" name="nama" value="<?= htmlspecialchars($user['nama']) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">

                        <div class="flex flex-col items-center gap-4 max-w-xs mx-auto">
                            <!-- Preview -->
                            <div id="previewFoto"
                                 class="w-40 h-40 rounded-full overflow-hidden border-4 border-slate-200
                                        bg-blue-100 flex items-center justify-center shadow-inner">
                                <?php if ($foto_src): ?>
                                    <img src="<?= $foto_src ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-blue-400 text-5xl font-bold">
                                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <label class="cursor-pointer w-full">
                                <div class="flex items-center justify-center gap-2 border-2 border-dashed
                                            border-slate-300 hover:border-blue-400 rounded-xl py-4
                                            text-slate-400 hover:text-blue-500 transition">
                                    <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                                    <span class="text-sm font-medium">Pilih foto baru</span>
                                </div>
                                <input type="file" name="foto" accept="image/*" class="hidden"
                                       onchange="previewFotoTab(this)">
                            </label>

                            <div class="flex gap-2 w-full">
                                <?php if ($foto_src): ?>
                                <a href="?aksi=hapus_foto"
                                   onclick="return confirm('Hapus foto profil?')"
                                   class="flex-1 flex items-center justify-center gap-1.5 border border-red-200
                                          text-red-500 hover:bg-red-50 py-2.5 rounded-lg text-sm transition">
                                    <i class="fa-solid fa-trash text-xs"></i> Hapus Foto
                                </a>
                                <?php endif; ?>
                                <button type="submit"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5
                                               rounded-lg text-sm font-medium transition">
                                    <i class="fa-solid fa-save mr-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>

<!-- Form tersembunyi untuk upload foto langsung dari kartu atas -->
<form id="formFotoLangsung" method="POST" enctype="multipart/form-data" class="hidden">
    <input type="hidden" name="aksi" value="update_profil">
    <input type="hidden" name="nama" value="<?= htmlspecialchars($user['nama']) ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
    <input type="file" name="foto" id="fotoLangsungInput" accept="image/*">
</form>

<script>
// Tab switching
function gantiTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('aktif'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('aktif');
        b.classList.add('text-slate-500');
        b.classList.remove('text-white');
    });
    document.getElementById('panel-' + tab).classList.add('aktif');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('aktif');
    btn.classList.remove('text-slate-500');
}

// Toggle password visibility
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'text'
        ? 'fa-solid fa-eye-slash text-sm'
        : 'fa-solid fa-eye text-sm';
}

// Indikator kekuatan password
function cekKekuatan(val) {
    const bars   = ['k1','k2','k3','k4'];
    const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
    const labels = ['','Lemah','Sedang','Kuat','Sangat Kuat'];
    let skor = 0;
    if (val.length >= 6)  skor++;
    if (val.length >= 10) skor++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) skor++;
    if (/[^A-Za-z0-9]/.test(val)) skor++;

    bars.forEach((id, i) => {
        const el = document.getElementById(id);
        el.className = 'h-1 flex-1 rounded transition-colors ' +
            (i < skor ? colors[skor - 1] : 'bg-slate-200');
    });
    document.getElementById('kekuatanLabel').textContent = labels[skor] || '';
    document.getElementById('kekuatanLabel').className =
        'text-xs mt-1 ' + ['','text-red-500','text-orange-500','text-yellow-600','text-green-600'][skor];
}

// Cek konfirmasi password
function cekKonfirmasi() {
    const baru    = document.getElementById('passBaru').value;
    const konfirm = document.getElementById('passKonfirm').value;
    const label   = document.getElementById('konfirmasiLabel');
    if (konfirm === '') {
        label.textContent = '';
    } else if (baru === konfirm) {
        label.textContent = '✓ Password cocok';
        label.className   = 'text-xs mt-1 text-green-600';
    } else {
        label.textContent = '✗ Password tidak cocok';
        label.className   = 'text-xs mt-1 text-red-500';
    }
}

// Preview foto di tab foto
function previewFotoTab(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('previewFoto');
        prev.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        // Juga update foto di kartu atas
        const container = document.getElementById('fotoContainer');
        container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(input.files[0]);
}

// Upload foto langsung dari klik kamera di kartu atas
function uploadFotoLangsung(input) {
    if (!input.files || !input.files[0]) return;
    // Preview dulu
    const reader = new FileReader();
    reader.onload = e => {
        const container = document.getElementById('fotoContainer');
        container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(input.files[0]);

    // Salin file ke form tersembunyi dan submit
    const dt   = new DataTransfer();
    dt.items.add(input.files[0]);
    document.getElementById('fotoLangsungInput').files = dt.files;
    document.getElementById('formFotoLangsung').submit();
}

// Buka tab dari error jika ada
<?php if ($error && strpos($error, 'password') !== false): ?>
gantiTab('password');
<?php endif; ?>
</script>
</body>
</html>