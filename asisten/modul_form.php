<?php
$pageTitle = 'Form Modul';
$activePage = 'modul';

require_once '../config.php';
require_once 'templates/header.php';

$praktikum_id = '';
$nama_modul = '';
$deskripsi_modul = '';
$file_materi = ''; // Existing file name
$modul_id = null;
$message = '';

// Fetch praktikum list for dropdown
$praktikum_options = [];
$result_praktikum = $conn->query("SELECT id, nama_praktikum FROM praktikum ORDER BY nama_praktikum ASC");
if ($result_praktikum) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikum_options[] = $row;
    }
    $result_praktikum->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching praktikum for dropdown: " . $conn->error . "</p></div>";
}


// Check if it's an edit request
if (isset($_GET['id'])) {
    $modul_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT praktikum_id, nama_modul, deskripsi_modul, file_materi FROM modul WHERE id = ?");
    $stmt->bind_param("i", $modul_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $modul_data = $result->fetch_assoc();
        $praktikum_id = $modul_data['praktikum_id'];
        $nama_modul = $modul_data['nama_modul'];
        $deskripsi_modul = $modul_data['deskripsi_modul'];
        $file_materi = $modul_data['file_materi'];
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Modul tidak ditemukan!</p></div>";
        $modul_id = null; // Reset ID if not found
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $praktikum_id = trim($_POST['praktikum_id']);
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi_modul = trim($_POST['deskripsi_modul']);
    $modul_id = $_POST['modul_id'] ?? null;
    $old_file_materi = $_POST['old_file_materi'] ?? ''; // Existing file name from hidden field

    $upload_dir = '../uploads/materi/'; // Define upload directory relative to this script
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
    }

    $new_file_name = $old_file_materi; // Default to old file name for updates

    // Handle file upload
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_materi']['tmp_name'];
        $file_name = basename($_FILES['file_materi']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx']; // Allowed file extensions

        if (!in_array($file_ext, $allowed_ext)) {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Hanya file PDF, DOC, atau DOCX yang diizinkan.</p></div>";
        } else {
            // Generate unique file name
            $new_file_name = uniqid('materi_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination)) {
                // If there was an old file and a new one is uploaded, delete the old one
                if (!empty($old_file_materi) && $old_file_materi != $new_file_name) {
                    $old_file_path = $upload_dir . $old_file_materi;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal mengunggah file materi.</p></div>";
                $new_file_name = $old_file_materi; // Revert to old file if upload fails
            }
        }
    } else if (isset($_POST['remove_file']) && $_POST['remove_file'] == '1') {
        // Handle file removal request
        if (!empty($old_file_materi)) {
            $old_file_path = $upload_dir . $old_file_materi;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        $new_file_name = null; // Set file_materi to NULL in DB
    }


    if (empty($praktikum_id) || empty($nama_modul)) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Praktikum dan Nama Modul harus diisi.</p></div>";
    } else {
        if ($modul_id) {
            // Update existing modul
            $stmt = $conn->prepare("UPDATE modul SET praktikum_id = ?, nama_modul = ?, deskripsi_modul = ?, file_materi = ? WHERE id = ?");
            $stmt->bind_param("isssi", $praktikum_id, $nama_modul, $deskripsi_modul, $new_file_name, $modul_id);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Modul berhasil diperbarui!</p></div>";
                $file_materi = $new_file_name; // Update current file_materi to reflect new upload/removal
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal memperbarui modul: " . $conn->error . "</p></div>";
            }
        } else {
            // Insert new modul
            $stmt = $conn->prepare("INSERT INTO modul (praktikum_id, nama_modul, deskripsi_modul, file_materi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $praktikum_id, $nama_modul, $deskripsi_modul, $new_file_name);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Modul berhasil ditambahkan!</p></div>";
                // Clear form fields after successful insert
                $praktikum_id = ''; // Consider resetting to 0 or keeping current for convenience
                $nama_modul = '';
                $deskripsi_modul = '';
                $file_materi = ''; // Clear file_materi as well
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menambahkan modul: " . $conn->error . "</p></div>";
            }
        }
        $stmt->close();
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $modul_id ? 'Edit' : 'Tambah'; ?> Modul</h2>

    <?php echo $message; ?>

    <form action="modul_form.php" method="POST" enctype="multipart/form-data">
        <?php if ($modul_id): ?>
            <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($modul_id); ?>">
            <input type="hidden" name="old_file_materi" value="<?php echo htmlspecialchars($file_materi); ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Pilih Praktikum:</label>
            <select id="praktikum_id" name="praktikum_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">-- Pilih Praktikum --</option>
                <?php foreach ($praktikum_options as $praktikum): ?>
                    <option value="<?php echo $praktikum['id']; ?>" <?php echo ($praktikum['id'] == $praktikum_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="nama_modul" class="block text-gray-700 text-sm font-bold mb-2">Nama Modul:</label>
            <input type="text" id="nama_modul" name="nama_modul" value="<?php echo htmlspecialchars($nama_modul); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="deskripsi_modul" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul:</label>
            <textarea id="deskripsi_modul" name="deskripsi_modul" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi_modul); ?></textarea>
        </div>

        <div class="mb-6">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX):</label>
            <input type="file" id="file_materi" name="file_materi" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
            <?php if (!empty($file_materi)): ?>
                <p class="text-sm text-gray-600 mt-2">File saat ini: <a href="../uploads/materi/<?php echo htmlspecialchars($file_materi); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($file_materi); ?></a></p>
                <div class="mt-2">
                    <input type="checkbox" id="remove_file" name="remove_file" value="1">
                    <label for="remove_file" class="text-sm text-gray-600">Hapus file materi yang ada</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                <?php echo $modul_id ? 'Update' : 'Simpan'; ?> Modul
            </button>
            <a href="modul.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                Batal
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>