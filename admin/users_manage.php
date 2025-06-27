<?php
require_once '../config.php';

// Periksa apakah pengguna sudah login dan adalah admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$error = '';
$success = '';

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id_to_delete = (int)$_POST['user_id'];

    // Jangan izinkan admin menghapus dirinya sendiri
    if ($user_id_to_delete === $_SESSION['user_id']) {
        $error = 'Anda tidak dapat menghapus akun Anda sendiri!';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'"); // Hanya hapus customer
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $success = 'Pengguna berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus pengguna: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Ambil semua pengguna (kecuali admin lain, opsional)
$result = $conn->query("SELECT id, nama, email, role, created_at FROM users ORDER BY created_at");
$users = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Dashboard Admin</title>
    <style>
        /* Gaya dasar dari dashboard admin (salin dari admin/dashboard.php) */
        * {
            margin: 0; 
            padding: 0; 
            box-sizing: border-box;
        }

        html {
            height: 100%;
        }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f5f0;
            line-height: 1.6;

            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: #f0e68c;
        }
        .nav-user {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .nav-user a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px; transition: all 0.3s;
        }
        .nav-user a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex-grow: 1;
        }
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .page-header h1 {
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        /* Gaya untuk Tabel Pengguna */
        .user-table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f8f5f0;
            color: #8B4513;
            font-weight: bold;
            text-transform: uppercase;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .action-buttons button {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            margin-right: 0.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        /* Footer */
        footer {
            background: #8B4513;
            color: white;
            text-align: center; 
            padding: 2rem;
            margin-top: 2rem;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem; 
            flex-wrap: wrap;
        }
        .footer-links a {
            color: white;
            text-decoration: none;
        }
        .footer-links a:hover {
            color: #f0e68c;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-links {
                gap: 1rem;
            }
            .page-header h1 {
                font-size: 2rem;
            }
            main {
                padding: 0 1rem;
            }
            table {
                font-size: 0.85rem;
            }
            th, td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">🍞 Toko Roti Emak (Admin)</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="produk_manage.php">Kelola Produk</a></li>
                <li><a href="users_manage.php">Kelola User</a></li>
                <li><a href="orders_manage.php">Kelola Pesanan</a></li>
            </ul>
            <div class="nav-user">
                <span>Halo, <?php echo $_SESSION['nama']; ?>!</span>
                <a href="../logout.php">Keluar</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1>Kelola Pengguna</h1>
            <p>Lihat dan hapus akun pengguna</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="user-table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" style="text-align: center;">Belum ada pengguna terdaftar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <?php if ($user['role'] !== 'admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">Hapus</button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color:#6c757d;">Tidak dapat dihapus</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>