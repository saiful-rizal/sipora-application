<?php
// =========================
// Konfigurasi Database SIPORA untuk InfinityFree
// =========================

define('DB_HOST', 'sql110.infinityfree.com'); // 1. Host dari InfinityFree
define('DB_PORT', '3306');
define('DB_USER', 'if0_40606857'); // 2. Username dari InfinityFree
define('DB_PASS', 'qEaQYQXyL1dr'); // 3. Password Akun InfinityFree Kamu
define('DB_NAME', 'if0_40606857_db_sipora'); // 4. Nama Database dari InfinityFree

try {
    // Membuat Data Source Name (DSN)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Buat koneksi PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // tampilkan error sebagai exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // hasil query jadi array asosiatif
    ]);

    // echo "Koneksi berhasil!"; // Bisa diaktifkan untuk testing, lalu nonaktifkan lagi

} catch (PDOException $e) {
    // Pada website live, lebih baik sembunyikan detail error dari pengguna
    // untuk alasan keamanan. Tampilkan pesan yang lebih umum.
    die("Terjadi kesalahan pada koneksi database. Silakan coba lagi nanti.");
    
    // Jika sedang debugging, kamu bisa aktifkan baris ini untuk melihat pesan error aslinya:
    // die("Koneksi database gagal: " . $e->getMessage());
}

// =========================
// PENTING: Keamanan
// =========================
// Ganti baris ini dengan kunci rahasia yang benar-benar acak dan panjang!
// Kamu bisa menggunakan generator password online untuk membuatnya.
define('SECRET_KEY', 'buatKunciRahasiaYangPanjangDanAcakMisalnyaA1b2C3d4E5f6G7h8I9j0K');