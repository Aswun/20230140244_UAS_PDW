<?php
$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses'; // Keep active on my_courses

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$message = '';
$user_id = $_SESSION['user_id'];
$praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;

$praktikum_data = null;
$modules_data = [];

if ($praktikum_id > 0) {
    // Verify student is enrolled in this praktikum
    $stmt_check_enrollment = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND praktikum_id = ?");
    $stmt_check_enrollment->bind_param("ii", $user_id, $praktikum_id);
    $stmt_check_enrollment->execute();
    $result_check_enrollment = $stmt_check_enrollment->get_result();

    if ($result_check_enrollment->num_rows === 0) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Anda tidak terdaftar di praktikum ini.</p></div>";
        $praktikum_id = 0; // Invalidate praktikum_id
    }
    $stmt_check_enrollment->close();
}

if ($praktikum_id > 0) {
    // Fetch praktikum details
    $stmt_praktikum = $conn->prepare("SELECT nama_praktikum, deskripsi FROM praktikum WHERE id = ?");
    $stmt_praktikum->bind_param("i", $praktikum_id);
    $stmt_praktikum->execute();
    $result_praktikum = $stmt_praktikum->get_result();
    if ($result_praktikum->num_rows > 0) {
        $praktikum_data = $result_praktikum->fetch_assoc();
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Praktikum tidak ditemukan.</p></div>";
        $praktikum_id = 0; // Invalidate praktikum_id
    }
    $stmt_praktikum->close();
}

if ($praktikum_id > 0) {
    // Handle report upload
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_report'])) {
        $modul_id = (int)$_POST['modul_id_for_upload'];
        $upload_dir = '../uploads/laporan/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
            $file_name = basename($_FILES['file_laporan']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'zip']; // Example allowed extensions for reports

            if (!in_array($file_ext, $allowed_ext)) {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Hanya file PDF, DOC, DOCX, atau ZIP yang diizinkan untuk laporan.</p></div>";
            } else {
                // Generate unique file name
                $new_file_name = uniqid('laporan_') . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                // Check if report already exists for this modul and user
                $stmt_check_laporan = $conn->prepare("SELECT id, file_laporan FROM laporan WHERE modul_id = ? AND user_id = ?");
                $stmt_check_laporan->bind_param("ii", $modul_id, $user_id);
                $stmt_check_laporan->execute();
                $result_check_laporan = $stmt_check_laporan->get_result();

                if ($result_check_laporan->num_rows > 0) {
                    // Update existing report, delete old file
                    $old_laporan = $result_check_laporan->fetch_assoc();
                    $old_file_path = $upload_dir . $old_laporan['file_laporan'];
                    if (file_exists($old_file_path) && !empty($old_laporan['file_laporan'])) {
                        unlink($old_file_path);
                    }
                    if (move_uploaded_file($file_tmp_name, $destination)) {
                        $stmt_update_laporan = $conn->prepare("UPDATE laporan SET file_laporan = ?, tgl_pengumpulan = CURRENT_TIMESTAMP, nilai = NULL, feedback = NULL WHERE id = ?");
                        $stmt_update_laporan->bind_param("si", $new_file_name, $old_laporan['id']);
                        if ($stmt_update_laporan->execute()) {
                            $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Laporan berhasil diperbarui!</p></div>";
                        } else {
                            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal memperbarui laporan: " . $conn->error . "</p></div>";
                        }
                        $stmt_update_laporan->close();
                    } else {
                        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal mengunggah file laporan.</p></div>";
                    }
                } else {
                    // Insert new report
                    if (move_uploaded_file($file_tmp_name, $destination)) {
                        $stmt_insert_laporan = $conn->prepare("INSERT INTO laporan (modul_id, user_id, file_laporan) VALUES (?, ?, ?)");
                        $stmt_insert_laporan->bind_param("iis", $modul_id, $user_id, $new_file_name);
                        if ($stmt_insert_laporan->execute()) {
                            $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Laporan berhasil diunggah!</p></div>";
                        } else {
                            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal mengunggah laporan: " . $conn->error . "</p></div>";
                        }
                        $stmt_insert_laporan->close();
                    } else {
                        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal mengunggah file laporan.</p></div>";
                    }
                }
                $stmt_check_laporan->close();
            }
        } else {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Terjadi kesalahan saat mengunggah file. Pastikan file dipilih dan ukurannya tidak melebihi batas server.</p></div>";
        }
    }

    // Fetch all modules for this praktikum, along with student's report and grade
    $query_modules = "SELECT m.id AS modul_id, m.nama_modul, m.deskripsi_modul, m.file_materi,
                             l.id AS laporan_id, l.file_laporan, l.nilai, l.feedback, l.tgl_pengumpulan
                      FROM modul m
                      LEFT JOIN laporan l ON m.id = l.modul_id AND l.user_id = ?
                      WHERE m.praktikum_id = ?
                      ORDER BY m.created_at ASC";

    $stmt_modules = $conn->prepare($query_modules);
    $stmt_modules->bind_param("ii", $user_id, $praktikum_id);
    $stmt_modules->execute();
    $result_modules = $stmt_modules->get_result();

    if ($result_modules) {
        while ($row = $result_modules->fetch_assoc()) {
            $modules_data[] = $row;
        }
        $result_modules->free();
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching modules: " . $conn->error . "</p></div>";
    }
    $stmt_modules->close();
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-8">
    <?php if ($praktikum_data): ?>
        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($praktikum_data['nama_praktikum']); ?></h1>
        <p class="text-gray-600 mb-6"><?php echo nl2br(htmlspecialchars($praktikum_data['deskripsi'])); ?></p>
        <hr class="my-6">

        <h2 class="text-2xl font-bold text-gray-800 mb-4">Modul Praktikum</h2>
        <?php echo $message; ?>

        <?php if (empty($modules_data)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
                <p>Belum ada modul yang tersedia untuk praktikum ini.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($modules_data as $modul): ?>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo nl2br(htmlspecialchars($modul['deskripsi_modul'])); ?></p>

                        <div class="mb-4">
                            <p class="font-medium text-gray-700 mb-1">Materi:</p>
                            <?php if (!empty($modul['file_materi'])): ?>
                                <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Unduh Materi
                                </a>
                            <?php else: ?>
                                <p class="text-gray-500">Tidak ada file materi.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <p class="font-medium text-gray-700 mb-1">Laporan Anda:</p>
                            <?php if (!empty($modul['file_laporan'])): ?>
                                <a href="../uploads/laporan/<?php echo htmlspecialchars($modul['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Lihat Laporan Anda (Diunggah: <?php echo date('d M Y H:i', strtotime($modul['tgl_pengumpulan'])); ?>)
                                </a>
                            <?php else: ?>
                                <p class="text-gray-500">Anda belum mengumpulkan laporan untuk modul ini.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <p class="font-medium text-gray-700 mb-1">Nilai & Feedback:</p>
                            <?php if ($modul['nilai'] !== null): ?>
                                <p class="text-lg font-bold text-blue-700">Nilai: <?php echo htmlspecialchars($modul['nilai']); ?></p>
                                <p class="text-gray-700">Feedback: <?php echo nl2br(htmlspecialchars($modul['feedback'] ?? 'Tidak ada feedback.')); ?></p>
                            <?php else: ?>
                                <p class="text-gray-500">Belum dinilai.</p>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="font-semibold text-gray-800 mt-6 mb-2">Unggah Laporan</h4>
                        <form action="course_detail.php?praktikum_id=<?php echo $praktikum_id; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="modul_id_for_upload" value="<?php echo $modul['modul_id']; ?>">
                            <input type="hidden" name="upload_report" value="1">
                            <div class="mb-3">
                                <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Pilih File Laporan (PDF/DOCX/ZIP):</label>
                                <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" required>
                            </div>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                                Unggah/Perbarui Laporan
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-gray-700">Silakan pilih praktikum dari halaman Praktikum Saya atau Cari Praktikum.</p>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>