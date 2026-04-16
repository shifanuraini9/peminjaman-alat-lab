<?php
session_start();
include "../koneksi.php";

/* ================== CEK LOGIN + ROLE ================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ================== ANTI CACHE (ANTI BACK LOGIN) ================== */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Cek dan tambah kolom status jika belum ada
$cek_status = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'status'");
if(mysqli_num_rows($cek_status) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN status ENUM('Aktif', 'Tidak Aktif') DEFAULT 'Aktif'");
}

// Cek dan tambah kolom id_user jika belum ada
$cek_id_user = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'id_user'");
if(mysqli_num_rows($cek_id_user) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN id_user VARCHAR(20) AFTER id");
}

// Auto fix id_user yang NULL
$cek_null = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE id_user IS NULL OR id_user = '' OR id_user = 'NULL'");
$data_null = mysqli_fetch_assoc($cek_null);
if($data_null['total'] > 0) {
    $fix_query = mysqli_query($koneksi, "SELECT id FROM users WHERE id_user IS NULL OR id_user = '' OR id_user = 'NULL'");
    while($row_fix = mysqli_fetch_assoc($fix_query)) {
        $new_id_fix = "USR" . str_pad($row_fix['id'], 3, "0", STR_PAD_LEFT);
        mysqli_query($koneksi, "UPDATE users SET id_user = '$new_id_fix' WHERE id = '{$row_fix['id']}'");
    }
}

/* ================== CREATE - TAMBAH USER ================== */
if (isset($_POST['tambah'])) {

    $username = mysqli_real_escape_string($koneksi, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';
    $status   = 'Aktif';

    // Validasi: role tidak boleh admin
    if ($role == 'admin') {
        $error = "Tidak dapat menambahkan user dengan role Admin!";
    } elseif ($username && $password && $role) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");

        if (mysqli_num_rows($cek) == 0) {

            // Generate ID User otomatis
            $query_max = mysqli_query($koneksi, "SELECT id_user FROM users ORDER BY id DESC LIMIT 1");
            $last = mysqli_fetch_assoc($query_max);
            if ($last && $last['id_user']) {
                $last_num = (int)substr($last['id_user'], 3);
                $new_num = $last_num + 1;
                $id_user = "USR" . str_pad($new_num, 3, "0", STR_PAD_LEFT);
            } else {
                $id_user = "USR001";
            }

            $query = "INSERT INTO users (id_user, username, password, role, status)
                      VALUES ('$id_user', '$username', '$password_hash', '$role', '$status')";

            if (mysqli_query($koneksi, $query)) {
                header("Location: kelola_user.php?success=tambah");
                exit;
            } else {
                $error = "Gagal menambahkan user: " . mysqli_error($koneksi);
            }
        } else {
            $error = "Username sudah ada!";
        }
    } else {
        $error = "Semua field wajib diisi!";
    }
}

/* ================== UPDATE - EDIT USER ================== */
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $password = $_POST['password'] ?? '';

    // Cek apakah user yang diedit adalah admin
    $cek = mysqli_query($koneksi, "SELECT role FROM users WHERE id='$id'");
    $user_data = mysqli_fetch_assoc($cek);

    if ($user_data['role'] == 'admin') {
        header("Location: kelola_user.php?error=tidak_bisa_edit_admin");
        exit;
    }

    if ($username && $role && $status) {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET 
                      username='$username', 
                      password='$password_hash', 
                      role='$role', 
                      status='$status' 
                      WHERE id='$id'";
        } else {
            $query = "UPDATE users SET 
                      username='$username', 
                      role='$role', 
                      status='$status' 
                      WHERE id='$id'";
        }

        if (mysqli_query($koneksi, $query)) {
            header("Location: kelola_user.php?success=edit");
            exit;
        } else {
            $error = "Gagal mengupdate user: " . mysqli_error($koneksi);
        }
    } else {
        $error = "Semua field wajib diisi!";
    }
}

/* ================== UPDATE - UBAH STATUS USER ================== */
if (isset($_GET['ubah_status'])) {
    $id = intval($_GET['ubah_status']);
    $status_baru = mysqli_real_escape_string($koneksi, $_GET['status']);

    $cek = mysqli_query($koneksi, "SELECT role FROM users WHERE id='$id'");
    $user_data = mysqli_fetch_assoc($cek);

    if ($user_data['role'] != 'admin') {
        $query = "UPDATE users SET status='$status_baru' WHERE id='$id'";
        if (mysqli_query($koneksi, $query)) {
            header("Location: kelola_user.php?success=ubah_status");
            exit;
        }
    } else {
        header("Location: kelola_user.php?error=tidak_bisa_ubah_status_admin");
        exit;
    }
}

/* ================== DELETE - HAPUS USER ================== */
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $data = mysqli_query($koneksi, "SELECT role FROM users WHERE id='$id'");
    $row = mysqli_fetch_assoc($data);

    if ($row && $row['role'] != 'admin') {
        $query = "DELETE FROM users WHERE id='$id'";
        if (mysqli_query($koneksi, $query)) {
            header("Location: kelola_user.php?success=hapus");
            exit;
        }
    } else {
        header("Location: kelola_user.php?error=tidak_bisa_hapus_admin");
        exit;
    }
}

/* ================== READ - AMBIL DATA USER ================== */
$users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY 
    CASE 
        WHEN role = 'admin' THEN 1
        WHEN role = 'petugas' THEN 2
        WHEN role = 'user' THEN 3
        ELSE 4
    END, id");
$total_users = mysqli_num_rows($users);
$username_session = $_SESSION['nama'] ?? 'Admin';

// Hitung statistik
$total_admin = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='admin'"));
$total_petugas = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='petugas'"));
$total_siswa = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='user'"));
$total_aktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE status='Aktif'"));
$total_nonaktif = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE status='Tidak Aktif'"));

// Ambil data user untuk diedit
$edit_user = null;
if (isset($_GET['edit'])) {
    $id_edit = intval($_GET['edit']);
    $query_edit = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id_edit'");
    $edit_user = mysqli_fetch_assoc($query_edit);

    if ($edit_user && $edit_user['role'] == 'admin') {
        header("Location: kelola_user.php?error=tidak_bisa_edit_admin");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ================= GLOBAL ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            display: flex;
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 0 25px;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .role {
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: white;
        }

        .menu-icon {
            margin-right: 12px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            padding: 0 25px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar-footer a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* ================= CONTENT ================= */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            font-size: 24px;
            color: #0f172a;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-email {
            color: #0284c7;
            background: #e0f2fe;
            padding: 8px 16px;
            border-radius: 30px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #0284c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* ================= STATS CARD (UBAH WARNA JADI SENADA BIRU) ================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

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

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-title {
            color: #0369a1;
            font-size: 14px;
            font-weight: 600;
        }

        .stat-icon {
            font-size: 28px;
            opacity: 0.7;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 5px;
        }

        .stat-desc {
            color: #64748b;
            font-size: 12px;
        }

        /* ================= CARD ================= */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h3 {
            color: #0f172a;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0369a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-admin { background: #fef3c7; color: #92400e; }
        .badge-petugas { background: #dbeafe; color: #1e40af; }
        .badge-user { background: #dcfce7; color: #166534; }
        .badge-aktif { background: #dcfce7; color: #166534; }
        .badge-nonaktif { background: #fee2e2; color: #991b1b; }

        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-group a.edit {
            color: #0284c7;
            background: #e0f2fe;
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
        }

        .action-group a.delete {
            color: #dc2626;
            background: #fee2e2;
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
        }

        .action-group span.disabled {
            color: #94a3b8;
            font-size: 12px;
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 6px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-note {
            background: #e0f2fe;
            color: #0369a1;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .close:hover {
            color: #0f172a;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <span class="logo">Admin Lab</span>
            <span class="role"><?= htmlspecialchars($username_session); ?></span>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a>
            <a href="kelola_user.php" class="active"><span class="menu-icon">👥</span> Kelola User</a>
            <a href="kelola_alat.php"><span class="menu-icon">🔧</span> Kelola Alat</a>
            <a href="kelola_kategori.php"><span class="menu-icon">📂</span> Kelola Kategori</a>
            <a href="log_aktivitas.php"><span class="menu-icon">📊</span> Log Aktivitas</a>
        </div>

        <div class="sidebar-footer">
            <a href="../logout.php"><span class="menu-icon">🚪</span> Logout</a>
        </div>
    </div>

    <div class="main-content">

        <div class="top-bar">
            <div class="page-title">Kelola User</div>
            <div class="user-info">
                <div class="user-email"><?= htmlspecialchars($username_session); ?></div>
                <div class="user-avatar"><?= strtoupper(substr($username_session, 0, 1)); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Total User</span><span class="stat-icon">👥</span></div>
                <div class="stat-value"><?= $total_users; ?></div>
                <div class="stat-desc">Semua pengguna</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Admin</span><span class="stat-icon">👑</span></div>
                <div class="stat-value"><?= $total_admin; ?></div>
                <div class="stat-desc">Pengelola sistem</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Petugas</span><span class="stat-icon">🔧</span></div>
                <div class="stat-value"><?= $total_petugas; ?></div>
                <div class="stat-desc">Pengelola alat</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">User</span><span class="stat-icon">📚</span></div>
                <div class="stat-value"><?= $total_siswa; ?></div>
                <div class="stat-desc">Peminjam alat</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Aktif</span><span class="stat-icon">✅</span></div>
                <div class="stat-value"><?= $total_aktif; ?></div>
                <div class="stat-desc">User aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Nonaktif</span><span class="stat-icon">❌</span></div>
                <div class="stat-value"><?= $total_nonaktif; ?></div>
                <div class="stat-desc">User tidak aktif</div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="autoAlert">
                <span>✅</span> User berhasil
                <?php
                if ($_GET['success'] == 'tambah') echo 'ditambahkan';
                elseif ($_GET['success'] == 'edit') echo 'diedit';
                elseif ($_GET['success'] == 'hapus') echo 'dihapus';
                elseif ($_GET['success'] == 'ubah_status') echo 'diubah statusnya';
                ?>!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" id="autoAlertError">
                <span>❌</span>
                <?php
                if ($_GET['error'] == 'tidak_bisa_hapus_admin') echo 'Tidak dapat menghapus user dengan role Admin!';
                elseif ($_GET['error'] == 'tidak_bisa_edit_admin') echo 'Tidak dapat mengedit user dengan role Admin!';
                elseif ($_GET['error'] == 'tidak_bisa_ubah_status_admin') echo 'Tidak dapat mengubah status Admin!';
                else echo 'Terjadi kesalahan!';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error" id="autoAlertErrorPhp"><span>❌</span> <?= $error ?></div>
        <?php endif; ?>

        <!-- FORM TAMBAH USER -->
        <div class="card">
            <div class="card-header">
                <h3><span>➕</span> Tambah User Baru</h3>
            </div>

            <div class="info-note">
                <span>ℹ️</span> Admin hanya dapat menambahkan user dengan role Petugas atau User
            </div>

            <form method="POST" id="formTambahUser">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Masukkan password" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="user">User</option>
                            <option value="petugas">Petugas</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="tambah" class="btn btn-primary" id="btnTambah">Tambah User</button>
            </form>
        </div>

        <!-- TABEL USER -->
        <div class="card">
            <div class="card-header">
                <h3><span>📋</span> Daftar User (Total: <?= $total_users; ?>)</h3>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID User</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($users) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id_user'] ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <?php
                                        if ($user['role'] == 'admin') echo '<span class="badge badge-admin">Admin</span>';
                                        elseif ($user['role'] == 'petugas') echo '<span class="badge badge-petugas">Petugas</span>';
                                        else echo '<span class="badge badge-user">User</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge badge-aktif">✅ Aktif</span>
                                        <?php else: ?>
                                            <?php if ($user['status'] == 'Aktif'): ?>
                                                <span class="badge badge-aktif">✅ Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-nonaktif">❌ Nonaktif</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <?php if ($user['role'] == 'admin'): ?>
                                                <span class="disabled">🔒 Default Admin</span>
                                            <?php else: ?>
                                                <a href="javascript:void(0)" onclick="confirmEdit(<?= $user['id'] ?>)" class="edit">✏️ Edit</a>
                                                <?php if ($user['status'] == 'Aktif'): ?>
                                                    <a href="javascript:void(0)" onclick="confirmUbahStatus(<?= $user['id'] ?>, 'Tidak Aktif', '<?= htmlspecialchars($user['username']) ?>')" class="btn-warning">⭕ Nonaktifkan</a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0)" onclick="confirmUbahStatus(<?= $user['id'] ?>, 'Aktif', '<?= htmlspecialchars($user['username']) ?>')" class="btn-success">✅ Aktifkan</a>
                                                <?php endif; ?>
                                                <a href="javascript:void(0)" onclick="confirmHapus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')" class="delete">🗑️ Hapus</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">Belum ada data user</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer>
            © <?= date('Y'); ?> Sistem Informasi Peminjaman Alat Laboratorium
        </footer>

    </div>

    <!-- MODAL EDIT USER -->
    <?php if ($edit_user): ?>
        <div id="editModal" class="modal" style="display:block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='kelola_user.php'">&times;</span>
                <h3><span>✏️</span> Edit User</h3>
                <form method="POST" id="formEditUser">
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" placeholder="Masukkan password baru">
                        <small style="color:#f59e0b; font-size:12px;">⚠️ Jika diisi, password akan berubah!</small>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="user" <?= $edit_user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                            <option value="petugas" <?= $edit_user['role'] == 'petugas' ? 'selected' : '' ?>>Petugas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Aktif" <?= $edit_user['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Tidak Aktif" <?= $edit_user['status'] == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <button type="submit" name="edit" class="btn btn-primary" id="btnSimpanEdit">Simpan Perubahan</button>
                    <a href="kelola_user.php" style="background:#64748b; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; display:inline-block;">Batal</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Hilangkan alert biasa setelah 3 detik
        setTimeout(function() {
            let alertBox = document.getElementById('autoAlert');
            if (alertBox) alertBox.style.display = 'none';
            let alertError = document.getElementById('autoAlertError');
            if (alertError) alertError.style.display = 'none';
            let alertErrorPhp = document.getElementById('autoAlertErrorPhp');
            if (alertErrorPhp) alertErrorPhp.style.display = 'none';
        }, 3000);

        // Konfirmasi Tambah User
        document.getElementById('formTambahUser')?.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Tambah User',
                text: "Apakah Anda yakin ingin menambahkan user baru?",
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

        // Konfirmasi Edit User (dari modal)
        document.getElementById('formEditUser')?.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Edit User',
                text: "Apakah Anda yakin ingin mengubah data user ini?",
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

        // Fungsi konfirmasi edit dari tabel (redirect ke GET edit)
        function confirmEdit(userId) {
            Swal.fire({
                title: 'Edit User',
                text: "Anda akan mengedit user ini. Lanjutkan?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0284c7',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Edit',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?edit=' + userId;
                }
            });
        }

        // Fungsi konfirmasi ubah status
        function confirmUbahStatus(userId, statusBaru, username) {
            let teks = statusBaru === 'Aktif' ? 'mengaktifkan' : 'menonaktifkan';
            Swal.fire({
                title: 'Konfirmasi Ubah Status',
                text: `Apakah Anda yakin ingin ${teks} user "${username}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?ubah_status=' + userId + '&status=' + statusBaru;
                }
            });
        }

        // Fungsi konfirmasi hapus user
        function confirmHapus(userId, username) {
            Swal.fire({
                title: 'Konfirmasi Hapus User',
                text: `Apakah Anda yakin ingin menghapus user "${username}"? Data yang dihapus tidak dapat dikembalikan!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?hapus=' + userId;
                }
            });
        }

        // Notifikasi sukses dengan SweetAlert2 (jika ada parameter success)
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'User berhasil 
                <?php 
                if ($_GET['success'] == 'tambah') echo 'ditambahkan';
                elseif ($_GET['success'] == 'edit') echo 'diedit';
                elseif ($_GET['success'] == 'hapus') echo 'dihapus';
                elseif ($_GET['success'] == 'ubah_status') echo 'diubah statusnya';
                ?>!',
                confirmButtonColor: '#0284c7',
                timer: 2000,
                showConfirmButton: true
            });
        <?php endif; ?>

        // Notifikasi error dengan SweetAlert2
        <?php if (isset($_GET['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?php 
                if ($_GET['error'] == 'tidak_bisa_hapus_admin') echo 'Tidak dapat menghapus user dengan role Admin!';
                elseif ($_GET['error'] == 'tidak_bisa_edit_admin') echo 'Tidak dapat mengedit user dengan role Admin!';
                elseif ($_GET['error'] == 'tidak_bisa_ubah_status_admin') echo 'Tidak dapat mengubah status Admin!';
                else echo 'Terjadi kesalahan!';
                ?>',
                confirmButtonColor: '#dc2626'
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

        // Hapus parameter URL setelah notifikasi agar tidak muncul terus
        if (window.history.replaceState) {
            let url = new URL(window.location.href);
            if (url.searchParams.has('success') || url.searchParams.has('error')) {
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.toString());
            }
        }
    </script>
</body>

</html>