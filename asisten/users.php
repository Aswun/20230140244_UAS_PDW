<?php
$pageTitle = 'Manajemen Akun Pengguna';
$activePage = 'users';

require_once '../config.php';
require_once 'templates/header.php';

$message = '';

// Handle Delete Operation
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];

    // Prevent deleting the currently logged-in user
    if ($id_to_delete === $_SESSION['user_id']) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Anda tidak dapat menghapus akun Anda sendiri.</p></div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Pengguna berhasil dihapus!</p></div>";
        } else {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menghapus pengguna: " . $conn->error . "</p></div>";
        }
        $stmt->close();
    }
}

// Fetch all users data
$users_list = [];
$result = $conn->query("SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users_list[] = $row;
    }
    $result->free();
} else {
    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Error fetching users: " . $conn->error . "</p></div>";
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold text-gray-800">Daftar Akun Pengguna</h2>
        <a href="user_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
            Tambah Pengguna Baru
        </a>
    </div>

    <?php echo $message; ?>

    <?php if (empty($users_list)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
            <p>Belum ada pengguna yang terdaftar.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Nama</th>
                        <th class="py-3 px-6 text-left">Email</th>
                        <th class="py-3 px-6 text-left">Peran</th>
                        <th class="py-3 px-6 text-left">Tgl. Daftar</th>
                        <th class="py-3 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($users_list as $user): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-3 px-6 text-left capitalize"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="py-3 px-6 text-left"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center">
                                    <a href="user_form.php?id=<?php echo $user['id']; ?>" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): // Prevent deleting self ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                    <?php endif; ?>
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