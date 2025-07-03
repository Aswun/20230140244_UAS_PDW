<?php
session_start();

// Cek jika pengguna sudah login, arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
        exit();
    }
}

// Jika belum login, tampilkan halaman utama dengan opsi login/register
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Pengumpulan Tugas Praktikum (SIMPRAK)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f4f8; /* Light blue-gray background */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl max-w-lg w-full text-center">
        <h1 class="text-4xl font-extrabold text-blue-800 mb-4">SIMPRAK</h1>
        <p class="text-lg text-gray-700 mb-6">Sistem Informasi Manajemen Praktikum</p>
        <p class="text-gray-600 mb-8">
            Memudahkan pengelolaan kegiatan praktikum, mulai dari pembagian materi, pengumpulan laporan, hingga penilaian tugas.
        </p>
        
        <div class="space-y-4">
            <a href="login.php" class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-300 text-lg">
                Login
            </a>
            <a href="register.php" class="block bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors duration-300 text-lg">
                Daftar Akun Baru
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-8">
            &copy; <?php echo date('Y'); ?> SIMPRAK. All rights reserved.
        </p>
    </div>
</body>
</html>