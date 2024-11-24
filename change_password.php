<?php
session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "fire_alarm";

// Tạo kết nối
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$message = "";

// Xử lý khi người dùng gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];  // Lấy user_id từ session
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Kiểm tra mật khẩu mới và mật khẩu xác nhận khớp
    if ($new_password !== $confirm_password) {
        $message = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
    } else {
        // Kiểm tra mật khẩu cũ có đúng không
        $old_password = $conn->real_escape_string($old_password);
        $new_password = $conn->real_escape_string($new_password);
        $user_id = $conn->real_escape_string($user_id);

        $sql = "SELECT password FROM users WHERE id = '$user_id'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // So sánh mật khẩu cũ
            if ($row['password'] !== $old_password) {
                $message = "Mật khẩu cũ không chính xác!";
            } else {
                // Cập nhật mật khẩu mới
                $sql_update = "UPDATE users SET password = '$new_password' WHERE id = '$user_id'";
                if ($conn->query($sql_update) === TRUE) {
                    $message = "Mật khẩu đã được thay đổi thành công!";
                } else {
                    $message = "Đã xảy ra lỗi khi cập nhật mật khẩu: " . $conn->error;
                }
            }
        } else {
            $message = "Không tìm thấy người dùng!";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi Mật Khẩu</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Đổi Mật Khẩu</h2>

    <!-- Thông báo thành công hoặc thất bại -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Form đổi mật khẩu -->
    <form action="change_password.php" method="POST">
        <div class="form-group">
            <label for="old_password">Mật khẩu cũ</label>
            <input type="password" name="old_password" id="old_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="new_password">Mật khẩu mới</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu mới</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
    </form>

    <!-- Nút quay lại trang người dùng -->
    <a href="main.php" class="btn btn-secondary mt-3">Quay lại</a>
</div>

<!-- Liên kết tới Bootstrap JS và các thư viện phụ thuộc -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
