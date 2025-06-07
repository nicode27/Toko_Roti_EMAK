<?php
require_once 'config.php'; // Pastikan ini baris pertama

// Periksa apakah pengguna sudah login
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Ambil riwayat pesanan untuk pengguna saat ini
$stmt = $conn->prepare("
    SELECT p.id AS order_id, p.tanggal_pesan, p.total_harga, p.status, p.alamat, p.metode_pembayaran, p.nama_penerima,
           dp.jumlah, dp.harga AS harga_satuan_item, pr.nama_produk
    FROM pesanan p
    JOIN pesanan_detail dp ON p.id = dp.id_pesanan
    JOIN produk pr ON dp.id_produk = pr.id
    WHERE p.id_user = ?
    ORDER BY p.tanggal_pesan DESC, p.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order_history_raw = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Kelompokkan item pesanan berdasarkan ID pesanan
$order_history = [];
foreach ($order_history_raw as $item) {
    $order_id = $item['order_id'];
    if (!isset($order_history[$order_id])) {
        $order_history[$order_id] = [
            'tanggal_pesan' => $item['tanggal_pesan'],
            'total_harga' => $item['total_harga'],
            'status' => $item['status'],
            'alamat' => $item['alamat'],
            'metode_pembayaran' => $item['metode_pembayaran'],
            'nama_penerima' => $item['nama_penerima'],
            'items' => []
        ];
    }
    $order_history[$order_id]['items'][] = [
        'nama_produk' => $item['nama_produk'],
        'jumlah' => $item['jumlah'],
        'harga_satuan' => $item['harga_satuan_item']
    ];
}

// Ambil riwayat status untuk setiap pesanan
$order_ids = array_keys($order_history);
if (!empty($order_ids)) {
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $types = str_repeat('i', count($order_ids));
    
    $stmt_status = $conn->prepare("
        SELECT id_pesanan, status_lama, status_baru, tanggal_perubahan, catatan
        FROM riwayat_status_pesanan
        WHERE id_pesanan IN ($placeholders)
        ORDER BY tanggal_perubahan ASC
    ");
    $stmt_status->bind_param($types, ...$order_ids);
    $stmt_status->execute();
    $status_history_raw = $stmt_status->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_status->close();

    foreach ($status_history_raw as $status_log) {
        $order_history[$status_log['id_pesanan']]['status_logs'][] = $status_log;
    }
}

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
        .nav-links a:hover { color: #f0e68c; }
        .nav-user { display: flex; gap: 1rem; align-items: center; }
        .nav-user a { color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: all 0.3s; }
        .nav-user a:hover { background: rgba(255,255,255,0.1); }
        
        main { max-width: 1000px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* Gaya Khusus Riwayat Pesanan */
        .order-history-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .order-card {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .order-card:last-child {
            border-bottom: none;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-id {
            font-weight: bold;
            color: #333;
            font-size: 1.1rem;
        }

        .order-date {
            color: #777;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            background-color: #6c757d; /* Default abu-abu */
        }

        /* Warna berdasarkan status ENUM Anda */
        .order-status.belum-diproses { background-color: #ffc107; color: #333; } /* Kuning */
        .order-status.diproses { background-color: #007bff; } /* Biru */
        .order-status.dikirim { background-color: #17a2b8; } /* Teal/Cyan */
        .order-status.selesai { background-color: #28a745; } /* Hijau */
        .order-status.dibatalkan { background-color: #dc3545; } /* Merah */


        .order-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #D2691E;
            text-align: right;
            flex-grow: 1;
        }

        .order-items {
            list-style: none;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .order-item-name { color: #555; }
        .order-item-price { font-weight: 600; }

        .order-details-toggle {
            display: block;
            background-color: #f0f0f0;
            color: #D2691E;
            text-align: center;
            padding: 0.5rem;
            margin-top: 1rem;
            cursor: pointer;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .order-details-toggle:hover {
            background-color: #e0e0e0;
        }

        .order-expanded-details {
            display: none; /* Default hidden */
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #eee;
            font-size: 0.9rem;
            color: #555;
        }
        .order-expanded-details p {
            margin-bottom: 0.5rem;
        }
        .order-expanded-details strong {
            color: #333;
        }

        .status-timeline {
            margin-top: 1rem;
            list-style: none;
            padding: 0;
            border-left: 2px solid #D2691E;
            padding-left: 10px;
        }
        .status-timeline li {
            position: relative;
            margin-bottom: 10px;
            padding-left: 20px;
            line-height: 1.4;
        }
        .status-timeline li::before {
            content: '•';
            position: absolute;
            left: -12px;
            top: 0;
            font-size: 1.5em;
            color: #D2691E;
            line-height: 1;
        }
        .status-timeline li span.date {
            display: block;
            font-size: 0.8em;
            color: #777;
        }
        .status-timeline li span.status-desc {
            font-weight: bold;
            color: #333;
        }
        .status-timeline li span.note {
            display: block;
            font-size: 0.85em;
            color: #888;
            font-style: italic;
        }


        .empty-history {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .empty-history h3 { margin-bottom: 1rem; font-size: 1.5rem; }
        .empty-history p { margin-bottom: 2rem; }

        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; transition: all 0.3s; font-size: 0.9rem; }
        .btn-primary { background: #D2691E; color: white; }
        .btn-primary:hover { background: #B8860B; }

        /* Footer */
        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }

        /* Responsif */
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .page-header h1 { font-size: 2rem; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-total { text-align: left; margin-top: 0.5rem; }
            main { padding: 0 1rem; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">🍞 Toko Roti Emak</div>
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
            <p>Lihat semua pesanan yang pernah Anda lakukan dan status pengirimannya</p>
        </div>

        <?php if (empty($order_history)): ?>
            <div class="empty-history">
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum pernah melakukan pesanan. Mulai belanja sekarang!</p>
                <a href="produk.php" class="btn btn-primary">Lihat Produk</a>
            </div>
        <?php else: ?>
            <div class="order-history-container">
                <?php foreach ($order_history as $order_id => $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Pesanan #<?php echo htmlspecialchars($order_id); ?></div>
                            <div class="order-date">Tanggal: <?php echo date('d M Y H:i', strtotime($order['tanggal_pesan'])); ?></div>
                            <div class="order-status <?php echo str_replace(' ', '-', strtolower(htmlspecialchars($order['status']))); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </div>
                            <div class="order-total">Total: <?php echo formatRupiah($order['total_harga']); ?></div>
                        </div>
                        <ul class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <li class="order-item">
                                    <span class="order-item-name"><?php echo htmlspecialchars($item['nama_produk']); ?> (x<?php echo htmlspecialchars($item['jumlah']); ?>)</span>
                                    <span class="order-item-price"><?php echo formatRupiah($item['harga_satuan'] * $item['jumlah']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="order-details-toggle" onclick="toggleOrderDetails(this)">
                            Lihat Detail Pesanan & Pelacakan ▼
                        </div>

                        <div class="order-expanded-details" id="order-details-<?php echo $order_id; ?>">
                            <p><strong>Nama Penerima:</strong> <?php echo htmlspecialchars($order['nama_penerima']); ?></p>
                            <p><strong>Alamat Pengiriman:</strong> <?php echo nl2br(htmlspecialchars($order['alamat'])); ?></p>
                            <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['metode_pembayaran']); ?></p>
                            
                            <?php if (!empty($order['status_logs'])): ?>
                                <h3>Riwayat Status:</h3>
                                <ul class="status-timeline">
                                    <?php foreach ($order['status_logs'] as $log): ?>
                                        <li>
                                            <span class="status-desc">
                                                <?php echo htmlspecialchars($log['status_baru']); ?>
                                            </span>
                                            <span class="date">
                                                <?php echo date('d M Y H:i', strtotime($log['tanggal_perubahan'])); ?>
                                            </span>
                                            <?php if ($log['catatan']): ?>
                                                <span class="note">Catatan: <?php echo htmlspecialchars($log['catatan']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Belum ada riwayat status yang tersedia untuk pesanan ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Beranda</a>
                <a href="produk.php">Produk</a>
                <a href="tentang.php">Tentang</a>
                <a href="kontak.php">Kontak</a>
            </div>
            <p>© 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>
    <script>
        function toggleOrderDetails(button) {
            const detailsDiv = button.nextElementSibling; // Get the div after the button
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