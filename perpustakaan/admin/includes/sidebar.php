<?php
$halaman_aktif = basename($_SERVER['PHP_SELF']);
$foto_sesi     = isset($_SESSION['foto']) ? $_SESSION['foto'] : null;

// Ambil jumlah pengajuan menunggu
$jml_menunggu = 0;
try {
    if (isset($pdo)) {
        $q = $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='menunggu'");
        $jml_menunggu = (int)$q->fetchColumn();
    }
} catch (Exception $e) { $jml_menunggu = 0; }

// Ambil jumlah pesan belum dibaca
$jml_pesan_baru = 0;
try {
    if (isset($pdo)) {
        $q2 = $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status='belum_dibaca'");
        $jml_pesan_baru = (int)$q2->fetchColumn();
    }
} catch (Exception $e) { $jml_pesan_baru = 0; }

// Daftar menu
$menus = array(
    array('file'=>'dashboard.php',  'icon'=>'fa-gauge-high',  'label'=>'Dashboard',       'badge'=>0),
    array('file'=>'mahasiswa.php',  'icon'=>'fa-users',       'label'=>'Data Mahasiswa',  'badge'=>0),
    array('file'=>'buku.php',       'icon'=>'fa-book',        'label'=>'Data Buku',       'badge'=>0),
    array('file'=>'peminjaman.php', 'icon'=>'fa-right-left',  'label'=>'Data Peminjaman', 'badge'=>0),
    array('file'=>'pengajuan.php',  'icon'=>'fa-inbox',       'label'=>'Pengajuan',       'badge'=>$jml_menunggu),
    array('file'=>'pesan.php',      'icon'=>'fa-envelope',    'label'=>'Pesan Masuk',     'badge'=>$jml_pesan_baru),
    array('file'=>'profil.php',     'icon'=>'fa-circle-user', 'label'=>'Profil Saya',     'badge'=>0),
);

// Cek foto admin
$ada_foto = false;
if ($foto_sesi) {
    $path_foto = __DIR__ . '/../uploads/foto_profil/' . $foto_sesi;
    if (file_exists($path_foto)) {
        $ada_foto = true;
    }
}
?>
<style>
    #sidebar { transition: transform .3s ease; }
    .menu-item { transition: all .2s ease; }
    .menu-item:hover { transform: translateX(4px); }
    .sidebar-gradient { background: linear-gradient(180deg, #1e3a5f 0%, #0f2744 100%); }
</style>

<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden"
     onclick="toggleSidebar()"></div>

<aside id="sidebar"
       class="sidebar-gradient fixed top-0 left-0 h-full w-64 z-30 flex flex-col
              -translate-x-full lg:translate-x-0 shadow-2xl">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-white border-opacity-10">
        <div class="bg-blue-400 bg-opacity-20 p-2 rounded-xl flex-shrink-0">
            <i class="fa-solid fa-book-open text-blue-300 text-xl"></i>
        </div>
        <div>
            <h1 class="text-white font-bold text-sm leading-tight">Perpustakaan</h1>
            <p class="text-blue-300 text-xs leading-tight">Universitas Harapan Medan</p>
        </div>
    </div>

    <!-- Info Admin -->
    <div class="px-4 py-4 border-b border-white border-opacity-10">
        <div class="flex items-center gap-3">
            <?php if ($ada_foto): ?>
                <img src="uploads/foto_profil/<?php echo $foto_sesi; ?>"
                     class="w-9 h-9 rounded-full object-cover border-2 border-blue-300 flex-shrink-0">
            <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-blue-400 bg-opacity-30
                            flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-user text-blue-200 text-sm"></i>
                </div>
            <?php endif; ?>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium leading-tight truncate">
                    <?php echo htmlspecialchars($_SESSION['nama']); ?>
                </p>
                <p class="text-blue-300 text-xs">Administrator</p>
            </div>
        </div>
    </div>

    <!-- Navigasi -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <p class="text-blue-400 text-xs font-semibold uppercase tracking-wider px-2 mb-2">
            Menu Utama
        </p>

        <?php foreach ($menus as $menu):
            $aktif = ($halaman_aktif === $menu['file'])
                ? 'bg-blue-500 bg-opacity-25 text-white'
                : 'text-blue-200 hover:bg-white hover:bg-opacity-10 hover:text-white';
        ?>
        <a href="<?php echo $menu['file']; ?>"
           class="menu-item flex items-center gap-3 px-3 py-2.5 rounded-xl
                  text-sm font-medium mb-0.5 <?php echo $aktif; ?>">
            <i class="fa-solid <?php echo $menu['icon']; ?> w-4 text-center"></i>
            <span class="flex-1"><?php echo $menu['label']; ?></span>
            <?php if ($menu['badge'] > 0): ?>
            <span class="bg-red-500 text-white text-xs font-bold px-1.5 py-0.5
                         rounded-full min-w-5 text-center">
                <?php echo $menu['badge']; ?>
            </span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <div class="pt-3 mt-3 border-t border-white border-opacity-10">
            <a href="../auth/logout.php"
               class="menu-item flex items-center gap-3 px-3 py-2.5 rounded-xl
                      text-red-300 text-sm hover:bg-red-500 hover:bg-opacity-20 hover:text-red-200">
                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="px-6 py-3 border-t border-white border-opacity-10">
        <p class="text-blue-400 text-xs text-center">
            v1.0.0 &mdash; <?php echo date('Y'); ?>
        </p>
    </div>
</aside>

<script>
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');
    var isOpen  = !sidebar.classList.contains('-translate-x-full');
    if (isOpen) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
}
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        document.getElementById('overlay').classList.add('hidden');
    }
});
</script>