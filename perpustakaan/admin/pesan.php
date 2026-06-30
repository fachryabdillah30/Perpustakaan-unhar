<?php
require_once __DIR__ . '/../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../config/db.php';

$notif = $_SESSION['pesan']      ?? '';
$error = $_SESSION['error_form'] ?? '';
unset($_SESSION['pesan'], $_SESSION['error_form']);

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// ── HAPUS PESAN ───────────────────────────────────────────────
if ($aksi === 'hapus') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM pesan_kontak WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Pesan berhasil dihapus.';
    header('Location: pesan.php'); exit;
}

// ── HAPUS BALASAN ─────────────────────────────────────────────
if ($aksi === 'hapus_balasan') {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE pesan_kontak SET balasan=NULL, waktu_balas=NULL,
                   status='sudah_dibaca' WHERE id=?")->execute([$id]);
    $_SESSION['pesan'] = 'Balasan berhasil dihapus.';
    header('Location: pesan.php?id='.$id); exit;
}

// ── BALAS PESAN ───────────────────────────────────────────────
if ($aksi === 'balas') {
    $id      = (int)$_POST['id'];
    $balasan = trim($_POST['balasan'] ?? '');
    if (empty($balasan)) {
        $_SESSION['error_form'] = 'Isi balasan tidak boleh kosong.';
        header('Location: pesan.php?id='.$id); exit;
    }
    $pdo->prepare("UPDATE pesan_kontak
                   SET status='dibalas', balasan=?, waktu_balas=NOW(),
                       sudah_dibaca_mahasiswa=0
                   WHERE id=?")->execute([$balasan, $id]);
    $_SESSION['pesan'] = 'Balasan berhasil dikirim.';
    header('Location: pesan.php?id='.$id); exit;
}

// ── KIRIM PESAN KE SATU USER ──────────────────────────────────
if ($aksi === 'kirim_ke_user') {
    $user_id = (int)$_POST['user_id'];
    $subjek  = trim($_POST['subjek'] ?? '');
    $isi     = trim($_POST['pesan']  ?? '');

    if (empty($isi)) {
        $_SESSION['error_form'] = 'Isi pesan tidak boleh kosong.';
        header('Location: pesan.php'); exit;
    }

    // Ambil data user
    $u = $pdo->prepare("SELECT nama, email FROM users WHERE id=?");
    $u->execute([$user_id]);
    $user_target = $u->fetch();

    if ($user_target) {
        // Simpan sebagai pesan dari admin (user_id = target, isi sudah dibalas)
        $ins = $pdo->prepare("INSERT INTO pesan_kontak
            (user_id, nama, email, subjek, pesan, status, balasan, waktu_balas, sudah_dibaca_mahasiswa)
            VALUES (?, ?, ?, ?, '[PESAN DARI ADMIN]', 'dibalas', ?, NOW(), 0)");
        $ins->execute([
            $user_id,
            $user_target['nama'],
            $user_target['email'],
            $subjek ?: 'Pesan dari Admin Perpustakaan',
            $isi
        ]);
        $_SESSION['pesan'] = 'Pesan berhasil dikirim ke ' . $user_target['nama'] . '.';
    }
    header('Location: pesan.php'); exit;
}

// ── KIRIM PESAN KE SEMUA USER ─────────────────────────────────
if ($aksi === 'kirim_ke_semua') {
    $subjek = trim($_POST['subjek'] ?? '');
    $isi    = trim($_POST['pesan']  ?? '');

    if (empty($isi)) {
        $_SESSION['error_form'] = 'Isi pesan tidak boleh kosong.';
        header('Location: pesan.php'); exit;
    }

    $semua_user = $pdo->query("SELECT id, nama, email FROM users WHERE role='mahasiswa'")->fetchAll();
    $jml = 0;
    foreach ($semua_user as $u) {
        $ins = $pdo->prepare("INSERT INTO pesan_kontak
            (user_id, nama, email, subjek, pesan, status, balasan, waktu_balas, sudah_dibaca_mahasiswa)
            VALUES (?, ?, ?, ?, '[PESAN DARI ADMIN]', 'dibalas', ?, NOW(), 0)");
        $ins->execute([
            $u['id'], $u['nama'], $u['email'],
            $subjek ?: 'Pengumuman Perpustakaan',
            $isi
        ]);
        $jml++;
    }
    $_SESSION['pesan'] = "Pesan berhasil dikirim ke $jml mahasiswa.";
    header('Location: pesan.php'); exit;
}

// ── EDIT PESAN (balasan yang sudah dikirim) ───────────────────
if ($aksi === 'edit_balasan') {
    $id         = (int)$_POST['id'];
    $balasan    = trim($_POST['balasan'] ?? '');
    if (empty($balasan)) {
        $_SESSION['error_form'] = 'Isi balasan tidak boleh kosong.';
        header('Location: pesan.php?id='.$id); exit;
    }
    $pdo->prepare("UPDATE pesan_kontak SET balasan=?, waktu_balas=NOW(),
                   sudah_dibaca_mahasiswa=0 WHERE id=?")->execute([$balasan, $id]);
    $_SESSION['pesan'] = 'Balasan berhasil diperbarui.';
    header('Location: pesan.php?id='.$id); exit;
}

// ── AMBIL DETAIL PESAN ────────────────────────────────────────
$pesan_detail = null;
$id_dipilih   = (int)($_GET['id'] ?? 0);
if ($id_dipilih) {
    $stmt = $pdo->prepare("SELECT p.*, u.nama as nama_user, u.nim
                           FROM pesan_kontak p
                           LEFT JOIN users u ON p.user_id=u.id
                           WHERE p.id=?");
    $stmt->execute([$id_dipilih]);
    $pesan_detail = $stmt->fetch();
    if ($pesan_detail && $pesan_detail['status'] === 'belum_dibaca') {
        $pdo->prepare("UPDATE pesan_kontak SET status='sudah_dibaca' WHERE id=?")
            ->execute([$id_dipilih]);
        $pesan_detail['status'] = 'sudah_dibaca';
    }
}

// ── DAFTAR PESAN ──────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'semua';
$where  = '';
if ($filter === 'belum_dibaca') $where = "WHERE p.status='belum_dibaca'";
if ($filter === 'sudah_dibaca') $where = "WHERE p.status='sudah_dibaca'";
if ($filter === 'dibalas')      $where = "WHERE p.status='dibalas'";

$semua_pesan = $pdo->query("
    SELECT p.*, u.nama as nama_user
    FROM pesan_kontak p
    LEFT JOIN users u ON p.user_id=u.id
    $where
    ORDER BY p.created_at DESC
")->fetchAll();

$stat_semua = $pdo->query("SELECT COUNT(*) FROM pesan_kontak")->fetchColumn();
$stat_belum = $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status='belum_dibaca'")->fetchColumn();
$stat_baca  = $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status='sudah_dibaca'")->fetchColumn();
$stat_balas = $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status='dibalas'")->fetchColumn();

// Data mahasiswa untuk form kirim pesan
$semua_mahasiswa = $pdo->query("SELECT id, nama, nim FROM users WHERE role='mahasiswa' ORDER BY nama")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk — Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sidebar-gradient{background:linear-gradient(180deg,#1e3a5f 0%,#0f2744 100%)}
        .menu-item{transition:all .2s ease}.menu-item:hover{transform:translateX(4px)}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeInUp .4s ease forwards}
        ::-webkit-scrollbar{width:5px}
        ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
        .pesan-list{max-height:calc(100vh - 320px);overflow-y:auto}
        .detail-panel{max-height:calc(100vh - 180px);overflow-y:auto}
        .item-aktif{background:#eff6ff;border-right:3px solid #3b82f6}
        .modal-bd{backdrop-filter:blur(6px);background:rgba(0,0,0,0.5)}
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
                    <h2 class="text-slate-800 font-semibold">Pesan Masuk</h2>
                    <p class="text-slate-400 text-xs"><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Tombol Kirim Pesan -->
                <button onclick="bukaModal('modalKirimPesan')"
                        class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white
                               text-sm px-4 py-2 rounded-lg transition shadow-sm">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    Kirim Pesan
                </button>
                <a href="../auth/logout.php"
                   class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600
                          text-sm px-3 py-1.5 rounded-lg transition">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 lg:p-6">

        <!-- Notifikasi -->
        <?php if ($notif): ?>
        <div id="notif" class="fade-up flex items-center gap-3 bg-green-50 border border-green-200
                               text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            <?= htmlspecialchars($notif) ?>
            <button onclick="document.getElementById('notif').remove()"
                    class="ml-auto text-green-400 hover:text-green-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div id="notif-err" class="fade-up flex items-center gap-3 bg-red-50 border border-red-200
                                   text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <?= htmlspecialchars($error) ?>
            <button onclick="document.getElementById('notif-err').remove()"
                    class="ml-auto text-red-400 hover:text-red-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="grid grid-cols-4 gap-3 mb-5">
            <?php
            $stats = [
                ['semua',        'Semua',        $stat_semua, 'fa-inbox',         '#64748b'],
                ['belum_dibaca', 'Belum Dibaca', $stat_belum, 'fa-envelope',      '#006e82'],
                ['sudah_dibaca', 'Dibaca',       $stat_baca,  'fa-envelope-open', '#6ab023'],
                ['dibalas',      'Dibalas',      $stat_balas, 'fa-reply',         '#e07b1a'],
            ];
            foreach ($stats as $s):
                $aktif = $filter === $s[0];
            ?>
            <a href="?filter=<?= $s[0] ?><?= $id_dipilih ? '&id='.$id_dipilih : '' ?>"
               class="bg-white rounded-xl p-3 shadow-sm border transition-all flex items-center gap-3
                      hover:shadow-md <?= $aktif ? 'border-blue-400 bg-blue-50' : 'border-slate-100' ?>">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:<?= $s[4] ?>15">
                    <i class="fa-solid <?= $s[3] ?> text-sm" style="color:<?= $s[4] ?>"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-800 leading-none"><?= $s[2] ?></p>
                    <p class="text-xs text-slate-400 mt-0.5"><?= $s[1] ?></p>
                </div>
                <?php if ($s[0]==='belum_dibaca' && $s[2]>0): ?>
                <span class="ml-auto w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse flex-shrink-0"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Layout 2 Kolom -->
        <div class="grid lg:grid-cols-5 gap-5">

            <!-- Daftar Pesan (Kiri) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <p class="font-semibold text-slate-700 text-sm">
                            <?= count($semua_pesan) ?> pesan
                            <?= $filter !== 'semua' ? '('.str_replace('_',' ',$filter).')' : '' ?>
                        </p>
                        <?php if ($filter !== 'semua'): ?>
                        <a href="pesan.php" class="text-xs text-slate-400 hover:text-slate-600 transition">
                            <i class="fa-solid fa-xmark mr-1"></i>Reset
                        </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($semua_pesan)): ?>
                    <div class="py-12 text-center text-slate-400">
                        <i class="fa-solid fa-inbox text-3xl mb-3 block"></i>
                        <p class="text-sm">Tidak ada pesan</p>
                    </div>
                    <?php else: ?>
                    <ul class="pesan-list divide-y divide-slate-50">
                        <?php foreach ($semua_pesan as $p):
                            $dipilih = $id_dipilih === (int)$p['id'];
                            $belum   = $p['status'] === 'belum_dibaca';
                        ?>
                        <li>
                            <a href="pesan.php?filter=<?= $filter ?>&id=<?= $p['id'] ?>"
                               class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition block
                                      <?= $dipilih ? 'item-aktif' : '' ?>">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0
                                            text-white text-sm font-bold"
                                     style="background:<?= $belum ? '#006e82' : '#94a3b8' ?>">
                                    <?= strtoupper(substr($p['nama'],0,1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <p class="text-slate-700 text-sm truncate
                                                  <?= $belum ? 'font-bold' : 'font-medium' ?>">
                                            <?= htmlspecialchars($p['nama_user'] ?? $p['nama']) ?>
                                        </p>
                                        <?php if ($belum): ?>
                                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <?php elseif ($p['status']==='dibalas'): ?>
                                        <i class="fa-solid fa-reply text-xs text-green-400 flex-shrink-0"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-slate-400 text-xs truncate">
                                        <?= htmlspecialchars($p['subjek'] ?: 'Tidak ada subjek') ?>
                                    </p>
                                    <p class="text-slate-300 text-xs mt-0.5">
                                        <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                                    </p>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Pesan (Kanan) -->
            <div class="lg:col-span-3">
                <?php if ($pesan_detail): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden detail-panel">

                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <h3 class="font-bold text-slate-800 text-base">
                                    <?= htmlspecialchars($pesan_detail['subjek'] ?: 'Tidak ada subjek') ?>
                                </h3>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center
                                                    text-white text-xs font-bold"
                                             style="background:#006e82">
                                            <?= strtoupper(substr($pesan_detail['nama'],0,1)) ?>
                                        </div>
                                        <div>
                                            <p class="text-slate-700 text-sm font-medium">
                                                <?= htmlspecialchars($pesan_detail['nama_user'] ?? $pesan_detail['nama']) ?>
                                                <?php if (!empty($pesan_detail['nim'])): ?>
                                                <span class="text-slate-400 font-normal font-mono text-xs ml-1">
                                                    (<?= $pesan_detail['nim'] ?>)
                                                </span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-slate-400 text-xs"><?= htmlspecialchars($pesan_detail['email']) ?></p>
                                        </div>
                                    </div>
                                    <?php
                                    $sl = ['belum_dibaca'=>'Belum Dibaca','sudah_dibaca'=>'Sudah Dibaca','dibalas'=>'Dibalas'];
                                    $sc = ['belum_dibaca'=>'#e0f2f8|#006e82','sudah_dibaca'=>'#f1f5f9|#64748b','dibalas'=>'#d1fae5|#065f46'];
                                    [$bg,$cl] = explode('|', $sc[$pesan_detail['status']] ?? '#f1f5f9|#64748b');
                                    ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                          style="background:<?= $bg ?>;color:<?= $cl ?>">
                                        <?= $sl[$pesan_detail['status']] ?? '-' ?>
                                    </span>
                                    <p class="text-slate-300 text-xs w-full mt-0.5">
                                        <?= date('d F Y, H:i', strtotime($pesan_detail['created_at'])) ?> WIB
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <!-- Edit pesan (jika ada balasan / pesan dari admin) -->
                                <?php if ($pesan_detail['balasan']): ?>
                                <button onclick="bukaEditBalasan(<?= $pesan_detail['id'] ?>, '<?= htmlspecialchars(addslashes($pesan_detail['balasan'])) ?>')"
                                        title="Edit Balasan"
                                        class="p-2 text-amber-500 hover:bg-amber-50 rounded-lg transition">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Hapus pesan -->
                                <a href="?aksi=hapus&id=<?= $pesan_detail['id'] ?>"
                                   onclick="return confirm('Hapus pesan ini secara permanen?')"
                                   title="Hapus Pesan"
                                   class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Isi Pesan dari Mahasiswa -->
                    <?php if ($pesan_detail['pesan'] !== '[PESAN DARI ADMIN]'): ?>
                    <div class="px-6 py-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                            Pesan dari Mahasiswa
                        </p>
                        <div class="bg-slate-50 rounded-2xl p-4 text-sm text-slate-600 leading-relaxed">
                            <?= nl2br(htmlspecialchars($pesan_detail['pesan'])) ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="px-6 py-4">
                        <div class="inline-flex items-center gap-2 bg-blue-50 text-blue-600
                                    text-xs px-3 py-1.5 rounded-full font-medium">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            Pesan dikirim oleh Admin ke mahasiswa ini
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Balasan / Isi Pesan Admin -->
                    <?php if ($pesan_detail['balasan']): ?>
                    <div class="px-6 pb-4">
                        <div class="rounded-2xl p-4 border" style="background:#f0fafa;border-color:#a5d8e0">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center"
                                         style="background:#006e82">
                                        <i class="fa-solid fa-shield-halved text-white text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold" style="color:#006e82">
                                            Admin Perpustakaan
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            <?= date('d M Y, H:i', strtotime($pesan_detail['waktu_balas'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button onclick="bukaEditBalasan(<?= $pesan_detail['id'] ?>, '<?= htmlspecialchars(addslashes($pesan_detail['balasan'])) ?>')"
                                            class="text-xs text-amber-500 hover:text-amber-700 px-2 py-1
                                                   rounded-lg hover:bg-amber-50 transition flex items-center gap-1">
                                        <i class="fa-solid fa-pen text-xs"></i> Edit
                                    </button>
                                    <a href="?aksi=hapus_balasan&id=<?= $pesan_detail['id'] ?>"
                                       onclick="return confirm('Hapus balasan ini?')"
                                       class="text-xs text-red-400 hover:text-red-600 px-2 py-1
                                              rounded-lg hover:bg-red-50 transition flex items-center gap-1">
                                        <i class="fa-solid fa-trash text-xs"></i> Hapus
                                    </a>
                                </div>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                <?= nl2br(htmlspecialchars($pesan_detail['balasan'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Form Balas -->
                    <?php if ($pesan_detail['pesan'] !== '[PESAN DARI ADMIN]'): ?>
                    <div class="px-6 pb-6 border-t border-slate-100 pt-5">
                        <h4 class="font-semibold text-slate-700 text-sm mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-reply text-xs" style="color:#006e82"></i>
                            <?= $pesan_detail['balasan'] ? 'Balasan Terkirim — Kirim Lagi?' : 'Tulis Balasan' ?>
                        </h4>
                        <form method="POST">
                            <input type="hidden" name="aksi" value="balas">
                            <input type="hidden" name="id" value="<?= $pesan_detail['id'] ?>">
                            <textarea name="balasan" rows="4" required
                                      placeholder="Tulis balasan Anda di sini. Mahasiswa akan melihatnya di halaman Pesan Saya."
                                      class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm
                                             focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none
                                             text-slate-600 placeholder-slate-300 mb-3 leading-relaxed"></textarea>
                            <div class="flex gap-2">
                                <a href="pesan.php?filter=<?= $filter ?>"
                                   class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-500
                                          text-sm hover:bg-slate-50 transition">
                                    Kembali
                                </a>
                                <button type="submit"
                                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white
                                               text-sm font-medium transition hover:shadow-lg hover:-translate-y-0.5"
                                        style="background:linear-gradient(135deg,#006e82,#008fa3)">
                                    <i class="fa-solid fa-paper-plane text-xs"></i>
                                    Kirim Balasan
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>

                <?php else: ?>
                <!-- Placeholder -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col
                            items-center justify-center py-24 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-envelope text-slate-400 text-2xl"></i>
                    </div>
                    <p class="text-slate-500 font-medium">Pilih pesan untuk membacanya</p>
                    <p class="text-slate-400 text-sm mt-1">Klik salah satu pesan di sebelah kiri</p>
                    <button onclick="bukaModal('modalKirimPesan')"
                            class="mt-6 flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm
                                   font-medium transition hover:shadow-lg"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        Kirim Pesan ke Mahasiswa
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <footer class="text-center py-4 text-slate-400 text-xs">
        &copy; <?= date('Y') ?> Perpustakaan Universitas Harapan Medan
    </footer>
</div>

<!-- ══ MODAL KIRIM PESAN ════════════════════════════════════ -->
<div id="modalKirimPesan" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-bd">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Kirim Pesan ke Mahasiswa</h3>
            <button onclick="tutupModal('modalKirimPesan')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5">

            <!-- Toggle: Satu User / Semua User -->
            <div class="flex rounded-xl border border-slate-200 overflow-hidden mb-5">
                <button onclick="setTujuan('satu')" id="btnSatu"
                        class="flex-1 py-2.5 text-sm font-medium transition bg-blue-600 text-white">
                    <i class="fa-solid fa-user mr-1.5 text-xs"></i>
                    Satu Mahasiswa
                </button>
                <button onclick="setTujuan('semua')" id="btnSemua"
                        class="flex-1 py-2.5 text-sm font-medium transition text-slate-500 hover:bg-slate-50">
                    <i class="fa-solid fa-users mr-1.5 text-xs"></i>
                    Semua Mahasiswa
                    <span class="ml-1 bg-slate-200 text-slate-600 text-xs px-1.5 py-0.5 rounded-full">
                        <?= count($semua_mahasiswa) ?>
                    </span>
                </button>
            </div>

            <!-- Form Kirim ke Satu User -->
            <form id="formSatu" method="POST" class="space-y-4">
                <input type="hidden" name="aksi" value="kirim_ke_user">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Pilih Mahasiswa <span class="text-red-500">*</span>
                    </label>
                    <select name="user_id" required
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php foreach ($semua_mahasiswa as $m): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['nama']) ?>
                            <?= $m['nim'] ? '(' . $m['nim'] . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Subjek</label>
                    <input type="text" name="subjek" placeholder="Subjek pesan (opsional)"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Isi Pesan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="pesan" rows="5" required
                              placeholder="Tulis pesan Anda di sini..."
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none
                                     text-slate-600 placeholder-slate-300"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" onclick="tutupModal('modalKirimPesan')"
                            class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl
                                   text-sm hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 text-white py-2.5 rounded-xl text-sm font-medium transition
                                   hover:shadow-lg flex items-center justify-center gap-2"
                            style="background:linear-gradient(135deg,#006e82,#008fa3)">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        Kirim Pesan
                    </button>
                </div>
            </form>

            <!-- Form Kirim ke Semua User -->
            <form id="formSemua" method="POST" class="space-y-4 hidden">
                <input type="hidden" name="aksi" value="kirim_ke_semua">

                <!-- Info -->
                <div class="flex items-start gap-3 p-4 rounded-xl"
                     style="background:#fffbeb;border:1px solid #fcd34d">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="text-amber-800 font-semibold text-sm">Pengiriman ke Semua Mahasiswa</p>
                        <p class="text-amber-700 text-xs mt-0.5 leading-relaxed">
                            Pesan ini akan dikirim ke <strong><?= count($semua_mahasiswa) ?> mahasiswa</strong>
                            yang terdaftar. Pastikan isi pesan sudah benar sebelum mengirim.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Subjek</label>
                    <input type="text" name="subjek" placeholder="Contoh: Pengumuman Perpustakaan"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Isi Pesan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="pesan" rows="5" required
                              placeholder="Tulis isi pengumuman atau pesan untuk semua mahasiswa..."
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none
                                     text-slate-600 placeholder-slate-300"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" onclick="tutupModal('modalKirimPesan')"
                            class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl
                                   text-sm hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            onclick="return confirm('Kirim pesan ke SEMUA mahasiswa? Tindakan ini tidak dapat dibatalkan.')"
                            class="flex-1 text-white py-2.5 rounded-xl text-sm font-medium transition
                                   hover:shadow-lg flex items-center justify-center gap-2"
                            style="background:linear-gradient(135deg,#e07b1a,#d97706)">
                        <i class="fa-solid fa-users text-xs"></i>
                        Kirim ke Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ MODAL EDIT BALASAN ═══════════════════════════════════ -->
<div id="modalEditBalasan" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-bd">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Edit Balasan</h3>
            <button onclick="tutupModal('modalEditBalasan')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" class="px-6 py-5 space-y-4">
            <input type="hidden" name="aksi" value="edit_balasan">
            <input type="hidden" name="id" id="editBalasanId">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Isi Balasan <span class="text-red-500">*</span>
                </label>
                <textarea name="balasan" id="editBalasanIsi" rows="6" required
                          class="w-full border border-slate-200 rounded-2xl px-4 py-3 text-sm
                                 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none
                                 text-slate-600 leading-relaxed"></textarea>
            </div>
            <div class="bg-amber-50 rounded-xl p-3 text-xs text-amber-700">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Mahasiswa akan melihat balasan yang telah diedit. Status akan kembali ke "belum dibaca".
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="tutupModal('modalEditBalasan')"
                        class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl
                               text-sm hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 text-white py-2.5 rounded-xl text-sm font-medium transition
                               hover:shadow-lg flex items-center justify-center gap-2"
                        style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <i class="fa-solid fa-save text-xs"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id) { document.getElementById(id).classList.remove('hidden'); }
function tutupModal(id) { document.getElementById(id).classList.add('hidden'); }

// Toggle form kirim satu/semua
function setTujuan(tipe) {
    const isSatu = tipe === 'satu';
    document.getElementById('formSatu').classList.toggle('hidden', !isSatu);
    document.getElementById('formSemua').classList.toggle('hidden', isSatu);

    const aktifClass   = 'flex-1 py-2.5 text-sm font-medium transition bg-blue-600 text-white';
    const nonAktifClass= 'flex-1 py-2.5 text-sm font-medium transition text-slate-500 hover:bg-slate-50';
    document.getElementById('btnSatu').className  = isSatu  ? aktifClass : nonAktifClass;
    document.getElementById('btnSemua').className = !isSatu ? aktifClass : nonAktifClass;
}

// Buka modal edit balasan
function bukaEditBalasan(id, isi) {
    document.getElementById('editBalasanId').value  = id;
    document.getElementById('editBalasanIsi').value = isi;
    bukaModal('modalEditBalasan');
}

// Tutup modal dengan ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modalKirimPesan','modalEditBalasan'].forEach(tutupModal);
    }
});
</script>
</body>
</html>