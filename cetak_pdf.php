<?php
session_start();
require_once "../security.php";

if(!isset($_SESSION['nama']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

include "../koneksi.php";

// Ambil parameter tanggal
$tgl_awal = isset($_GET['awal']) ? $_GET['awal'] : date('Y-m-d');
$tgl_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : date('Y-m-d');

// Ambil data peminjaman berdasarkan rentang tanggal
$query = mysqli_query($koneksi, "
    SELECT p.*, a.nama_alat, a.kode_alat 
    FROM peminjaman p 
    JOIN alat a ON p.id_alat = a.id_alat 
    WHERE DATE(p.tanggal_pinjam) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY p.tanggal_pinjam DESC
");

// Hitung total
$total_peminjaman = mysqli_num_rows($query);
$total_pinjam = 0;
$total_kembali = 0;

$data_peminjaman = [];
while($row = mysqli_fetch_assoc($query)) {
    $data_peminjaman[] = $row;
    if($row['status'] == 'Disetujui' || $row['status'] == 'Dipinjam') {
        $total_pinjam++;
    }
    if($row['status'] == 'Dikembalikan') {
        $total_kembali++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman Alat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #0284c7;
            font-size: 20px;
        }
        .header p {
            color: #64748b;
            font-size: 12px;
        }
        .periode {
            text-align: center;
            margin-bottom: 20px;
            padding: 8px;
            background: #e0f2fe;
            border-radius: 8px;
        }
        .summary {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .summary-box {
            flex: 1;
            background: #f8fafc;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            border-left: 3px solid #0284c7;
        }
        .summary-box .label {
            font-size: 11px;
            color: #64748b;
        }
        .summary-box .value {
            font-size: 18px;
            font-weight: bold;
            color: #0284c7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background: #0284c7;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .status-menunggu {
            color: #92400e;
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .status-dipinjam, .status-disetujui {
            color: #1e40af;
            background: #dbeafe;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .status-kembali, .status-dikembalikan {
            color: #166534;
            background: #dcfce7;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .status-ditolak {
            color: #991b1b;
            background: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-primary {
            background: #0284c7;
            color: white;
        }
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 LAPORAN PEMINJAMAN ALAT</h1>
        <p>Laboratorium</p>
    </div>
    
    <div class="periode">
        <strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)); ?>
    </div>
    
    <div class="summary">
        <div class="summary-box">
            <div class="value"><?= $total_peminjaman; ?></div>
            <div class="label">Total Peminjaman</div>
        </div>
        <div class="summary-box">
            <div class="value"><?= $total_pinjam; ?></div>
            <div class="label">Sedang Dipinjam</div>
        </div>
        <div class="summary-box">
            <div class="value"><?= $total_kembali; ?></div>
            <div class="label">Selesai Dikembalikan</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pinjam</th>
                <th>Peminjam</th>
                <th>Alat</th>
                <th>Kode Alat</th>
                <th>Jumlah</th>
                <th>Tanggal Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($data_peminjaman) > 0): ?>
                <?php $no = 1; foreach($data_peminjaman as $row): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pinjam'])); ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= htmlspecialchars($row['nama_alat']); ?></td>
                    <td><?= htmlspecialchars($row['kode_alat']); ?></td>
                    <td><?= $row['jumlah']; ?></td>
                    <td><?= $row['tanggal_kembali'] ? date('d/m/Y H:i', strtotime($row['tanggal_kembali'])) : '-'; ?></td>
                    <td>
                        <?php 
                        $status = $row['status'];
                        if($status == 'Menunggu'): ?>
                            <span class="status-menunggu">⏳ Menunggu</span>
                        <?php elseif($status == 'Disetujui' || $status == 'Dipinjam'): ?>
                            <span class="status-dipinjam">✅ Dipinjam</span>
                        <?php elseif($status == 'Dikembalikan'): ?>
                            <span class="status-kembali">↩️ Dikembalikan</span>
                        <?php elseif($status == 'Ditolak'): ?>
                            <span class="status-ditolak">❌ Ditolak</span>
                        <?php else: ?>
                            <span><?= $status; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;">Tidak ada data peminjaman</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s'); ?> WIB</p>
        <p>© <?= date('Y'); ?> Peminjaman Alat Laboratorium</p>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak / Simpan PDF</button>
        <button onclick="window.location.href='laporan.php'" class="btn btn-secondary">← Kembali</button>
    </div>
    
    <script>
        // Optional: auto print (uncomment if needed)
        // setTimeout(function() { window.print(); }, 500);
    </script>
</body>
</html>