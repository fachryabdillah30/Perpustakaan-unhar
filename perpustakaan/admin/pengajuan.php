<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$pesan = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// ── SETUJUI PENGAJUAN ─────────────────────────────────────────
if ($aksi === 'setujui') {
    $id = (int)$_POST['id'];

    // Ambil data pengajuan
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_peminjaman WHERE id=?");
    $stmt->execute([$id]);
    $pngj = $stmt->fetch();

    if ($pngj && $pngj['status'] === 'menunggu') {
        // Cek stok
        $stok = $pdo->prepare("SELECT stok FROM buku WHERE id=?");
        $stok->execute([$pngj['buku_id']]);
        $buku = $stok->fetch();

        if (!$buku || $buku['stok'] < 1) {
            $_SESSION['error_form'] = 'Stok buku habis, tidak bisa disetujui.';
        } else {
            // Buat peminjaman otomatis
            $tgl_pinjam    = date('Y-m-d');
            $durasi        = (int)($_POST['durasi_hari'] ?? 7);
            $denda_per_hr  = (int)($_POST['denda_per_hari'] ?? 1000);
            $batas_kembali = date('Y-m-d', strtotime("+{$durasi} days"));

            $ins = $pdo->prepare("INSERT INTO peminjaman
                (user_id,buku_id,tanggal_pinjam,batas_kembali,durasi_hari,denda_per_hari,status)
                VALUES (?,?,?,?,?,?,'dipinjam')");
            $ins->execute([
                $pngj['user_id'], $pngj['buku_id'],
                $tgl_pinjam, $batas_kembali,
                $durasi, $denda_per_hr
            ]);

            // Kurangi stok
            $pdo->prepare("UPDATE buku SET stok=stok-1 WHERE id=?")->execute([$pngj['buku_id']]);

            // Update status pengajuan
            $pdo->prepare("UPDATE pengajuan_peminjaman SET status='disetujui' WHERE id=?")
                ->execute([$id]);

            $_SESSION['pesan'] = 'Pengajuan disetujui. Peminjaman berhasil dicatat.';
        }
    }
    header('Location: pengajuan.php'); exit;
}

// ── TOLAK PENGAJUAN ───────────────────────────────────────────
if ($aksi === 'tolak') {
    $id     = (int)$_POST['id'];
    $alasan = trim($_POST['alasan'] ?? '');

    $pdo->prepare("UPDATE pengajuan_peminjaman SET status='ditolak', alasan_tolak=? WHERE id=?")
        ->execute([$alasan, $id]);

    $_SESSION['pesan'] = 'Pengajuan berhasil ditolak.';
    header('Location: pengajuan.php'); exit;
}

// ── AMBIL DATA ────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'menunggu';

$where  = $filter !== 'semua' ? "WHERE pp.status = '$filter'" : '';

$pengajuans = $pdo->query("
    SELECT pp.id, pp.user_id, pp.buku_id, pp.catatan, pp.status,
           COALESCE(pp.alasan_tolak, '') as alasan_tolak,
           pp.created_at,
           u.nama as nama_mahasiswa, u.nim, u.foto as foto_mhs,
           b.judul as judul_buku, b.stok, b.foto as foto_buku
    FROM pengajuan_peminjaman pp
    JOIN users u ON pp.user_id = u.id
    JOIN buku b  ON pp.buku_id = b.id
    $where
    ORDER BY pp.created_at DESC
")->fetchAll();

// Statistik
$stat_menunggu   = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='menunggu'")->fetchColumn();
$stat_disetujui  = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='disetujui'")->fetchColumn();
$stat_ditolak    = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='ditolak'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Peminjaman — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}.menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        .modal-backdrop{backdrop-filter:blur(4px)}
        @keyframes pulse-amber{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.4)}50%{box-shadow:0 0 0 6px rgba(245,158,11,0)}}
        .pulse-amber{animation:pulse-amber 2s infinite}
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
                    <h2 class="text-slate-800 font-semibold">Pengajuan Peminjaman</h2>
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
            <button onclick="document.getElementById('notif').remove()" class="ml-auto">
                <i class="fa-solid fa-xmark text-green-400"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="fade-up flex items-center gap-3 bg-red-50 border border-red-200
                    text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Kartu Statistik -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="fade-up bg-white rounded-2xl p-5 shadow-sm border border-amber-100
                        hover:shadow-md transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-clock text-amber-500 text-sm"></i>
                    </div>
                    <span class="text-slate-400 text-xs">Menunggu</span>
                </div>
                <p class="text-2xl font-bold text-slate-800"><?= $stat_menunggu ?></p>
                <?php if ($stat_menunggu > 0): ?>
                <p class="text-amber-500 text-xs mt-1">Perlu ditindaklanjuti</p>
                <?php endif; ?>
            </div>
            <div class="fade-up bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    </div>
                    <span class="text-slate-400 text-xs">Disetujui</span>
                </div>
                <p class="text-2xl font-bold text-slate-800"><?= $stat_disetujui ?></p>
            </div>
            <div class="fade-up bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-xmark text-red-500 text-sm"></i>
                    </div>
                    <span class="text-slate-400 text-xs">Ditolak</span>
                </div>
                <p class="text-2xl font-bold text-slate-800"><?= $stat_ditolak ?></p>
            </div>
        </div>

        <!-- Filter Tab -->
        <div class="flex items-center gap-1 bg-white rounded-xl p-1 shadow-sm border border-slate-100 mb-4 w-fit">
            <?php
            $tabs = [
                'menunggu'  => 'Menunggu',
                'disetujui' => 'Disetujui',
                'ditolak'   => 'Ditolak',
                'semua'     => 'Semua',
            ];
            foreach ($tabs as $key => $label):
                $aktif = $filter === $key;
            ?>
            <a href="?filter=<?= $key ?>"
               class="px-4 py-1.5 rounded-lg text-xs font-medium transition
                      <?= $aktif ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                <?= $label ?>
                <?php if ($key === 'menunggu' && $stat_menunggu > 0): ?>
                <span class="ml-1 bg-red-500 text-white text-xs px-1 rounded-full">
                    <?= $stat_menunggu ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- DAFTAR PENGAJUAN -->
        <?php if (empty($pengajuans)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col
                    items-center justify-center py-16">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                <i class="fa-solid fa-inbox text-slate-400 text-2xl"></i>
            </div>
            <p class="text-slate-500 font-medium">Tidak ada pengajuan</p>
            <p class="text-slate-400 text-sm mt-1">
                <?= $filter === 'menunggu' ? 'Semua pengajuan sudah ditangani' : 'Belum ada data' ?>
            </p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($pengajuans as $p): ?>
            <div class="fade-up bg-white rounded-2xl shadow-sm border
                        <?= $p['status'] === 'menunggu' ? 'border-amber-200' : 'border-slate-100' ?>
                        p-5 hover:shadow-md transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                    <!-- Info Mahasiswa -->
                    <div class="flex items-center gap-3 lg:w-56 flex-shrink-0">
                        <?php
                        $ada_foto_mhs = $p['foto_mhs'] &&
                            file_exists(__DIR__ . '/uploads/foto_profil/' . $p['foto_mhs']);
                        ?>
                        <?php if ($ada_foto_mhs): ?>
                        <img src="uploads/foto_profil/<?= $p['foto_mhs'] ?>"
                             class="w-10 h-10 rounded-full object-cover border-2 border-slate-100">
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center
                                    justify-center flex-shrink-0">
                            <span class="text-blue-600 font-bold">
                                <?= strtoupper(substr($p['nama_mahasiswa'], 0, 1)) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-semibold text-slate-700 text-sm">
                                <?= htmlspecialchars($p['nama_mahasiswa']) ?>
                            </p>
                            <p class="text-slate-400 text-xs font-mono"><?= $p['nim'] ?? '-' ?></p>
                        </div>
                    </div>

                    <!-- Info Buku -->
                    <div class="flex items-center gap-3 flex-1">
                        <?php
                        $ada_foto_buku = $p['foto_buku'] &&
                            file_exists(__DIR__ . '/uploads/foto_buku/' . $p['foto_buku']);
                        ?>
                        <?php if ($ada_foto_buku): ?>
                        <img src="uploads/foto_buku/<?= $p['foto_buku'] ?>"
                             class="w-10 h-14 object-cover rounded-lg border border-slate-100 flex-shrink-0">
                        <?php else: ?>
                        <div class="w-10 h-14 bg-slate-100 rounded-lg flex items-center
                                    justify-center flex-shrink-0">
                            <i class="fa-solid fa-book text-slate-300 text-sm"></i>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-slate-700 text-sm">
                                <?= htmlspecialchars($p['judul_buku']) ?>
                            </p>
                            <p class="text-xs <?= $p['stok'] > 0 ? 'text-emerald-600' : 'text-red-500' ?> mt-0.5">
                                Stok: <?= $p['stok'] ?> tersedia
                            </p>
                            <?php if ($p['catatan']): ?>
                            <p class="text-slate-400 text-xs mt-1 italic">
                                "<?= htmlspecialchars($p['catatan']) ?>"
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Waktu & Status -->
                    <div class="flex items-center gap-3 lg:flex-col lg:items-end">
                        <p class="text-slate-400 text-xs">
                            <?= date('d M Y H:i', strtotime($p['created_at'])) ?>
                        </p>
                        <?php if ($p['status'] === 'menunggu'): ?>
                        <span class="pulse-amber bg-amber-100 text-amber-700 text-xs font-semibold
                                     px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-clock mr-1"></i>Menunggu
                        </span>
                        <?php elseif ($p['status'] === 'disetujui'): ?>
                        <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold
                                     px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-check mr-1"></i>Disetujui
                        </span>
                        <?php else: ?>
                        <span class="bg-red-100 text-red-700 text-xs font-semibold
                                     px-2.5 py-1 rounded-full">
                            <i class="fa-solid fa-xmark mr-1"></i>Ditolak
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Tombol Aksi (hanya muncul kalau menunggu) -->
                    <?php if ($p['status'] === 'menunggu'): ?>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button onclick="bukaSetujui(<?= $p['id'] ?>, '<?= addslashes($p['nama_mahasiswa']) ?>', '<?= addslashes($p['judul_buku']) ?>')"
                                class="flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600
                                       text-white text-sm px-3 py-2 rounded-lg transition">
                            <i class="fa-solid fa-check text-xs"></i>
                            Setujui
                        </button>
                        <button onclick="bukaTolak(<?= $p['id'] ?>, '<?= addslashes($p['nama_mahasiswa']) ?>', '<?= addslashes($p['judul_buku']) ?>')"
                                class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100
                                       text-red-600 text-sm px-3 py-2 rounded-lg transition border border-red-200">
                            <i class="fa-solid fa-xmark text-xs"></i>
                            Tolak
                        </button>
                    </div>
                    <?php elseif ($p['status'] === 'ditolak' && $p['alasan_tolak']): ?>
                    <div class="lg:max-w-48">
                        <p class="text-xs text-red-400 italic">
                            Alasan: <?= htmlspecialchars($p['alasan_tolak']) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>

    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>

<!-- ══ MODAL SETUJUI ═════════════════════════════════════════ -->
<div id="modalSetujui" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Setujui Pengajuan</h3>
            <button onclick="tutupModal('modalSetujui')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="setujui">
            <input type="hidden" name="id" id="setujuiId">

            <div id="infoSetujui" class="bg-emerald-50 rounded-xl p-4 text-sm space-y-1"></div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Durasi Pinjam (hari)
                    </label>
                    <input type="number" name="durasi_hari" value="7" min="1" max="365"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500"
                           onchange="updateInfoSetujui()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Denda/Hari (Rp)
                    </label>
                    <input type="number" name="denda_per_hari" value="1000" min="0" step="500"
                           class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="bg-blue-50 rounded-xl p-3 text-xs text-blue-600 space-y-1">
                <div class="flex justify-between">
                    <span>Tanggal mulai</span>
                    <strong><?= date('d/m/Y') ?></strong>
                </div>
                <div class="flex justify-between">
                    <span>Batas kembali</span>
                    <strong id="batasKembaliInfo"><?= date('d/m/Y', strtotime('+7 days')) ?></strong>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="tutupModal('modalSetujui')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-check mr-1"></i> Setujui & Catat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL TOLAK ═══════════════════════════════════════════ -->
<div id="modalTolak" class="hidden fixed inset-0 z-50 flex items-center justify-center modal-backdrop bg-black bg-opacity-40 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Tolak Pengajuan</h3>
            <button onclick="tutupModal('modalTolak')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="tolak">
            <input type="hidden" name="id" id="tolakId">

            <div id="infoTolak" class="bg-red-50 rounded-xl p-4 text-sm space-y-1"></div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Alasan Penolakan
                    <span class="text-slate-400 font-normal text-xs">(opsional)</span>
                </label>
                <textarea name="alasan" rows="3" placeholder="Contoh: Stok sedang habis, buku sedang diperbaiki, dll..."
                          class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="tutupModal('modalTolak')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-lg text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-xmark mr-1"></i> Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id){ document.getElementById(id).classList.remove('hidden'); }
function tutupModal(id){ document.getElementById(id).classList.add('hidden'); }

function bukaSetujui(id, nama, judul) {
    document.getElementById('setujuiId').value = id;
    document.getElementById('infoSetujui').innerHTML = `
        <div class="flex justify-between text-emerald-700">
            <span class="text-emerald-500">Mahasiswa</span>
            <strong>${nama}</strong>
        </div>
        <div class="flex justify-between text-emerald-700">
            <span class="text-emerald-500">Buku</span>
            <strong class="text-right max-w-48">${judul}</strong>
        </div>
    `;
    updateInfoSetujui();
    bukaModal('modalSetujui');
}

function bukaTolak(id, nama, judul) {
    document.getElementById('tolakId').value = id;
    document.getElementById('infoTolak').innerHTML = `
        <div class="flex justify-between text-red-700">
            <span class="text-red-400">Mahasiswa</span>
            <strong>${nama}</strong>
        </div>
        <div class="flex justify-between text-red-700">
            <span class="text-red-400">Buku</span>
            <strong class="text-right max-w-48">${judul}</strong>
        </div>
    `;
    bukaModal('modalTolak');
}

function updateInfoSetujui() {
    const durasi = parseInt(document.querySelector('[name="durasi_hari"]').value) || 7;
    const tglKembali = new Date();
    tglKembali.setDate(tglKembali.getDate() + durasi);
    const fmt = d => d.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'});
    document.getElementById('batasKembaliInfo').textContent = fmt(tglKembali);
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modalSetujui','modalTolak'].forEach(tutupModal);
    }
});
</script>
</body>
</html>