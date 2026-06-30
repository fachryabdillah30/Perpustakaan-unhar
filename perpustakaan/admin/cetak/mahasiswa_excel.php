<?php
require_once __DIR__ . '/../../includes/auth_check.php';
cekAdmin();
require_once __DIR__ . '/../../config/db.php';

$stmt = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama");
$data = $stmt->fetchAll();

// Header agar browser download sebagai file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="data_mahasiswa_' . date('Ymd') . '.xls"');
?>
<table border="1">
    <thead>
        <tr>
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