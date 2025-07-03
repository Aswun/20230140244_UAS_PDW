<?php
$pageTitle = 'Form Praktikum';
$activePage = 'praktikum';

require_once '../config.php';
require_once 'templates/header.php';

$nama_praktikum = '';
$kode_praktikum = '';
$deskripsi = '';
$praktikum_id = null;
$message = '';

// Check if it's an edit request
if (isset($_GET['id'])) {
    $praktikum_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT nama_praktikum, kode_praktikum, deskripsi FROM praktikum WHERE id = ?");
    $stmt->bind_param("i", $praktikum_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $praktikum_data = $result->fetch_assoc();
        $nama_praktikum = $praktikum_data['nama_praktikum'];
        $kode_praktikum = $praktikum_data['kode_praktikum'];
        $deskripsi = $praktikum_data['deskripsi'];
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Praktikum tidak ditemukan!</p></div>";
        $praktikum_id = null; // Reset ID if not found
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $kode_praktikum = trim($_POST['kode_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $praktikum_id = $_POST['praktikum_id'] ?? null;

    if (empty($nama_praktikum) || empty($kode_praktikum)) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Nama praktikum dan kode praktikum harus diisi.</p></div>";
    } else {
        if ($praktikum_id) {
            // Update existing praktikum
            $stmt = $conn->prepare("UPDATE praktikum SET nama_praktikum = ?, kode_praktikum = ?, deskripsi = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama_praktikum, $kode_praktikum, $deskripsi, $praktikum_id);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Praktikum berhasil diperbarui!</p></div>";
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal memperbarui praktikum: " . $conn->error . "</p></div>";
            }
        } else {
            // Insert new praktikum
            $stmt = $conn->prepare("INSERT INTO praktikum (nama_praktikum, kode_praktikum, deskripsi) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama_praktikum, $kode_praktikum, $deskripsi);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Praktikum berhasil ditambahkan!</p></div>";
                // Clear form fields after successful insert
                $nama_praktikum = '';
                $kode_praktikum = '';
                $deskripsi = '';
            } else {
                $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menambahkan praktikum: " . $conn->error . "</p></div>";
            }
        }
        $stmt->close();
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $praktikum_id ? 'Edit' : 'Tambah'; ?> Mata Praktikum</h2>

    <?php echo $message; ?>

    <form action="praktikum_form.php" method="POST">
        <?php if ($praktikum_id): ?>
            <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($praktikum_id); ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($nama_praktikum); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="kode_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Kode Praktikum:</label>
            <input type="text" id="kode_praktikum" name="kode_praktikum" value="<?php echo htmlspecialchars($kode_praktikum); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-6">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                <?php echo $praktikum_id ? 'Update' : 'Simpan'; ?> Praktikum
            </button>
            <a href="praktikum.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                Batal
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>