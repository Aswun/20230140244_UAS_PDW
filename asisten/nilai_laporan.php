<?php
$pageTitle = 'Nilai Laporan';
$activePage = 'laporan';

require_once '../config.php';
require_once 'templates/header.php';

$laporan_data = null;
$message = '';
$laporan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($laporan_id > 0) {
    // Fetch laporan details
    $stmt = $conn->prepare("SELECT l.id, l.file_laporan, l.nilai, l.feedback, l.tgl_pengumpulan,
                                   m.nama_modul, p.nama_praktikum, u.nama AS nama_mahasiswa, u.email AS email_mahasiswa
                            FROM laporan l
                            JOIN modul m ON l.modul_id = m.id
                            JOIN praktikum p ON m.praktikum_id = p.id
                            JOIN users u ON l.user_id = u.id
                            WHERE l.id = ?");
    $stmt->bind_param("i", $laporan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $laporan_data = $result->fetch_assoc();
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Laporan tidak ditemukan.</p></div>";
    }
    $stmt->close();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>ID Laporan tidak valid.</p></div>";
}

// Handle grading submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $laporan_data) {
    $nilai = trim($_POST['nilai']);
    $feedback = trim($_POST['feedback']);

    // Basic validation for nilai
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Nilai harus angka antara 0-100.</p></div>";
    } else {
        $stmt_update = $conn->prepare("UPDATE laporan SET nilai = ?, feedback = ?, tgl_dinilai = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt_update->bind_param("isi", $nilai, $feedback, $laporan_id);
        if ($stmt_update->execute()) {
            $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Nilai dan feedback berhasil disimpan.</p></div>";
            // Update laporan_data with new values
            $laporan_data['nilai'] = $nilai;
            $laporan_data['feedback'] = $feedback;
        } else {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menyimpan nilai: " . $conn->error . "</p></div>";
        }
        $stmt_update->close();
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Penilaian Laporan</h2>

    <?php echo $message; ?>

    <?php if ($laporan_data): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-gray-700 font-semibold">Mahasiswa:</p>
                <p class="text-gray-900"><?php echo htmlspecialchars($laporan_data['nama_mahasiswa']); ?></p>
            </div>
            <div>
                <p class="text-gray-700 font-semibold">Email Mahasiswa:</p>
                <p class="text-gray-900"><?php echo htmlspecialchars($laporan_data['email_mahasiswa']); ?></p>
            </div>
            <div>
                <p class="text-gray-700 font-semibold">Praktikum:</p>
                <p class="text-gray-900"><?php echo htmlspecialchars($laporan_data['nama_praktikum']); ?></p>
            </div>
            <div>
                <p class="text-gray-700 font-semibold">Modul:</p>
                <p class="text-gray-900"><?php echo htmlspecialchars($laporan_data['nama_modul']); ?></p>
            </div>
            <div>
                <p class="text-gray-700 font-semibold">Tanggal Pengumpulan:</p>
                <p class="text-gray-900"><?php echo date('d M Y H:i', strtotime($laporan_data['tgl_pengumpulan'])); ?></p>
            </div>
            <div>
                <p class="text-gray-700 font-semibold">File Laporan:</p>
                <?php if (!empty($laporan_data['file_laporan'])): ?>
                    <a href="../uploads/laporan/<?php echo htmlspecialchars($laporan_data['file_laporan']); ?>" target="_blank" class="text-blue-600 hover:underline">
                        Unduh Laporan (<?php echo htmlspecialchars($laporan_data['file_laporan']); ?>)
                    </a>
                <?php else: ?>
                    <p class="text-gray-500">Tidak ada file laporan.</p>
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-6">

        <h3 class="text-xl font-semibold text-gray-800 mb-4">Beri Penilaian</h3>
        <form action="nilai_laporan.php?id=<?php echo $laporan_id; ?>" method="POST">
            <div class="mb-4">
                <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100):</label>
                <input type="number" id="nilai" name="nilai" min="0" max="100" value="<?php echo htmlspecialchars($laporan_data['nilai'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-6">
                <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback (Opsional):</label>
                <textarea id="feedback" name="feedback" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($laporan_data['feedback'] ?? ''); ?></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                    Simpan Penilaian
                </button>
                <a href="laporan.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                    Kembali ke Daftar Laporan
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>