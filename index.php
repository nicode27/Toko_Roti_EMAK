<?php
require_once 'config.php';

// Get featured products (limit 6)
$conn = getConnection();
$result = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY created_at DESC LIMIT 6");
$featured_products = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Roti Emak - Roti Segar Setiap Hari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f5f0;
            line-height: 1.6;
        }

        /* Header */
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
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-user a:hover {
            background: rgba(255,255,255,0.1);
        }
        /* Dropdown */
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
            z-index: 1000;
            overflow: hidden;
        }

        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(139, 69, 19, 0.8), rgba(210, 105, 30, 0.8)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f5e6d3" width="1200" height="600"/><circle fill="%23e6d7c1" cx="200" cy="200" r="100" opacity="0.5"/><circle fill="%23dac5a8" cx="800" cy="150" r="80" opacity="0.4"/><circle fill="%23ceb48f" cx="1000" cy="400" r="120" opacity="0.3"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 4rem 2rem;
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            font-weight: 500;
        }

        .btn-primary {
            background: #fff;
            color: #8B4513;
        }

        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #8B4513;
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #8B4513;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
        }

        /* Products Section */
        .products-section {
            background: white;
            padding: 4rem 2rem;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .products-section h2 {
            text-align: center;
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f0e68c, #daa520);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #8B4513;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1rem;
        }

        .more-products {
            text-align: center;
        }

        /* Footer */
        footer {
            background: #8B4513;
            color: white;
            text-align: center;
            padding: 2rem;
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

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-cta {
                flex-direction: column;
                align-items: center;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }
        }
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
                    <!-- <a href="cart.php">🛒 Keranjang</a>
                    <a href="riwayat.php">Riwayat</a> -->
                    <!-- Dropdown Pesanan -->
                <ul class="nav-links">
                    <li class="nav-item dropdown" id="pesananDropdown">
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
                    </li>
                </ul>
                    <a href="profile.php">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</a> <a href="logout.php">Keluar</a>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Selamat Datang di Toko Roti Emak</h1>
            <p>Nikmati kelezatan roti segar yang dipanggang dengan cinta setiap hari. Dibuat dari bahan-bahan berkualitas untuk keluarga tercinta.</p>
            <div class="hero-cta">
                <a href="produk.php" class="btn btn-primary">Lihat Produk</a>
                <a href="tentang.php" class="btn btn-outline">Tentang Kami</a>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Mengapa Pilih Toko Roti Emak?</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🥖</div>
                <h3>Segar Setiap Hari</h3>
                <p>Roti dipanggang fresh setiap pagi dengan resep turun temurun yang terjaga kualitasnya</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌾</div>
                <h3>Bahan Berkualitas</h3>
                <p>Menggunakan tepung terbaik dan bahan-bahan pilihan tanpa pengawet berbahaya</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">❤️</div>
                <h3>Dibuat dengan Cinta</h3>
                <p>Setiap roti dibuat dengan penuh perhatian dan kasih sayang untuk keluarga Indonesia</p>
            </div>
        </div>
    </section>

    <section class="products-section">
        <div class="products-container">
            <h2>Produk Unggulan</h2>
            <?php if (!empty($featured_products)): ?>
                <div class="product-grid">
                    <?php foreach ($featured_products as $produk): ?>
                        <div class="product-card">
                        <div class="product-image" style="background: none; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <?php
                            $imagePath = 'uploads/products/' . htmlspecialchars($produk['gambar']);
                            if ($produk['gambar'] && file_exists($imagePath)) {
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($produk['nama_produk']) . '" style="width: 100%; height: 100%; object-fit: cover;">';
                            } else {
                                // Placeholder atau biarkan kosong jika tidak ada gambar
                                echo '<span style="color: #ccc; font-size: 1.2rem;">No Image</span>'; // Teks placeholder sederhana
                            }
                            ?>
                        </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                                <div class="product-price"><?php echo formatRupiah($produk['harga']); ?></div>
                                <a href="produk_detail.php?id=<?php echo $produk['id']; ?>" class="btn btn-primary">Lihat Detail</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="more-products">
                <a href="produk.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Beranda</a>
                <a href="produk.php">Produk</a>
                <a href="tentang.php">Tentang</a>
                <a href="kontak.php">Kontak</a>
            </div>
            <p>&copy; 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown, .profile-dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle, .profile-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    dropdowns.forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('active');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('active');
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                dropdowns.forEach(dropdown => {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            });
            
            // Prevent dropdown from closing when clicking inside menu
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });

        // Add some demo functionality for dropdown items
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('logout')) {
                    e.preventDefault();
                    const itemText = this.textContent.trim();
                    alert(`Navigasi ke: ${itemText}`);
                }
            });
        });
    </script>
</body>
</html>