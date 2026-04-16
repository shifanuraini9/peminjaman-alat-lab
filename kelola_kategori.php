<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Proses tambah kategori
if (isset($_POST['tambah'])) {
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $kode_kategori = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    // Cek apakah ID sudah ada
    $cek = mysqli_query($koneksi, "SELECT * FROM kategori WHERE id_kategori='$id_kategori'");
    if(mysqli_num_rows($cek) > 0) {
        $error = "ID Kategori sudah ada!";
    } else {
        $query = "INSERT INTO kategori (id_kategori, kode_kategori, nama_kategori) 
                  VALUES ('$id_kategori', '$kode_kategori', '$nama_kategori')";
        
        if(mysqli_query($koneksi, $query)) {
            header("Location: kelola_kategori.php?success=tambah");
            exit;
        } else {
            $error = "Gagal menambahkan: " . mysqli_error($koneksi);
        }
    }
}

// Proses edit kategori
if (isset($_POST['edit'])) {
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $kode_kategori = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    $query = "UPDATE kategori SET kode_kategori='$kode_kategori', nama_kategori='$nama_kategori' 
              WHERE id_kategori='$id_kategori'";
    
    if(mysqli_query($koneksi, $query)) {
        header("Location: kelola_kategori.php?success=edit");
        exit;
    } else {
        $error = "Gagal mengedit: " . mysqli_error($koneksi);
    }
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    
    // Cek apakah kategori sedang digunakan di tabel alat
    $cek = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_kategori='$id'");
    if(mysqli_num_rows($cek) > 0) {
        $error = "Kategori tidak bisa dihapus karena masih digunakan oleh alat!";
    } else {
        mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori='$id'");
        header("Location: kelola_kategori.php?success=hapus");
        exit;
    }
}

// Ambil data kategori
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori ASC");

// Hitung statistik
$total_kategori = mysqli_num_rows($kategori);
$total_alat_terkait = 0;

while($row = mysqli_fetch_assoc($kategori)) {
    $alat_terkait = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM alat WHERE id_kategori='{$row['id_kategori']}'"));
    $total_alat_terkait += $alat_terkait;
}
mysqli_data_seek($kategori, 0);

$username = $_SESSION['nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori</title>
    <!-- SweetAlert2 CSS -->
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
        
        /* ================= STATS CARD (SENADA BIRU) ================= */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
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
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        .form-group input:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2,132,199,0.1); }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2,132,199,0.3); }
        .btn-warning { background: #f59e0b; color: white; padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-warning:hover { background: #d97706; transform: translateY(-1px); }
        .btn-secondary { background: #64748b; color: white; padding: 6px 12px; font-size: 12px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-secondary:hover { background: #475569; transform: translateY(-1px); }
        
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 10px; background: #f8fafc; color: #0f172a; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px 10px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        tr:hover td { background: #f8fafc; }
        
        .badge { padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 500; display: inline-block; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        
        .action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { background-color: white; margin: 50px auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; position: relative; animation: slideDown 0.3s; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #64748b; }
        .close:hover { color: #0f172a; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; margin-top: 20px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
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
        <a href="kelola_alat.php"><span class="menu-icon">🔧</span> Kelola Alat</a>
        <a href="kelola_kategori.php" class="active"><span class="menu-icon">📂</span> Kelola Kategori</a>
        <a href="log_aktivitas.php"><span class="menu-icon">📊</span> Log Aktivitas</a>
    </div>
    <div class="sidebar-footer">
        <a href="../logout.php"><span class="menu-icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">📂 Kelola Kategori</div>
        <div class="user-info">
            <div class="user-email"><?= htmlspecialchars($username); ?></div>
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </div>
    
    <!-- Statistik dengan warna biru senada -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $total_kategori; ?></div>
            <div class="stat-title">Total Kategori</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $total_alat_terkait; ?></div>
            <div class="stat-title">Total Alat Terkait</div>
        </div>
    </div>
    
    <!-- Form Tambah Kategori -->
    <div class="card">
        <div class="card-header">
            <h3>➕ Tambah Kategori Baru</h3>
        </div>
        <form method="POST" id="formTambahKategori">
            <div class="form-grid">
                <div class="form-group">
                    <label>ID Kategori</label>
                    <input type="text" name="id_kategori" placeholder="Contoh: KAT001" required>
                </div>
                <div class="form-group">
                    <label>Kode Kategori</label>
                    <input type="text" name="kode_kategori" placeholder="Contoh: KAT-001" required>
                </div>
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" placeholder="Contoh: Komputer" required>
                </div>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah Kategori</button>
        </form>
    </div>
    
    <!-- Tabel Daftar Kategori -->
    <div class="card">
        <div class="card-header">
            <h3>📋 Daftar Kategori</h3>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Kategori</th>
                        <th>Kode Kategori</th>
                        <th>Nama Kategori</th>
                        <th>Jumlah Alat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if($total_kategori == 0): 
                    ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px;">📭 Belum ada kategori</td>
                        </tr>
                    <?php else: ?>
                        <?php while($row = mysqli_fetch_assoc($kategori)): 
                            $jumlah_alat = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM alat WHERE id_kategori='{$row['id_kategori']}'"));
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['id_kategori']); ?></td>
                            <td><?= htmlspecialchars($row['kode_kategori']); ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                            <td><span class="badge badge-info"><?= $jumlah_alat; ?> alat</span></td>
                            <td>
                                <div class="action-group">
                                    <button onclick="openEditModal('<?= $row['id_kategori']; ?>', '<?= htmlspecialchars(addslashes($row['kode_kategori'])); ?>', '<?= htmlspecialchars(addslashes($row['nama_kategori'])); ?>')" class="btn-warning">✏️ Edit</button>
                                    <a href="javascript:void(0)" onclick="confirmHapus('<?= $row['id_kategori']; ?>', '<?= htmlspecialchars(addslashes($row['nama_kategori'])); ?>')" class="btn-secondary">🗑️ Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <footer>© <?= date('Y'); ?> Peminjaman Alat Laboratorium</footer>
</div>

<!-- Modal Edit -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>✏️ Edit Kategori</h3>
        <form method="POST" id="formEditKategori">
            <input type="hidden" name="id_kategori" id="edit_id_kategori">
            <div class="form-group">
                <label>Kode Kategori</label>
                <input type="text" name="kode_kategori" id="edit_kode_kategori" required>
            </div>
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="nama_kategori" id="edit_nama_kategori" required>
            </div>
            <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Konfirmasi Tambah Kategori
document.getElementById('formTambahKategori')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Tambah Kategori',
        text: "Apakah Anda yakin ingin menambahkan kategori baru?",
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

// Konfirmasi Edit Kategori
document.getElementById('formEditKategori')?.addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Edit Kategori',
        text: "Apakah Anda yakin ingin mengubah data kategori ini?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Fungsi konfirmasi hapus kategori
function confirmHapus(idKategori, namaKategori) {
    Swal.fire({
        title: 'Konfirmasi Hapus Kategori',
        text: `Apakah Anda yakin ingin menghapus kategori "${namaKategori}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?hapus=' + idKategori;
        }
    });
}

function openEditModal(id, kode, nama) {
    document.getElementById('edit_id_kategori').value = id;
    document.getElementById('edit_kode_kategori').value = kode;
    document.getElementById('edit_nama_kategori').value = nama;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Notifikasi sukses dengan SweetAlert2
<?php if (isset($_GET['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Kategori berhasil 
        <?php 
        if ($_GET['success'] == 'tambah') echo 'ditambahkan';
        elseif ($_GET['success'] == 'edit') echo 'diperbarui';
        elseif ($_GET['success'] == 'hapus') echo 'dihapus';
        ?>!',
        confirmButtonColor: '#0284c7',
        timer: 2000,
        showConfirmButton: true
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

// Hapus parameter URL setelah notifikasi
if (window.history.replaceState) {
    let url = new URL(window.location.href);
    if (url.searchParams.has('success')) {
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.toString());
    }
}
</script>

</body>
</html>