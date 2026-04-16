<?php
session_start();

// CEK LOGIN - PASTIKAN SUDAH LOGIN
if(!isset($_SESSION['nama'])) {
    header('location:../login.php');
    exit();
}

// CEK ROLE - HANYA USER YANG BOLEH AKSES
if($_SESSION['role'] != 'user') {
    if($_SESSION['role'] == 'admin') {
        header('location:../admin/dashboard.php');
        exit();
    }
    if($_SESSION['role'] == 'petugas') {
        header('location:../petugas/dashboard.php');
        exit();
    }
    session_destroy();
    header('location:../login.php');
    exit();
}

include '../koneksi.php';

if(!$koneksi) {
    die("Koneksi database gagal");
}

$nama = $_SESSION['nama'];

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $nama);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if(!$user) {
    session_destroy();
    header('location:../login.php');
    exit();
}

if(empty($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

$timeout = 86400;
if(time() - $_SESSION['login_time'] > $timeout) {
    session_destroy();
    header('location:../login.php?timeout=1');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Beranda User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            background: #f0f8ff;
        }
        
        /* NAVBAR RESPONSIF */
        nav {
            background: #0077b6;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        nav .logo {
            font-size: 16px;
            font-weight: bold;
        }
        nav ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
        }
        nav ul li a:hover {
            text-decoration: underline;
        }
        
        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px;
        }
        
        /* WELCOME CARD */
        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 25px 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .welcome-card h1 {
            color: #0077b6;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .welcome-card p {
            color: #555;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .welcome-card small {
            color: #888;
            font-size: 12px;
        }
        
        /* TOMBOL MULAI PINJAM */
        .btn-mulai {
            display: inline-block;
            background: linear-gradient(135deg, #0077b6, #00a8e8);
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 50px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,119,182,0.3);
        }
        .btn-mulai:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #005f92, #0077b6);
        }
        
        /* MENU CARDS RESPONSIF */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 20px 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border: 1px solid #eaeaea;
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,119,182,0.15);
            border-color: #0077b6;
        }
        .menu-icon {
            font-size: 38px;
            margin-bottom: 12px;
        }
        .menu-card h3 {
            color: #0077b6;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .menu-card p {
            color: #666;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        .btn-menu {
            display: inline-block;
            background: #0077b6;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.3s;
        }
        .btn-menu:hover {
            background: #005f92;
        }
        
        /* FOOTER */
        footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 11px;
            border-top: 1px solid #ddd;
            margin-top: 30px;
            background: white;
        }
        
        /* ========== RESPONSIVE HP ========== */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                text-align: center;
            }
            nav ul {
                justify-content: center;
            }
            
            .container {
                padding: 10px;
                margin: 10px auto;
            }
            
            .welcome-card {
                padding: 20px 15px;
            }
            .welcome-card h1 {
                font-size: 18px;
            }
            .welcome-card p {
                font-size: 13px;
            }
            
            .btn-mulai {
                padding: 10px 25px;
                font-size: 14px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .menu-card {
                padding: 15px;
            }
            .menu-icon {
                font-size: 32px;
            }
            .menu-card h3 {
                font-size: 16px;
            }
            .menu-card p {
                font-size: 12px;
            }
            .btn-menu {
                padding: 7px 18px;
                font-size: 12px;
            }
        }
        
        /* HP KECIL */
        @media (max-width: 480px) {
            nav .logo {
                font-size: 14px;
            }
            nav ul li a {
                font-size: 11px;
            }
            
            .welcome-card h1 {
                font-size: 16px;
            }
            .welcome-card p {
                font-size: 12px;
            }
            
            .btn-mulai {
                padding: 8px 20px;
                font-size: 13px;
            }
            
            .menu-card h3 {
                font-size: 15px;
            }
        }
        
        /* LAPTOP */
        @media (min-width: 1024px) {
            .container {
                margin: 40px auto;
            }
            .welcome-card h1 {
                font-size: 28px;
            }
            .btn-mulai {
                padding: 14px 35px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">⚡ Peminjaman Alat Lab</div>
        <ul>
            <li><a href="beranda.php">Beranda</a></li>
            <li><a href="daftar_alat.php">Daftar Alat</a></li>
            <li><a href="peminjaman.php">Pinjam Alat</a></li>
            <li><a href="riwayat_peminjaman.php">Riwayat Peminjaman</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>! 👋</h1>
            <p>Website peminjaman alat laboratorium komputer</p>
            <small>SMKN REKAYASA PERANGKAT LUNAK</small>
            
            <!-- TOMBOL MULAI PINJAM -->
            <div>
                <a href="daftar_alat.php" class="btn-mulai"></a>
            </div>
        </div>

        <div class="menu-grid">
            <div class="menu-card">
                <div class="menu-icon">🖥️</div>
                <h3>Daftar Alat</h3>
                <p>Lihat alat yang tersedia</p>
                <a href="daftar_alat.php" class="btn-menu">Lihat</a>
            </div>
            
            <div class="menu-card">
                <div class="menu-icon">📝</div>
                <h3>Pinjam Alat</h3>
                <p>Ajukan peminjaman alat</p>
                <a href="peminjaman.php" class="btn-menu">Pinjam</a>
            </div>
            
            <div class="menu-card">
                <div class="menu-icon">📋</div>
                <h3>Riwayat Peminjaman</h3>
                <p>Peminjaman & pengembalian</p>
                <a href="riwayat_peminjaman.php" class="btn-menu">Buka</a>
            </div>
        </div>
    </div>

    <footer>
        © <?= date('Y'); ?> SMKN REKAYASA PERANGKAT LUNAK
    </footer>
</body>
</html>