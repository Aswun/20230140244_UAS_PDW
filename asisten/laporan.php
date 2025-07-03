<?php
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';

require_once '../config.php';
require_once 'templates/header.php';

$message = '';
$filter_modul_id = isset($_GET['modul_id']) ? (int)$_GET['modul_id'] : 0;
$filter_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all'; // 'all', 'graded', 'ungraded'

// Fetch filter options: modules and users (mahasiswa)
$modul_options = [];
$result_modul = $conn->query("SELECT id, nama_modul, praktikum_id FROM modul ORDER BY nama_modul ASC");
if ($result_modul) {
    while ($row = $result_modul->fetch_assoc()) {
        $modul_options[] = $row;
    }
    $result_modul->free();
}

$mahasiswa_options = [];
$result_users = $conn->query("SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC");
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $mahasiswa_options[] = $row;
    }
    $result_users->free();
}

// Fetch laporan data with filters
$laporan_list = [];
$query = "SELECT l.id, l.file_laporan, l.nilai, l.feedback, l.tgl_pengumpulan, l.tgl_dinilai,
                 m.nama_modul, p.nama_praktikum, u.nama AS nama_mahasiswa
          FROM laporan l
          JOIN modul m ON l.modul_id = m.id
          JOIN praktikum p ON m.praktikum_id = p.id
          JOIN users u ON l.user_id = u.id";

$conditions = [];
$params = [];
$types = '';

if ($filter_modul_id > 0) {
    $conditions[] = "l.modul_id = ?";
    $params[] = $filter_modul_id;
    $types .= 'i';
}
if ($filter_user_id > 0) {
    $conditions[] = "l.user_id = ?";
    $params[] = $filter_user_id;
    $types .= 'i';
}
if ($filter_status == 'graded') {
    $conditions[] = "l.nilai IS NOT NULL";
} elseif ($filter_status == 'ungraded') {
    $conditions[] = "l.nilai IS NULL";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY l.tgl_pengumpulan DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $laporan_list[] = $row;
    }
    $result->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching laporan: " . $conn->error . "</p></div>";
}
$stmt->close();
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Laporan Masuk</h2>

    <?php echo $message; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
            <label for="filter_modul" class="block text-gray-700 text-sm font-bold mb-2">Filter Modul:</label>
            <select id="filter_modul" onchange="applyFilter()" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="0">Semua Modul</option>
                <?php foreach ($modul_options as $modul): ?>
                    <option value="<?php echo $modul['id']; ?>" <?php echo ($modul['id'] == $filter_modul_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($modul['nama_modul']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_user" class="block text-gray-700 text-sm font-bold mb-2">Filter Mahasiswa:</label>
            <select id="filter_user" onchange="applyFilter()" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="0">Semua Mahasiswa</option>
                <?php foreach ($mahasiswa_options as $mahasiswa): ?>
                    <option value="<?php echo $mahasiswa['id']; ?>" <?php echo ($mahasiswa['id'] == $filter_user_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mahasiswa['nama']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_status" class="block text-gray-700 text-sm font-bold mb-2">Filter Status:</label>
            <select id="filter_status" onchange="applyFilter()" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>Semua Status</option>
                <option value="ungraded" <?php echo ($filter_status == 'ungraded') ? 'selected' : ''; ?>>Belum Dinilai</option>
                <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Sudah Dinilai</option>
            </select>
        </div>
    </div>

    <script>
        function applyFilter() {
            const modulId = document.getElementById('filter_modul').value;
            const userId = document.getElementById('filter_user').value;
            const status = document.getElementById('filter_status').value;
            window.location.href = `laporan.php?modul_id=${modulId}&user_id=${userId}&status=${status}`;
        }
    </script>

    <?php if (empty($laporan_list)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p>Belum ada laporan yang masuk sesuai filter yang dipilih.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Mahasiswa</th>
                        <th class="py-3 px-6 text-left">Praktikum</th>
                        <th class="py-3 px-6 text-left">Modul</th>
                        <th class="py-3 px-6 text-left">Tgl. Pengumpulan</th>
                        <th class="py-3 px-6 text-left">Nilai</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($laporan_list as $laporan): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($laporan['nama_modul']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo date('d M Y H:i', strtotime($laporan['tgl_pengumpulan'])); ?></td>
                            <td class="py-3 px-6 text-left">
                                <?php echo ($laporan['nilai'] !== null) ? htmlspecialchars($laporan['nilai']) : '<span class="text-red-500 font-semibold">Belum Dinilai</span>'; ?>
                            </td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center">
                                    <a href="nilai_laporan.php?id=<?php echo $laporan['id']; ?>" class="w-4 mr-2 transform hover:text-blue-500 hover:scale-110" title="Nilai Laporan">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
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