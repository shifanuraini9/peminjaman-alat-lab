<?php
session_start();

// ========== CEK LOGIN ==========
if(!isset($_SESSION['nama'])) {
    header("Location: ../login.php");
    exit();
}

// ========== CEK ROLE ==========
if($_SESSION['role'] != 'user') {
    if($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
    if($_SESSION['role'] == 'petugas') {
        header("Location: ../petugas/dashboard.php");
        exit();
    }
    session_destroy();
    header("Location: ../login.php");
    exit();
}

include "../koneksi.php";

if(!$koneksi) {
    die("Koneksi database gagal");
}

$username = $_SESSION['nama'];

// ========== FILTER KATEGORI (dengan nama kategori) ==========
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'Semua';

// Mapping kategori yang benar sesuai tabel kategori
if($filter_kategori == 'Semua') {
    $query_alat = "SELECT * FROM alat ORDER BY id_alat ASC";
} elseif($filter_kategori == 'Komputer') {
    $query_alat = "SELECT * FROM alat WHERE id_kategori IN ('KAT001') ORDER BY id_alat ASC";
} elseif($filter_kategori == 'Proyektor') {
    $query_alat = "SELECT * FROM alat WHERE id_kategori IN ('KAT002') ORDER BY id_alat ASC";
} elseif($filter_kategori == 'Mouse') {
    $query_alat = "SELECT * FROM alat WHERE id_kategori IN ('KAT003') ORDER BY id_alat ASC";
} elseif($filter_kategori == 'Keyboard') {
    $query_alat = "SELECT * FROM alat WHERE id_kategori IN ('KAT004') ORDER BY id_alat ASC";
} else {
    $query_alat = "SELECT * FROM alat ORDER BY id_alat ASC";
}

$alat = mysqli_query($koneksi, $query_alat);

// Ambil peminjaman user yang statusnya 'Menunggu' atau 'Dipinjam'
$peminjaman_user = mysqli_query($koneksi, "SELECT p.*, a.nama_alat, a.kode_alat 
                                           FROM peminjaman p 
                                           JOIN alat a ON p.id_alat = a.id_alat 
                                           WHERE p.username = '$username' AND (p.status = 'Menunggu' OR p.status = 'Dipinjam')
                                           ORDER BY p.tanggal_pinjam DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Alat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #0f172a;
        }

        nav {
            background: linear-gradient(90deg, #0284c7, #0369a1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        nav .logo {
            font-weight: bold;
            font-size: 18px;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
        }
        nav a:hover {
            text-decoration: underline;
        }

        /* FILTER KATEGORI */
        .filter-section {
            max-width: 1200px;
            margin: 20px auto 0 auto;
            padding: 0 40px;
        }
        .filter-box {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filter-box label {
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }
        .filter-btn {
            padding: 6px 16px;
            background: #e2e8f0;
            color: #475569;
            text-decoration: none;
            border-radius: 20px;
            font-size: 13px;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background: #cbd5e1;
        }
        .filter-btn.active {
            background: #0284c7;
            color: white;
        }

        .container {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 12px;
            background: #f8fafc;
        }
        .card h3 {
            margin: 12px 0 5px;
            color: #0369a1;
            font-size: 18px;
        }
        .kode {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .kategori-badge {
            display: inline-block;
            background: #e0f2fe;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            color: #0284c7;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .stok {
            background: #f8fafc;
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            margin: 12px 0;
        }
        .stok-value {
            font-size: 24px;
            font-weight: bold;
            color: #0369a1;
        }
        .stok-label {
            font-size: 12px;
            color: #64748b;
        }
        
        .btn-pinjam {
            width: 100%;
            padding: 10px;
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-pinjam:hover {
            background: #0369a1;
        }
        .btn-pinjam:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        
        .no-foto {
            width: 100%;
            height: 160px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: #94a3b8;
            font-size: 12px;
        }
        
        .pinjam-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin: 0 40px 30px 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        .pinjam-section h3 {
            color: #0369a1;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #0f172a;
        }
        .status-menunggu {
            background: #fde68a;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-dipinjam {
            background: #93c5fd;
            color: #1e3a8a;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .kosong {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }
        
        .pesan {
            padding: 12px;
            border-radius: 10px;
            margin: 20px 40px 0 40px;
            text-align: center;
            font-weight: bold;
        }
        .pesan.success {
            background: #d1fae5;
            color: #065f46;
        }
        .pesan.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0 10px;
        }
        .page-title h1 {
            color: #0369a1;
            font-size: 28px;
        }
        .page-title p {
            color: #64748b;
            margin-top: 5px;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            nav a {
                margin: 0 10px;
            }
            .filter-section {
                padding: 0 20px;
            }
            .container {
                padding: 20px;
            }
            .pinjam-section {
                margin: 0 20px 20px 20px;
                padding: 15px;
                overflow-x: auto;
            }
            .pesan {
                margin: 15px 20px 0 20px;
            }
            .page-title h1 {
                font-size: 22px;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">🔬 Peminjaman Alat Lab</div>
    <div>
        <a href="beranda.php">Beranda</a>
        <a href="daftar_alat.php" style="text-decoration:underline;">Daftar Alat</a>
        <a href="riwayat_peminjaman.php">Riwayat Peminjaman</a>
    </div>
</nav>

<div class="page-title">
    <h1>📋 Daftar Alat Laboratorium</h1>
    <p>Silakan pilih alat yang ingin dipinjam</p>
</div>

<!-- FILTER KATEGORI DENGAN NAMA -->
<div class="filter-section">
    <div class="filter-box">
        <label>🔍 Filter Kategori:</label>
        <a href="?kategori=Semua" class="filter-btn <?= ($filter_kategori == 'Semua') ? 'active' : '' ?>">Semua</a>
        <a href="?kategori=Komputer" class="filter-btn <?= ($filter_kategori == 'Komputer') ? 'active' : '' ?>">💻 Komputer</a>
        <a href="?kategori=Proyektor" class="filter-btn <?= ($filter_kategori == 'Proyektor') ? 'active' : '' ?>">📽️ Proyektor</a>
        <a href="?kategori=Mouse" class="filter-btn <?= ($filter_kategori == 'Mouse') ? 'active' : '' ?>">🖱️ Mouse</a>
        <a href="?kategori=Keyboard" class="filter-btn <?= ($filter_kategori == 'Keyboard') ? 'active' : '' ?>">⌨️ Keyboard</a>
    </div>
</div>

<?php if(isset($_SESSION['pesan'])): ?>
    <div class="pesan <?= $_SESSION['pesan_tipe'] ?>">
        <?= $_SESSION['pesan'] ?>
    </div>
    <?php unset($_SESSION['pesan'], $_SESSION['pesan_tipe']); ?>
<?php endif; ?>

<div class="container">
    <?php if(mysqli_num_rows($alat) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($alat)): 
            // Tentukan nama kategori untuk ditampilkan
            $id_kat = $row['id_kategori'];
            if(in_array($id_kat, ['KAT001'])) {
                $nama_kategori = "Komputer";
            } elseif(in_array($id_kat, ['KAT002'])) {
                $nama_kategori = "Proyektor";
            } elseif(in_array($id_kat, ['KAT003'])) {
                $nama_kategori = "Mouse";
            } else {
                $nama_kategori = "Keyboard";
            }
            
            // Ambil foto dari database (kolom foto)
            $foto = !empty($row['foto']) ? $row['foto'] : 'default.jpg';
            $path_foto = "../img/" . $foto;
        ?>
        <div class="card">
            <?php if(file_exists($path_foto)): ?>
                <img src="<?= $path_foto ?>" alt="<?= htmlspecialchars($row['nama_alat']) ?>">
            <?php else: ?>
                <div class="no-foto">📷 <br>Foto belum tersedia</div>
            <?php endif; ?>
            <h3><?= htmlspecialchars($row['nama_alat']) ?></h3>
            <div class="kode">Kode: <?= htmlspecialchars($row['kode_alat']) ?></div>
            <div class="kategori-badge"><?= $nama_kategori ?></div>
            <div class="stok">
                <span class="stok-value"><?= $row['stok'] ?></span>
                <span class="stok-label"> unit tersedia</span>
            </div>
            
            <!-- FORM LANGSUNG KE PEMINJAMAN.PHP -->
            <form method="POST" action="peminjaman.php">
                <input type="hidden" name="id_alat" value="<?= $row['id_alat'] ?>">
                <input type="hidden" name="nama_alat" value="<?= $row['nama_alat'] ?>">
                <button type="submit" class="btn-pinjam">📥 Ajukan Peminjaman</button>
            </form>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; grid-column: 1/-1; padding: 50px; color: #64748b;">
            📭 Tidak ada alat dalam kategori ini
        </div>
    <?php endif; ?>
</div>

<!-- PEMINJAMAN USER -->
<div class="pinjam-section">
    <h3>📌 Status Peminjaman Saya</h3>
    <?php if(mysqli_num_rows($peminjaman_user) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Nama Alat</th>
                <th>Kode</th>
                <th>Jumlah</th>
                <th>Tanggal Pinjam</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($pinjam = mysqli_fetch_assoc($peminjaman_user)): ?>
            <tr>
                <td><?= htmlspecialchars($pinjam['nama_alat']) ?></td>
                <td><?= htmlspecialchars($pinjam['kode_alat']) ?></td>
                <td><?= $pinjam['jumlah'] ?></td>
                <td><?= date('d-m-Y H:i', strtotime($pinjam['tanggal_pinjam'])) ?></td>
                <td>
                    <?php if($pinjam['status'] == 'Menunggu'): ?>
                        <span class="status-menunggu">⏳ Menunggu Konfirmasi</span>
                    <?php else: ?>
                        <span class="status-dipinjam">✅ Dipinjam</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="kosong">✨ Anda belum mengajukan peminjaman apapun</div>
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date('Y') ?> Peminjaman Alat Laboratorium
</footer>

</body>
</html>