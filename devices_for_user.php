<?php
// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fire_alarm";

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset
$conn->set_charset("utf8");

// Lấy ID người dùng từ URL
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Lấy danh sách các device_id đã được phân quyền
$assigned_device_ids = [];
$assigned_query = "SELECT device_id FROM device_user WHERE user_id = ?";
$stmt = $conn->prepare($assigned_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assigned_device_ids[] = $row['device_id'];
}
$stmt->close();

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Lấy danh sách thiết bị (có tìm kiếm nếu cần)
$query = "SELECT id, address FROM device";
if (!empty($search)) {
    $query .= " WHERE address LIKE ?";
}
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $like_search = "%$search%";
    $stmt->bind_param("s", $like_search);
}

$stmt->execute();
$result = $stmt->get_result();
$devices = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lưu phân quyền thiết bị
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['device_ids'])) {
    $device_ids = $_POST['device_ids']; // Danh sách ID thiết bị được chọn

    // Xóa phân quyền cũ của người dùng này
    $delete_query = "DELETE FROM device_user WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Thêm phân quyền mới
    $insert_query = "INSERT INTO device_user (user_id, device_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_query);

    foreach ($device_ids as $device_id) {
        $stmt->bind_param("ii", $user_id, $device_id);
        $stmt->execute();
    }
    $stmt->close();

    $message = "Phân quyền thiết bị thành công!";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân Quyền Thiết Bị</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center text-primary mb-3">Phân Quyền Thiết Bị</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Thanh tìm kiếm -->
    <form method="GET" action="devices_for_user.php">
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
        <div class="input-group mb-3">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo địa chỉ" value="<?= htmlspecialchars($search) ?>">
            <div class="input-group-append">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            </div>
        </div>
    </form>

    <!-- Danh sách thiết bị -->
    <form method="POST" action="devices_for_user.php?user_id=<?= $user_id ?>">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Chọn</th>
                    <th>ID Thiết Bị</th>
                    <th>Địa Chỉ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($devices)): ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td>
                                <input type="checkbox" 
                                       name="device_ids[]" 
                                       value="<?= $device['id'] ?>" 
                                       <?= in_array($device['id'], $assigned_device_ids) ? 'checked' : '' ?>>
                            </td>
                            <td><?= htmlspecialchars($device['id']) ?></td>
                            <td><?= htmlspecialchars($device['address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">Không có thiết bị nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg">Lưu</button>
        </div>
    </form>

    <div class="text-center mt-4">
        <a href="user.php" class="btn btn-secondary btn-lg">Quay lại</a>
    </div>
</div>
</body>
</html>
