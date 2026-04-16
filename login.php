<?php
session_start();
include "koneksi.php";

// CEK APAKAH SUDAH LOGIN
if(isset($_SESSION['nama'])) {
    if(isset($_SESSION['role'])) {
        if($_SESSION['role'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit;
        } elseif($_SESSION['role'] == 'petugas') {
            header("Location: petugas/dashboard.php");
            exit;
        } elseif($_SESSION['role'] == 'user') {
            header("Location: user/beranda.php");
            exit;
        }
    }
}

$error = '';

if(isset($_POST['login'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $password = $_POST['password'];
    
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$nama'");
    
    if(mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        if(password_verify($password, $data['password'])) {
            $_SESSION['nama'] = $data['username'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['id_user'] = $data['id'];
            
            if($data['role'] == 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } elseif($data['role'] == 'petugas') {
                header("Location: petugas/dashboard.php");
                exit;
            } else {
                header("Location: user/beranda.php");
                exit;
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Login - Peminjaman Alat Lab</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #0f172a;
            overflow-x: hidden;
        }

        /* ========== SLIDER CAROUSEL ========== */
        .carousel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .carousel-inner {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .carousel-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .carousel-item.active {
            opacity: 1;
        }

        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* OVERLAY */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        /* ========== SEMUA KONTEN DI ATAS ========== */
        .wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* NAVBAR */
        nav {
            background: rgba(0, 119, 182, 0.9);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
        }
        .logo {
            font-size: 16px;
            font-weight: bold;
        }
        nav div:last-child {
            display: flex;
            gap: 15px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-size: 13px;
        }
        nav a:hover {
            text-decoration: underline;
        }

        /* LOGIN CONTAINER - PUSAT */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* FORM LOGIN */
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 30px 25px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: #0077b6;
            text-align: center;
            margin-bottom: 8px;
            font-size: 26px;
        }

        .sub {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        input:focus {
            outline: none;
            border-color: #0077b6;
            box-shadow: 0 0 0 3px rgba(0, 119, 182, 0.1);
        }

        button {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0077b6, #00a8e8);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 119, 182, 0.4);
        }

        .back {
            text-align: center;
            margin-top: 20px;
        }

        .back a {
            color: #666;
            text-decoration: none;
            font-size: 13px;
        }

        .back a:hover {
            color: #0077b6;
        }

        /* INDICATOR */
        .indicators {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 15;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active {
            background: white;
            width: 25px;
            border-radius: 10px;
        }

        footer {
            text-align: center;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 11px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            nav {
                padding: 10px 15px;
            }
            .logo {
                font-size: 14px;
            }
            nav a {
                font-size: 12px;
            }

            .login-card {
                padding: 25px 20px;
                max-width: 90%;
            }

            h2 {
                font-size: 22px;
            }

            input, button {
                font-size: 14px;
            }

            .indicators {
                bottom: 15px;
                gap: 8px;
            }
            .dot {
                width: 8px;
                height: 8px;
            }
            .dot.active {
                width: 20px;
            }
        }

        @media (max-width: 480px) {
            nav {
                flex-direction: column;
                text-align: center;
            }
            nav div:last-child {
                justify-content: center;
            }

            .login-card {
                padding: 20px 15px;
            }

            h2 {
                font-size: 20px;
            }

            .sub {
                font-size: 11px;
            }

            label {
                font-size: 12px;
            }

            input {
                padding: 10px 12px;
                font-size: 13px;
            }

            button {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (min-width: 1024px) {
            .login-card {
                padding: 40px 35px;
                max-width: 420px;
            }
            h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- CAROUSEL BACKGROUND (Z-INDEX 0) -->
    <div class="carousel">
        <div class="carousel-inner" id="carouselInner">
            <div class="carousel-item active">
                <img src="img/lab.jpg" alt="Lab Komputer">
            </div>
            <div class="carousel-item">
                <img src="img/proyektor.jpg" alt="Proyektor">
            </div>
            <div class="carousel-item">
                <img src="img/komputer.jpg" alt="Komputer">
            </div>
            <div class="carousel-item">
                <img src="img/mouse.jpg" alt="Mouse">
            </div>
            <div class="carousel-item">
                <img src="img/keyboard.jpg" alt="Keyboard">
            </div>
        </div>
    </div>
    
    <!-- OVERLAY (Z-INDEX 1) -->
    <div class="overlay"></div>
    
    <!-- INDICATOR (Z-INDEX 15) -->
    <div class="indicators" id="indicators"></div>

    <!-- KONTEN UTAMA (Z-INDEX 10) -->
    <div class="wrapper">
        <nav>
            <div class="logo">🔬 Peminjaman Alat Lab</div>
            <div>
                <a href="index.php">Beranda</a>
                <a href="login.php">Login</a>
            </div>
        </nav>

        <div class="login-container">
            <div class="login-card">
                <h2>🔐 LOGIN</h2>
                <div class="sub">Masukkan username dan password</div>
                
                <?php if($error): ?>
                    <div class="error">⚠️ <?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="nama" placeholder="Masukkan username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="login">🔐 MASUK</button>
                </form>
                
                <div class="back">
                    <a href="index.php">← Kembali ke Beranda</a>
                </div>
            </div>
        </div>

        <footer>
            SMKN REKAYASA PERANGKAT LUNAK &copy; <?= date('Y'); ?>
        </footer>
    </div>

    <script>
        // CAROUSEL FADE EFFECT - GANTI GAMBAR SETIAP 3 DETIK
        const items = document.querySelectorAll('.carousel-item');
        const indicatorsContainer = document.getElementById('indicators');
        let currentIndex = 0;
        let interval;
        
        // Buat indicator dots
        function createIndicators() {
            for(let i = 0; i < items.length; i++) {
                const dot = document.createElement('div');
                dot.classList.add('dot');
                if(i === currentIndex) dot.classList.add('active');
                dot.addEventListener('click', () => goToSlide(i));
                indicatorsContainer.appendChild(dot);
            }
        }
        
        // Update indicator
        function updateIndicators() {
            const dots = document.querySelectorAll('.dot');
            dots.forEach((dot, index) => {
                if(index === currentIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        // Pergi ke slide
        function goToSlide(index) {
            items[currentIndex].classList.remove('active');
            currentIndex = index;
            items[currentIndex].classList.add('active');
            updateIndicators();
            resetInterval();
        }
        
        // Next slide
        function nextSlide() {
            let next = currentIndex + 1;
            if(next >= items.length) next = 0;
            goToSlide(next);
        }
        
        // Reset interval
        function resetInterval() {
            clearInterval(interval);
            interval = setInterval(nextSlide, 3000);
        }
        
        // Inisialisasi
        createIndicators();
        interval = setInterval(nextSlide, 3000);
    </script>
</body>
</html>