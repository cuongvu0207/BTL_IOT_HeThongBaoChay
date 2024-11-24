<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fire_alarm";

// Kết nối cơ sở dữ liệu
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Khởi tạo biến lưu kết quả tìm kiếm
$search_keyword = "";
$query = "SELECT * FROM fire_alerts ORDER BY id DESC";

// Kiểm tra xem người dùng đã gửi từ khóa tìm kiếm hay chưa
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_keyword = $_POST['keyword'];
    $query = "SELECT * FROM fire_alerts 
              WHERE alert_date LIKE '%$search_keyword%' OR alert_time LIKE '%$search_keyword%' OR  alert_type LIKE '%$search_keyword%'
              ORDER BY id DESC";
}

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử Báo Cháy</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container my-5">
    <h1 class="text-center">Lịch sử Báo Cháy</h1>
    <div class="row align-items-center mb-4">
        <!-- Thanh tìm kiếm -->
        <div class="col-md-8">
            <form method="POST" id="search-form" class="form-inline">
                <input type="text" name="keyword" class="form-control w-100" placeholder="Nhập từ khoá để tìm kiếm" value="<?= htmlspecialchars($search_keyword) ?>">
            </form>
        </div>

        <!-- Nút tìm kiếm -->
        <div class="col-md-2">
            <button type="submit" form="search-form" class="btn btn-primary">Tìm kiếm</button>
        </div>

        <!-- Nút quay lại -->
        <div class="col-md-2 text-right">
            <a href="index.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Ngày</th>
                <th>Thời gian</th>
                <th>Nhiệt độ (°C)</th>
                <th>Độ ẩm (%)</th>
                <th>Loại cảnh báo</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['alert_date'] ?></td>
                        <td><?= $row['alert_time'] ?></td>
                        <td><?= $row['temperature'] ?></td>
                        <td><?= $row['humidity'] ?></td>
                        <td><?= $row['alert_type'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Không tìm thấy dữ liệu</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
