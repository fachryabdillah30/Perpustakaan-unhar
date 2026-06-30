<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// ── TAMBAH PEMINJAMAN ─────────────────────────────────────────
if ($aksi === 'tambah') {
    $user_id      = (int)$_POST['user_id'];
    $buku_id      = (int)$_POST['buku_id'];
    $tgl_pinjam   = $_POST['tanggal_pinjam'] ?? date('Y-m-d');
    $durasi       = max(1, (int)($_POST['durasi_hari'] ?? 7));
    $denda_per_hr = max(0, (int)($_POST['denda_per_hari'] ?? 1000));

    $stok = $pdo->prepare("SELECT stok FROM buku WHERE id = ?");
    $stok->execute([$buku_id]);
    $buku = $stok->fetch();

    if (!$buku || $buku['stok'] < 1) {
        $_SESSION['error_form'] = 'Stok buku tidak tersedia.';
    } else {
        $cek = $pdo->prepare("SELECT id FROM peminjaman WHERE user_id=? AND buku_id=? AND status='dipinjam'");
        $cek->execute([$user_id, $buku_id]);
        if ($cek->fetch()) {
            $_SESSION['error_form'] = 'Mahasiswa masih meminjam buku yang sama.';
        } else {
            $batas = date('Y-m-d', strtotime($tgl_pinjam . " +{$durasi} days"));
            $stmt  = $pdo->prepare("INSERT INTO peminjaman
                (user_id,buku_id,tanggal_pinjam,batas_kembali,durasi_hari,denda_per_hari,status)
                VALUES (?,?,?,?,?,?,'dipinjam')");
            $stmt->execute([$user_id,$buku_id,$tgl_pinjam,$batas,$durasi,$denda_per_hr]);
            $pdo->prepare("UPDATE buku SET stok=stok-1 WHERE id=?")->execute([$buku_id]);
            $_SESSION['pesan'] = "Peminjaman dicatat. Batas kembali: " . date('d/m/Y', strtotime($batas));
        }
    }
    header('Location: peminjaman.php'); exit;
}

// ── KEMBALIKAN BUKU ───────────────────────────────────────────
if ($aksi === 'kembali') {
    $id = (int)$_POST['id'];

    // Ambil data peminjaman
    $stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id = ?");
    $stmt->execute([$id]);
    $pinjam = $stmt->fetch();

    if ($pinjam && $pinjam['status'] === 'dipinjam') {
        $tgl_kembali   = date('Y-m-d');
        $batas_kembali = $pinjam['batas_kembali'];

        // Hitung denda
        $denda = 0;
        if ($tgl_kembali > $batas_kembali) {
            $selisih = (strtotime($tgl_kembali) - strtotime($batas_kembali)) / 86400;
            $denda   = (int)$selisih * 1000; // Rp1.000/hari
        }

        // Update status peminjaman
        $update = $pdo->prepare("UPDATE peminjaman SET
            status='dikembalikan', tanggal_kembali=?, denda=? WHERE id=?");
        $update->execute([$tgl_kembali, $denda, $id]);

        // Tambah stok buku kembali
        $pdo->prepare("UPDATE buku SET stok = stok + 1 WHERE id = ?")
            ->execute([$pinjam['buku_id']]);

        $pesan_kembali = 'Buku berhasil dikembalikan.';
        if ($denda > 0) {
            $pesan_kembali .= ' Denda: Rp ' . number_format($denda, 0, ',', '.');
        }
        $_SESSION['pesan'] = $pesan_kembali;
    }
    header('Location: peminjaman.php'); exit;
}

// ── HAPUS ─────────────────────────────────────────────────────
if ($aksi === 'hapus') {
    $id   = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id=?");
    $stmt->execute([$id]);
    $pinjam = $stmt->fetch();

    // Kalau masih dipinjam, kembalikan stok dulu
    if ($pinjam && $pinjam['status'] === 'dipinjam') {
        $pdo->prepare("UPDATE buku SET stok = stok + 1 WHERE id=?")
            ->execute([$pinjam['buku_id']]);
    }
    $pdo->prepare("DELETE FROM peminjaman WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Data peminjaman berhasil dihapus.';
    header('Location: peminjaman.php'); exit;
}

// ── AMBIL DATA ────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'semua';
$cari   = trim($_GET['cari'] ?? '');

$where  = [];
$params = [];

if ($filter === 'dipinjam') {
    $where[] = "p.status = 'dipinjam'";
} elseif ($filter === 'dikembalikan') {
    $where[] = "p.status = 'dikembalikan'";
} elseif ($filter === 'terlambat') {
    $where[] = "p.status = 'dipinjam' AND p.batas_kembali < CURDATE()";
}

if ($cari) {
    $where[]  = "(u.nama LIKE ? OR b.judul LIKE ? OR u.nim LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$sql = "SELECT p.*, u.nama as nama_mahasiswa, u.nim,
               b.judul as judul_buku, b.foto as foto_buku,
               CASE
                   WHEN p.status='dipinjam' AND p.batas_kembali < CURDATE()
                   THEN DATEDIFF(CURDATE(), p.batas_kembali)
                   ELSE 0
               END as hari_terlambat
        FROM peminjaman p
        JOIN users u ON p.user_id = u.id
        JOIN buku b  ON p.buku_id = b.id";

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$peminjamans = $stmt->fetchAll();

// Statistik
$stat_dipinjam     = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$stat_dikembalikan = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dikembalikan'")->fetchColumn();
$stat_terlambat    = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND batas_kembali < CURDATE()")->fetchColumn();
$stat_denda        = $pdo->query("SELECT COALESCE(SUM(denda),0) FROM peminjaman")->fetchColumn();

// Data untuk dropdown form tambah
$mahasiswas = $pdo->query("SELECT id,nama,nim FROM users WHERE role='mahasiswa' ORDER BY nama")->fetchAll();
$bukus      = $pdo->query("SELECT id,judul,stok FROM buku WHERE stok > 0 ORDER BY judul")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman — Perpustakaan</title>
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
        @keyframes pulse-red{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4)}50%{box-shadow:0 0 0 6px rgba(239,68,68,0)}}
        .pulse-red{animation:pulse-red 2s infinite}
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
                    <h2 class="text-slate-800 font-semibold">Data Peminjaman</h2>
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
            <button onclick="document.getElementById('notif').remove()" class="ml-auto text-green-400 hover:text-green-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div id="notif-err" class="fade-up flex items-center gap-3 bg-red-50 border border-red-200
                                   text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('notif-err').remove()" class="ml-auto text-red-400 hover:text-red-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- KARTU STATISTIK -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php
            $stats = [
                ['label'=>'Sedang Dipinjam', 'nilai'=>$stat_dipinjam,     'icon'=>'fa-book-open',   'warna'=>'blue'],
                ['label'=>'Dikembalikan',    'nilai'=>$stat_dikembalikan, 'icon'=>'fa-circle-check','warna'=>'emerald'],
                ['label'=>'Terlambat',       'nilai'=>$stat_terlambat,    'icon'=>'fa-clock',       'warna'=>'red'],
                ['label'=>'Total Denda',     'nilai'=>'Rp '.number_format($stat_denda,0,',','.'),
                 'icon'=>'fa-money-bill',    'warna'=>'amber'],
            ];
            foreach ($stats as $s):
            ?>
            <div class="fade-up bg-white rounded-2xl p-4 shadow-sm border border-slate-100
                        hover:shadow-md transition-all duration-200">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 bg-<?= $s['warna'] ?>-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid <?= $s['icon'] ?> text-<?= $s['warna'] ?>-500 text-sm"></i>
                    </div>
                    <span class="text-slate-400 text-xs"><?= $s['label'] ?></span>
                </div>
                <p class="text-2xl font-bold text-slate-800"><?= $s['nilai'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FILTER & AKSI -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <!-- Filter Tab -->
            <div class="flex items-center gap-1 bg-white rounded-xl p-1 shadow-sm border border-slate-100">
                <?php
                $tabs = [
                    'semua'        => ['label'=>'Semua',       'warna'=>'slate'],
                    'dipinjam'     => ['label'=>'Dipinjam',    'warna'=>'blue'],
                    'dikembalikan' => ['label'=>'Dikembalikan','warna'=>'emerald'],
                    'terlambat'    => ['label'=>'Terlambat',   'warna'=>'red'],
                ];
                foreach ($tabs as $key => $tab):
                    $aktif = $filter === $key;
                ?>
                <a href="?filter=<?= $key ?><?= $cari ? '&cari='.$cari : '' ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                          <?= $aktif
                              ? 'bg-blue-600 text-white shadow-sm'
                              : 'text-slate-500 hover:bg-slate-50' ?>">
                    <?= $tab['label'] ?>
                    <?php if ($key === 'terlambat' && $stat_terlambat > 0): ?>
                        <span class="ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
                            <?= $stat_terlambat ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-2">
                <!-- Search -->
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="filter" value="<?= $filter ?>">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                               placeholder="Cari nama / buku..."
                               class="pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 w-44">
                    </div>
                    <?php if ($cari): ?>
                    <a href="?filter=<?= $filter ?>" class="text-sm text-slate-400 hover:text-slate-600">✕</a>
                    <?php endif; ?>
                </form>
                <!-- Tambah -->
                <button onclick="bukaModal('modalTambah')"
                        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white
                               text-sm px-4 py-2 rounded-lg transition shadow-sm whitespace-nowrap">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Catat Peminjaman
                </button>
            </div>
        </div>

        <!-- TABEL -->
        <div class="fade-up bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <?php if (empty($peminjamans)): ?>
            <div class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fa-solid fa-right-left text-slate-400 text-2xl"></i>
                </div>
                <p class="text-slate-500 font-medium">Belum ada data peminjaman</p>
                <p class="text-slate-400 text-sm mt-1">Klik "Catat Peminjaman" untuk memulai</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-3">No</th>
                            <th class="text-left px-4 py-3">Mahasiswa</th>
                            <th class="text-left px-4 py-3 hidden md:table-cell">Buku</th>
                            <th class="text-left px-4 py-3 hidden lg:table-cell">Tgl Pinjam</th>
                            <th class="text-left px-4 py-3">Batas Kembali</th>
                            <th class="text-left px-4 py-3 hidden md:table-cell">Tgl Kembali</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3 hidden lg:table-cell">Denda</th>
                            <th class="text-left px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($peminjamans as $i => $p): ?>
                        <?php
                            $terlambat   = $p['hari_terlambat'] > 0;
                            $dikembalikan = $p['status'] === 'dikembalikan';
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors
                                   <?= $terlambat ? 'bg-red-50 hover:bg-red-50' : '' ?>">
                            <td class="px-4 py-4 text-slate-400 text-xs"><?= $i+1 ?></td>

                            <!-- Mahasiswa -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-blue-600 font-bold text-xs">
                                            <?= strtoupper(substr($p['nama_mahasiswa'],0,1)) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-700 text-xs leading-tight">
                                            <?= htmlspecialchars($p['nama_mahasiswa']) ?>
                                        </p>
                                        <p class="text-slate-400 text-xs font-mono"><?= $p['nim'] ?? '-' ?></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Buku -->
                            <td class="px-4 py-4 hidden md:table-cell">
                                <div class="flex items-center gap-2">
                                    <?php if ($p['foto_buku'] && file_exists(__DIR__.'/uploads/foto_buku/'.$p['foto_buku'])): ?>
                                    <img src="uploads/foto_buku/<?= $p['foto_buku'] ?>"
                                         class="w-8 h-10 object-cover rounded border border-slate-100 flex-shrink-0">
                                    <?php else: ?>
                                    <div class="w-8 h-10 bg-slate-100 rounded flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-book text-slate-300 text-xs"></i>
                                    </div>
                                    <?php endif; ?>
                                    <p class="text-slate-600 text-xs line-clamp-2 max-w-32">
                                        <?= htmlspecialchars($p['judul_buku']) ?>
                                    </p>
                                </div>
                            </td>

                            <!-- Tgl Pinjam -->
                            <td class="px-4 py-4 text-slate-400 text-xs hidden lg:table-cell">
                                <?= date('d/m/Y', strtotime($p['tanggal_pinjam'])) ?>
                            </td>

                            <!-- Batas Kembali -->
                            <td class="px-4 py-4 text-xs">
                                <span class="<?= $terlambat && !$dikembalikan ? 'text-red-600 font-semibold' : 'text-slate-500' ?>">
                                    <?= date('d/m/Y', strtotime($p['batas_kembali'])) ?>
                                </span>
                                <?php if ($terlambat && !$dikembalikan): ?>
                                <p class="text-red-400 text-xs">+<?= $p['hari_terlambat'] ?> hari</p>
                                <?php endif; ?>
                            </td>

                            <!-- Tgl Kembali -->
                            <td class="px-4 py-4 text-slate-400 text-xs hidden md:table-cell">
                                <?= $p['tanggal_kembali']
                                    ? date('d/m/Y', strtotime($p['tanggal_kembali']))
                                    : '<span class="text-slate-300">—</span>' ?>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-4">
                                <?php if ($dikembalikan): ?>
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-check mr-1"></i>Kembali
                                    </span>
                                <?php elseif ($terlambat): ?>
                                    <span class="pulse-red bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-clock mr-1"></i>Terlambat
                                    </span>
                                <?php else: ?>
                                    <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full">
                                        <i class="fa-solid fa-book-open mr-1"></i>Dipinjam
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Denda -->
                            <td class="px-4 py-4 hidden lg:table-cell">
                                <?php
                                $denda_tampil = $p['denda'];
                                if (!$dikembalikan && $terlambat) {
                                    $denda_tampil = $p['hari_terlambat'] * 1000;
                                }
                                ?>
                                <?php if ($denda_tampil > 0): ?>
                                    <span class="text-red-600 font-semibold text-xs">
                                        Rp <?= number_format($denda_tampil, 0, ',', '.') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1">
                                    <!-- Detail -->
                                    <button onclick="lihatDetail(<?= htmlspecialchars(json_encode($p)) ?>)"
                                            class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition"
                                            title="Detail">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                    <!-- Kembalikan -->
                                    <?php if (!$dikembalikan): ?>
                                    <button onclick="konfirmasiKembali(<?= $p['id'] ?>,
                                                '<?= addslashes($p['nama_mahasiswa']) ?>',
                                                '<?= addslashes($p['judul_buku']) ?>',
                                                <?= $p['hari_terlambat'] ?>)"
                                            class="p-1.5 text-emerald-500 hover:bg-emerald-50 rounded-lg transition"
                                            title="Proses Pengembalian">
                                        <i class="fa-solid fa-rotate-left text-xs"></i>
                                    </button>
                                    <?php endif; ?>
                                    <!-- Hapus -->
                                    <a href="?aksi=hapus&id=<?= $p['id'] ?>"
                                       onclick="return confirm('Yakin hapus data peminjaman ini?')"
                                       class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                       title="Hapus">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </a>
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

<!-- ══ MODAL CATAT PEMINJAMAN ════════════════════════════════ -->
<div id="modalTambah" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Catat Peminjaman Baru</h3>
            <button onclick="tutupModal('modalTambah')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="tambah">

            <!-- Search Mahasiswa -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Mahasiswa <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchMahasiswa" placeholder="Ketik nama atau NIM mahasiswa..."
                           autocomplete="off"
                           class="w-full pl-8 pr-3 border border-slate-200 rounded-lg py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="hidden" name="user_id" id="selectedMahasiswaId" required>
                </div>
                <!-- Dropdown hasil search mahasiswa -->
                <div id="dropdownMahasiswa"
                     class="hidden absolute z-50 bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-48 overflow-y-auto w-full max-w-lg">
                </div>
                <p id="selectedMahasiswaInfo" class="text-xs text-blue-600 mt-1 hidden">
                    <i class="fa-solid fa-circle-check mr-1"></i>
                    <span id="selectedMahasiswaLabel"></span>
                </p>
            </div>

            <!-- Search Buku -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Buku <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <i class="fa-solid fa-book absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchBuku" placeholder="Ketik judul buku..."
                           autocomplete="off"
                           class="w-full pl-8 pr-3 border border-slate-200 rounded-lg py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="hidden" name="buku_id" id="selectedBukuId" required>
                </div>
                <!-- Dropdown hasil search buku -->
                <div id="dropdownBuku"
                     class="hidden absolute z-50 bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-48 overflow-y-auto w-full max-w-lg">
                </div>
                <p id="selectedBukuInfo" class="text-xs text-blue-600 mt-1 hidden">
                    <i class="fa-solid fa-circle-check mr-1"></i>
                    <span id="selectedBukuLabel"></span>
                </p>
            </div>

            <!-- Pengaturan Tanggal & Denda -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Tanggal Pinjam
                    </label>
                    <input type="date" name="tanggal_pinjam" id="inputTglPinjam"
                           value="<?= date('Y-m-d') ?>"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onchange="updateInfoPinjam()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Durasi Pinjam (hari)
                    </label>
                    <input type="number" name="durasi_hari" id="inputDurasi"
                           value="7" min="1" max="365"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onchange="updateInfoPinjam()">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Denda per Hari (Rp)
                    </label>
                    <input type="number" name="denda_per_hari" id="inputDenda"
                           value="1000" min="0" step="500"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Info Ringkasan -->
            <div class="bg-blue-50 rounded-xl p-4 text-sm space-y-1.5">
                <div class="flex items-center gap-2 text-blue-700 font-medium mb-2">
                    <i class="fa-solid fa-circle-info text-blue-400"></i>
                    Ringkasan Peminjaman
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-blue-500">Tanggal Pinjam</span>
                    <span id="infTglPinjam" class="font-semibold text-blue-700"><?= date('d/m/Y') ?></span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-blue-500">Batas Kembali</span>
                    <span id="infTglKembali" class="font-semibold text-blue-700"></span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-blue-500">Denda/Hari</span>
                    <span id="infDenda" class="font-semibold text-blue-700">Rp 1.000</span>
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <button type="button" onclick="tutupModal('modalTambah')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-save mr-1"></i> Catat Peminjaman
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL KONFIRMASI KEMBALI ══════════════════════════════ -->
<div id="modalKembali" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Konfirmasi Pengembalian</h3>
            <button onclick="tutupModal('modalKembali')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5">
            <div id="infoKembali" class="bg-slate-50 rounded-xl p-4 mb-4 text-sm space-y-2"></div>
            <div id="dendaInfo" class="hidden bg-red-50 border border-red-100 rounded-xl p-4 mb-4">
                <div class="flex items-center gap-2 text-red-700">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span class="font-semibold text-sm">Ada Denda Keterlambatan!</span>
                </div>
                <p id="dendaJumlah" class="text-red-600 text-xl font-bold mt-1"></p>
                <p id="dendaDetail" class="text-red-400 text-xs mt-0.5"></p>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="kembali">
                <input type="hidden" name="id" id="kembaliId">
                <div class="flex gap-2">
                    <button type="button" onclick="tutupModal('modalKembali')"
                            class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-lg text-sm font-medium transition">
                        <i class="fa-solid fa-rotate-left mr-1"></i>
                        Proses Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL DETAIL ══════════════════════════════════════════ -->
<div id="modalDetail" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Detail Peminjaman</h3>
            <button onclick="tutupModal('modalDetail')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5 space-y-3 text-sm" id="isiDetail"></div>
    </div>
</div>

<script>
function bukaModal(id){ document.getElementById(id).classList.remove('hidden'); }
function tutupModal(id){ document.getElementById(id).classList.add('hidden'); }

function konfirmasiKembali(id, nama, judul, hariTerlambat) {
    document.getElementById('kembaliId').value = id;

    const info = document.getElementById('infoKembali');
    info.innerHTML = `
        <div class="flex justify-between"><span class="text-slate-400">Mahasiswa</span><span class="font-medium text-slate-700">${nama}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Buku</span><span class="font-medium text-slate-700 text-right max-w-48">${judul}</span></div>
        <div class="flex justify-between"><span class="text-slate-400">Tgl Kembali</span><span class="font-medium text-slate-700"><?= date('d/m/Y') ?></span></div>
    `;

    const dendaInfo = document.getElementById('dendaInfo');
    if (hariTerlambat > 0) {
        const denda = hariTerlambat * 1000;
        dendaInfo.classList.remove('hidden');
        document.getElementById('dendaJumlah').textContent =
            'Rp ' + denda.toLocaleString('id-ID');
        document.getElementById('dendaDetail').textContent =
            hariTerlambat + ' hari × Rp 1.000/hari';
    } else {
        dendaInfo.classList.add('hidden');
    }

    bukaModal('modalKembali');
}

function lihatDetail(data) {
    const status = data.status === 'dikembalikan'
        ? '<span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded-full">Dikembalikan</span>'
        : data.hari_terlambat > 0
            ? '<span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">Terlambat</span>'
            : '<span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">Dipinjam</span>';

    const denda = data.denda > 0
        ? '<span class="text-red-600 font-semibold">Rp ' +
          parseInt(data.denda).toLocaleString('id-ID') + '</span>'
        : data.hari_terlambat > 0
            ? '<span class="text-red-500 font-semibold">Rp ' +
              (data.hari_terlambat * 1000).toLocaleString('id-ID') +
              ' (estimasi)</span>'
            : '<span class="text-slate-300">—</span>';

    document.getElementById('isiDetail').innerHTML = `
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Mahasiswa</span>
            <span class="font-medium text-slate-700">${data.nama_mahasiswa}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">NIM</span>
            <span class="text-slate-600 font-mono text-xs">${data.nim || '-'}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Buku</span>
            <span class="text-slate-600 text-right max-w-48">${data.judul_buku}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Tgl Pinjam</span>
            <span class="text-slate-600">${data.tanggal_pinjam}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Batas Kembali</span>
            <span class="text-slate-600">${data.batas_kembali}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Tgl Kembali</span>
            <span class="text-slate-600">${data.tanggal_kembali || '—'}</span>
        </div>
        <div class="flex justify-between py-1 border-b border-slate-50">
            <span class="text-slate-400">Status</span>
            ${status}
        </div>
        <div class="flex justify-between py-1">
            <span class="text-slate-400">Denda</span>
            ${denda}
        </div>
    `;
    bukaModal('modalDetail');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modalTambah','modalKembali','modalDetail'].forEach(tutupModal);
    }
});

// ── DATA untuk search ─────────────────────────────────────────
const dataMahasiswa = <?= json_encode($mahasiswas) ?>;
const dataBuku      = <?= json_encode($bukus) ?>;

// Search Mahasiswa
document.getElementById('searchMahasiswa').addEventListener('input', function() {
    const q   = this.value.toLowerCase();
    const dd  = document.getElementById('dropdownMahasiswa');
    if (!q) { dd.classList.add('hidden'); return; }

    const hasil = dataMahasiswa.filter(m =>
        m.nama.toLowerCase().includes(q) || (m.nim && m.nim.toLowerCase().includes(q))
    );

    if (hasil.length === 0) {
        dd.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400">Tidak ditemukan</div>';
    } else {
        dd.innerHTML = hasil.map(m => `
            <div class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-sm border-b border-slate-50 last:border-0"
                 onclick="pilihMahasiswa(${m.id},'${m.nama.replace(/'/g,"\\'")} (${m.nim || "-"})')">
                <p class="font-medium text-slate-700">${m.nama}</p>
                <p class="text-slate-400 text-xs font-mono">${m.nim || '-'}</p>
            </div>
        `).join('');
    }
    dd.classList.remove('hidden');
});

function pilihMahasiswa(id, label) {
    document.getElementById('selectedMahasiswaId').value = id;
    document.getElementById('searchMahasiswa').value     = label;
    document.getElementById('selectedMahasiswaLabel').textContent = label;
    document.getElementById('selectedMahasiswaInfo').classList.remove('hidden');
    document.getElementById('dropdownMahasiswa').classList.add('hidden');
}

// Search Buku
document.getElementById('searchBuku').addEventListener('input', function() {
    const q  = this.value.toLowerCase();
    const dd = document.getElementById('dropdownBuku');
    if (!q) { dd.classList.add('hidden'); return; }

    const hasil = dataBuku.filter(b => b.judul.toLowerCase().includes(q));

    if (hasil.length === 0) {
        dd.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400">Tidak ditemukan / stok habis</div>';
    } else {
        dd.innerHTML = hasil.map(b => `
            <div class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer text-sm border-b border-slate-50 last:border-0"
                 onclick="pilihBuku(${b.id},'${b.judul.replace(/'/g,"\\'")}',${b.stok})">
                <p class="font-medium text-slate-700">${b.judul}</p>
                <p class="text-slate-400 text-xs">Stok: ${b.stok}</p>
            </div>
        `).join('');
    }
    dd.classList.remove('hidden');
});

function pilihBuku(id, label, stok) {
    document.getElementById('selectedBukuId').value = id;
    document.getElementById('searchBuku').value     = label + ' (Stok: ' + stok + ')';
    document.getElementById('selectedBukuLabel').textContent = label;
    document.getElementById('selectedBukuInfo').classList.remove('hidden');
    document.getElementById('dropdownBuku').classList.add('hidden');
}

// Update info ringkasan
function updateInfoPinjam() {
    const tgl    = document.getElementById('inputTglPinjam').value;
    const durasi = parseInt(document.getElementById('inputDurasi').value) || 7;
    const denda  = parseInt(document.getElementById('inputDenda').value) || 1000;

    if (tgl) {
        const tglPinjam  = new Date(tgl);
        const tglKembali = new Date(tgl);
        tglKembali.setDate(tglKembali.getDate() + durasi);

        const fmt = d => d.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'});
        document.getElementById('infTglPinjam').textContent  = fmt(tglPinjam);
        document.getElementById('infTglKembali').textContent = fmt(tglKembali);
        document.getElementById('infDenda').textContent      = 'Rp ' + denda.toLocaleString('id-ID');
    }
}

// Input denda juga trigger update
document.getElementById('inputDenda').addEventListener('input', updateInfoPinjam);

// Tutup dropdown kalau klik di luar
document.addEventListener('click', e => {
    if (!e.target.closest('#searchMahasiswa') && !e.target.closest('#dropdownMahasiswa')) {
        document.getElementById('dropdownMahasiswa').classList.add('hidden');
    }
    if (!e.target.closest('#searchBuku') && !e.target.closest('#dropdownBuku')) {
        document.getElementById('dropdownBuku').classList.add('hidden');
    }
});

// Init ringkasan saat pertama load
updateInfoPinjam();
</script>
</body>
</html>