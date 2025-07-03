<?php
$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$message = '';
$user_id = $_SESSION['user_id'];

// Fetch praktikum that the current user is enrolled in
$enrolled_praktikum_list = [];
$query = "SELECT p.id, p.nama_praktikum, p.deskripsi, e.tgl_enrollment
          FROM praktikum p
          JOIN enrollments e ON p.id = e.praktikum_id
          WHERE e.user_id = ?
          ORDER BY e.tgl_enrollment DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrolled_praktikum_list[] = $row;
    }
    $result->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching enrolled praktikum: " . $conn->error . "</p></div>";
}
$stmt->close();
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Praktikum yang Saya Ikuti</h2>
    <?php echo $message; ?>

    <?php if (empty($enrolled_praktikum_list)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p>Anda belum terdaftar di praktikum manapun. Silakan cari praktikum di halaman <a href="courses.php" class="text-blue-600 hover:underline">Cari Praktikum</a>.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($enrolled_praktikum_list as $praktikum): ?>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($praktikum['deskripsi'], 0, 150)); ?>...</p>
                        <p class="text-xs text-gray-500 mb-4">Terdaftar sejak: <?php echo date('d M Y', strtotime($praktikum['tgl_enrollment'])); ?></p>
                    </div>
                    <a href="course_detail.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md text-center transition-colors duration-300">
                        Lihat Detail & Tugas
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>