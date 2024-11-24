<?php
// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fire_alarm";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset để hỗ trợ tiếng Việt
$conn->set_charset("utf8");

// Xử lý khi người dùng gửi form
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $user = trim($_POST['user']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']); // Lấy mật khẩu xác nhận
    $role = isset($_POST['role']) ? (int)$_POST['role'] : 1;

    // Kiểm tra các trường thông tin
    if (empty($name) || empty($user) || empty($password) || empty($confirm_password)) {
        $message = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($password !== $confirm_password) {  // Kiểm tra mật khẩu và xác nhận mật khẩu có khớp không
        $message = "Mật khẩu và xác nhận mật khẩu không khớp.";
    } else {
        // Thêm người dùng mới vào cơ sở dữ liệu
        $query = "INSERT INTO users (name, user, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $user, $password, $role);

        if ($stmt->execute()) {
            $message = "Thêm người dùng thành công!";
        } else {
            $message = "Có lỗi xảy ra: " . $conn->error;
        }
        $stmt->close();
    }
}

// Xử lý khi người dùng muốn xoá
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Kiểm tra xem người dùng có phải là Admin không trước khi xoá
    $check_query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role == 0) {
        $message = "Không thể xoá người dùng có vai trò Admin.";
    } else {
        // Xoá người dùng khỏi cơ sở dữ liệu
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $delete_id);

        if ($stmt->execute()) {
            $message = "Người dùng đã được xoá thành công!";
        } else {
            $message = "Có lỗi xảy ra khi xoá người dùng.";
        }
        $stmt->close();
    }
}

// Lấy danh sách người dùng
$sql = "SELECT id, name, user, role FROM users";
$result = $conn->query($sql);
$users = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Người Dùng</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center text-primary mb-3">Thêm Người Dùng</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Form Thêm Người Dùng -->
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Họ và tên</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="user">Tên đăng nhập</label>
            <input type="text" class="form-control" id="user" name="user" required>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <div class="form-group">
            <label for="role">Vai trò</label>
            <select class="form-control" id="role" name="role">
                <option value="1">User</option>
                <option value="0">Admin</option>
            </select>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg  mt-3 mb-3">Thêm</button>
        </div>
    </form>

    <!-- Danh Sách Người Dùng -->
    <h2 class="text-center text-primary mt-3 mb-3">Danh Sách Người Dùng</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>STT</th>
                <th>Họ và tên</th>
                <th>Tên đăng nhập</th>
                <th>Vai trò</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = 1; ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $stt++; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['user']); ?></td>
                    <td><?php echo $user['role'] == 1 ? 'User' : 'Admin'; ?></td>
                    <td>
                        <!-- Nút phân quyền -->
                        <a href="devices_for_user.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Phân quyền</a>
                        <!-- Nút xoá, chỉ hiển thị nếu không phải Admin -->
                        <?php if ($user['role'] != 0): ?>
                            <a href="user.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xoá người dùng này?');">Xoá</a>
                        <?php else: ?>
                            <span class="text-muted">Không thể xoá</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-center mt-4">
        <a href="main.php" class="btn btn-secondary btn-lg mb-5">Quay lại</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
