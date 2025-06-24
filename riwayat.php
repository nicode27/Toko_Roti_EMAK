<?php
require_once 'config.php'; // Pastikan ini baris pertama

// Periksa apakah pengguna sudah login
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// =================================================================
// PERBAIKAN: LOGIKA PENGAMBILAN DATA YANG LEBIH HANDAL
// =================================================================

// 1. Ambil data pesanan utama untuk pengguna ini
$main_query = "
    SELECT id, tanggal_pesan, total_harga, status, alamat, metode_pembayaran, nama_penerima, detail_pembayaran
    FROM pesanan
    WHERE id_user = ?
    ORDER BY tanggal_pesan DESC
";
$stmt_main = $conn->prepare($main_query);
$stmt_main->bind_param("i", $user_id);
$stmt_main->execute();
$orders = $stmt_main->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_main->close();

// 2. Siapkan statement untuk mengambil detail item dan riwayat untuk setiap pesanan
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

// 3. Loop melalui setiap pesanan dan ambil data detailnya
foreach ($orders as $key => $order) {
    $order_id = $order['id'];

    // Ambil item-item pesanan
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    $orders[$key]['items'] = $items_result->fetch_all(MYSQLI_ASSOC);

    // Ambil log status pesanan
    $stmt_logs->bind_param("i", $order_id);
    $stmt_logs->execute();
    $logs_result = $stmt_logs->get_result();
    $orders[$key]['status_logs'] = $logs_result->fetch_all(MYSQLI_ASSOC);
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
    <title>Riwayat Pesanan - Toko Roti Emak</title>
    <style>
        /* Gaya dasar - disalin dari file lain untuk konsistensi */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f5f0; line-height: 1.6; }
        header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; transition: color 0.3s; }
        .nav-user { display: flex; gap: 1rem; align-items: center; }
        .nav-user a { color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: all 0.3s; }
        
        main { max-width: 1000px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; }

        /* Gaya Khusus Riwayat Pesanan */
        .order-history-container { display: flex; flex-direction: column; gap: 1.5rem; }
        .order-card { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); overflow: hidden; }
        .order-header { padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .order-info { display: flex; flex-direction: column; }
        .order-id { font-weight: bold; font-size: 1.2rem; }
        .order-date { color: #777; font-size: 0.9rem; }
        .order-status { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; color: white; }
        .order-total { font-size: 1.3rem; font-weight: bold; color: #D2691E; text-align: right; }

        /* Warna badge status */
        .order-status.belum-diproses { background-color: #ffc107; color: #333; }
        .order-status.diproses { background-color: #007bff; }
        .order-status.dikirim { background-color: #17a2b8; }
        .order-status.selesai { background-color: #28a745; }
        .order-status.dibatalkan { background-color: #dc3545; }

        .order-items { padding: 0 1.5rem; list-style: none; }
        .order-item { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.95rem; }
        
        .order-details-toggle { display: block; background-color: #f8f9fa; color: #D2691E; text-align: center; padding: 0.75rem; cursor: pointer; transition: background-color 0.3s; }
        .order-details-toggle:hover { background-color: #e9ecef; }
        .order-expanded-details { display: none; padding: 1.5rem; background: #fafafa; border-top: 1px solid #eee; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .details-grid h4 { color: #8B4513; margin-bottom: 1rem; }
        .info-list { display: grid; grid-template-columns: 100px 1fr; gap: 0.5rem; }
        .info-list dt { font-weight: 600; }
        .status-timeline { list-style: none; padding: 0; }
        .status-timeline li { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; }
        .status-timeline .icon { font-size: 1.2rem; color: #28a745; line-height: 1.4; }
        .status-timeline .content { flex: 1; }
        .status-timeline .status-header { display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
        .status-timeline .note { font-style: italic; color: #666; font-size: 0.9em; margin-top: 4px; }
        
        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Toko Roti Emak</div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="tentang.php">Tentang</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <div class="nav-user">
                <?php if (isLoggedIn()): ?>
                    <a href="cart.php">🛒 Keranjang</a>
                    <a href="riwayat.php">Riwayat</a>
                    <a href="profile.php">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</a>
                    <a href="logout.php">Keluar</a>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1>Riwayat Pesanan Anda</h1>
        </div>

        <div class="order-history-container">
            <?php if (empty($orders)): ?>
                <div class="order-card" style="text-align: center; padding: 2rem;">Anda belum memiliki riwayat pesanan.</div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-id">Pesanan #<?php echo htmlspecialchars($order['id']); ?></span>
                                <span class="order-date">Tanggal: <?php echo date('d M Y H:i', strtotime($order['tanggal_pesan'])); ?></span>
                            </div>
                            <div class="order-status <?php echo str_replace(' ', '-', strtolower(htmlspecialchars($order['status']))); ?>">
                                <?php echo htmlspecialchars(ucwords($order['status'])); ?>
                            </div>
                            <div class="order-total">Total: <?php echo formatRupiah($order['total_harga']); ?></div>
                        </div>

                        <ul class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <li class="order-item">
                                    <span><?php echo htmlspecialchars($item['nama_produk']); ?> (x<?php echo htmlspecialchars($item['jumlah']); ?>)</span>
                                    <span><?php echo formatRupiah($item['harga_satuan_item'] * $item['jumlah']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="order-details-toggle" onclick="toggleOrderDetails(this)">
                            Lihat Detail Pesanan & Pelacakan ▼
                        </div>

                        <div class="order-expanded-details">
                            <div class="details-grid">
                                <div>
                                    <h4>Info Pengiriman</h4>
                                    <dl class="info-list">
                                        <dt>Penerima:</dt>
                                        <dd><?php echo htmlspecialchars($order['nama_penerima']); ?></dd>
                                        <dt>Alamat:</dt>
                                        <dd><?php echo nl2br(htmlspecialchars($order['alamat'])); ?></dd>
                                        <dt>Pembayaran:</dt>
                                        <dd><?php echo htmlspecialchars($order['metode_pembayaran']); ?></dd>
                                    </dl>
                                </div>
                                <div>
                                    <h4>Riwayat Status</h4>
                                    <ul class="status-timeline">
                                        <?php foreach ($order['status_logs'] as $log): ?>
                                            <li>
                                                <span class="icon">✓</span>
                                                <div class="content">
                                                    <div class="status-header">
                                                        <span><?php echo htmlspecialchars(ucwords($log['status_baru'])); ?></span>
                                                        <small><?php echo date('d M Y, H:i', strtotime($log['tanggal_perubahan'])); ?></small>
                                                    </div>
                                                    <?php if ($log['catatan']): ?>
                                                        <p class="note">Catatan: <?php echo htmlspecialchars($log['catatan']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        </footer>

    <script>
        function toggleOrderDetails(button) {
            const detailsDiv = button.nextElementSibling;
            if (detailsDiv.style.display === "block") {
                detailsDiv.style.display = "none";
                button.innerHTML = "Lihat Detail Pesanan & Pelacakan ▼";
            } else {
                detailsDiv.style.display = "block";
                button.innerHTML = "Sembunyikan Detail Pesanan & Pelacakan ▲";
            }
        }
    </script>
</body>
</html>