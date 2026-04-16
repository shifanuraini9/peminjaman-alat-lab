<?php
session_start();

// CEK APAKAH SUDAH LOGIN
if(isset($_SESSION['nama'])) {
    if(isset($_SESSION['role'])) {
        if($_SESSION['role'] == 'admin') {
            header('Location: admin/dashboard.php');
            exit;
        } elseif($_SESSION['role'] == 'petugas') {
            header('Location: petugas/dashboard.php');
            exit;
        } elseif($_SESSION['role'] == 'user') {
            header('Location: user/beranda.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Peminjaman Alat Lab</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('img/lab.jpg') center/cover fixed;
            display: flex;
            flex-direction: column;
        }
        
        /* NAVBAR RESPONSIF */
        nav {
            background: rgba(0,119,182,0.3);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
        }
        nav div:last-child {
            display: flex;
            gap: 15px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        nav a:hover {
            text-decoration: underline;
        }
        
        /* MAIN CONTENT */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 40px 20px;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 8px black;
        }
        h2 {
            font-size: 22px;
            color: #38bdf8;
            margin-bottom: 25px;
            text-shadow: 2px 2px 8px black;
        }
        p {
            font-size: 14px;
            max-width: 800px;
            margin-bottom: 35px;
            line-height: 1.6;
            background: rgba(0,0,0,0.3);
            padding: 15px 20px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
        }
        .btn {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            padding: 14px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        footer {
            text-align: center;
            padding: 12px;
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 11px;
        }
        
        /* ========== RESPONSIVE HP (≤ 768px) ========== */
        @media (max-width: 768px) {
            nav {
                padding: 12px 15px;
            }
            .logo {
                font-size: 14px;
            }
            nav a {
                font-size: 12px;
            }
            
            h1 {
                font-size: 24px;
            }
            h2 {
                font-size: 18px;
                margin-bottom: 20px;
            }
            p {
                font-size: 12px;
                padding: 12px 15px;
                margin-bottom: 30px;
            }
            .btn {
                padding: 12px 30px;
                font-size: 16px;
            }
            footer {
                padding: 10px;
                font-size: 10px;
            }
        }
        
        /* ========== HP KECIL (≤ 480px) ========== */
        @media (max-width: 480px) {
            nav {
                flex-direction: column;
                text-align: center;
            }
            nav div:last-child {
                justify-content: center;
            }
            
            h1 {
                font-size: 20px;
            }
            h2 {
                font-size: 16px;
            }
            p {
                font-size: 11px;
                padding: 10px 12px;
            }
            .btn {
                padding: 10px 25px;
                font-size: 14px;
            }
        }
        
        /* ========== LAPTOP (≥ 1024px) ========== */
        @media (min-width: 1024px) {
            nav {
                padding: 20px 50px;
            }
            .logo {
                font-size: 24px;
            }
            nav a {
                font-size: 16px;
            }
            h1 {
                font-size: 48px;
            }
            h2 {
                font-size: 36px;
            }
            p {
                font-size: 18px;
                padding: 20px 30px;
            }
            .btn {
                padding: 18px 60px;
                font-size: 22px;
            }
            footer {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">🔬 Peminjaman Alat Lab</div>
        <div>
            <a href="index.php">Beranda</a>
            <a href="login.php">Login</a>
        </div>
    </nav>

    <div class="main">
        <h1>WEBSITE PEMINJAMAN ALAT</h1>
        <h2>LABORATORIUM KOMPUTER</h2>
        <p>Kelola peminjaman alat lab dengan mudah, cepat, dan terintegrasi. Sistem Informasi Laboratorium SMKN REKAYASA PERANGKAT LUNAK.</p>
        <a href="login.php" class="btn">🔐 SILAHKAN LOGIN</a>
    </div>

    <footer>
        &copy; <?= date('Y'); ?> Peminjaman Alat Laboratorium | SMKN REKAYASA PERANGKAT LUNAK
    </footer>
</body>
</html>