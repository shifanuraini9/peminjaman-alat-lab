<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// FORCE NO CACHE - PENTING!
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// Cek apakah tabel kategori ada
$cek_kategori = mysqli_query($koneksi, "SHOW TABLES LIKE 'kategori'");
$kategori_exists = (mysqli_num_rows($cek_kategori) > 0);

// ========== PROSES TAMBAH ALAT ==========
if (isset($_POST['tambah'])) {
    $id_alat = mysqli_real_escape_string($koneksi, $_POST['id_alat']);
    $kode_alat = mysqli_real_escape_string($koneksi, $_POST['kode_alat']);
    $nama_alat = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $stok = (int)$_POST['stok'];
    $id_kategori = isset($_POST['id_kategori']) ? mysqli_real_escape_string($koneksi, $_POST['id_kategori']) : '';
    $foto = isset($_FILES['foto']['name']) ? $_FILES['foto']['name'] : '';
    
    if(!empty($foto)) {
        $target_dir = "../img/";
        $nama_file = time() . '_' . basename($foto);
        $target_file = $target_dir . $nama_file;
        $ekstensi = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($ekstensi, $allowed) && move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto_db = $nama_file;
        } else {
            $foto_db = '';
        }
    } else {
        $foto_db = '';
    }
    
    if($kategori_exists && !empty($id_kategori)) {
        $query = "INSERT INTO alat (id_alat, kode_alat, nama_alat, stok, id_kategori, foto) 
                  VALUES ('$id_alat', '$kode_alat', '$nama_alat', '$stok', '$id_kategori', '$foto_db')";
    } else {
        $query = "INSERT INTO alat (id_alat, kode_alat, nama_alat, stok, foto) 
                  VALUES ('$id_alat', '$kode_alat', '$nama_alat', '$stok', '$foto_db')";
    }
    
    if(mysqli_query($koneksi, $query)) {
        header("Location: kelola_alat.php?success=tambah&t=" . time());
        exit;
    } else {
        $error = "Gagal menambahkan: " . mysqli_error($koneksi);
    }
}

// ========== PROSES EDIT ALAT (SEDERHANA - LANGSUNG UPDATE STOK) ==========
if (isset($_POST['edit'])) {
    $id_alat = mysqli_real_escape_string($koneksi, $_POST['id_alat']);
    $stok = (int)$_POST['stok'];
    
    // Query update stok saja
    $query = "UPDATE alat SET stok = $stok WHERE id_alat = '$id_alat'";
    
    if(mysqli_query($koneksi, $query)) {
        // Redirect dengan timestamp untuk force refresh
        header("Location: kelola_alat.php?success=edit&t=" . time());
        exit;
    } else {
        $error = "Gagal mengedit: " . mysqli_error($koneksi);
    }
}

// ========== PROSES HAPUS ALAT ==========
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    $ambil_foto = mysqli_query($koneksi, "SELECT foto FROM alat WHERE id_alat='$id'");
    $foto_data = mysqli_fetch_assoc($ambil_foto);
    if($foto_data && !empty($foto_data['foto'])) {
        $path_foto = "../img/" . $foto_data['foto'];
        if(file_exists($path_foto)) {
            unlink($path_foto);
        }
    }
    
    mysqli_query($koneksi, "DELETE FROM alat WHERE id_alat='$id'");
    header("Location: kelola_alat.php?success=hapus&t=" . time());
    exit;
}

// ========== AMBIL DATA ALAT ==========
if($kategori_exists) {
    $alat = mysqli_query($koneksi, "
        SELECT a.*, k.nama_kategori 
        FROM alat a
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
        ORDER BY a.id_alat ASC
    ");
    $kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
} else {
    $alat = mysqli_query($koneksi, "SELECT * FROM alat ORDER BY id_alat ASC");
}

// Hitung statistik
$total_alat = mysqli_num_rows($alat);
$total_stok = 0;
$alat_tersedia = 0;
$alat_habis = 0;

$alat_temp = $alat;
while($row = mysqli_fetch_assoc($alat_temp)) {
    $total_stok += $row['stok'];
    if($row['stok'] > 5) $alat_tersedia++;
    elseif($row['stok'] == 0) $alat_habis++;
}
mysqli_data_seek($alat, 0);

$username = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Kelola Alat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f8fafc; display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header { padding: 0 25px; margin-bottom: 40px; }
        .sidebar-header .logo { font-size: 24px; font-weight: 700; }
        .sidebar-header .role { font-size: 14px; background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; display: inline-block; margin-top: 10px; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 25px; color: white; text-decoration: none; border-left: 4px solid transparent; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.15); border-left-color: white; }
        .sidebar-menu .menu-icon { margin-right: 12px; }
        .sidebar-footer { position: absolute; bottom: 30px; width: 100%; padding: 0 25px; }
        .sidebar-footer a { display: flex; align-items: center; padding: 12px 15px; color: white; text-decoration: none; background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar-footer a:hover { background: rgba(255,255,255,0.2); }
        
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .top-bar { background: white; padding: 20px 30px; border-radius: 12px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .page-title { font-size: 24px; font-weight: 600; color: #0f172a; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-email { color: #0284c7; background: #e0f2fe; padding: 8px 16px; border-radius: 30px; }
        .user-avatar { width: 40px; height: 40px; background: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            border-bottom: 3px solid #0284c7;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0284c7, #0369a1, #0284c7);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(2, 132, 199, 0.15); }
        .stat-value { font-size: 32px; font-weight: 700; color: #0284c7; margin-bottom: 5px; }
        .stat-title { color: #0369a1; font-size: 14px; font-weight: 600; }
        
        .card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; }
        .card-header h3 { color: #0f172a; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #64748b; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2,132,199,0.1); }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2,132,199,0.3); }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-warning:hover { background: #d97706; transform: translateY(-1px); }
        .btn-secondary { background: #64748b; color: white; padding: 6px 12px; font-size: 12px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-secondary:hover { background: #475569; transform: translateY(-1px); }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 10px; background: #f8fafc; color: #0f172a; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #334155; }
        tr:hover td { background: #f8fafc; }
        
        .badge { padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-kategori { background: #e0f2fe; color: #0369a1; }
        
        .action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { background-color: white; margin: 50px auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; position: relative; animation: slideDown 0.3s; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #64748b; }
        .close:hover { color: #0f172a; }
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        
        .foto-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; }
        footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; margin-top: 20px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">Admin Lab</span>
        <span class="role"><?= htmlspecialchars($username); ?></span>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a>
        <a href="kelola_user.php"><span class="menu-icon">👥</span> Kelola User</a>
        <a href="kelola_alat.php" class="active"><span class="menu-icon">🔧</span> Kelola Alat</a>
        <a href="kelola_kategori.php"><span class="menu-icon">📂</span> Kelola Kategori</a>
        <a href="log_aktivitas.php"><span class="menu-icon">📊</span> Log Aktivitas</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span class="menu-icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">🔧 Kelola Alat</div>
        <div class="user-info">
            <div class="user-email"><?= htmlspecialchars($username); ?></div>
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total_alat; ?></div>
            <div class="stat-title">Total Jenis Alat</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_stok; ?></div>
            <div class="stat-title">Total Stok</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $alat_tersedia; ?></div>
            <div class="stat-title">Alat Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $alat_habis; ?></div>
            <div class="stat-title">Alat Habis</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>➕ Tambah Alat Baru</h3>
        </div>
        <form method="POST" enctype="multipart/form-data" id="formTambahAlat">
            <div class="form-grid">
                <div class="form-group">
                    <label>ID Alat</label>
                    <input type="text" name="id_alat" placeholder="Contoh: ALT001" required>
                </div>
                <div class="form-group">
                    <label>Kode Alat</label>
                    <input type="text" name="kode_alat" placeholder="Contoh: KMP-001" required>
                </div>
                <div class="form-group">
                    <label>Nama Alat</label>
                    <input type="text" name="nama_alat" placeholder="Contoh: Komputer LED" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" min="0" required>
                </div>
                <?php if($kategori_exists): ?>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="id_kategori">
                        <option value="">-- Pilih Kategori --</option>
                        <?php 
                        if($kategori_exists && isset($kategori)) {
                            mysqli_data_seek($kategori, 0);
                            while($kat = mysqli_fetch_assoc($kategori)): 
                        ?>
                            <option value="<?= $kat['id_kategori']; ?>"><?= htmlspecialchars($kat['nama_kategori']); ?></option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Foto Alat</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah Alat</button>
        </form>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>📋 Daftar Alat</h3>
        </div>
        <div class="table-container">
            <form method="POST" id="formEditStok">
                <input type="hidden" name="edit" value="1">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>ID Alat</th>
                            <th>Kode Alat</th>
                            <th>Nama Alat</th>
                            <?php if($kategori_exists): ?>
                            <th>Kategori</th>
                            <?php endif; ?>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if($total_alat == 0): 
                        ?>
                            <tr>
                                <td colspan="<?= $kategori_exists ? 9 : 8 ?>" style="text-align:center; padding:40px;">📭 Belum ada alat</td>
                            </tr>
                        <?php else: ?>
                            <?php while($row = mysqli_fetch_assoc($alat)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <?php if(!empty($row['foto']) && file_exists("../img/" . $row['foto'])): ?>
                                        <img src="../img/<?= $row['foto']; ?>?t=<?= time(); ?>" class="foto-preview" alt="foto">
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['id_alat']); ?></td>
                                <td><?= htmlspecialchars($row['kode_alat'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['nama_alat']); ?></td>
                                <?php if($kategori_exists): ?>
                                <td>
                                    <?php if(isset($row['nama_kategori']) && $row['nama_kategori']): ?>
                                        <span class="badge badge-kategori"><?= htmlspecialchars($row['nama_kategori']); ?></span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#f1f5f9; color:#64748b;">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <input type="number" name="stok_<?= $row['id_alat']; ?>" id="stok_<?= $row['id_alat']; ?>" value="<?= $row['stok']; ?>" style="width:80px; padding:5px;" step="1">
                                </td>
                                <td>
                                    <?php if($row['stok'] > 5): ?>
                                        <span class="badge badge-success">Tersedia</span>
                                    <?php elseif($row['stok'] > 0): ?>
                                        <span class="badge badge-warning">Menipis</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button type="button" onclick="updateStok('<?= $row['id_alat']; ?>')" class="btn-warning">💾 Update Stok</button>
                                        <a href="javascript:void(0)" onclick="confirmHapus('<?= $row['id_alat']; ?>')" class="btn-secondary">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    
    <footer>© <?= date('Y'); ?> Peminjaman Alat Laboratorium</footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Function update stok via AJAX
function updateStok(idAlat) {
    let stokBaru = document.getElementById('stok_' + idAlat).value;
    
    if(stokBaru === '') {
        Swal.fire('Error', 'Stok harus diisi!', 'error');
        return;
    }
    
    if(parseInt(stokBaru) < 0) {
        Swal.fire('Error', 'Stok tidak boleh negatif!', 'error');
        return;
    }
    
    // Kirim via AJAX
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'edit=1&id_alat=' + encodeURIComponent(idAlat) + '&stok=' + encodeURIComponent(stokBaru)
    })
    .then(response => response.text())
    .then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Stok berhasil diupdate menjadi ' + stokBaru,
            confirmButtonColor: '#0284c7',
            timer: 1500
        }).then(() => {
            window.location.reload();
        });
    })
    .catch(error => {
        Swal.fire('Error', 'Gagal update stok!', 'error');
    });
}

function confirmHapus(idAlat) {
    Swal.fire({
        title: 'Konfirmasi Hapus Alat',
        text: "Apakah Anda yakin ingin menghapus alat ini?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?hapus=' + idAlat + '&t=' + Date.now();
        }
    });
}

// Form Tambah
document.getElementById('formTambahAlat')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Tambah Alat',
        text: "Apakah Anda yakin ingin menambahkan alat baru?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tambah!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Notifikasi sukses
<?php if (isset($_GET['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Alat berhasil 
        <?php 
        if ($_GET['success'] == 'tambah') echo 'ditambahkan';
        elseif ($_GET['success'] == 'edit') echo 'diperbarui';
        elseif ($_GET['success'] == 'hapus') echo 'dihapus';
        ?>!',
        confirmButtonColor: '#0284c7',
        timer: 2000,
        showConfirmButton: true
    }).then(() => {
        // Hapus parameter URL
        let url = new URL(window.location.href);
        url.searchParams.delete('success');
        url.searchParams.delete('t');
        window.history.replaceState({}, document.title, url.toString());
    });
<?php endif; ?>

<?php if (isset($error)): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '<?= addslashes($error); ?>',
        confirmButtonColor: '#dc2626'
    });
<?php endif; ?>
</script>

</body>
</html>