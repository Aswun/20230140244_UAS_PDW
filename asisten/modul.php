<?php
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';

require_once '../config.php';
require_once 'templates/header.php';

$message = '';
$selected_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;

// Handle Delete Operation
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // First, get the file_materi path to delete the file from the server
    $stmt_select = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select->num_rows > 0) {
        $modul_data = $result_select->fetch_assoc();
        $file_path = '../uploads/materi/' . $modul_data['file_materi'];
        if (file_exists($file_path) && !empty($modul_data['file_materi'])) {
            unlink($file_path); // Delete the file
        }
    }
    $stmt_select->close();

    $stmt_delete = $conn->prepare("DELETE FROM modul WHERE id = ?");
    $stmt_delete->bind_param("i", $id);
    if ($stmt_delete->execute()) {
        $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Modul berhasil dihapus!</p></div>";
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menghapus modul: " . $conn->error . "</p></div>";
    }
    $stmt_delete->close();
}

// Fetch praktikum list for filtering
$praktikum_options = [];
$result_praktikum = $conn->query("SELECT id, nama_praktikum FROM praktikum ORDER BY nama_praktikum ASC");
if ($result_praktikum) {
    while ($row = $result_praktikum->fetch_assoc()) {
        $praktikum_options[] = $row;
    }
    $result_praktikum->free();
}

// Fetch modul data based on selected praktikum
$modul_list = [];
$query = "SELECT m.id, m.nama_modul, m.deskripsi_modul, m.file_materi, p.nama_praktikum 
          FROM modul m JOIN praktikum p ON m.praktikum_id = p.id";
$params = [];
$types = '';

if ($selected_praktikum_id > 0) {
    $query .= " WHERE m.praktikum_id = ?";
    $params[] = $selected_praktikum_id;
    $types .= 'i';
}
$query .= " ORDER BY m.created_at DESC";

$stmt_modul = $conn->prepare($query);
if ($selected_praktikum_id > 0) {
    $stmt_modul->bind_param($types, ...$params);
}
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();

if ($result_modul) {
    while ($row = $result_modul->fetch_assoc()) {
        $modul_list[] = $row;
    }
    $result_modul->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching modul: " . $conn->error . "</p></div>";
}
$stmt_modul->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold text-gray-800">Daftar Modul</h2>
        <a href="modul_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
            Tambah Modul Baru
        </a>
    </div>

    <?php echo $message; ?>

    <div class="mb-4">
        <label for="filter_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Filter Berdasarkan Praktikum:</label>
        <select id="filter_praktikum" onchange="window.location.href = 'modul.php?praktikum_id=' + this.value" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="0">Semua Praktikum</option>
            <?php foreach ($praktikum_options as $praktikum): ?>
                <option value="<?php echo $praktikum['id']; ?>" <?php echo ($praktikum['id'] == $selected_praktikum_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (empty($modul_list)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p>Belum ada modul yang ditambahkan <?php echo ($selected_praktikum_id > 0) ? 'untuk praktikum ini' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Praktikum</th>
                        <th class="py-3 px-6 text-left">Nama Modul</th>
                        <th class="py-3 px-6 text-left">Deskripsi</th>
                        <th class="py-3 px-6 text-left">Materi</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($modul_list as $modul): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($modul['nama_modul']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars(substr($modul['deskripsi_modul'], 0, 100)); ?>...</td>
                            <td class="py-3 px-6 text-left">
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline">Unduh Materi</a>
                                <?php else: ?>
                                    Tidak Ada
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center">
                                    <a href="modul_form.php?id=<?php echo $modul['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <a href="modul.php?action=delete&id=<?php echo $modul['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini? File materi terkait juga akan dihapus.');">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>