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

// Validasi user
$cek_user = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($cek_user, "s", $username);
mysqli_stmt_execute($cek_user);
$user_result = mysqli_stmt_get_result($cek_user);
$user_valid = mysqli_fetch_assoc($user_result);

if(!$user_valid) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// ========== AMBIL SEMUA ALAT ==========
$query_alat = "SELECT * FROM alat ORDER BY nama_alat ASC";
$alat = mysqli_query($koneksi, $query_alat);

// ========== CEK APAKAH ADA ID_ALAT DARI URL ==========
$selected_id = isset($_GET['id_alat']) ? $_GET['id_alat'] : '';

$message = "";
$message_type = "";

if (isset($_POST['pinjam'])) {
    $id_alat = mysqli_real_escape_string($koneksi, $_POST['id_alat']);
    $jumlah = (int)$_POST['jumlah'];
    $tgl_pinjam = $_POST['tanggal_pinjam'];
    $tgl_kembali = $_POST['tanggal_kembali'];
    
    $selisih = (strtotime($tgl_kembali) - strtotime($tgl_pinjam)) / 3600;
    
    // VALIDASI (tanpa batasan jam 13:00)
    if ($selisih < 2) {
        $message = "❌ Minimal peminjaman 2 jam! (Durasi: " . round($selisih,1) . " jam)";
        $message_type = "error";
    } elseif ($selisih > 168) {
        $message = "❌ Maksimal peminjaman 7 hari!";
        $message_type = "error";
    } else {
        // Cek stok
        $stmt_stok = mysqli_prepare($koneksi, "SELECT stok FROM alat WHERE id_alat = ?");
        mysqli_stmt_bind_param($stmt_stok, "s", $id_alat);
        mysqli_stmt_execute($stmt_stok);
        $result_stok = mysqli_stmt_get_result($stmt_stok);
        $stok_data = mysqli_fetch_assoc($result_stok);
        
        if (!$stok_data) {
            $message = "❌ Alat tidak ditemukan!";
            $message_type = "error";
        } elseif ($stok_data['stok'] < $jumlah) {
            $message = "❌ Stok tidak cukup! (Sisa: {$stok_data['stok']})";
            $message_type = "error";
        } else {
            // Simpan ke session untuk dikonfirmasi
            $_SESSION['konfirmasi'] = [
                'id_alat' => $id_alat,
                'nama_alat' => $_POST['nama_alat'],
                'jumlah' => $jumlah,
                'tanggal_pinjam' => $tgl_pinjam,
                'tanggal_kembali' => $tgl_kembali
            ];
            
            // Redirect ke halaman konfirmasi
            header("Location: konfirmasi_peminjaman.php");
            exit;
        }
    }
}

// Ambil nama alat jika ada selected_id
$nama_alat_selected = '';
if(!empty($selected_id)) {
    $query_nama = mysqli_query($koneksi, "SELECT nama_alat FROM alat WHERE id_alat='$selected_id'");
    if($data_nama = mysqli_fetch_assoc($query_nama)) {
        $nama_alat_selected = $data_nama['nama_alat'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Peminjaman Alat | User</title>
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
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
        }
        nav .logo {
            font-weight: bold;
            font-size: 16px;
        }
        nav div:last-child {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
        }
        nav a:hover, nav a.active {
            text-decoration: underline;
        }

        .container {
            padding: 30px 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 130px);
        }

        form {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 25px 20px;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        form h2 {
            color: #0369a1;
            margin-bottom: 15px;
            text-align: center;
            font-size: 22px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #0f172a;
            font-size: 13px;
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2,132,199,0.2);
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border-radius: 10px;
            border: none;
            background: #0284c7;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
        }
        button:hover:not(:disabled) {
            background: #0369a1;
        }
        button:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        .info-user {
            background: #e0f2fe;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
            color: #0369a1;
            font-size: 13px;
        }
        .alert {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .hint-box {
            background: #f0f9ff;
            padding: 10px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #0284c7;
            font-size: 12px;
            color: #0369a1;
        }
        .hint-box p {
            margin: 4px 0;
        }
        .error-text {
            color: #dc2626;
            font-size: 11px;
            margin-top: 5px;
        }
        .success-text {
            color: #16a34a;
            font-size: 11px;
            margin-top: 5px;
        }
        .clock-box {
            background: #e0f2fe;
            border-radius: 50px;
            padding: 5px 12px;
            display: inline-block;
            font-size: 12px;
            margin-bottom: 15px;
            text-align: center;
            width: 100%;
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #64748b;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            nav { flex-direction: column; text-align: center; }
            nav div:last-child { justify-content: center; }
            .container { padding: 20px 12px; }
            form { padding: 20px 15px; }
            form h2 { font-size: 20px; }
            label { font-size: 12px; }
            input, select { padding: 9px 10px; font-size: 13px; }
            button { padding: 10px; font-size: 14px; }
        }

        @media (max-width: 480px) {
            nav .logo { font-size: 14px; }
            nav a { font-size: 11px; }
            .container { padding: 15px 10px; }
            form { padding: 15px 12px; }
            form h2 { font-size: 18px; }
            input, select { padding: 8px 8px; font-size: 12px; }
            button { padding: 9px; font-size: 13px; }
        }

        @media (min-width: 1024px) {
            .container { padding: 40px; }
            form { padding: 30px; }
            form h2 { font-size: 24px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">🔬 Peminjaman Alat Lab</div>
    <div>
        <a href="beranda.php">Beranda</a>
        <a href="daftar_alat.php">Daftar Alat</a>
        <a href="peminjaman.php" class="active">Pinjam Alat</a>
        <a href="riwayat_peminjaman.php">Riwayat</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <form method="POST" id="formPinjam">
        <h2>📋 Form Peminjaman Alat</h2>
        
        <div class="clock-box">
            🕐 <span id="realTimeClock">--:--:--</span> WIB
        </div>
        
        <div class="info-user">
            👤 Peminjam: <strong><?php echo htmlspecialchars($username); ?></strong>
        </div>
        
        <?php if($message && $message_type == "error"): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif($message && $message_type == "success"): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>🔧 Pilih Alat</label>
            <select name="id_alat" id="id_alat" required>
                <option value="">-- Pilih Alat --</option>
                <?php 
                mysqli_data_seek($alat, 0);
                while($row = mysqli_fetch_assoc($alat)): 
                    $selected = ($selected_id == $row['id_alat']) ? 'selected' : '';
                ?>
                    <option value="<?php echo htmlspecialchars($row['id_alat']); ?>" data-stok="<?php echo (int)$row['stok']; ?>" data-nama="<?php echo htmlspecialchars($row['nama_alat']); ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($row['nama_alat']); ?> (Stok: <?php echo (int)$row['stok']; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <div id="stokWarning" class="error-text"></div>
        </div>
        
        <div class="form-group">
            <label>🔢 Jumlah</label>
            <input type="number" name="jumlah" id="jumlah" min="1" placeholder="Masukkan jumlah" required>
        </div>
        
        <div class="form-group">
            <label>📅 Tanggal & Waktu Pinjam</label>
            <input type="datetime-local" name="tanggal_pinjam" id="tanggal_pinjam" required>
            <div id="errorPinjam" class="error-text"></div>
        </div>
        
        <div class="form-group">
            <label>📅 Tanggal & Waktu Kembali</label>
            <input type="datetime-local" name="tanggal_kembali" id="tanggal_kembali" required>
            <div id="errorKembali" class="error-text"></div>
            <div id="durasiInfo" class="success-text"></div>
        </div>
        
        <div class="hint-box">
            <p>⏰ <strong>Aturan Peminjaman:</strong></p>
            <p>• ⏱️ Minimal durasi: <strong>2 jam</strong></p>
            <p>• 📆 Maksimal peminjaman <strong>7 hari</strong></p>
            <p>• ✅ Bisa pinjam jam berapa saja</p>
        </div>
        
        <input type="hidden" name="nama_alat" id="nama_alat_hidden">
        
        <button type="submit" name="pinjam" id="btnPinjam">📋 AJUKAN PEMINJAMAN</button>
    </form>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Peminjaman Alat Laboratorium
</footer>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('realTimeClock').innerText = 
            String(now.getHours()).padStart(2,'0') + ':' + 
            String(now.getMinutes()).padStart(2,'0') + ':' + 
            String(now.getSeconds()).padStart(2,'0');
    }
    setInterval(updateClock, 1000);
    updateClock();

    const tglPinjam = document.getElementById('tanggal_pinjam');
    const tglKembali = document.getElementById('tanggal_kembali');
    const btnPinjam = document.getElementById('btnPinjam');
    const errorKembali = document.getElementById('errorKembali');
    const durasiInfo = document.getElementById('durasiInfo');
    const jumlahInput = document.getElementById('jumlah');
    const alatSelect = document.getElementById('id_alat');
    const stokWarning = document.getElementById('stokWarning');
    const namaAlatHidden = document.getElementById('nama_alat_hidden');

    function setMinDateTime() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        
        tglPinjam.min = minDateTime;
        tglKembali.min = minDateTime;
    }

    function formatDurasi(jam) {
        if (jam < 1) {
            return `${Math.round(jam * 60)} menit`;
        }
        return `${jam.toFixed(1)} jam`;
    }

    function validateStock() {
        const selectedOption = alatSelect.options[alatSelect.selectedIndex];
        const stok = selectedOption.getAttribute('data-stok');
        const nama = selectedOption.getAttribute('data-nama');
        const jumlah = parseInt(jumlahInput.value);
        
        if (nama) {
            namaAlatHidden.value = nama;
        }
        
        if (stok && jumlah && jumlah > parseInt(stok)) {
            stokWarning.innerHTML = `⚠️ Jumlah melebihi stok! (Maksimal ${stok})`;
            return false;
        } else {
            stokWarning.innerHTML = '';
            return true;
        }
    }

    function validateForm() {
        let isValid = true;
        
        errorKembali.innerHTML = '';
        durasiInfo.innerHTML = '';
        
        const pinjamValue = tglPinjam.value;
        const kembaliValue = tglKembali.value;
        
        if (pinjamValue && kembaliValue) {
            const pinjamDate = new Date(pinjamValue);
            const kembaliDate = new Date(kembaliValue);
            
            const selisihMs = kembaliDate - pinjamDate;
            const selisihJam = selisihMs / (1000 * 60 * 60);
            
            if (selisihMs < 0) {
                isValid = false;
                errorKembali.innerHTML = '❌ Tanggal kembali harus lebih besar dari tanggal pinjam';
            } else if (selisihJam < 2) {
                isValid = false;
                errorKembali.innerHTML = `⏱️ Minimal peminjaman 2 jam! (Durasi: ${formatDurasi(selisihJam)})`;
                durasiInfo.innerHTML = `⏱️ Durasi: ${formatDurasi(selisihJam)} (Minimal 2 jam)`;
                durasiInfo.style.color = "#dc2626";
            } else if (selisihJam > 168) {
                isValid = false;
                errorKembali.innerHTML = `📆 Maksimal peminjaman 7 hari!`;
                durasiInfo.innerHTML = `📆 Durasi: ${formatDurasi(selisihJam)} (Maksimal 7 hari)`;
                durasiInfo.style.color = "#dc2626";
            } else {
                durasiInfo.innerHTML = `✅ Durasi: ${formatDurasi(selisihJam)} (Memenuhi syarat)`;
                durasiInfo.style.color = "#16a34a";
            }
        }
        
        const stockValid = validateStock();
        if (!stockValid) isValid = false;
        
        btnPinjam.disabled = !isValid;
        return isValid;
    }
    
    tglPinjam.addEventListener('change', validateForm);
    tglPinjam.addEventListener('input', validateForm);
    tglKembali.addEventListener('change', validateForm);
    tglKembali.addEventListener('input', validateForm);
    jumlahInput.addEventListener('input', function() {
        validateStock();
        validateForm();
    });
    alatSelect.addEventListener('change', function() {
        validateStock();
        validateForm();
    });
    
    document.getElementById('formPinjam').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            alert('❌ Peminjaman tidak dapat diproses!\n\nPastikan:\n✓ Durasi minimal 2 jam\n✓ Maksimal 7 hari\n✓ Jumlah tidak melebihi stok');
        }
    });
    
    window.onload = function() {
        setMinDateTime();
        validateForm();
    };
</script>

</body>
</html>