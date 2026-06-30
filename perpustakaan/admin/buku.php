<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// TAMBAH
if ($aksi === 'tambah') {
    $judul       = trim($_POST['judul']);
    $penulis     = trim($_POST['penulis']);
    $penerbit    = trim($_POST['penerbit']);
    $tahun       = trim($_POST['tahun_terbit']);
    $stok        = (int)$_POST['stok'];
    $isbn        = trim($_POST['isbn']);
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $kategori_id = (int)$_POST['kategori_id'];

    if (empty($judul) || empty($penulis) || empty($penerbit)) {
        $_SESSION['error_form'] = 'Judul, penulis, dan penerbit wajib diisi.';
    } else {
        $foto_nama = null;
        if (!empty($_FILES['foto']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $foto_nama = 'buku_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'],
                    __DIR__ . '/uploads/foto_buku/' . $foto_nama);
            }
        }
        $stmt = $pdo->prepare("INSERT INTO buku
            (kategori_id,judul,penulis,penerbit,tahun_terbit,stok,isbn,foto,deskripsi)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$kategori_id,$judul,$penulis,$penerbit,$tahun,$stok,$isbn,$foto_nama,$deskripsi]);
        $_SESSION['pesan'] = 'Buku berhasil ditambahkan.';
    }
    header('Location: buku.php'); exit;
}

// EDIT
if ($aksi === 'edit') {
    $id          = (int)$_POST['id'];
    $judul       = trim($_POST['judul']);
    $penulis     = trim($_POST['penulis']);
    $penerbit    = trim($_POST['penerbit']);
    $tahun       = trim($_POST['tahun_terbit']);
    $stok        = (int)$_POST['stok'];
    $isbn        = trim($_POST['isbn']);
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $kategori_id = (int)$_POST['kategori_id'];

    $lama      = $pdo->prepare("SELECT foto FROM buku WHERE id=?");
    $lama->execute([$id]);
    $foto_lama = $lama->fetchColumn();
    $foto_nama = $foto_lama;

    if (!empty($_FILES['foto']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            if ($foto_lama && file_exists(__DIR__ . '/uploads/foto_buku/' . $foto_lama)) {
                unlink(__DIR__ . '/uploads/foto_buku/' . $foto_lama);
            }
            $foto_nama = 'buku_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'],
                __DIR__ . '/uploads/foto_buku/' . $foto_nama);
        }
    }

    $stmt = $pdo->prepare("UPDATE buku SET
        kategori_id=?,judul=?,penulis=?,penerbit=?,
        tahun_terbit=?,stok=?,isbn=?,foto=?,deskripsi=? WHERE id=?");
    $stmt->execute([$kategori_id,$judul,$penulis,$penerbit,$tahun,$stok,$isbn,$foto_nama,$deskripsi,$id]);
    $_SESSION['pesan'] = 'Data buku berhasil diperbarui.';
    header('Location: buku.php'); exit;
}

// HAPUS
if ($aksi === 'hapus') {
    $id = (int)$_GET['id'];

    // Cek apakah buku sedang dipinjam
    $cek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE buku_id=? AND status='dipinjam'");
    $cek->execute([$id]);
    if ($cek->fetchColumn() > 0) {
        $_SESSION['error_form'] = 'Buku tidak dapat dihapus karena sedang dipinjam.';
        header('Location: buku.php'); exit;
    }

    // Hapus pengajuan terkait dulu
    $pdo->prepare("DELETE FROM pengajuan_peminjaman WHERE buku_id=?")->execute([$id]);

    $foto = $pdo->prepare("SELECT foto FROM buku WHERE id=?");
    $foto->execute([$id]);
    $foto_file = $foto->fetchColumn();
    if ($foto_file && file_exists(__DIR__ . '/uploads/foto_buku/' . $foto_file)) {
        unlink(__DIR__ . '/uploads/foto_buku/' . $foto_file);
    }
    $pdo->prepare("DELETE FROM buku WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Buku berhasil dihapus.';
    header('Location: buku.php'); exit;
}

// HAPUS FOTO
if ($aksi === 'hapus_foto') {
    $id = (int)$_GET['id'];
    $foto = $pdo->prepare("SELECT foto FROM buku WHERE id=?");
    $foto->execute([$id]);
    $foto_file = $foto->fetchColumn();
    if ($foto_file && file_exists(__DIR__ . '/uploads/foto_buku/' . $foto_file)) {
        unlink(__DIR__ . '/uploads/foto_buku/' . $foto_file);
    }
    $pdo->prepare("UPDATE buku SET foto=NULL WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Foto buku berhasil dihapus.';
    header('Location: buku.php'); exit;
}

// AMBIL DATA
$cari = trim($_GET['cari'] ?? '');
if ($cari) {
    $stmt = $pdo->prepare("SELECT b.*, k.nama_kategori FROM buku b
                           LEFT JOIN kategori k ON b.kategori_id = k.id
                           WHERE b.judul LIKE ? OR b.penulis LIKE ? OR k.nama_kategori LIKE ?
                           ORDER BY b.judul");
    $stmt->execute(["%$cari%","%$cari%","%$cari%"]);
} else {
    $stmt = $pdo->query("SELECT b.*, k.nama_kategori FROM buku b
                         LEFT JOIN kategori k ON b.kategori_id = k.id
                         ORDER BY b.judul");
}
$bukus     = $stmt->fetchAll();
$kategoris = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Buku — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}.menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        .modal-bd{backdrop-filter:blur(4px)}
        ::-webkit-scrollbar{width:6px}
        ::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:3px}
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
                    <h2 class="text-slate-800 font-semibold">Data Buku</h2>
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
                <h3 class="text-slate-800 font-bold text-lg">Koleksi Buku</h3>
                <p class="text-slate-400 text-sm"><?= count($bukus) ?> buku tersedia</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                               placeholder="Cari judul / penulis..."
                               class="pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                    </div>
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm px-3 py-2 rounded-lg transition">Cari</button>
                    <?php if ($cari): ?>
                    <a href="buku.php" class="text-sm text-slate-400 hover:text-slate-600">✕</a>
                    <?php endif; ?>
                </form>
                <a href="kategori.php"
                   class="flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm px-3 py-2 rounded-lg transition">
                    <i class="fa-solid fa-tags text-xs"></i>
                    Kelola Kategori
                </a>
                <button onclick="bukaModal('modalTambah')"
                        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Tambah Buku
                </button>
            </div>
        </div>

        <?php if (empty($bukus)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center justify-center py-16">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-book text-slate-400 text-2xl"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada data buku</p>
            <p class="text-slate-400 text-sm mt-1">Klik "Tambah Buku" untuk memulai</p>
        </div>
        <?php else: ?>

        <!-- View Toggle -->
        <div class="flex justify-end mb-3 gap-2">
            <button onclick="setView('grid')" id="btnGrid"
                    class="p-2 rounded-lg bg-blue-600 text-white transition" title="Grid View">
                <i class="fa-solid fa-grip text-sm"></i>
            </button>
            <button onclick="setView('list')" id="btnList"
                    class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition" title="List View">
                <i class="fa-solid fa-list text-sm"></i>
            </button>
        </div>

        <!-- GRID VIEW -->
        <div id="viewGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <?php foreach ($bukus as $buku):
                $ada_foto = $buku['foto'] && file_exists(__DIR__ . '/uploads/foto_buku/' . $buku['foto']);
            ?>
            <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden
                        hover:shadow-md hover:-translate-y-1 transition-all duration-200 flex flex-col">
                <!-- Foto -->
                <div class="relative bg-slate-100 h-48 flex items-center justify-center overflow-hidden">
                    <?php if ($ada_foto): ?>
                        <img src="uploads/foto_buku/<?= $buku['foto'] ?>"
                             class="w-full h-full object-cover hover:scale-105 transition-transform duration-300 cursor-pointer"
                             onclick="lihatFoto('uploads/foto_buku/<?= $buku['foto'] ?>', '<?= htmlspecialchars(addslashes($buku['judul'])) ?>')">
                    <?php else: ?>
                        <div class="flex flex-col items-center gap-2 text-slate-300">
                            <i class="fa-solid fa-book text-4xl"></i>
                            <span class="text-xs">No Image</span>
                        </div>
                    <?php endif; ?>
                    <div class="absolute top-2 right-2">
                        <span class="<?= $buku['stok'] > 0 ? 'bg-green-500' : 'bg-red-500' ?> text-white text-xs px-2 py-0.5 rounded-full font-medium">
                            Stok: <?= $buku['stok'] ?>
                        </span>
                    </div>
                </div>
                <!-- Info -->
                <div class="p-3 flex-1 flex flex-col">
                    <span class="text-xs text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full w-fit mb-1">
                        <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                    </span>
                    <h4 class="text-slate-800 font-semibold text-sm leading-tight line-clamp-2 mb-1 flex-1">
                        <?= htmlspecialchars($buku['judul']) ?>
                    </h4>
                    <p class="text-slate-400 text-xs"><?= htmlspecialchars($buku['penulis']) ?></p>
                    <p class="text-slate-300 text-xs mt-0.5"><?= $buku['tahun_terbit'] ?></p>

                    <!-- Aksi Grid — 4 tombol terpisah jelas -->
                    <div class="grid grid-cols-4 gap-1 mt-3 pt-3 border-t border-slate-100">
                        <!-- Lihat Detail -->
                        <button onclick="bukaDetail(<?= htmlspecialchars(json_encode($buku)) ?>)"
                                title="Lihat Detail"
                                class="flex items-center justify-center py-2 rounded-lg text-blue-600 hover:bg-blue-50 transition">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </button>
                        <!-- Edit -->
                        <button onclick="bukaEdit(<?= htmlspecialchars(json_encode($buku)) ?>)"
                                title="Edit Buku"
                                class="flex items-center justify-center py-2 rounded-lg text-amber-600 hover:bg-amber-50 transition">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </button>
                        <!-- Lihat Foto -->
<?php if ($ada_foto): ?>
<button onclick="lihatFoto('uploads/foto_buku/<?= $buku['foto'] ?>', '<?= htmlspecialchars(addslashes($buku['judul'])) ?>')"
        title="Lihat Foto Buku"
        class="flex items-center justify-center py-2 rounded-lg text-purple-600 hover:bg-purple-50 transition">
    <i class="fa-solid fa-image text-sm"></i>
</button>
                        <?php else: ?>
                        <div class="flex items-center justify-center py-2 opacity-20" title="Tidak ada foto">
                            <i class="fa-solid fa-image text-sm text-slate-400"></i>
                        </div>
                        <?php endif; ?>
                        <!-- Hapus Buku -->
                        <button onclick="konfirmasiHapusBuku(<?= $buku['id'] ?>, '<?= htmlspecialchars(addslashes($buku['judul'])) ?>')"
                                title="Hapus Buku"
                                class="flex items-center justify-center py-2 rounded-lg text-red-600 hover:bg-red-50 transition">
                            <i class="fa-solid fa-trash text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- LIST VIEW -->
        <div id="viewList" class="hidden bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-6 py-3">No</th>
                            <th class="text-left px-6 py-3">Foto</th>
                            <th class="text-left px-6 py-3">Judul</th>
                            <th class="text-left px-6 py-3 hidden md:table-cell">Penulis</th>
                            <th class="text-left px-6 py-3 hidden lg:table-cell">Kategori</th>
                            <th class="text-left px-6 py-3 hidden lg:table-cell">Tahun</th>
                            <th class="text-left px-6 py-3">Stok</th>
                            <th class="text-left px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($bukus as $i => $buku):
                            $ada_foto = $buku['foto'] && file_exists(__DIR__ . '/uploads/foto_buku/' . $buku['foto']);
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-slate-400 text-xs"><?= $i+1 ?></td>
                            <td class="px-6 py-4">
                                <?php if ($ada_foto): ?>
                                    <img src="uploads/foto_buku/<?= $buku['foto'] ?>"
                                         class="w-10 h-14 object-cover rounded-lg cursor-pointer border border-slate-100"
                                         onclick="lihatFoto('uploads/foto_buku/<?= $buku['foto'] ?>','<?= htmlspecialchars(addslashes($buku['judul'])) ?>')">
                                <?php else: ?>
                                    <div class="w-10 h-14 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-book text-slate-300 text-sm"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700 max-w-xs">
                                <?= htmlspecialchars($buku['judul']) ?>
                                <?php if ($buku['isbn']): ?>
                                <p class="text-xs text-slate-400 font-mono"><?= $buku['isbn'] ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-500 hidden md:table-cell"><?= htmlspecialchars($buku['penulis']) ?></td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <span class="bg-blue-50 text-blue-600 text-xs px-2 py-0.5 rounded-full">
                                    <?= htmlspecialchars($buku['nama_kategori'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400 hidden lg:table-cell"><?= $buku['tahun_terbit'] ?></td>
                            <td class="px-6 py-4">
                                <span class="<?= $buku['stok'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?> text-xs font-semibold px-2.5 py-1 rounded-full">
                                    <?= $buku['stok'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <button onclick="bukaDetail(<?= htmlspecialchars(json_encode($buku)) ?>)"
                                            title="Lihat Detail"
                                            class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                    <button onclick="bukaEdit(<?= htmlspecialchars(json_encode($buku)) ?>)"
                                            title="Edit"
                                            class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                   <?php if ($ada_foto): ?>
<button onclick="lihatFoto('uploads/foto_buku/<?= $buku['foto'] ?>', '<?= htmlspecialchars(addslashes($buku['judul'])) ?>')"
        title="Lihat Foto Buku"
        class="p-1.5 text-purple-500 hover:bg-purple-50 rounded-lg transition">
    <i class="fa-solid fa-image text-xs"></i>
</button>
                                    <?php endif; ?>
                                    <button onclick="konfirmasiHapusBuku(<?= $buku['id'] ?>, '<?= htmlspecialchars(addslashes($buku['judul'])) ?>')"
                                            title="Hapus Buku"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>

<!-- MODAL TAMBAH -->
<div id="modalTambah" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-bd bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Tambah Buku</h3>
            <button onclick="bukaModal('modalTambah')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="tambah">

            <div class="flex flex-col items-center gap-2">
                <div id="previewTambah"
                     class="w-28 h-36 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden
                            border-2 border-dashed border-slate-300 cursor-pointer"
                     onclick="document.getElementById('fotoTambah').click()">
                    <div class="text-center text-slate-300">
                        <i class="fa-solid fa-image text-3xl mb-1"></i>
                        <p class="text-xs">Klik untuk upload</p>
                    </div>
                </div>
                <input type="file" id="fotoTambah" name="foto" accept="image/*" class="hidden"
                       onchange="previewFoto(this,'previewTambah')">
                <p class="text-slate-400 text-xs">JPG, PNG, WEBP. Maks 2MB</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Judul Buku <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" required placeholder="Judul buku..."
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Penulis <span class="text-red-500">*</span></label>
                    <input type="text" name="penulis" required placeholder="Nama penulis"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Penerbit <span class="text-red-500">*</span></label>
                    <input type="text" name="penerbit" required placeholder="Nama penerbit"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" placeholder="2024" min="1900" max="2099"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Stok</label>
                    <input type="number" name="stok" value="1" min="0"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ISBN</label>
                    <input type="text" name="isbn" placeholder="978-xxx-xxx-xxx"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                    <select name="kategori_id"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($kategoris as $kat): ?>
                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Sinopsis</label>
                    <textarea name="deskripsi" rows="3"
                              placeholder="Tulis sinopsis atau deskripsi singkat buku..."
                              class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
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

<!-- MODAL EDIT -->
<div id="modalEdit" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-bd bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Edit Buku</h3>
            <button onclick="tutupModal('modalEdit')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="editId">

            <div class="flex flex-col items-center gap-2">
                <div id="previewEdit"
                     class="w-28 h-36 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden
                            border-2 border-dashed border-slate-300 cursor-pointer"
                     onclick="document.getElementById('fotoEdit').click()">
                </div>
                <input type="file" id="fotoEdit" name="foto" accept="image/*" class="hidden"
                       onchange="previewFoto(this,'previewEdit')">
                <p class="text-slate-400 text-xs">Klik gambar untuk ganti foto</p>
<?php // Tombol hapus foto di modal edit — diisi via JavaScript ?>
<div id="tombolHapusFotoEdit" class="hidden">
    <a id="linkHapusFotoEdit" href="#"
       onclick="return confirm('Hapus foto buku ini?')"
       class="text-xs text-red-400 hover:text-red-600 flex items-center gap-1 transition">
        <i class="fa-solid fa-trash text-xs"></i>
        Hapus Foto
    </a>
</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Judul Buku</label>
                    <input type="text" name="judul" id="editJudul" required
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Penulis</label>
                    <input type="text" name="penulis" id="editPenulis"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Penerbit</label>
                    <input type="text" name="penerbit" id="editPenerbit"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" id="editTahun"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Stok</label>
                    <input type="number" name="stok" id="editStok" min="0"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ISBN</label>
                    <input type="text" name="isbn" id="editIsbn"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                    <select name="kategori_id" id="editKategori"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($kategoris as $kat): ?>
                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Sinopsis</label>
                    <textarea name="deskripsi" id="editDeskripsi" rows="3"
                              placeholder="Tulis sinopsis atau deskripsi singkat buku..."
                              class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
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

<!-- MODAL DETAIL -->
<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-bd bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Detail Buku</h3>
            <button onclick="tutupModal('modalDetail')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5">
            <div class="flex justify-center mb-4">
                <div id="detailFoto" class="w-28 h-36 rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center border border-slate-200">
                    <i class="fa-solid fa-book text-slate-300 text-3xl"></i>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Judul</span>
                    <span id="dJudul" class="font-medium text-slate-700 text-right max-w-xs"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Penulis</span>
                    <span id="dPenulis" class="text-slate-600"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Penerbit</span>
                    <span id="dPenerbit" class="text-slate-600"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Kategori</span>
                    <span id="dKategori" class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full text-xs"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Tahun</span>
                    <span id="dTahun" class="text-slate-600"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">ISBN</span>
                    <span id="dIsbn" class="text-slate-600 font-mono text-xs"></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Stok</span>
                    <span id="dStok" class="font-bold"></span>
                </div>
                <div class="py-1">
                    <p class="text-slate-400 mb-1">Deskripsi</p>
                    <p id="dDeskripsi" class="text-slate-600 text-xs leading-relaxed"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FOTO FULLSCREEN -->
<div id="modalFoto" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-85 p-4"
     onclick="tutupModal('modalFoto')">
    <div class="text-center" onclick="event.stopPropagation()">
        <img id="fotoFull" src="" class="max-h-[85vh] max-w-4xl rounded-2xl object-contain shadow-2xl">
        <p id="fotoNama" class="text-white text-sm mt-3 opacity-70"></p>
        <button onclick="tutupModal('modalFoto')"
                class="mt-3 px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-sm rounded-xl transition">
            <i class="fa-solid fa-xmark mr-1"></i>Tutup
        </button>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div id="modalHapusBuku" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5);backdrop-filter:blur(4px)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-lg mb-2">Hapus Buku?</h3>
        <p class="text-slate-500 text-sm mb-1">Anda akan menghapus buku:</p>
        <p id="namaHapusBuku" class="font-semibold text-slate-700 text-sm mb-4 px-2"></p>
        <div class="bg-orange-50 rounded-xl px-3 py-2 mb-5 text-left">
            <p class="text-xs text-orange-600">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Buku yang sedang dipinjam tidak dapat dihapus.
                Semua pengajuan terkait buku ini akan ikut terhapus.
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="document.getElementById('modalHapusBuku').classList.add('hidden')"
                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">
                Batal
            </button>
            <a id="linkHapusBuku" href="#"
               class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm
                      font-semibold transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-trash text-xs"></i>
                Ya, Hapus
            </a>
        </div>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.remove('hidden'); }
function tutupModal(id) { document.getElementById(id).classList.add('hidden'); }

function bukaEdit(data) {
    document.getElementById('editId').value        = data.id;
    document.getElementById('editJudul').value     = data.judul;
    document.getElementById('editPenulis').value   = data.penulis;
    document.getElementById('editPenerbit').value  = data.penerbit;
    document.getElementById('editTahun').value     = data.tahun_terbit;
    document.getElementById('editStok').value      = data.stok;
    document.getElementById('editIsbn').value      = data.isbn || '';
    document.getElementById('editKategori').value  = data.kategori_id;
    document.getElementById('editDeskripsi').value = data.deskripsi || '';

    const prev = document.getElementById('previewEdit');
    if (data.foto) {
        prev.innerHTML = `<img src="uploads/foto_buku/${data.foto}" class="w-full h-full object-cover">`;
    } else {
        prev.innerHTML = '<div class="text-center text-slate-300"><i class="fa-solid fa-image text-3xl mb-1"></i><p class="text-xs">Klik untuk upload</p></div>';
    }
    // Tampilkan tombol hapus foto jika ada foto
const tombolHapus = document.getElementById('tombolHapusFotoEdit');
const linkHapus   = document.getElementById('linkHapusFotoEdit');
if (data.foto) {
    linkHapus.href = '?aksi=hapus_foto&id=' + data.id;
    tombolHapus.classList.remove('hidden');
} else {
    tombolHapus.classList.add('hidden');
}
    bukaModal('modalEdit');
}

function bukaDetail(data) {
    document.getElementById('dJudul').textContent    = data.judul;
    document.getElementById('dPenulis').textContent  = data.penulis;
    document.getElementById('dPenerbit').textContent = data.penerbit;
    document.getElementById('dKategori').textContent = data.nama_kategori || '-';
    document.getElementById('dTahun').textContent    = data.tahun_terbit;
    document.getElementById('dIsbn').textContent     = data.isbn || '-';
    document.getElementById('dDeskripsi').textContent = data.deskripsi || 'Belum ada deskripsi.';

    const stokEl = document.getElementById('dStok');
    stokEl.textContent = data.stok + ' buku';
    stokEl.className   = data.stok > 0 ? 'font-bold text-green-600' : 'font-bold text-red-500';

    const fotoEl = document.getElementById('detailFoto');
    fotoEl.innerHTML = data.foto
        ? `<img src="uploads/foto_buku/${data.foto}" class="w-full h-full object-cover">`
        : '<i class="fa-solid fa-book text-slate-300 text-3xl"></i>';

    bukaModal('modalDetail');
}

function lihatFoto(src, nama) {
    document.getElementById('fotoFull').src = src;
    document.getElementById('fotoNama').textContent = nama;
    bukaModal('modalFoto');
}

function previewFoto(input, targetId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById(targetId).innerHTML =
            `<img src="${e.target.result}" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(input.files[0]);
}

function konfirmasiHapusBuku(id, nama) {
    document.getElementById('namaHapusBuku').textContent = nama;
    document.getElementById('linkHapusBuku').href = '?aksi=hapus&id=' + id;
    document.getElementById('modalHapusBuku').classList.remove('hidden');
}

function setView(mode) {
    const isGrid = mode === 'grid';
    document.getElementById('viewGrid').classList.toggle('hidden', !isGrid);
    document.getElementById('viewList').classList.toggle('hidden', isGrid);
    document.getElementById('btnGrid').className = isGrid
        ? 'p-2 rounded-lg bg-blue-600 text-white transition'
        : 'p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition';
    document.getElementById('btnList').className = !isGrid
        ? 'p-2 rounded-lg bg-blue-600 text-white transition'
        : 'p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 transition';
    localStorage.setItem('bukuView', mode);
}

window.addEventListener('load', () => {
    if (localStorage.getItem('bukuView') === 'list') setView('list');
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modalTambah','modalEdit','modalDetail','modalFoto','modalHapusBuku'].forEach(tutupModal);
    }
});
</script>
</body>
</html>