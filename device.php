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

// Xử lý khi người dùng gửi form thêm thiết bị
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['address'])) {
    $address = trim($_POST['address']); // Địa chỉ thiết bị từ form nhập

    // Kiểm tra trường thông tin địa chỉ
    if (empty($address)) {
        $message = "Vui lòng nhập địa chỉ thiết bị.";
    } else {
        // Thêm thiết bị vào cơ sở dữ liệu
        $query = "INSERT INTO device (address) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $address);

        if ($stmt->execute()) {
            $message = "Thiết bị đã được thêm thành công!";
        } else {
            $message = "Có lỗi xảy ra khi thêm thiết bị: " . $conn->error;
        }
        $stmt->close();
    }
}

// Xử lý khi người dùng nhấn nút xóa thiết bị
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql_delete = "DELETE FROM device WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $delete_id);

    if ($stmt_delete->execute()) {
        $message = "Thiết bị đã được xóa thành công!";
    } else {
        $message = "Có lỗi xảy ra khi xóa thiết bị: " . $conn->error;
    }
    $stmt_delete->close();
}

// Lấy danh sách thiết bị từ cơ sở dữ liệu
$sql = "SELECT * FROM device";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Thiết Bị</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Thêm Thiết Bị Báo Cháy</h2>

    <!-- Thông báo thành công hoặc thất bại -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Form thêm thiết bị -->
    <form action="device.php" method="POST">
        <div class="form-group">
            <label for="address">Serialnumber</label>
            <input type="text" name="serialnumber" id="serialnumber" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="address">Địa chỉ thiết bị</label>
            <input type="text" name="address" id="address" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Thêm Thiết Bị</button>
    </form>

    <h3 class="mt-4">Danh Sách Thiết Bị</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>STT</th>
                <th>ID</th>
                <th>Địa chỉ</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                $stt = 1; // Biến đếm số thứ tự
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>$stt</td>
                            <td>{$row['id']}</td>
                            <td>{$row['address']}</td>
                            <td>
                                <a href='device.php?delete_id={$row['id']}' class='btn btn-danger' onclick='return confirm(\"Bạn có chắc muốn xóa thiết bị này?\")'>Xóa</a>
                            </td>
                          </tr>";
                    $stt++;
                }
            } else {
                echo "<tr><td colspan='4' class='text-center'>Không có thiết bị nào</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Nút quay lại trang danh sách thiết bị -->
    <a href="main.php" class="btn btn-secondary mt-3">Quay lại</a>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
