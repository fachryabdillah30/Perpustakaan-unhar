<?php
require_once __DIR__ . '/../../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../../config/db.php';

$cari = trim($_GET['cari'] ?? '');
if ($cari) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='mahasiswa'
                           AND (nama LIKE ? OR nim LIKE ?) ORDER BY nama");
    $stmt->execute(["%$cari%","%$cari%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama");
}
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:12px;margin:20px}
        h2{text-align:center;margin-bottom:4px}
        p.sub{text-align:center;color:#666;margin-bottom:16px;font-size:11px}
        table{width:100%;border-collapse:collapse}
        th{background:#1e3a5f;color:white;padding:8px;text-align:left}
        td{padding:7px 8px;border-bottom:1px solid #e5e7eb}
        tr:nth-child(even){background:#f8fafc}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<div class="no-print" style="margin-bottom:16px">
    <button onclick="window.print()"
            style="background:#1e3a5f;color:white;border:none;padding:8px 16px;border-radius:6px;cursor:pointer">
        🖨️ Cetak / Simpan PDF
    </button>
    <a href="../dashboard.php" style="margin-left:8px;color:#64748b;text-decoration:none">← Kembali</a>
</div>
<h2>Data Mahasiswa</h2>
<p class="sub">Perpustakaan Universitas Harapan Medan — <?= date('d F Y') ?></p>
<table>
    <thead>
        <tr><th>No</th><th>NIM</th><th>Nama</th><th>Email</th><th>Terdaftar</th></tr>
    </thead>
    <tbody>
        <?php foreach ($data as $i => $row): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['nim'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p style="text-align:right;margin-top:12px;color:#94a3b8;font-size:10px">
    Total: <?= count($data) ?> mahasiswa
</p>
</body>
</html>