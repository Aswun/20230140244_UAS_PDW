<?php
$pageTitle = 'Cari Praktikum';
$activePage = 'courses';

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$message = '';
$user_id = $_SESSION['user_id'];

// Handle enrollment request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_praktikum_id'])) {
    $praktikum_id_to_enroll = (int)$_POST['enroll_praktikum_id'];

    // Check if already enrolled
    $stmt_check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND praktikum_id = ?");
    $stmt_check->bind_param("ii", $user_id, $praktikum_id_to_enroll);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $message = "<div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4' role='alert'><p>Anda sudah terdaftar di praktikum ini.</p></div>";
    } else {
        // Enroll student
        $stmt_enroll = $conn->prepare("INSERT INTO enrollments (user_id, praktikum_id) VALUES (?, ?)");
        $stmt_enroll->bind_param("ii", $user_id, $praktikum_id_to_enroll);
        if ($stmt_enroll->execute()) {
            $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Berhasil mendaftar ke praktikum!</p></div>";
        } else {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal mendaftar ke praktikum: " . $conn->error . "</p></div>";
        }
        $stmt_enroll->close();
    }
    $stmt_check->close();
}

// Fetch all available praktikum, and check if the current user is enrolled
$praktikum_list = [];
$query = "SELECT p.id, p.nama_praktikum, p.deskripsi,
                 CASE WHEN e.id IS NOT NULL THEN TRUE ELSE FALSE END AS is_enrolled
          FROM praktikum p
          LEFT JOIN enrollments e ON p.id = e.praktikum_id AND e.user_id = ?
          ORDER BY p.nama_praktikum ASC";

$stmt_praktikum = $conn->prepare($query);
$stmt_praktikum->bind_param("i", $user_id);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();

if ($result_praktikum) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikum_list[] = $row;
    }
    $result_praktikum->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching praktikum: " . $conn->error . "</p></div>";
}
$stmt_praktikum->close();
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Cari Praktikum</h2>
    <?php echo $message; ?>

    <?php if (empty($praktikum_list)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p>Belum ada mata praktikum yang tersedia.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($praktikum_list as $praktikum): ?>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($praktikum['deskripsi'], 0, 150)); ?>...</p>
                    
                    <form action="courses.php" method="POST">
                        <input type="hidden" name="enroll_praktikum_id" value="<?php echo $praktikum['id']; ?>">
                        <?php if ($praktikum['is_enrolled']): ?>
                            <button type="button" class="w-full bg-green-500 text-white font-bold py-2 px-4 rounded-md opacity-75 cursor-not-allowed">
                                Sudah Terdaftar
                            </button>
                        <?php else: ?>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                                Daftar Sekarang
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>