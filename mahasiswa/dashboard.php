<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require_once '../config.php'; // Pastikan ini ada dan mengarah ke config.php
require_once 'templates/header_mahasiswa.php';

$user_id = $_SESSION['user_id'];

// Praktikum Diikuti
$praktikum_diikuti = 0;
$stmt_diikuti = $conn->prepare("SELECT COUNT(*) AS total FROM enrollments WHERE user_id = ?");
$stmt_diikuti->bind_param("i", $user_id);
$stmt_diikuti->execute();
$result_diikuti = $stmt_diikuti->get_result();
if ($result_diikuti->num_rows > 0) {
    $row = $result_diikuti->fetch_assoc();
    $praktikum_diikuti = $row['total'];
}
$stmt_diikuti->close();

// Tugas Selesai (sudah dinilai)
$tugas_selesai = 0;
$stmt_selesai = $conn->prepare("SELECT COUNT(*) AS total FROM laporan WHERE user_id = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $user_id);
$stmt_selesai->execute();
$result_selesai = $stmt_selesai->get_result();
if ($result_selesai->num_rows > 0) {
    $row = $result_selesai->fetch_assoc();
    $tugas_selesai = $row['total'];
}
$stmt_selesai->close();

// Tugas Menunggu (sudah dikumpulkan, tapi belum dinilai)
$tugas_menunggu = 0;
$stmt_menunggu = $conn->prepare("SELECT COUNT(*) AS total FROM laporan WHERE user_id = ? AND nilai IS NULL");
$stmt_menunggu->bind_param("i", $user_id);
$stmt_menunggu->execute();
$result_menunggu = $stmt_menunggu->get_result();
if ($result_menunggu->num_rows > 0) {
    $row = $result_menunggu->fetch_assoc();
    $tugas_menunggu = $row['total'];
}
$stmt_menunggu->close();

// Notifikasi Terbaru (contoh, bisa disesuaikan lebih lanjut)
// Misalnya, notifikasi nilai baru atau batas waktu pengumpulan
$notifikasi_terbaru = [];
// Contoh: Mengambil laporan yang baru dinilai atau yang mendekati deadline (ini akan lebih kompleks)
// Untuk saat ini, kita bisa mengambil beberapa laporan terakhir yang dinilai atau baru diunggah oleh user ini
$stmt_notif = $conn->prepare("SELECT l.tgl_dinilai, l.nilai, l.modul_id, m.nama_modul, p.nama_praktikum
                              FROM laporan l
                              JOIN modul m ON l.modul_id = m.id
                              JOIN praktikum p ON m.praktikum_id = p.id
                              WHERE l.user_id = ? AND l.nilai IS NOT NULL
                              ORDER BY l.tgl_dinilai DESC LIMIT 3");
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$result_notif = $stmt_notif->get_result();
while($row = $result_notif->fetch_assoc()){
    $notifikasi_terbaru[] = [
        'type' => 'nilai_diberikan',
        'modul_name' => $row['nama_modul'],
        'praktikum_name' => $row['nama_praktikum'],
        'nilai' => $row['nilai'],
        'date' => $row['tgl_dinilai']
    ];
}
$stmt_notif->close();


?>


<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-blue-600"><?php echo $praktikum_diikuti; ?></div>
        <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?php echo $tugas_selesai; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Selesai</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?php echo $tugas_menunggu; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Menunggu</div>
    </div>
    
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <ul class="space-y-4">
        <?php if (empty($notifikasi_terbaru)): ?>
            <p class="text-gray-500">Tidak ada notifikasi terbaru.</p>
        <?php else: ?>
            <?php foreach ($notifikasi_terbaru as $notif): ?>
                <?php if ($notif['type'] == 'nilai_diberikan'): ?>
                    <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                        <span class="text-xl mr-4">🔔</span>
                        <div>
                            Nilai untuk <a href="course_detail.php?praktikum_id=<?php // Anda mungkin perlu melewati praktikum_id di sini, sesuaikan ?>#" class="font-semibold text-blue-600 hover:underline"><?php echo htmlspecialchars($notif['modul_name']); ?></a> (Praktikum: <?php echo htmlspecialchars($notif['praktikum_name']); ?>) telah diberikan: **<?php echo htmlspecialchars($notif['nilai']); ?>**
                            <p class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($notif['date'])); ?></p>
                        </div>
                    </li>
                <?php endif; ?>
                <?php // Anda bisa menambahkan tipe notifikasi lain di sini, misal untuk deadline ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </ul>
</div>


<?php
// Panggil Footer
require_once 'templates/footer_mahasiswa.php';
$conn->close(); // Tutup koneksi database di akhir
?>