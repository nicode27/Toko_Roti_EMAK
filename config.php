<?php
// config.php - Database Configuration
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'toko_roti_emak');

// Create connection
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to format currency
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Base URL
// SESUAIKAN BARIS INI DENGAN PATH ASLI PROYEK ANDA DI HTDOCS
define('BASE_URL', 'http://localhost/PWT/Toko_Roti_EMAK/');
?>