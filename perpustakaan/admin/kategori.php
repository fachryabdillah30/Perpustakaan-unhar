<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan = $_SESSION['pesan'] ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

if ($aksi === 'tambah') {
    $nama = trim($_POST['nama_kategori']);
    if (empty($nama)) {
        $_SESSION['error_form'] = 'Nama kategori wajib diisi.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM kategori WHERE nama_kategori = ?");
        $cek->execute([$nama]);
        if ($cek->fetch()) {
            $_SESSION['error_form'] = 'Kategori sudah ada.';
        } else {
            $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)")->execute([$nama]);
            $_SESSION['pesan'] = 'Kategori berhasil ditambahkan.';
        }
    }
    header('Location: kategori.php'); exit;
}

if ($aksi === 'edit') {
    $id   = (int)$_POST['id'];
    $nama = trim($_POST['nama_kategori']);
    if (!empty($nama)) {
        $pdo->prepare("UPDATE kategori SET nama_kategori=? WHERE id=?")->execute([$nama, $id]);
        $_SESSION['pesan'] = 'Kategori berhasil diperbarui.';
    }
    header('Location: kategori.php'); exit;
}

if ($aksi === 'hapus') {
    $id = (int)$_GET['id'];
    // Cek apakah kategori dipakai buku
    $cek = $pdo->prepare("SELECT COUNT(*) FROM buku WHERE kategori_id=?");
    $cek->execute([$id]);
    if ($cek->fetchColumn() > 0) {
        $_SESSION['error_form'] = 'Kategori tidak bisa dihapus karena masih digunakan oleh buku.';
    } else {
        $pdo->prepare("DELETE FROM kategori WHERE id=?")->execute([$id]);
        $_SESSION['pesan'] = 'Kategori berhasil dihapus.';
    }
    header('Location: kategori.php'); exit;
}

$kategoris = $pdo->query("SELECT k.*, COUNT(b.id) as jumlah_buku
                           FROM kategori k
                           LEFT JOIN buku b ON k.id = b.kategori_id
                           GROUP BY k.id
                           ORDER BY k.nama_kategori")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Buku — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}.menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        .modal-backdrop{backdrop-filter:blur(4px)}
    </style>
</head>
<body class="bg-slate-100 font-sans">
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between px-4 lg:px-8 py-3">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2 class="text-slate-800 font-semibold">Kategori Buku</h2>
                    <p class="text-slate-400 text-xs"><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <a href="../auth/logout.php" class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-sm px-3 py-1.5 rounded-lg transition">
                <i class="fa-solid fa-right-from-bracket text-xs"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>
        </div>
    </header>

    <main class="flex-1 p-4 lg:p-8 max-w-2xl mx-auto w-full">

        <?php if ($pesan): ?>
        <div id="notif" class="fade-up flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-check text-green-500"></i> <?= htmlspecialchars($pesan) ?>
            <button onclick="document.getElementById('notif').remove()" class="ml-auto"><i class="fa-solid fa-xmark text-green-400"></i></button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div id="notif-err" class="fade-up flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i> <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('notif-err').remove()" class="ml-auto"><i class="fa-solid fa-xmark text-red-400"></i></button>
        </div>
        <?php endif; ?>

        <!-- Form Tambah -->
        <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
            <h3 class="font-semibold text-slate-800 mb-4">Tambah Kategori Baru</h3>
            <form method="POST" class="flex gap-2">
                <input type="hidden" name="aksi" value="tambah">
                <input type="text" name="nama_kategori" required placeholder="Contoh: Fiksi Ilmiah, Bisnis, dll..."
                       class="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah
                </button>
            </form>
        </div>

        <!-- Daftar Kategori -->
        <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Daftar Kategori</h3>
                <span class="text-slate-400 text-sm"><?= count($kategoris) ?> kategori</span>
            </div>
            <?php if (empty($kategoris)): ?>
            <div class="py-12 text-center text-slate-400">Belum ada kategori</div>
            <?php else: ?>
            <ul class="divide-y divide-slate-50">
                <?php foreach ($kategoris as $kat): ?>
                <li class="flex items-center justify-between px-6 py-3 hover:bg-slate-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-tag text-blue-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700 text-sm"><?= htmlspecialchars($kat['nama_kategori']) ?></p>
                            <p class="text-slate-400 text-xs"><?= $kat['jumlah_buku'] ?> buku</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button onclick="bukaEdit(<?= $kat['id'] ?>,'<?= addslashes($kat['nama_kategori']) ?>')"
                                class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>
                        <a href="?aksi=hapus&id=<?= $kat['id'] ?>"
                           onclick="return confirm('Hapus kategori <?= addslashes($kat['nama_kategori']) ?>?')"
                           class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="buku.php" class="text-blue-600 hover:text-blue-700 text-sm">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Data Buku
            </a>
        </div>
    </main>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Edit Kategori</h3>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="editNama" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">Batal</button>
                <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium transition">Perbarui</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaEdit(id, nama) {
    document.getElementById('editId').value   = id;
    document.getElementById('editNama').value = nama;
    document.getElementById('modalEdit').classList.remove('hidden');
}
</script>
</body>
</html>