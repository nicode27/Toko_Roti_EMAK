<?php
require_once '../config.php'; // Adjust path as necessary

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Example: Get some stats for the dashboard
$total_products_query = $conn->query("SELECT COUNT(*) as total FROM produk");
$total_products = $total_products_query->fetch_assoc()['total'];

$total_users_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$total_users = $total_users_query->fetch_assoc()['total'];

$total_orders_query = $conn->query("SELECT COUNT(*) as total FROM pesanan");
$total_orders = $total_orders_query->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Toko Roti Emak</title>
    <style>
        /* Basic Styles - Copied from other files for consistency */
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
            min-height:100%;
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
            max-width: 1000px;
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

        .admin-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card h3 {
            color: #D2691E;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .dashboard-card p {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .dashboard-card .btn {
            background: #8B4513;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .dashboard-card .btn:hover {
            background: #D2691E;
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

            .admin-dashboard-grid {
                grid-template-columns: 1fr;
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
            <h1>Selamat Datang di Dashboard Admin</h1>
            <p>Ringkasan Toko Roti Emak Anda</p>
        </div>

        <div class="admin-dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Produk</h3>
                <p><?php echo $total_products; ?></p>
                <a href="produk_manage.php" class="btn">Lihat Produk</a>
            </div>
            <div class="dashboard-card">
                <h3>Total Pengguna</h3>
                <p><?php echo $total_users; ?></p>
                <a href="users_manage.php" class="btn">Lihat Pengguna</a>
            </div>
            <div class="dashboard-card">
                <h3>Total Pesanan</h3>
                <p><?php echo $total_orders; ?></p>
                <a href="orders_manage.php" class="btn">Lihat Pesanan</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>