<?php
session_start();
require_once __DIR__ . '/includes/auth_check.php';
cekMahasiswa();
require_once __DIR__ . '/config/db.php';

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? '';

// Update profil
if ($aksi === 'update_profil') {
    $nama  = trim($_POST['nama']);
    $email = trim($_POST['email']);

    $cek = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $cek->execute([$email, $_SESSION['user_id']]);
    if ($cek->fetch()) {
        $_SESSION['error_form'] = 'Email sudah digunakan.';
    } else {
        $foto_nama = $_SESSION['foto'] ?? null;
        if (!empty($_FILES['foto']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                if ($foto_nama && file_exists(__DIR__ . '/admin/uploads/foto_profil/' . $foto_nama)) {
                    unlink(__DIR__ . '/admin/uploads/foto_profil/' . $foto_nama);
                }
                $foto_nama = 'mhs_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'],
                    __DIR__ . '/admin/uploads/foto_profil/' . $foto_nama);
            }
        }
        $pdo->prepare("UPDATE users SET nama=?,email=?,foto=? WHERE id=?")
            ->execute([$nama,$email,$foto_nama,$_SESSION['user_id']]);
        $_SESSION['nama'] = $nama;
        $_SESSION['foto'] = $foto_nama;
        $_SESSION['pesan'] = 'Profil berhasil diperbarui.';
    }
    header('Location: profil_mahasiswa.php'); exit;
}

// Update password
if ($aksi === 'update_password') {
    $lama    = $_POST['password_lama'];
    $baru    = $_POST['password_baru'];
    $konfirm = $_POST['konfirmasi'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($lama, $user['password'])) {
        $_SESSION['error_form'] = 'Password lama tidak sesuai.';
    } elseif (strlen($baru) < 6) {
        $_SESSION['error_form'] = 'Password baru minimal 6 karakter.';
    } elseif ($baru !== $konfirm) {
        $_SESSION['error_form'] = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($baru, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$_SESSION['user_id']]);
        $_SESSION['pesan'] = 'Password berhasil diperbarui.';
    }
    header('Location: profil_mahasiswa.php'); exit;
}

// Hapus foto
if (($_GET['aksi'] ?? '') === 'hapus_foto') {
    $foto = $_SESSION['foto'] ?? null;
    if ($foto && file_exists(__DIR__ . '/admin/uploads/foto_profil/' . $foto)) {
        unlink(__DIR__ . '/admin/uploads/foto_profil/' . $foto);
    }
    $pdo->prepare("UPDATE users SET foto=NULL WHERE id=?")->execute([$_SESSION['user_id']]);
    $_SESSION['foto']  = null;
    $_SESSION['pesan'] = 'Foto berhasil dihapus.';
    header('Location: profil_mahasiswa.php'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$foto_src = null;
if ($user['foto'] && file_exists(__DIR__ . '/admin/uploads/foto_profil/' . $user['foto'])) {
    $foto_src = 'admin/uploads/foto_profil/' . $user['foto'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya — Perpustakaan UHM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .tab-btn.aktif{background:#006e82;color:white}
        .tab-panel{display:none}
        .tab-panel.aktif{display:block}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
    </style>
</head>
<body class="bg-slate-100 font-sans min-h-screen">

    <!-- Navbar sederhana -->
    <nav style="background:#004d5a" class="shadow-lg">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="beranda.php" class="flex items-center gap-2 text-white">
                <i class="fa-solid fa-arrow-left text-sm"></i>
                <span class="text-sm font-medium">Kembali ke Beranda</span>
            </a>
            <p class="text-white font-bold text-sm">Pengaturan Profil</p>
            <a href="auth/logout.php" class="text-red-300 hover:text-red-200 text-sm">
                <i class="fa-solid fa-right-from-bracket mr-1"></i>Logout
            </a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 py-8">

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
        <div class="fade-up flex items-center gap-3 bg-green-50 border border-green-200
                    text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            <?= htmlspecialchars($pesan) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="fade-up flex items-center gap-3 bg-red-50 border border-red-200
                    text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Kartu Profil -->
        <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
            <div class="flex items-center gap-5">
                <div class="relative">
                    <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-slate-100 bg-blue-100 flex items-center justify-center">
                        <?php if ($foto_src): ?>
                            <img src="<?= $foto_src ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-blue-500 font-bold text-3xl">
                                <?= strtoupper(substr($user['nama'],0,1)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h2 class="text-slate-800 font-bold text-xl"><?= htmlspecialchars($user['nama']) ?></h2>
                    <p class="text-slate-400 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              style="background:#e0f2f8;color:#006e82">Mahasiswa</span>
                        <?php if ($user['nim']): ?>
                        <span class="text-xs text-slate-400 font-mono"><?= $user['nim'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="flex gap-1 p-1 bg-slate-50 border-b border-slate-100">
                <button onclick="gantiTab('profil')" id="tab-profil"
                        class="tab-btn aktif flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition">
                    <i class="fa-solid fa-user text-xs"></i> Edit Profil
                </button>
                <button onclick="gantiTab('password')" id="tab-password"
                        class="tab-btn flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:bg-white transition">
                    <i class="fa-solid fa-lock text-xs"></i> Ubah Password
                </button>
                <button onclick="gantiTab('foto')" id="tab-foto"
                        class="tab-btn flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:bg-white transition">
                    <i class="fa-solid fa-image text-xs"></i> Foto Profil
                </button>
            </div>

            <!-- Panel Profil -->
            <div id="panel-profil" class="tab-panel aktif px-6 py-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="aksi" value="update_profil">
                    <input type="hidden" name="foto" value="">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                               style="--tw-ring-color:#006e82">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">NIM</label>
                        <input type="text" value="<?= htmlspecialchars($user['nim'] ?? '-') ?>" disabled
                               class="w-full border border-slate-100 rounded-xl px-3 py-2.5 text-sm bg-slate-50 text-slate-400">
                        <p class="text-xs text-slate-400 mt-1">NIM tidak dapat diubah</p>
                    </div>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl text-white text-sm font-medium transition hover:shadow-lg"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-save mr-1.5"></i>Simpan
                    </button>
                </form>
            </div>

            <!-- Panel Password -->
            <div id="panel-password" class="tab-panel px-6 py-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="aksi" value="update_password">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password Lama</label>
                        <input type="password" name="password_lama" required placeholder="Password saat ini"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                        <input type="password" name="password_baru" required placeholder="Min. 6 karakter"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
                        <input type="password" name="konfirmasi" required placeholder="Ulangi password baru"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2">
                    </div>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl text-white text-sm font-medium transition hover:shadow-lg"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-lock mr-1.5"></i>Perbarui Password
                    </button>
                </form>
            </div>

            <!-- Panel Foto -->
            <div id="panel-foto" class="tab-panel px-6 py-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="aksi" value="update_profil">
                    <input type="hidden" name="nama" value="<?= htmlspecialchars($user['nama']) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">

                    <div class="flex flex-col items-center gap-4">
                        <div id="previewFoto"
                             class="w-32 h-32 rounded-full overflow-hidden border-4 border-slate-200 bg-blue-100 flex items-center justify-center">
                            <?php if ($foto_src): ?>
                                <img src="<?= $foto_src ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-blue-400 text-4xl font-bold">
                                    <?= strtoupper(substr($user['nama'],0,1)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <label class="cursor-pointer w-full max-w-xs">
                            <div class="flex items-center justify-center gap-2 border-2 border-dashed
                                        border-slate-300 hover:border-teal-500 rounded-xl py-4
                                        text-slate-400 hover:text-teal-600 transition">
                                <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                                <span class="text-sm font-medium">Pilih foto baru</span>
                            </div>
                            <input type="file" name="foto" accept="image/*" class="hidden"
                                   onchange="previewFoto(this)">
                        </label>
                        <div class="flex gap-2 w-full max-w-xs">
                            <?php if ($foto_src): ?>
                            <a href="?aksi=hapus_foto"
                               onclick="return confirm('Hapus foto profil?')"
                               class="flex-1 flex items-center justify-center gap-1.5 border border-red-200
                                      text-red-500 hover:bg-red-50 py-2.5 rounded-xl text-sm transition">
                                <i class="fa-solid fa-trash text-xs"></i> Hapus
                            </a>
                            <?php endif; ?>
                            <button type="submit"
                                    class="flex-1 text-white py-2.5 rounded-xl text-sm font-medium transition hover:shadow-lg"
                                    style="background:linear-gradient(135deg,#006e82,#008fa3)">
                                <i class="fa-solid fa-save mr-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
function gantiTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('aktif'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('aktif');
        b.classList.add('text-slate-500');
    });
    document.getElementById('panel-' + tab).classList.add('aktif');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('aktif');
    btn.classList.remove('text-slate-500');
}
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const el = document.getElementById('previewFoto');
        el.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>