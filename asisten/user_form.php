<?php
$pageTitle = 'Form Pengguna';
$activePage = 'users';

require_once '../config.php';
require_once 'templates/header.php';

$nama = '';
$email = '';
$role = '';
$user_id = null;
$message = '';
$is_edit_current_user = false;

// Check if it's an edit request
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $is_edit_current_user = ($user_id == $_SESSION['user_id']); // Check if editing own account

    $stmt = $conn->prepare("SELECT nama, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $nama = $user_data['nama'];
        $email = $user_data['email'];
        $role = $user_data['role'];
    } else {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Pengguna tidak ditemukan!</p></div>";
        $user_id = null; // Reset ID if not found
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? ''; // Optional for update
    $role = trim($_POST['role']);
    $user_id = $_POST['user_id'] ?? null;

    if (empty($nama) || empty($email) || empty($role)) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Nama, email, dan peran harus diisi.</p></div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Format email tidak valid!</p></div>";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Peran tidak valid!</p></div>";
    } else {
        // Check if email already exists for another user (for update)
        $sql_check_email = "SELECT id FROM users WHERE email = ?";
        if ($user_id) {
            $sql_check_email .= " AND id != ?";
        }
        $stmt_check_email = $conn->prepare($sql_check_email);
        if ($user_id) {
            $stmt_check_email->bind_param("si", $email, $user_id);
        } else {
            $stmt_check_email->bind_param("s", $email);
        }
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Email sudah terdaftar untuk pengguna lain. Silakan gunakan email lain.</p></div>";
        } else {
            if ($user_id) {
                // Update existing user
                $sql_update = "UPDATE users SET nama = ?, email = ?, role = ?";
                if (!empty($password)) {
                    $sql_update .= ", password = ?";
                }
                $sql_update .= " WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt_update->bind_param("ssssi", $nama, $email, $role, $hashed_password, $user_id);
                } else {
                    $stmt_update->bind_param("sssi", $nama, $email, $role, $user_id);
                }

                if ($stmt_update->execute()) {
                    $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Pengguna berhasil diperbarui!</p></div>";
                    // If current user's data is updated, update session as well
                    if ($is_edit_current_user) {
                        $_SESSION['nama'] = $nama;
                        $_SESSION['role'] = $role;
                    }
                } else {
                    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal memperbarui pengguna: " . $conn->error . "</p></div>";
                }
                $stmt_update->close();
            } else {
                // Insert new user
                if (empty($password)) {
                    $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Password harus diisi untuk pengguna baru.</p></div>";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sql_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);

                    if ($stmt_insert->execute()) {
                        $message = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>Pengguna berhasil ditambahkan!</p></div>";
                        // Clear form fields after successful insert
                        $nama = '';
                        $email = '';
                        $role = '';
                    } else {
                        $message = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>Gagal menambahkan pengguna: " . $conn->error . "</p></div>";
                    }
                    $stmt_insert->close();
                }
            }
        }
        $stmt_check_email->close();
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $user_id ? 'Edit' : 'Tambah'; ?> Pengguna</h2>

    <?php echo $message; ?>

    <form action="user_form.php" method="POST">
        <?php if ($user_id): ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password <?php echo $user_id ? '(Biarkan kosong jika tidak ingin mengubah)' : ''; ?>:</label>
            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" <?php echo $user_id ? '' : 'required'; ?>>
            <?php if ($user_id): ?>
                <p class="text-xs text-gray-600 italic">Isi kolom ini hanya jika Anda ingin mengubah password.</p>
            <?php endif; ?>
        </div>

        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Peran:</label>
            <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">-- Pilih Peran --</option>
                <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">
                <?php echo $user_id ? 'Update' : 'Tambah'; ?> Pengguna
            </button>
            <a href="users.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">
                Batal
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>