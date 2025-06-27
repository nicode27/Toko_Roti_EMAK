<?php
// Pastikan config.php sudah di-require di file utama yang memanggil navbar ini
// require_once 'config.php'; // Atau '../config.php' tergantung lokasinya

$user_data = []; // Initialize user data
$upload_dir = 'uploads/profiles/'; // Define upload directory relative to the file using this navbar

if (function_exists('isLoggedIn') && isLoggedIn()) { // Check if function exists before calling
    $conn = getConnection(); // Make sure getConnection() is available
    $stmt = $conn->prepare("SELECT nama, foto_profil FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
}

// Ensure $user_data has default values if not logged in or user_id invalid
if (!$user_data) {
    $user_data = ['nama' => 'Guest', 'foto_profil' => ''];
}
?>
<header>
    <nav>
        <div class="logo">Toko Roti Emak</div>
        <ul class="nav-links">
            <li><a href="<?php echo BASE_URL; ?>index.php">Beranda</a></li>
            <li><a href="<?php echo BASE_URL; ?>produk.php">Produk</a></li>
            <li><a href="<?php echo BASE_URL; ?>tentang.php">Tentang</a></li>
            <li><a href="<?php echo BASE_URL; ?>kontak.php">Kontak</a></li>
        </ul>
        <div class="nav-user">
            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                <div class="dropdown" id="pesananDropdown">
                    <button class="dropdown-toggle">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                        </svg>
                        Pesanan
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?php echo BASE_URL; ?>cart.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M17,18C17.56,18 18,17.56 18,17V5C18,4.44 17.56,4 17,4H7C6.44,4 6,4.44 6,5V17C6,17.56 6.44,18 7,18H17M17,2A2,2 0 0,1 19,4V18A2,2 0 0,1 17,20H7C6.46,20 5.96,19.79 5.59,19.41C5.21,19.04 5,18.53 5,18V4A2,2 0 0,1 7,2H17Z"/>
                            </svg>
                            Keranjang
                        </a>
                        <a href="<?php echo BASE_URL; ?>riwayat.php" class="dropdown-item">
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
                        <a href="<?php echo BASE_URL; ?>profile.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item logout">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php">Masuk</a>
                <a href="<?php echo BASE_URL; ?>register.php">Daftar</a>
            <?php endif; ?>
        </div>
    </nav>
</header>