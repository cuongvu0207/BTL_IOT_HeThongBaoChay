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

session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Thiết lập thời gian hết hạn session (5 phút)
$session_lifetime = 5 * 60;  // 5 phút tính bằng giây

// Kiểm tra nếu thời gian phiên đã hết hạn
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_lifetime) {
    // Nếu hết hạn, hủy session và chuyển hướng đến trang đăng nhập
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Cập nhật thời gian hoạt động cuối cùng
$_SESSION['last_activity'] = time();

// Kiểm tra vai trò của người dùng
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Lấy danh sách thiết bị dựa trên vai trò của người dùng
if ($role === 1) {
    // Lấy thiết bị được cấp quyền từ bảng device_user
    $query = "SELECT d.id, d.address 
              FROM device_user du
              JOIN device d ON du.device_id = d.id
              WHERE du.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
} else {
    // Lấy tất cả thiết bị cho Admin
    $query = "SELECT id, address FROM device";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

// Lưu danh sách thiết bị vào biến $fire_devices
$fire_devices = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fire_devices[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang người dùng</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Quản lý hệ thông báo cháy</h2>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <!-- Nhóm các nút bên trái -->
        <div class="d-flex">
            <!-- Nút Thêm User (Chỉ dành cho Admin) -->
            <?php if ($_SESSION['role'] === 0): ?>
                <a href="user.php" class="btn btn-primary mr-2">Người dùng</a>
            <?php else: ?>
                <button class="btn btn-primary mr-2" id="adminButton">Người dùng</button>
            <?php endif; ?>

            <!-- Nút Thêm Thiết bị (Chỉ dành cho Admin) -->
            <?php if ($_SESSION['role'] === 0): ?>
                <a href="device.php" class="btn btn-primary mr-2">Thiết bị</a>
            <?php else: ?>
                <button class="btn btn-primary mr-2" id="adminButton1">Thiết bị</button>
            <?php endif; ?>
        </div>

        <!-- Nhóm các nút bên phải -->
        <div class="d-flex">
            <!-- Nút Đổi mật khẩu -->
            <a href="change_password.php" class="btn btn-success mr-2">Đổi mật khẩu</a>

            <!-- Nút Đăng xuất -->
            <a href="logout.php" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h4 class="mt-4">Danh sách thiết bị báo cháy</h4>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>STT</th>
                <th>ID</th>
                <th>Địa chỉ</th>
                <th>Truy cập</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = 1; // Khởi tạo STT ?>
            <?php foreach ($fire_devices as $device): ?>
                <tr>
                    <td><?php echo $stt++; ?></td>
                    <td><?php echo $device['id']; ?></td>
                    <td><?php echo $device['address']; ?></td>
                    <td>
                        <a href="index.php?device_id=<?php echo $device['id']; ?>" class="btn btn-primary">Truy cập</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Xử lý thông báo cho nút User và Device-->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const adminButton = document.getElementById('adminButton');
        if (adminButton) {
            adminButton.addEventListener('click', function () {
                alert('Tính năng này chỉ dành cho Admin.');
            });
        }
        const adminButton1 = document.getElementById('adminButton1');
        if (adminButton1) {
            adminButton1.addEventListener('click', function () {
                alert('Tính năng này chỉ dành cho Admin.');
            });
        }
    });
</script>

<!-- Liên kết tới Bootstrap JS và các thư viện phụ thuộc -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
