<?php
session_start();
$error = "";

// Kiểm tra nếu người dùng đã đăng nhập, chuyển hướng đến trang chính
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: main.php");
    exit;
}

// Cấu hình kết nối cơ sở dữ liệu
$servername = "localhost";
$username_db = "root"; // Thay đổi nếu cần
$password_db = "";     // Thay đổi nếu cần
$dbname = "fire_alarm"; // Thay đổi tên database nếu cần

// Kết nối đến cơ sở dữ liệu
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}

// Xử lý khi người dùng gửi biểu mẫu đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Truy vấn tìm người dùng trong bảng users
    $query = "SELECT id, password, role FROM users WHERE user = ?";  // Dùng 'user' làm tên cột cho tên đăng nhập
    $stmt = $conn->prepare($query);

    // Kiểm tra nếu câu lệnh prepare thành công
    if ($stmt === false) {
        die("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
    }

    $stmt->bind_param("s", $username); // Sử dụng prepared statement để tránh SQL Injection
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra xem tài khoản có tồn tại trong cơ sở dữ liệu không
    if ($result->num_rows == 1) {
        // Lấy dữ liệu người dùng
        $row = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu
        if ($password == $row['password']) {  // So sánh trực tiếp nếu mật khẩu không mã hóa
            // Lưu thông tin đăng nhập vào session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $row['id'];  // Lưu ID người dùng
            $_SESSION['role'] = $row['role'];   // Lưu vai trò người dùng

            // Chuyển hướng đến trang user.php
            header("Location: main.php");
            exit;
        } else {
            $error = "Tài khoản và mật khẩu của bạn không chính xác!";
        }
    } else {
        $error = "Tài khoản và mật khẩu của bạn không chính xác!";
    }

    // Đóng kết nối
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <!-- Liên kết tới Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <!-- CSS cho Background -->
        <style>
        body {
            background-image: url('Background.jpg'); /* Đường dẫn tới ảnh nền */
            background-size: cover; /* Đảm bảo ảnh phủ đầy toàn bộ màn hình */
            background-position: center center; /* Căn giữa ảnh */
            height: 100vh; /* Đảm bảo chiều cao trang đầy đủ */
        }

        .container {
            z-index: 1;
            position: relative;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.8); /* Nền trắng mờ cho card */
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Đăng nhập</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>
    </div>
</div>

<!-- Liên kết tới Bootstrap JS và các thư viện phụ thuộc -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
