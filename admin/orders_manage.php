<?php
require_once '../config.php'; // Adjust path as necessary

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$error = '';
$success = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $catatan = trim($_POST['catatan'] ?? ''); // Catatan opsional, e.g., nomor resi

    // Validasi status yang diizinkan (sesuai ENUM di DB Anda)
    $allowed_statuses = ['belum diproses', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

    if (!in_array($new_status, $allowed_statuses)) {
        $error = 'Status tidak valid.';
    } else {
        // Ambil status lama dari pesanan
        $stmt_get_old_status = $conn->prepare("SELECT status FROM pesanan WHERE id = ?");
        $stmt_get_old_status->bind_param("i", $order_id);
        $stmt_get_old_status->execute();
        $old_status_data = $stmt_get_old_status->get_result()->fetch_assoc();
        $old_status = $old_status_data['status'] ?? null;
        $stmt_get_old_status->close();

        if ($old_status === $new_status) {
            // Jika status sama, periksa apakah ada catatan baru.
            // Jika tidak ada catatan baru dan status sama, baru dianggap tidak ada perubahan.
            if (empty($catatan) || ($catatan === ($old_status_data['catatan'] ?? ''))) { // Asumsi ada kolom catatan di tabel pesanan juga
                $error = 'Status pesanan tidak berubah.';
            } else {
                // Jika ada catatan baru, update catatan walaupun status sama
                $conn->begin_transaction();
                try {
                    // HANYA update catatan di riwayat status, bukan di pesanan utama
                    $stmt_log_status = $conn->prepare("INSERT INTO riwayat_status_pesanan (id_pesanan, status_lama, status_baru, catatan) VALUES (?, ?, ?, ?)");
                    $stmt_log_status->bind_param("isss", $order_id, $old_status, $new_status, $catatan);
                    $stmt_log_status->execute();
                    $stmt_log_status->close();
        
                    $conn->commit();
                    $success = 'Catatan pesanan #' . $order_id . ' berhasil diperbarui.';
                } catch (mysqli_sql_exception $e) {
                    $conn->rollback();
                    $error = 'Gagal memperbarui catatan pesanan: ' . $e->getMessage();
                }
            }
        } else {
            // Jika status berubah
            $conn->begin_transaction();
            try {
                // Update status di tabel pesanan
                $stmt_update_order = $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
                $stmt_update_order->bind_param("si", $new_status, $order_id);
                $stmt_update_order->execute();
                $stmt_update_order->close();
        
                // Masukkan log ke riwayat_status_pesanan
                $stmt_log_status = $conn->prepare("INSERT INTO riwayat_status_pesanan (id_pesanan, status_lama, status_baru, catatan) VALUES (?, ?, ?, ?)");
                $stmt_log_status->bind_param("isss", $order_id, $old_status, $new_status, $catatan);
                $stmt_log_status->execute();
                $stmt_log_status->close();
        
                $conn->commit();
                $success = 'Status pesanan #' . $order_id . ' berhasil diperbarui menjadi "' . htmlspecialchars($new_status) . '".';
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $error = 'Gagal memperbarui status pesanan: ' . $e->getMessage();
            }
        }
    }
}

// Get all orders
$stmt = $conn->prepare("
    SELECT p.id, u.nama as user_nama, p.tanggal_pesan, p.total_harga, p.status, p.alamat, p.metode_pembayaran, p.nama_penerima
    FROM pesanan p
    JOIN users u ON p.id_user = u.id
    ORDER BY p.tanggal_pesan DESC
");
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Dashboard Admin</title>
    <style>
        /* Gaya dasar dari dashboard admin (salin dari admin/dashboard.php) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f5f0; line-height: 1.6; }
        header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; transition: color 0.3s; }
        .nav-links a:hover { color: #f0e68c; }
        .nav-user { display: flex; gap: 1rem; align-items: center; }
        .nav-user a { color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: all 0.3s; }
        .nav-user a:hover { background: rgba(255,255,255,0.1); }
        
        main { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* Gaya untuk Tabel Pesanan */
        .order-table-container {
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
        .status-select, .catatan-input {
            padding: 0.4rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .btn-update-status {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        .btn-update-status:hover {
            background-color: #218838;
        }
        .alert {
            padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;
        }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #363; border: 1px solid #cfc; }

        /* Footer */
        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }

        /* Responsive */
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .page-header h1 { font-size: 2rem; }
            main { padding: 0 1rem; }
            table { font-size: 0.85rem; }
            th, td { padding: 0.75rem; }
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
                <span>Halo, Admin <?php echo $_SESSION['nama']; ?>!</span>
                <a href="../logout.php">Keluar</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1>Kelola Pesanan</h1>
            <p>Lihat dan perbarui status pesanan pelanggan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="order-table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Pesan</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Penerima</th>
                        <th>Alamat</th>
                        <th>Metode Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="9" style="text-align: center;">Belum ada pesanan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_nama']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($order['tanggal_pesan'])); ?></td>
                                <td><?php echo formatRupiah($order['total_harga']); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="belum diproses" <?php echo ($order['status'] == 'belum diproses') ? 'selected' : ''; ?>>Belum Diproses</option>
                                            <option value="diproses" <?php echo ($order['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                            <option value="dikirim" <?php echo ($order['status'] == 'dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                                            <option value="selesai" <?php echo ($order['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                            <option value="dibatalkan" <?php echo ($order['status'] == 'dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
                                        </select>
                                        <input type="text" name="catatan" class="catatan-input" placeholder="No. resi atau catatan" value="">
                                </td>
                                <td><?php echo htmlspecialchars($order['nama_penerima']); ?></td>
                                <td><?php echo htmlspecialchars($order['alamat']); ?></td>
                                <td><?php echo htmlspecialchars($order['metode_pembayaran']); ?></td>
                                <td>
                                        <button type="submit" class="btn-update-status">Update</button>
                                    </form>
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
            <div class="footer-links">
                <a href="dashboard.php">Dashboard Admin</a>
                <a href="../index.php">Kembali ke Situs</a>
            </div>
            <p>© 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>