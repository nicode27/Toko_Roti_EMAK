<?php
require_once '../config.php';

// Initialize user data and upload directory for profiles
$user_data = [];
$upload_dir = '../uploads/profiles/'; // Relative path for admin folder

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
    $user_data = ['nama' => $_SESSION['nama'] ?? 'Admin', 'foto_profil' => ''];
}

// Periksa apakah pengguna sudah login dan adalah admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$error = '';
$success = '';

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $product_id = (int)$_POST['product_id'];

    $stmt = $conn->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $success = 'Produk berhasil dihapus!';
    } else {
        $error = 'Gagal menghapus produk: ' . $stmt->error;
    }
    $stmt->close();
}

// Ambil semua produk
$result = $conn->query("SELECT * FROM produk ORDER BY created_at DESC");
$products = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Define upload directory for products
$upload_dir_products = '../uploads/products/'; // Path for product images
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Dashboard Admin</title>
    <style>
        /* Gaya dasar dari dashboard admin (salin dari admin/dashboard.php) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f5f0; line-height: 1.6; }
        header { 
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); 
            color: white; 
            padding: 1rem 0; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            /* Removed position and z-index as they are not in provided dashboard.php */
        }
        nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; /* Removed align-items: center; as not in provided dashboard.php */ }
        .nav-links a { color: white; text-decoration: none; transition: color 0.3s; /* Removed padding and border-radius as not in provided dashboard.php */ }
        .nav-links a:hover { background: rgba(255,255,255,0.1); color: #f0e68c; }
        .nav-user { display: flex; gap: 1rem; align-items: center; }
        .nav-user a { color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: all 0.3s; }
        .nav-user a:hover { background: rgba(255,255,255,0.1); }
        
        /* Removed Dropdown Styles - Not in provided dashboard.php */
        /* Removed Profile Dropdown Specific Styles - Not in provided dashboard.php */
        /* Removed Icon styles - Not in provided dashboard.php */
        
        main { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* Gaya untuk Tabel Produk */
        .product-table-container {
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
        .action-buttons a, .action-buttons button {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            margin-right: 0.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-edit { background-color: #007bff; color: white; border: none; }
        .btn-edit:hover { background-color: #0056b3; }
        .btn-delete { background-color: #dc3545; color: white; border: none; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.5rem;
            float: right; /* Posisikan ke kanan */
        }
        .btn-add:hover { background-color: #218838; }

        .alert {
            padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;
        }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #363; border: 1px solid #cfc; }

        /* Product image in table */
        .product-image-thumbnail {
            width: 50px; /* Small fixed width */
            height: 50px; /* Small fixed height */
            object-fit: cover; /* Crop image to fit */
            border-radius: 5px;
            vertical-align: middle; /* Align with text */
        }

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
            .btn-add { float: none; width: 100%; text-align: center; }
            /* Removed profile-name and dropdown-menu styles for responsive as they are not needed */
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
            <h1>Kelola Produk</h1>
            <p>Tambah, edit, atau hapus produk</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <a href="product_form.php" class="btn-add">Tambah Produk Baru</a>
        <div style="clear: both;"></div> <div class="product-table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gambar</th> <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="6" style="text-align: center;">Belum ada produk.</td></tr> <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td>
                                    <?php
                                    $imagePath = $upload_dir_products . htmlspecialchars($product['gambar']);
                                    if ($product['gambar'] && file_exists($imagePath)) {
                                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['nama_produk']) . '" class="product-image-thumbnail">';
                                    } else {
                                        echo '<span>No Image</span>'; // Placeholder if no image
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['nama_produk']); ?></td>
                                <td><?php echo formatRupiah($product['harga']); ?></td>
                                <td><?php echo htmlspecialchars($product['stok']); ?></td>
                                <td class="action-buttons">
                                    <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn-edit">Edit</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</button>
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
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
    <script>
        // Removed Dropdown JavaScript - Not in provided dashboard.php
    </script>
</body>
</html>