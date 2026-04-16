<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// TAMPILKAN SEMUA DATA ALAT
$alat = mysqli_query($koneksi, "SELECT * FROM alat");

// PROSES UPDATE
if (isset($_POST['update'])) {
    $id = $_POST['id_alat'];
    $stok_baru = $_POST['stok'];
    
    echo "<h3>DEBUG INFO:</h3>";
    echo "ID Alat: " . $id . "<br>";
    echo "Stok Baru: " . $stok_baru . "<br>";
    
    $query = "UPDATE alat SET stok = $stok_baru WHERE id_alat = '$id'";
    echo "Query: " . $query . "<br>";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<span style='color:green'>✅ UPDATE BERHASIL!</span><br>";
        echo "Jumlah baris terpengaruh: " . mysqli_affected_rows($koneksi) . "<br>";
    } else {
        echo "<span style='color:red'>❌ GAGAL: " . mysqli_error($koneksi) . "</span><br>";
    }
    echo "<hr>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Update Stok</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #0284c7; color: white; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px; }
        input { width: 80px; padding: 5px; }
        button { background: #0284c7; color: white; border: none; padding: 5px 15px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔧 TEST UPDATE STOK</h1>
    
    <h2>Form Update Stok</h2>
    <form method="POST">
        <label>Pilih Alat:</label>
        <select name="id_alat" required>
            <option value="">-- Pilih --</option>
            <?php 
            mysqli_data_seek($alat, 0);
            while($row = mysqli_fetch_assoc($alat)): 
            ?>
            <option value="<?= $row['id_alat']; ?>">
                <?= $row['id_alat'] . ' - ' . $row['nama_alat'] . ' (Stok: ' . $row['stok'] . ')'; ?>
            </option>
            <?php endwhile; ?>
        </select>
        
        <br><br>
        <label>Stok Baru:</label>
        <input type="number" name="stok" value="0" required>
        
        <br><br>
        <button type="submit" name="update">UPDATE STOK</button>
    </form>
    
    <h2>Data Alat Saat Ini</h2>
    <table>
        <thead>
            <tr><th>ID Alat</th><th>Kode</th><th>Nama Alat</th><th>Stok</th><th>Aksi Manual</th></tr>
        </thead>
        <tbody>
            <?php
            // Ambil data terbaru
            $alat2 = mysqli_query($koneksi, "SELECT * FROM alat");
            while($row = mysqli_fetch_assoc($alat2)):
            ?>
            <tr>
                <td><?= $row['id_alat']; ?></td>
                <td><?= $row['kode_alat']; ?></td>
                <td><?= $row['nama_alat']; ?></td>
                <td><strong id="stok_<?= $row['id_alat']; ?>"><?= $row['stok']; ?></strong></td>
                <td>
                    <button onclick="updateStokManual('<?= $row['id_alat']; ?>')">Update via AJAX</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <script>
    function updateStokManual(idAlat) {
        let stokBaru = prompt("Masukkan stok baru untuk alat " + idAlat + ":", 0);
        if (stokBaru !== null) {
            window.location.href = 'test_update_stok.php?id=' + idAlat + '&stok=' + stokBaru;
        }
    }
    </script>
    
    <?php
    // PROSES VIA GET (untuk tombol AJAX)
    if (isset($_GET['id']) && isset($_GET['stok'])) {
        $id = $_GET['id'];
        $stok = (int)$_GET['stok'];
        $query = "UPDATE alat SET stok = $stok WHERE id_alat = '$id'";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Berhasil update stok!'); window.location.href='test_update_stok.php';</script>";
        } else {
            echo "<script>alert('Gagal: " . mysqli_error($koneksi) . "');</script>";
        }
    }
    ?>
</body>
</html>