<?php
require_once 'config.php';

// Initialize user data and upload directory
$user_data = [];
$upload_dir = 'uploads/profiles/'; // For profile pictures

// Fetch user data if logged in
if (isLoggedIn()) {
    $conn_local = getConnection();
    $stmt_user = $conn_local->prepare("SELECT nama, foto_profil FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $_SESSION['user_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();
    $conn_local->close();
}

// Ensure $user_data has default values if not logged in or user_id invalid
if (!$user_data) {
    $user_data = ['nama' => 'Guest', 'foto_profil' => ''];
}

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
        html {
            height: 100%; /* Ensure html takes full height */
        }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f8f5f0; 
            line-height: 1.6; 
            display: flex; /* Enable flexbox */
            flex-direction: column; /* Stack children vertically */
            min-height: 100vh; /* Ensure body takes at least full viewport height */
        }
        header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
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
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
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
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-user a:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .dropdown-toggle::after {
            content: '▼';
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .dropdown.active .dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 2000;
            overflow: hidden;
            margin-top: 5px;
        }

        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a.dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-menu a.dropdown-item .icon {
            fill: #333;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Profile Dropdown Specific Styles */
        .profile-dropdown {
            position: relative;
        }

        .profile-toggle {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        .profile-name {
            font-weight: 500;
            font-size: 14px;
        }

        .profile-dropdown .dropdown-menu {
            right: 0;
            left: auto;
            min-width: 200px;
        }

        .dropdown-item.logout {
            color: #dc3545;
            font-weight: 500;
        }

        .dropdown-item.logout:hover {
            background-color: #fff5f5;
        }

        /* Icon styles */
        .icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            flex-shrink: 0;
        }
        
        main { 
            max-width: 1000px; 
            margin: 2rem auto; 
            padding: 0 2rem; 
            flex-grow: 1; /* Allow main content to grow and push footer */
        }
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
        
        footer { 
            background: #8B4513; 
            color: white; 
            text-align: center; 
            padding: 2rem; 
            margin-top: 2rem; 
        } /* Footer CSS based on index.php and general standards */
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }
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
                <div class="dropdown" id="pesananDropdown">
                    <button class="dropdown-toggle">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                        </svg>
                        Pesanan
                    </button>
                    <div class="dropdown-menu">
                        <a href="cart.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M17,18C17.56,18 18,17.56 18,17V5C18,4.44 17.56,4 17,4H7C6.44,4 6,4.44 6,5V17C6,17.56 6.44,18 7,18H17M17,2A2,2 0 0,1 19,4V18A2,2 0 0,1 17,20H7C6.46,20 5.96,19.79 5.59,19.41C5.21,19.04 5,18.53 5,18V4A2,2 0 0,1 7,2H17Z"/>
                            </svg>
                            Keranjang
                        </a>
                        <a href="riwayat.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3"/>
                            </svg>
                            Riwayat
                        </a>
                    </div>
                </div>

                <div class="dropdown profile-dropdown" id="profileDropdown">
                    <button class="profile-toggle">
                        <img src="<?php echo ($user_data['foto_profil'] && file_exists($upload_dir . $user_data['foto_profil'])) ? $upload_dir . htmlspecialchars($user_data['foto_profil']) : $upload_dir . 'default.png'; ?>" alt="Profile" class="profile-avatar">
                        <span class="profile-name"><?php echo htmlspecialchars($user_data['nama']); ?></span>
                        <svg class="icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24">
                            <path d="M7,10L12,15L17,10H7Z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <a href="logout.php" class="dropdown-item logout">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>
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
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Beranda</a>
                <a href="produk.php">Produk</a>
                <a href="tentang.php">Tentang</a>
                <a href="kontak.php">Kontak</a>
            </div>
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown, .profile-dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle, .profile-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Cek apakah dropdown ini sudah aktif
                    const isActive = dropdown.classList.contains('active');

                    // Tutup semua dropdown lain terlebih dahulu
                    dropdowns.forEach(otherDropdown => {
                        otherDropdown.classList.remove('active');
                    });
                    
                    // Jika dropdown yang diklik belum aktif, aktifkan
                    if (!isActive) {
                        dropdown.classList.add('active');
                    }
                });
            }
        });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                dropdowns.forEach(dropdown => {
                    // Jika area yang diklik bukan bagian dari dropdown, tutup dropdown tersebut
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            });

            
            // Prevent dropdown from closing when clicking inside menu
            const logoutButton = document.querySelector('.dropdown-item.logout');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    if (e.target.getAttribute('href') === 'logout.php') {
                        if (!confirm('Apakah Anda yakin ingin keluar?')) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>