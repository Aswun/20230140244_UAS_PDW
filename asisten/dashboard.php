<?php
// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Panggil Header dan konfigurasi database
require_once '../config.php'; // Pastikan ini ada dan mengarah ke config.php
require_once 'templates/header.php';

// Ambil data dinamis dari database
$total_modul_diajarkan = 0;
$total_laporan_masuk = 0;
$laporan_belum_dinilai = 0;

// Total Modul Diajarkan
$stmt_modul = $conn->prepare("SELECT COUNT(*) AS total FROM modul");
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();
if ($result_modul->num_rows > 0) {
    $row = $result_modul->fetch_assoc();
    $total_modul_diajarkan = $row['total'];
}
$stmt_modul->close();

// Total Laporan Masuk
$stmt_laporan_masuk = $conn->prepare("SELECT COUNT(*) AS total FROM laporan");
$stmt_laporan_masuk->execute();
$result_laporan_masuk = $stmt_laporan_masuk->get_result();
if ($result_laporan_masuk->num_rows > 0) {
    $row = $result_laporan_masuk->fetch_assoc();
    $total_laporan_masuk = $row['total'];
}
$stmt_laporan_masuk->close();

// Laporan Belum Dinilai
$stmt_belum_dinilai = $conn->prepare("SELECT COUNT(*) AS total FROM laporan WHERE nilai IS NULL");
$stmt_belum_dinilai->execute();
$result_belum_dinilai = $stmt_belum_dinilai->get_result();
if ($result_belum_dinilai->num_rows > 0) {
    $row = $result_belum_dinilai->fetch_assoc();
    $laporan_belum_dinilai = $row['total'];
}
$stmt_belum_dinilai->close();

// Query untuk Aktivitas Laporan Terbaru
$aktivitas_laporan_terbaru = [];
$stmt_aktivitas = $conn->prepare("SELECT l.tgl_pengumpulan, m.nama_modul, u.nama AS nama_mahasiswa 
                                  FROM laporan l 
                                  JOIN modul m ON l.modul_id = m.id 
                                  JOIN users u ON l.user_id = u.id 
                                  ORDER BY l.tgl_pengumpulan DESC LIMIT 5"); // Ambil 5 aktivitas terbaru
$stmt_aktivitas->execute();
$result_aktivitas = $stmt_aktivitas->get_result();
while ($row = $result_aktivitas->fetch_assoc()) {
    $aktivitas_laporan_terbaru[] = $row;
}
$stmt_aktivitas->close();

?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_modul_diajarkan; ?></p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Laporan Masuk</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_laporan_masuk; ?></p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $laporan_belum_dinilai; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if (empty($aktivitas_laporan_terbaru)): ?>
            <p class="text-gray-500">Tidak ada aktivitas laporan terbaru.</p>
        <?php else: ?>
            <?php foreach ($aktivitas_laporan_terbaru as $aktivitas): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                        <span class="font-bold text-gray-500"><?php echo strtoupper(substr($aktivitas['nama_mahasiswa'], 0, 2)); ?></span>
                    </div>
                    <div>
                        <p class="text-gray-800"><strong><?php echo htmlspecialchars($aktivitas['nama_mahasiswa']); ?></strong> mengumpulkan laporan untuk <strong><?php echo htmlspecialchars($aktivitas['nama_modul']); ?></strong></p>
                        <p class="text-sm text-gray-500"><?php echo time_elapsed_string($aktivitas['tgl_pengumpulan']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Fungsi pembantu untuk format waktu (opsional, bisa ditempatkan di file helpers.php jika ada)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($v);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' lalu' : 'baru saja';
}

// 3. Panggil Footer
require_once 'templates/footer.php';
$conn->close(); // Tutup koneksi database di akhir
?>