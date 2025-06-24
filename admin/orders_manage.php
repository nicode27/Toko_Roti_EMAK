<?php
require_once '../config.php';

// Periksa login dan status admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$error = '';
$success = '';

// Handle update status dari form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $catatan = trim($_POST['catatan'] ?? '');

    $allowed_statuses = ['belum diproses', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
    if (!in_array($new_status, $allowed_statuses)) {
        $error = 'Status tidak valid.';
    } else {
        $stmt_get_old_status = $conn->prepare("SELECT status FROM pesanan WHERE id = ?");
        $stmt_get_old_status->bind_param("i", $order_id);
        $stmt_get_old_status->execute();
        $old_status = $stmt_get_old_status->get_result()->fetch_assoc()['status'] ?? null;
        $stmt_get_old_status->close();

        $is_status_changed = ($old_status !== $new_status);
        $is_note_added = !empty($catatan);

        if (!$is_status_changed && !$is_note_added) {
            $error = 'Tidak ada perubahan status atau catatan baru untuk pesanan #' . $order_id . '.';
        } else {
            $conn->begin_transaction();
            try {
                if ($is_status_changed) {
                    $stmt_update_order = $conn->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
                    $stmt_update_order->bind_param("si", $new_status, $order_id);
                    $stmt_update_order->execute();
                    $stmt_update_order->close();
                }

                $stmt_log_status = $conn->prepare("INSERT INTO riwayat_status_pesanan (id_pesanan, status_lama, status_baru, catatan) VALUES (?, ?, ?, ?)");
                $stmt_log_status->bind_param("isss", $order_id, $old_status, $new_status, $catatan);
                $stmt_log_status->execute();
                $stmt_log_status->close();

                $conn->commit();
                $success = 'Pesanan #' . $order_id . ' berhasil diperbarui.';
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $error = 'Gagal memperbarui status pesanan: ' . $e->getMessage();
            }
        }
    }
}

// Logika pengambilan data yang sudah diperbaiki
$main_query = "
    SELECT 
        p.id, p.tanggal_pesan, p.total_harga, p.status, p.alamat, p.metode_pembayaran, p.nama_penerima, p.detail_pembayaran,
        u.nama AS user_nama
    FROM pesanan p
    JOIN users u ON p.id_user = u.id
    ORDER BY p.tanggal_pesan DESC
";
$orders_result = $conn->query($main_query);
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

$stmt_items = $conn->prepare("
    SELECT pd.jumlah, pd.harga AS harga_satuan_item, pr.nama_produk
    FROM pesanan_detail pd
    JOIN produk pr ON pd.id_produk = pr.id
    WHERE pd.id_pesanan = ?
");

$stmt_logs = $conn->prepare("
    SELECT status_baru, tanggal_perubahan, catatan
    FROM riwayat_status_pesanan
    WHERE id_pesanan = ?
    ORDER BY tanggal_perubahan ASC
");

foreach ($orders as $key => $order) {
    $order_id = $order['id'];
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $orders[$key]['items'] = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt_logs->bind_param("i", $order_id);
    $stmt_logs->execute();
    $orders[$key]['status_logs'] = $stmt_logs->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stmt_items->close();
$stmt_logs->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Dashboard Admin</title>
    <style>
        /* Gaya dasar dari yang Anda berikan */
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
        .alert { padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #363; border: 1px solid #cfc; }
        
        /* Gaya Tabel Pesanan yang Diperbaiki */
        .order-table-container { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th, td { padding: 1rem; text-align: left; vertical-align: middle; }
        th { background-color: #f8f5f0; color: #8B4513; font-weight: bold; text-transform: uppercase; }
        
        /* [PERBAIKAN] Menambahkan batas antar pesanan */
        .order-summary-row {
            border-top: 3px solid #e9ecef;
        }
        .order-summary-row:first-child {
            border-top: none;
        }

        .order-details-row { display: none; }
        .order-details-row > td { padding: 1.5rem; background-color: #fafafa; border-bottom: 3px solid #e9ecef;}
        
        .order-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .detail-box h4 { color: #D2691E; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f0f0f0; }
        .detail-box ul { list-style: none; padding: 0; }
        
        /* [PERBAIKAN] Tata letak info pengiriman yang sejajar */
        .info-list { display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem 1rem; }
        .info-list dt { font-weight: 600; color: #555; }
        .info-list dd { margin: 0; }
        
        /* [PERBAIKAN] Tata letak riwayat status yang rapi */
        .status-timeline { list-style: none; padding: 0; }
        .status-timeline li { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; }
        .status-timeline .icon { font-size: 1.2rem; color: #28a745; line-height: 1.4; }
        .status-timeline .content { flex: 1; }
        .status-timeline .status-header { display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
        .status-timeline .status-header small { font-weight: 400; color: #777; font-size: 0.9em; }
        .status-timeline .note { font-style: italic; color: #666; font-size: 0.9em; margin-top: 4px; padding: 5px 8px; background: #f0f0f0; border-radius: 4px; }
        
        .status-form select, .status-form input { padding: 0.5rem; border-radius: 5px; border: 1px solid #ddd; width: 100%; margin-bottom: 0.5rem; }
        .btn-update-status { background-color: #28a745; color: white; border: none; padding: 0.5rem 0.8rem; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; width: 100%; }
        .btn-update-status:hover { background-color: #218838; }

        .toggle-details { cursor: pointer; color: #007bff; text-decoration: none; font-weight: 500; }
        .toggle-details:hover { text-decoration: underline; }

        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
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

        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <div class="order-table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="5" style="text-align: center;">Belum ada pesanan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="order-summary-row">
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['user_nama']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($order['tanggal_pesan'])); ?></td>
                                <td><strong><?php echo htmlspecialchars(ucwords($order['status'])); ?></strong></td>
                                <td><a href="#" class="toggle-details" onclick="toggleDetails(event, <?php echo $order['id']; ?>)">Rincian</a></td>
                            </tr>
                            <tr class="order-details-row" id="details-<?php echo $order['id']; ?>">
                                <td colspan="5">
                                    <div class="order-details-grid">
                                        <div class="detail-box">
                                            <h4>Rincian Pembelian</h4>
                                            <ul>
                                                <?php foreach($order['items'] as $item): ?>
                                                <li style="display:flex; justify-content:space-between;">
                                                    <span><?php echo htmlspecialchars($item['nama_produk']); ?> (x<?php echo $item['jumlah']; ?>)</span>
                                                    <span><?php echo formatRupiah($item['harga_satuan_item'] * $item['jumlah']); ?></span>
                                                </li>
                                                <?php endforeach; ?>
                                                <li style="display:flex; justify-content:space-between; border-top: 1px solid #ddd; margin-top:10px; padding-top:10px;">
                                                    <strong>Total Harga</strong>
                                                    <strong><?php echo formatRupiah($order['total_harga']); ?></strong>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="detail-box">
                                            <h4>Info Pengiriman & Pembayaran</h4>
                                            <dl class="info-list">
                                                <dt>Penerima:</dt>
                                                <dd><?php echo htmlspecialchars($order['nama_penerima']); ?></dd>
                                                <dt>Alamat:</dt>
                                                <dd><?php echo nl2br(htmlspecialchars($order['alamat'])); ?></dd>
                                                <dt>Metode Bayar:</dt>
                                                <dd><?php echo htmlspecialchars($order['metode_pembayaran']); ?></dd>
                                                <?php if(!empty($order['detail_pembayaran'])): ?>
                                                    <dt>Detail Bayar:</dt>
                                                    <dd><?php echo htmlspecialchars($order['detail_pembayaran']); ?></dd>
                                                <?php endif; ?>
                                            </dl>
                                        </div>
                                        
                                        <div class="detail-box">
                                            <h4>Update Status Pesanan</h4>
                                            <form method="POST" action="" class="status-form">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status">
                                                    <option value="belum diproses" <?php echo ($order['status'] == 'belum diproses') ? 'selected' : ''; ?>>Belum Diproses</option>
                                                    <option value="diproses" <?php echo ($order['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                                    <option value="dikirim" <?php echo ($order['status'] == 'dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                                                    <option value="selesai" <?php echo ($order['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="dibatalkan" <?php echo ($order['status'] == 'dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
                                                </select>
                                                <input type="text" name="catatan" placeholder="Tulis catatan (misal: No. Resi)">
                                                <button type="submit" class="btn-update-status">Simpan Perubahan</button>
                                            </form>
                                        </div>

                                        <div class="detail-box">
                                            <h4>Riwayat Status</h4>
                                            <ul class="status-timeline">
                                                <?php foreach($order['status_logs'] as $log): ?>
                                                <li>
                                                    <span class="icon">✓</span>
                                                    <div class="content">
                                                        <div class="status-header">
                                                            <span><?php echo htmlspecialchars(ucwords($log['status_baru'])); ?></span>
                                                            <small><?php echo date('d M Y, H:i', strtotime($log['tanggal_perubahan'])); ?></small>
                                                        </div>
                                                        <?php if(!empty($log['catatan'])): ?>
                                                            <p class="note"><?php echo htmlspecialchars($log['catatan']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
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
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
    <script>
        function toggleDetails(event, orderId) {
            event.preventDefault();
            const detailsRow = document.getElementById('details-' + orderId);
            const toggleLink = event.target;
            
            if (detailsRow.style.display === 'table-row') {
                detailsRow.style.display = 'none';
                toggleLink.textContent = 'Rincian';
            } else {
                detailsRow.style.display = 'table-row';
                toggleLink.textContent = 'Tutup';
            }
        }
    </script>
</body>
</html>