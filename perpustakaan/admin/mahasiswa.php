<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan   = $_SESSION['pesan']   ?? '';
$error   = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

// ── PROSES FORM ──────────────────────────────────────────────
$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// TAMBAH
if ($aksi === 'tambah') {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $nim      = trim($_POST['nim']);
    $password = trim($_POST['password']);

    if (empty($nama) || empty($email) || empty($nim) || empty($password)) {
        $_SESSION['error_form'] = 'Semua field wajib diisi.';
    } else {
        // Cek email duplikat
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $_SESSION['error_form'] = 'Email sudah terdaftar.';
        } else {
            $foto_nama = null;
            if (!empty($_FILES['foto']['name'])) {
                $ext       = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed   = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) {
                    $_SESSION['error_form'] = 'Format foto harus JPG, PNG, atau WEBP.';
                    header('Location: mahasiswa.php'); exit;
                }
                $foto_nama = 'mhs_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'],
                    __DIR__ . '/uploads/foto_profil/' . $foto_nama);
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nama,email,password,nim,foto,role)
                                   VALUES (?,?,?,?,?,'mahasiswa')");
            $stmt->execute([$nama, $email, $hash, $nim, $foto_nama]);
            $_SESSION['pesan'] = 'Mahasiswa berhasil ditambahkan.';
        }
    }
    header('Location: mahasiswa.php'); exit;
}

// EDIT
if ($aksi === 'edit') {
    $id   = (int)$_POST['id'];
    $nama = trim($_POST['nama']);
    $nim  = trim($_POST['nim']);
    $email= trim($_POST['email']);

    // Ambil data lama
    $lama = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
    $lama->execute([$id]);
    $foto_lama = $lama->fetchColumn();

    $foto_nama = $foto_lama;
    if (!empty($_FILES['foto']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            // Hapus foto lama
            if ($foto_lama && file_exists(__DIR__ . '/uploads/foto_profil/' . $foto_lama)) {
                unlink(__DIR__ . '/uploads/foto_profil/' . $foto_lama);
            }
            $foto_nama = 'mhs_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'],
                __DIR__ . '/uploads/foto_profil/' . $foto_nama);
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET nama=?,email=?,nim=?,foto=? WHERE id=?");
    $stmt->execute([$nama, $email, $nim, $foto_nama, $id]);

    // Ganti password kalau diisi
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $id]);
    }

    $_SESSION['pesan'] = 'Data mahasiswa berhasil diperbarui.';
    header('Location: mahasiswa.php'); exit;
}

// HAPUS
if ($aksi === 'hapus') {
    $id = (int)$_GET['id'];

    // Cek peminjaman aktif
    $cek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman 
                          WHERE user_id=? AND status='dipinjam'");
    $cek->execute([$id]);
    if ($cek->fetchColumn() > 0) {
        $_SESSION['error_form'] = 'Mahasiswa tidak dapat dihapus karena masih memiliki peminjaman aktif.';
        header('Location: mahasiswa.php'); exit;
    }

    // Hapus foto
    $foto = $pdo->prepare("SELECT foto FROM users WHERE id=?");
    $foto->execute([$id]);
    $foto_file = $foto->fetchColumn();
    if ($foto_file && file_exists(__DIR__ . '/uploads/foto_profil/' . $foto_file)) {
        unlink(__DIR__ . '/uploads/foto_profil/' . $foto_file);
    }

    // Hapus data terkait dulu (cascade tidak semua)
    $pdo->prepare("DELETE FROM pengajuan_peminjaman WHERE user_id=?")->execute([$id]);
    $pdo->prepare("UPDATE pesan_kontak SET user_id=NULL WHERE user_id=?")->execute([$id]);
    
    // Hapus user
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='mahasiswa'")->execute([$id]);
    $_SESSION['pesan'] = 'Mahasiswa berhasil dihapus.';
    header('Location: mahasiswa.php'); exit;
}

// HAPUS FOTO SAJA
if ($aksi === 'hapus_foto') {
    $id   = (int)$_GET['id'];
    $foto = $pdo->prepare("SELECT foto FROM users WHERE id=?");
    $foto->execute([$id]);
    $foto_file = $foto->fetchColumn();
    if ($foto_file && file_exists(__DIR__ . '/uploads/foto_profil/' . $foto_file)) {
        unlink(__DIR__ . '/uploads/foto_profil/' . $foto_file);
    }
    $pdo->prepare("UPDATE users SET foto=NULL WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Foto berhasil dihapus.';
    header('Location: mahasiswa.php'); exit;
}

// ── AMBIL DATA ───────────────────────────────────────────────
$cari = trim($_GET['cari'] ?? '');
if ($cari) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='mahasiswa'
                           AND (nama LIKE ? OR nim LIKE ? OR email LIKE ?)
                           ORDER BY nama");
    $stmt->execute(["%$cari%", "%$cari%", "%$cari%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama");
}
$mahasiswas = $stmt->fetchAll();

// Data untuk modal edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='mahasiswa'");
    $s->execute([(int)$_GET['edit']]);
    $edit_data = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}
        .menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:3px}
        .modal-backdrop{backdrop-filter:blur(4px)}
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
                    <h2 class="text-slate-800 font-semibold">Data Mahasiswa</h2>
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
        <div id="notif" class="fade-up flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            <?= htmlspecialchars($pesan) ?>
            <button onclick="document.getElementById('notif').remove()" class="ml-auto text-green-400 hover:text-green-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div id="notif-err" class="fade-up flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('notif-err').remove()" class="ml-auto text-red-400 hover:text-red-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Header Aksi -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
                <h3 class="text-slate-800 font-bold text-lg">Daftar Mahasiswa</h3>
                <p class="text-slate-400 text-sm"><?= count($mahasiswas) ?> mahasiswa terdaftar</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search -->
                <form method="GET" class="flex items-center gap-2">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                               placeholder="Cari nama / NIM..."
                               class="pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                    </div>
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm px-3 py-2 rounded-lg transition">
                        Cari
                    </button>
                    <?php if ($cari): ?>
                    <a href="mahasiswa.php" class="text-sm text-slate-400 hover:text-slate-600 px-2">✕ Reset</a>
                    <?php endif; ?>
                </form>

                <!-- Cetak -->
                <div class="relative" id="menuCetak">
                    <button onclick="toggleMenu('menuCetak')"
                            class="flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-sm px-3 py-2 rounded-lg transition border border-emerald-200">
                        <i class="fa-solid fa-print text-xs"></i>
                        Cetak
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </button>
                    <div id="menuCetakDropdown" class="hidden absolute right-0 mt-1 bg-white rounded-xl shadow-lg border border-slate-100 py-1 w-40 z-10">
                        <a href="cetak/mahasiswa_pdf.php<?= $cari ? '?cari='.$cari : '' ?>" target="_blank"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-file-pdf text-red-500 w-4"></i> Cetak PDF
                        </a>
                        <a href="cetak/mahasiswa_excel.php<?= $cari ? '?cari='.$cari : '' ?>"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-file-excel text-green-500 w-4"></i> Cetak Excel
                        </a>
                        <a href="cetak/mahasiswa_word.php<?= $cari ? '?cari='.$cari : '' ?>"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-file-word text-blue-500 w-4"></i> Cetak Word
                        </a>
                    </div>
                </div>

                <!-- Tambah -->
                <button onclick="bukaModal('modalTambah')"
                        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Tambah Mahasiswa
                </button>
            </div>
        </div>

        <!-- TABEL -->
        <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <?php if (empty($mahasiswas)): ?>
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-users text-slate-400 text-2xl"></i>
                </div>
                <p class="text-slate-500 font-medium">Belum ada data mahasiswa</p>
                <p class="text-slate-400 text-sm mt-1">Klik "Tambah Mahasiswa" untuk memulai</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-6 py-3">No</th>
                            <th class="text-left px-6 py-3">Foto</th>
                            <th class="text-left px-6 py-3">Nama</th>
                            <th class="text-left px-6 py-3 hidden md:table-cell">NIM</th>
                            <th class="text-left px-6 py-3 hidden lg:table-cell">Email</th>
                            <th class="text-left px-6 py-3 hidden lg:table-cell">Terdaftar</th>
                            <th class="text-left px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($mahasiswas as $i => $mhs): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-400 text-xs"><?= $i+1 ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $foto_src = 'uploads/foto_profil/' . ($mhs['foto'] ?? '');
                                $ada_foto = $mhs['foto'] && file_exists(__DIR__ . '/uploads/foto_profil/' . $mhs['foto']);
                                ?>
                                <?php if ($ada_foto): ?>
                                    <img src="<?= $foto_src ?>"
                                         class="w-10 h-10 rounded-full object-cover border-2 border-slate-100 cursor-pointer"
                                         onclick="lihatFoto('<?= $foto_src ?>', '<?= htmlspecialchars($mhs['nama']) ?>')">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-500 font-bold text-sm">
                                            <?= strtoupper(substr($mhs['nama'], 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">
                                <?= htmlspecialchars($mhs['nama']) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500 hidden md:table-cell">
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-xs font-mono">
                                    <?= htmlspecialchars($mhs['nim'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400 hidden lg:table-cell">
                                <?= htmlspecialchars($mhs['email']) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs hidden lg:table-cell">
                                <?= date('d M Y', strtotime($mhs['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <td class="px-6 py-4">
    <div class="flex items-center gap-1.5">
        <!-- Lihat Detail -->
        <button onclick="lihatDetail(<?= htmlspecialchars(json_encode($mhs)) ?>)"
                class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                title="Lihat Detail">
            <i class="fa-solid fa-eye text-xs"></i>
        </button>
        <!-- Edit -->
        <button onclick="bukaEdit(<?= htmlspecialchars(json_encode($mhs)) ?>)"
                class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition"
                title="Edit">
            <i class="fa-solid fa-pen text-xs"></i>
        </button>
        <!-- Lihat Foto (buka modal foto) -->
        <?php if ($ada_foto): ?>
        <button onclick="lihatFotoMhs('<?= $foto_src ?>','<?= htmlspecialchars(addslashes($mhs['nama'])) ?>')"
                class="p-1.5 text-purple-500 hover:bg-purple-50 rounded-lg transition"
                title="Lihat Foto">
            <i class="fa-solid fa-image text-xs"></i>
        </button>
        <?php endif; ?>
        <!-- Hapus -->
        <button onclick="konfirmasiHapusMhs(<?= $mhs['id'] ?>, '<?= htmlspecialchars(addslashes($mhs['nama'])) ?>')"
                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                title="Hapus">
            <i class="fa-solid fa-trash text-xs"></i>
        </button>
    </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </main>
    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- MODAL TAMBAH -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="modalTambah" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Tambah Mahasiswa</h3>
            <button onclick="tutupModal('modalTambah')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="tambah">

            <!-- Preview Foto -->
            <div class="flex flex-col items-center gap-2">
                <div id="previewTambah" class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border-2 border-slate-200">
                    <i class="fa-solid fa-user text-slate-400 text-2xl"></i>
                </div>
                <label class="cursor-pointer text-blue-600 hover:text-blue-700 text-sm font-medium">
                    <i class="fa-solid fa-camera mr-1"></i> Upload Foto
                    <input type="file" name="foto" accept="image/*" class="hidden" onchange="previewFoto(this,'previewTambah')">
                </label>
                <p class="text-slate-400 text-xs">JPG, PNG, WEBP. Maks 2MB</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama" required placeholder="Contoh: Budi Santoso"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">NIM <span class="text-red-500">*</span></label>
                <input type="text" name="nim" required placeholder="Contoh: 2021001"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="contoh@email.com"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" name="password" id="passT" required placeholder="Min. 6 karakter"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                    <button type="button" onclick="togglePass('passT','eyeT')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i id="eyeT" class="fa-solid fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="tutupModal('modalTambah')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-sm font-medium transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- MODAL EDIT -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Edit Mahasiswa</h3>
            <button onclick="tutupModal('modalEdit')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="editId">

            <!-- Preview Foto Edit -->
            <div class="flex flex-col items-center gap-2">
                <div id="previewEdit" class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border-2 border-slate-200">
                    <i class="fa-solid fa-user text-slate-400 text-2xl" id="editFotoIcon"></i>
                </div>
                <label class="cursor-pointer text-blue-600 hover:text-blue-700 text-sm font-medium">
                    <i class="fa-solid fa-camera mr-1"></i> Ganti Foto
                    <input type="file" name="foto" accept="image/*" class="hidden" onchange="previewFoto(this,'previewEdit')">
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="nama" id="editNama" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">NIM</label>
                <input type="text" name="nim" id="editNim" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" id="editEmail" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Password Baru
                    <span class="text-slate-400 font-normal text-xs">(kosongkan jika tidak diganti)</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="passE" placeholder="Isi untuk mengganti password"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                    <button type="button" onclick="togglePass('passE','eyeE')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i id="eyeE" class="fa-solid fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="tutupModal('modalEdit')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium transition">
                    Perbarui
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- MODAL DETAIL -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Detail Mahasiswa</h3>
            <button onclick="tutupModal('modalDetail')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-6 text-center">
            <div id="detailFotoWrap" class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 overflow-hidden border-4 border-blue-50">
                <span id="detailInisial" class="text-blue-500 font-bold text-2xl"></span>
            </div>
            <p id="detailNama"  class="text-slate-800 font-bold text-lg"></p>
            <p id="detailNim"   class="text-slate-400 text-sm font-mono mt-1"></p>
            <p id="detailEmail" class="text-slate-500 text-sm mt-1"></p>
            <p id="detailTgl"   class="text-slate-400 text-xs mt-2"></p>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════ -->
<!-- MODAL LIHAT FOTO -->
<!-- ══════════════════════════════════════════════════════ -->
<div id="modalFoto" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 p-4"
     onclick="tutupModal('modalFoto')">
    <div class="text-center">
        <img id="fotoFull" src="" class="max-w-xs max-h-96 rounded-2xl object-cover shadow-2xl">
        <p id="fotoNama" class="text-white text-sm mt-3 opacity-70"></p>
    </div>
</div>

<!-- Modal Foto Mahasiswa -->
<div id="modalFoto" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 p-4"
     onclick="tutupModal('modalFoto')">
    <div class="text-center" onclick="event.stopPropagation()">
        <img id="fotoFull" src="" class="max-h-96 max-w-sm rounded-2xl object-cover shadow-2xl">
        <p id="fotoNama" class="text-white text-sm mt-3 opacity-70"></p>
        <button onclick="tutupModal('modalFoto')"
                class="mt-3 px-4 py-2 bg-white bg-opacity-20 text-white text-sm rounded-xl hover:bg-opacity-30 transition">
            <i class="fa-solid fa-xmark mr-1"></i>Tutup
        </button>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Mahasiswa -->
<div id="modalHapusMhs" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5);backdrop-filter:blur(4px)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-lg mb-2">Hapus Mahasiswa?</h3>
        <p class="text-slate-500 text-sm mb-1">Anda akan menghapus akun:</p>
        <p id="namaHapusMhs" class="font-semibold text-slate-700 text-sm mb-4"></p>
        <div class="bg-orange-50 rounded-xl px-3 py-2 mb-5 text-left">
            <p class="text-xs text-orange-600">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Mahasiswa yang masih memiliki peminjaman aktif tidak dapat dihapus.
                Semua pengajuan dan pesan terkait akan ikut terhapus.
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="document.getElementById('modalHapusMhs').classList.add('hidden')"
                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">
                Batal
            </button>
            <a id="linkHapusMhs" href="#"
               class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm
                      font-semibold transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-trash text-xs"></i>
                Ya, Hapus
            </a>
        </div>
    </div>
</div>

<script>
// Modal
function bukaModal(id) { document.getElementById(id).classList.remove('hidden'); }
function tutupModal(id){ document.getElementById(id).classList.add('hidden'); }

// Isi modal edit
function bukaEdit(data) {
    document.getElementById('editId').value    = data.id;
    document.getElementById('editNama').value  = data.nama;
    document.getElementById('editNim').value   = data.nim  || '';
    document.getElementById('editEmail').value = data.email;

    const prev = document.getElementById('previewEdit');
    prev.innerHTML = '';
    if (data.foto) {
        const img = document.createElement('img');
        img.src = 'uploads/foto_profil/' + data.foto;
        img.className = 'w-full h-full object-cover';
        prev.appendChild(img);
    } else {
        prev.innerHTML = '<i class="fa-solid fa-user text-slate-400 text-2xl"></i>';
    }
    bukaModal('modalEdit');
}

// Lihat detail
function lihatDetail(data) {
    document.getElementById('detailNama').textContent  = data.nama;
    document.getElementById('detailNim').textContent   = 'NIM: ' + (data.nim || '-');
    document.getElementById('detailEmail').textContent = data.email;
    document.getElementById('detailTgl').textContent   = 'Terdaftar: ' + data.created_at;

    const wrap = document.getElementById('detailFotoWrap');
    wrap.innerHTML = '';
    if (data.foto) {
        const img = document.createElement('img');
        img.src = 'uploads/foto_profil/' + data.foto;
        img.className = 'w-full h-full object-cover';
        wrap.appendChild(img);
    } else {
        wrap.innerHTML = '<span class="text-blue-500 font-bold text-2xl">' + data.nama.charAt(0).toUpperCase() + '</span>';
    }
    bukaModal('modalDetail');
}

// Lihat foto fullscreen
function lihatFoto(src, nama) {
    document.getElementById('fotoFull').src = src;
    document.getElementById('fotoNama').textContent = nama;
    bukaModal('modalFoto');
}

// Preview foto sebelum upload
function previewFoto(input, targetId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const el = document.getElementById(targetId);
        el.innerHTML = '';
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'w-full h-full object-cover';
        el.appendChild(img);
    };
    reader.readAsDataURL(input.files[0]);
}

// Toggle password visibility
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash text-sm';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye text-sm';
    }
}

function toggleMenu(id) {
    const dropdown = document.getElementById(id + 'Dropdown');
    dropdown.classList.toggle('hidden');
    // Tutup dropdown lain
    document.querySelectorAll('[id$="Dropdown"]').forEach(d => {
        if (d.id !== id + 'Dropdown') d.classList.add('hidden');
    });
}

// Tutup semua dropdown kalau klik di luar
document.addEventListener('click', function(e) {
    if (!e.target.closest('#menuCetak')) {
        document.getElementById('menuCetakDropdown').classList.add('hidden');
    }
});

// Lihat foto mahasiswa di modal
function lihatFotoMhs(src, nama) {
    document.getElementById('fotoFull').src = src;
    document.getElementById('fotoNama').textContent = nama;
    bukaModal('modalFoto');
}

// Konfirmasi hapus mahasiswa
function konfirmasiHapusMhs(id, nama) {
    document.getElementById('namaHapusMhs').textContent = nama;
    document.getElementById('linkHapusMhs').href = '?aksi=hapus&id=' + id;
    document.getElementById('modalHapusMhs').classList.remove('hidden');
}
</script>
</body>
</html>