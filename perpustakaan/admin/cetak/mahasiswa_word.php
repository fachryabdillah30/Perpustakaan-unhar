<?php
require_once __DIR__ . '/../../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../../config/db.php';

$stmt = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama");
$data = $stmt->fetchAll();

header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="data_mahasiswa_' . date('Ymd') . '.doc"');
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<h2 style="text-align:center">Data Mahasiswa</h2>
<p style="text-align:center">Perpustakaan Universitas Harapan Medan — <?= date('d F Y') ?></p>
<br>
<table border="1" cellpadding="6" width="100%" style="border-collapse:collapse">
    <thead>
        <tr style="background:#1e3a5f;color:white">
            <th>No</th><th>NIM</th><th>Nama</th><th>Email</th><th>Terdaftar</th>
        </tr>
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
</body>
</html>