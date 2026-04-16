<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("location:../login.php");
    exit;
}

// Proses AJAX untuk Setujui / Tolak (tanpa loading lama)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $id = intval($_POST['id']);
    $aksi = $_POST['aksi'];
    $response = ['success' => false, 'message' => ''];
    
    if ($aksi == 'acc') {
        // Ambil data peminjaman
        $query_pinjam = mysqli_query($koneksi, "SELECT id_alat, jumlah FROM peminjaman WHERE id='$id'");
        $data_pinjam = mysqli_fetch_assoc($query_pinjam);
        
        if ($data_pinjam) {
            // Cek stok
            $query_stok = mysqli_query($koneksi, "SELECT stok FROM alat WHERE id_alat = '{$data_pinjam['id_alat']}'");
            $stok = mysqli_fetch_assoc($query_stok);
            
            if ($stok['stok'] >= $data_pinjam['jumlah']) {
                // Kurangi stok
                mysqli_query($koneksi, "UPDATE alat SET stok = stok - {$data_pinjam['jumlah']} WHERE id_alat = '{$data_pinjam['id_alat']}'");
                // Update status
                mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Disetujui' WHERE id='$id'");
                $response['success'] = true;
                $response['message'] = 'Peminjaman berhasil disetujui!';
            } else {
                $response['success'] = false;
                $response['message'] = 'Stok tidak mencukupi!';
            }
        }
    } elseif ($aksi == 'tolak') {
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Ditolak' WHERE id='$id'");
        $response['success'] = true;
        $response['message'] = 'Peminjaman ditolak!';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Hitung statistik
$total_pending = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT * FROM peminjaman WHERE status = 'Menunggu'
"));

$hari_ini = date('Y-m-d');
$disetujui_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM peminjaman 
    WHERE status = 'Disetujui' 
    AND DATE(tanggal_pinjam) = '$hari_ini'
"));

$jatuh_tempo = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM peminjaman 
    WHERE status = 'Disetujui' 
    AND tanggal_kembali < '$hari_ini'
"));

// Ambil data pengajuan dengan status 'Menunggu'
$query = mysqli_query($koneksi, "
    SELECT p.*, a.nama_alat, a.stok as stok_alat
    FROM peminjaman p
    JOIN alat a ON p.id_alat = a.id_alat
    WHERE p.status = 'Menunggu'
    ORDER BY p.id DESC
");

$username = $_SESSION['nama'] ?? 'Petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Pengajuan Peminjaman - Petugas</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* ===== SIDEBAR NAVBAR ===== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .sidebar-header .logo {
            font-size: 20px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .sidebar-header .role {
            font-size: 12px;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 8px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: white;
            font-weight: 600;
        }

        .sidebar-menu .menu-icon {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-menu .badge {
            background: #ef4444;
            color: white;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 10px;
            margin-left: 8px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            color: #0369a1;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-info {
            color: #64748b;
            font-size: 12px;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 20px;
        }

        /* ===== STATS CARD ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            font-size: 30px;
            padding: 10px;
            border-radius: 10px;
        }

        .stat-info h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .stat-info p {
            font-size: 24px;
            font-weight: 600;
            line-height: 1.2;
        }

        .stat-desc {
            font-size: 10px;
            opacity: 0.8;
        }

        /* ===== CARD ===== */
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .card-header h3 {
            color: #0369a1;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== TABLE RESPONSIF ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            font-size: 13px;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
            font-size: 13px;
        }

        .table tr:hover td {
            background: #f1f5f9;
        }

        .empty {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
            font-style: italic;
        }

        /* ===== BADGE ===== */
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ===== BUTTON ===== */
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 12px;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* ===== USER INFO ===== */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: #e0f2fe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0369a1;
            font-weight: 600;
            font-size: 12px;
        }

        .user-detail {
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
        }

        /* ===== FOOTER ===== */
        footer {
            text-align: center;
            padding: 15px;
            color: #64748b;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        /* ========== RESPONSIVE HP ========== */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 0;
            }
            
            .sidebar-header {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
                padding: 0 10px;
            }
            
            .sidebar-menu li {
                display: inline-block;
                margin-bottom: 0;
            }
            
            .sidebar-menu a {
                padding: 8px 12px;
                border-left: none;
                border-radius: 8px;
                font-size: 12px;
            }
            
            .sidebar-menu a.active {
                border-left: none;
                background: rgba(255,255,255,0.3);
            }
            
            .sidebar-footer {
                position: relative;
                bottom: auto;
                margin-top: 15px;
                text-align: center;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header h1 {
                font-size: 18px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-info p {
                font-size: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-group {
                flex-direction: row;
            }
            
            .btn {
                padding: 5px 10px;
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .sidebar-menu a {
                padding: 6px 8px;
                font-size: 11px;
            }
            
            .sidebar-menu .menu-icon {
                font-size: 14px;
                margin-right: 5px;
            }
            
            .table th, .table td {
                padding: 8px 6px;
                font-size: 11px;
            }
            
            .user-avatar {
                width: 25px;
                height: 25px;
                font-size: 10px;
            }
            
            .btn {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR NAVBAR PETUGAS -->
<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">🔬 Peminjaman Lab</span>
        <span class="role">👤 <?= htmlspecialchars($username); ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php">
                <span class="menu-icon">📊</span>
                Dashboard
            </a>
        </li>
        <li>
            <a href="pengajuan_peminjaman.php" class="active">
                <span class="menu-icon">📝</span>
                Pengajuan Peminjaman
                <?php if($total_pending > 0): ?>
                    <span class="badge" id="badgePending"><?= $total_pending; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="pengembalian.php">
                <span class="menu-icon">🔄</span>
                Pengembalian Alat
            </a>
        </li>
        <li>
            <a href="laporan.php">
                <span class="menu-icon">📄</span>
                Laporan
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span class="menu-icon">🚪</span>
            Logout
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1>
            <span>📝</span> 
            Pengajuan Peminjaman
        </h1>
        <div class="date-info">
            <?= date('l, d F Y'); ?>
        </div>
    </div>
    
    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">⏳</div>
            <div class="stat-info">
                <h4 style="color: rgba(255,255,255,0.9);">Menunggu</h4>
                <p style="color: white;" id="statPending"><?= $total_pending; ?></p>
                <div class="stat-desc">Pengajuan baru</div>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">✅</div>
            <div class="stat-info">
                <h4 style="color: rgba(255,255,255,0.9);">Disetujui Hari Ini</h4>
                <p style="color: white;"><?= $disetujui_hari_ini['total']; ?></p>
                <div class="stat-desc"><?= date('d/m/Y'); ?></div>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #c0392b, #a93226); color: white;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">⚠️</div>
            <div class="stat-info">
                <h4 style="color: rgba(255,255,255,0.9);">Perlu Perhatian</h4>
                <p style="color: white;"><?= $jatuh_tempo['total']; ?></p>
                <div class="stat-desc">Melewati batas</div>
            </div>
        </div>
    </div>
    
    <!-- MAIN CARD -->
    <div class="card">
        <div class="card-header">
            <h3>
                <span>📋</span>
                Daftar Pengajuan
            </h3>
            <span class="badge badge-warning" id="totalPengajuan">
                Total: <?= $total_pending; ?> pengajuan
            </span>
        </div>
        
        <div class="table-responsive">
            <table class="table" id="tabelPengajuan">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Peminjam</th>
                        <th>Alat</th>
                        <th>Jumlah</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbodyPengajuan">
                <?php if(mysqli_num_rows($query) == 0): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            <div style="font-size: 40px;">📭</div>
                            <div style="font-weight: 600;">Tidak ada pengajuan</div>
                            <div style="font-size: 12px;">Tidak ada pengajuan yang menunggu</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php while($data = mysqli_fetch_assoc($query)): ?>
                        <tr id="row-<?= $data['id']; ?>">
                            <td><strong><?= $no++; ?></strong></td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($data['username'], 0, 1)); ?>
                                    </div>
                                    <div class="user-detail">
                                        <?= htmlspecialchars($data['username']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($data['nama_alat']); ?></strong>
                                <?php if($data['stok_alat'] < $data['jumlah']): ?>
                                    <br>
                                    <small style="color:#dc2626;">Stok: <?= $data['stok_alat']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?= $data['jumlah']; ?> unit</span></td>
                            <td><?= date('d/m/Y', strtotime($data['tanggal_pinjam'])); ?></td>
                            <td><?= date('d/m/Y', strtotime($data['tanggal_kembali'])); ?></td>
                            <td>
                                <div class="action-group">
                                    <button class="btn btn-success btn-setujui" data-id="<?= $data['id']; ?>" data-nama="<?= htmlspecialchars($data['nama_alat']); ?>" data-stok="<?= $data['stok_alat']; ?>" data-jumlah="<?= $data['jumlah']; ?>">
                                        ✅ Setujui
                                    </button>
                                    <button class="btn btn-danger btn-tolak" data-id="<?= $data['id']; ?>" data-nama="<?= htmlspecialchars($data['nama_alat']); ?>">
                                        ❌ Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if(mysqli_num_rows($query) > 0): ?>
        <div style="margin-top: 15px; padding: 10px 15px; background: #f8fafc; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <span style="background: #16a34a; width: 10px; height: 10px; border-radius: 3px;"></span>
                    <span style="font-size: 12px;">Setujui jika stok cukup</span>
                </span>
                <span style="display: flex; align-items: center; gap: 6px;">
                    <span style="background: #dc2626; width: 10px; height: 10px; border-radius: 3px;"></span>
                    <span style="font-size: 12px;">Tolak jika tidak sesuai</span>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>
        © <?= date('Y'); ?> Peminjaman Alat Laboratorium
        <br>
        <span style="font-size: 10px;">
            Login sebagai: <?= htmlspecialchars($username); ?>
        </span>
    </footer>
    
</div>

<!-- SweetAlert2 JS & jQuery (untuk AJAX) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Proses SETUJUI dengan AJAX
    $('.btn-setujui').on('click', function() {
        let btn = $(this);
        let id = btn.data('id');
        let namaAlat = btn.data('nama');
        let stokTersedia = btn.data('stok');
        let jumlahPinjam = btn.data('jumlah');
        
        // Cek stok
        if (stokTersedia < jumlahPinjam) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Tidak Mencukupi!',
                text: `Stok ${namaAlat} tersisa ${stokTersedia} unit, sedangkan yang dipinjam ${jumlahPinjam} unit.`,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Setujui Peminjaman?',
            text: `Anda akan menyetujui peminjaman ${namaAlat} sebanyak ${jumlahPinjam} unit.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            confirmButtonText: '✅ Ya, Setujui!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button
                btn.prop('disabled', true).text('⏳ Memproses...');
                
                // AJAX request
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        id: id,
                        aksi: 'acc'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Hapus baris dari tabel
                            $('#row-' + id).fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update nomor urut
                                $('#tabelPengajuan tbody tr').each(function(index) {
                                    $(this).find('td:first').html('<strong>' + (index + 1) + '</strong>');
                                });
                                
                                // Update jumlah pending
                                let currentPending = parseInt($('#statPending').text());
                                let newPending = currentPending - 1;
                                $('#statPending').text(newPending);
                                $('#totalPengajuan').text('Total: ' + newPending + ' pengajuan');
                                
                                if (newPending > 0) {
                                    $('#badgePending').text(newPending);
                                } else {
                                    $('#badgePending').remove();
                                    // Tampilkan pesan kosong
                                    if ($('#tabelPengajuan tbody tr').length === 0) {
                                        $('#tbodyPengajuan').html(`
                                            <tr>
                                                <td colspan="7" class="empty">
                                                    <div style="font-size: 40px;">📭</div>
                                                    <div style="font-weight: 600;">Tidak ada pengajuan</div>
                                                    <div style="font-size: 12px;">Tidak ada pengajuan yang menunggu</div>
                                                </td>
                                            </tr>
                                        `);
                                    }
                                }
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonColor: '#16a34a',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message,
                                confirmButtonColor: '#dc2626'
                            });
                            btn.prop('disabled', false).text('✅ Setujui');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server.',
                            confirmButtonColor: '#dc2626'
                        });
                        btn.prop('disabled', false).text('✅ Setujui');
                    }
                });
            }
        });
    });
    
    // Proses TOLAK dengan AJAX
    $('.btn-tolak').on('click', function() {
        let btn = $(this);
        let id = btn.data('id');
        let namaAlat = btn.data('nama');
        
        Swal.fire({
            title: 'Tolak Peminjaman?',
            text: `Anda akan menolak peminjaman ${namaAlat}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '❌ Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button
                btn.prop('disabled', true).text('⏳ Memproses...');
                
                // AJAX request
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        id: id,
                        aksi: 'tolak'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Hapus baris dari tabel
                            $('#row-' + id).fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update nomor urut
                                $('#tabelPengajuan tbody tr').each(function(index) {
                                    $(this).find('td:first').html('<strong>' + (index + 1) + '</strong>');
                                });
                                
                                // Update jumlah pending
                                let currentPending = parseInt($('#statPending').text());
                                let newPending = currentPending - 1;
                                $('#statPending').text(newPending);
                                $('#totalPengajuan').text('Total: ' + newPending + ' pengajuan');
                                
                                if (newPending > 0) {
                                    $('#badgePending').text(newPending);
                                } else {
                                    $('#badgePending').remove();
                                    // Tampilkan pesan kosong
                                    if ($('#tabelPengajuan tbody tr').length === 0) {
                                        $('#tbodyPengajuan').html(`
                                            <tr>
                                                <td colspan="7" class="empty">
                                                    <div style="font-size: 40px;">📭</div>
                                                    <div style="font-weight: 600;">Tidak ada pengajuan</div>
                                                    <div style="font-size: 12px;">Tidak ada pengajuan yang menunggu</div>
                                                </td>
                                            </tr>
                                        `);
                                    }
                                }
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonColor: '#16a34a',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message,
                                confirmButtonColor: '#dc2626'
                            });
                            btn.prop('disabled', false).text('❌ Tolak');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan pada server.',
                            confirmButtonColor: '#dc2626'
                        });
                        btn.prop('disabled', false).text('❌ Tolak');
                    }
                });
            }
        });
    });
});
</script>

</body>
</html>