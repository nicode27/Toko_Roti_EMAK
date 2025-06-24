<?php
require_once 'config.php'; // This must be the very first line.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Kami - Toko Roti Emak</title>
    <style>
        /* Basic Styles - Copied from other files for consistency */
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

        /* Main Content */
        main {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
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

        .contact-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 2.5rem;
            text-align: center;
        }

        .contact-content p {
            margin-bottom: 1rem;
            color: #444;
            line-height: 1.7;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .contact-item {
            font-size: 1.1rem;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .contact-item strong {
            color: #D2691E;
        }

        .contact-map {
            margin-top: 2rem;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .contact-map iframe {
            width: 100%;
            height: 350px;
            border: 0;
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

            .contact-content {
                padding: 1.5rem;
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
                    <a href="cart.php">🛒 Keranjang</a>
                    <a href="riwayat.php">Riwayat</a>
                    <a href="profile.php">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</a> <a href="logout.php">Keluar</a>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1>Kontak Kami</h1>
            <p>Kami senang mendengar dari Anda! Hubungi kami untuk pertanyaan atau saran.</p>
        </div>

        <div class="contact-content">
            <p>Jangan ragu untuk menghubungi kami melalui informasi di bawah ini atau kunjungi toko fisik kami.</p>

            <div class="contact-info">
                <div class="contact-item">
                    📍 <strong>Alamat:</strong> Jalan Roti Lezat No. 123, Kota Nikmat, 12345
                </div>
                <div class="contact-item">
                    📞 <strong>Telepon:</strong> (021) 123-4567
                </div>
                <div class="contact-item">
                    📧 <strong>Email:</strong> info@tokorotiemak.com
                </div>
                <div class="contact-item">
                    ⏰ <strong>Jam Buka:</strong> Senin - Sabtu: 08.00 - 20.00 WIB
                </div>
            </div>

            <div class="contact-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.398934789523!2d106.8227093147699!3d-6.20876399550756!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f43c2d4a7c03%3A0x7d2b4d4b1a1b1a1b!2sMonumen%20Nasional!5e0!3m2!1sen!2sid!4v1678888888888!5m2!1sen!2sid" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
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
</body>
</html>