<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fire_alarm";

session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Kiểm tra thời gian hết hạn session (5 phút)
$session_lifetime = 300; // 5 phút = 300 giây
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_lifetime) {
    // Nếu quá thời gian giới hạn, hủy session và chuyển hướng về login.php
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Cập nhật thời gian hoạt động cuối cùng của người dùng
$_SESSION['last_activity'] = time();


// Kết nối cơ sở dữ liệu
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Biến lưu kết quả
$current_temperature = "--"; // Nhiệt độ hiện tại
$current_humidity = "--";    // Độ ẩm hiện tại
$alert_status = false;       // Mặc định trạng thái là "Bình thường"

// Kiểm tra trạng thái và lấy dữ liệu khi nút kiểm tra được nhấn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_status'])) {
    // Lấy dữ liệu nhiệt độ và độ ẩm hiện tại từ bảng sensor_data
    $query_sensor = "SELECT temperature, humidity, date, time FROM sensor_data ORDER BY id DESC LIMIT 1";
    $result_sensor = $conn->query($query_sensor);

    if ($result_sensor && $row_sensor = $result_sensor->fetch_assoc()) {
        $current_temperature = $row_sensor['temperature'];
        $current_humidity = $row_sensor['humidity'];
        $sensor_date = $row_sensor['date']; // Định dạng dd/mm/yyyy
        $sensor_time = $row_sensor['time'];
    }

    // Lấy bản ghi cuối cùng của bảng fire_alerts
    $query_fire_alert = "SELECT alert_date, alert_time FROM fire_alerts ORDER BY id DESC LIMIT 1";
    $result_fire_alert = $conn->query($query_fire_alert);

    if ($result_fire_alert && $row_fire_alert = $result_fire_alert->fetch_assoc()) {
        $alert_date = $row_fire_alert['alert_date']; // Định dạng dd/mm/yyyy
        $alert_time = $row_fire_alert['alert_time'];

        // Chuyển đổi định dạng ngày từ dd/mm/yyyy sang yyyy-mm-dd để so sánh
        $sensor_date_converted = DateTime::createFromFormat('d/m/Y', $sensor_date)->format('Y-m-d');
        $alert_date_converted = DateTime::createFromFormat('d/m/Y', $alert_date)->format('Y-m-d');

        // Kiểm tra ngày có trùng khớp không
        if ($sensor_date_converted === $alert_date_converted) {
            // Tính toán chênh lệch thời gian
            $sensor_datetime = new DateTime("$sensor_date_converted $sensor_time");
            $alert_datetime = new DateTime("$alert_date_converted $alert_time");
            $interval = $sensor_datetime->getTimestamp() - $alert_datetime->getTimestamp();

            // Kiểm tra nếu chênh lệch <= 1800 giây (30 phút)
            $alert_status = $interval >= 0 && $interval <= 180;
        } else {
            $alert_status = false; // Không cùng ngày
        }
    } else {
        $alert_status = false; // Không có bản ghi trong fire_alerts
    }
}



// Lấy 10 bản ghi gần nhất về nhiệt độ và độ ẩm từ bảng sensor_data
$query = "SELECT temperature, humidity, date, time 
FROM sensor_data 
WHERE MOD(id, 10) = 1 
ORDER BY id DESC 
LIMIT 10;
";
$result = $conn->query($query);

// Mảng để lưu dữ liệu nhiệt độ, độ ẩm và thời gian
$temperature_data = [];
$humidity_data = [];
$time_labels = [];
$date_labels = [];

while ($row = $result->fetch_assoc()) {
    $temperature_data[] = $row['temperature'];
    $humidity_data[] = $row['humidity'];
    $date_labels[] = $row['date'];
    $time_labels[] = $row['time'];
}

// Lấy 10 bản ghi gần nhất từ bảng fire_alerts
$result_alerts = $conn->query("SELECT alert_date, alert_time, temperature, humidity, alert_type FROM fire_alerts ORDER BY id DESC LIMIT 10");

// Kiểm tra và lấy device_id từ URL
if (isset($_GET['device_id'])) {
    $device_id = $_GET['device_id'];

    // Nếu device_id khác 3, hiển thị thông báo và không tiếp tục xử lý
    if ($device_id != 3) {
        echo "Chưa có thiết bị báo cháy";
        exit; // Dừng lại và không tiếp tục hiển thị trang
    }

    // Truy vấn thông tin thiết bị từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT address FROM device WHERE id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Lấy kết quả và hiển thị address
        $device = $result->fetch_assoc();
    } else {
        echo "Không tìm thấy thiết bị với ID này.";
    }

    $stmt->close();
} else {
    echo "Không có device_id trong URL.";
}
// Đóng kết nối
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>HỆ THỐNG CẢNH BÁO CHÁY</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 100%;
            margin: 0 auto;
            height: 300px;
        }
    </style>
     <style>
        .btn-status {
            font-size: 1.5rem;
            font-weight: bold;
            width: 200px;
            height: 60px;
            text-transform: uppercase;
        }
        .status-normal {
            background-color: #28a745; /* Màu xanh lá */
            color: white;
        }
        .status-alert {
            background-color: #dc3545; /* Màu đỏ */
            color: white;
        }
        .info-box {
            font-size: 1.25rem;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
    </style>
    <style>
    .border-box {
        border: 2px solid #ccc; /* Màu viền */
        border-radius: 10px;   /* Góc bo tròn */
        padding: 20px;         /* Khoảng cách bên trong */
        background-color: #f9f9f9; /* Màu nền nhạt */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Tạo bóng nhẹ */
    }
</style>
<style>
    .btn-back {
        font-size: 1rem;
        font-weight: bold;
        padding: 8px 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px; /* Tạo khoảng cách giữa icon và chữ */
        transition: background-color 0.3s, color 0.3s;
    }
    .btn-back:hover {
        background-color: #6c757d; /* Màu xám đậm khi hover */
        color: white;
    }
    .btn-back i {
        font-size: 1.2rem; /* Tăng kích thước icon */
    }
</style>


</head>
<body class="bg-light">
    
    <div class="container my-5">
        <h1 class="text-danger">HỆ THỐNG BÁO CHÁY</h1>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="display: flex"><?php echo htmlspecialchars($device['address']);?></h2>
        <div class="text-center mt-4">
            <a href="main.php" class="btn btn-secondary btn-lg">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

        
        <!-- Khu vực thông tin -->
        <div class="text-center mb-5 mt-5" >
            <form method="POST">
                <button type="submit" name="check_status" class="btn btn-primary btn-lg">
                    Kiểm tra
                </button>
            </form>
        </div>

        <div class="container mt-4 border-box">
            <!-- Nút kiểm tra -->

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="info-box">
                        <p>Nhiệt độ hiện tại</p>
                        <p><?= htmlspecialchars($current_temperature) ?> °C</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <p>Độ ẩm hiện tại</p>
                        <p><?= htmlspecialchars($current_humidity) ?> %</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <p>Trạng thái</p>
                            <button class="btn btn-status <?= $alert_status ? 'status-alert' : 'status-normal' ?>" 
                                    disabled 
                                    title="<?= $alert_status ? 'Có báo cháy trong vòng 5 phút' : 'Không có báo cháy nào trong vòng 5 phút' ?>">
                                <?= $alert_status ? 'Cảnh báo' : 'Bình thường' ?>
                            </button>
                    </div>
                </div>
            </div>



            
        </div>
        <!-- Biểu đồ nhiệt độ và độ ẩm -->
        <div class="row mb-5 mt-5">
            <div class="col-6">
                <h2 class="text-center">Biểu đồ nhiệt độ</h2>
                <div class="chart-container">
                    <canvas id="temperatureChart"></canvas>
                </div>
            </div>
            <div class="col-6">
                <h2 class="text-center">Biểu đồ độ ẩm</h2>
                <div class="chart-container">
                    <canvas id="humidityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Hai bảng đặt cạnh nhau -->
        <div class="row">
            <!-- Bảng nhiệt độ và độ ẩm -->
            <div class="col-6">
                <h2 class="text-center">10 Bản ghi gần nhất</h2>
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Ngày</th>
                            <th>Thời gian</th>
                            <th>Nhiệt độ (°C)</th>
                            <th>Độ ẩm (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($temperature_data as $index => $temperature): ?>
                            <tr>
                                <td><?= $date_labels[$index] ?></td>
                                <td><?= $time_labels[$index] ?></td>
                                <td><?= $temperature ?></td>
                                <td><?= $humidity_data[$index] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="text-center mt-4">
                    <a href="sensor_data.php" class="btn btn-secondary">Xem thêm</a>
                </div>
            </div>

            <!-- Bảng lịch sử báo cháy -->
            <div class="col-6">
                <h2 class="text-center">Lịch sử báo cháy</h2>
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Ngày</th>
                            <th>Thời gian</th>
                            <th>Nhiệt độ (°C)</th>
                            <th>Độ ẩm (%)</th>
                            <th>Loại cảnh báo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row_alerts = $result_alerts->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row_alerts['alert_date'] ?></td>
                                <td><?= $row_alerts['alert_time'] ?></td>
                                <td><?= $row_alerts['temperature'] ?></td>
                                <td><?= $row_alerts['humidity'] ?></td>
                                <td><?= $row_alerts['alert_type'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="text-center mt-4">
                    <a href="fire_alert_history.php" class="btn btn-secondary">Xem thêm</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        var temperatureData = <?php echo json_encode($temperature_data); ?>;
        var humidityData = <?php echo json_encode($humidity_data); ?>;
        var timeLabels = <?php echo json_encode($time_labels); ?>;

        // Biểu đồ nhiệt độ
        var ctxTemp = document.getElementById('temperatureChart').getContext('2d');
        new Chart(ctxTemp, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Nhiệt độ (°C)',
                    data: temperatureData,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        reverse: true,  // Không đảo ngược trục X
                        ticks: {
                            autoSkip: true,  // Tự động bỏ qua các nhãn để tránh chồng chéo
                            maxRotation: 45, // Xoay nhãn theo một góc nếu cần thiết
                            minRotation: 0
                        }
                    },
                    y: { beginAtZero: true }
                }
            }
        });

        // Biểu đồ độ ẩm
        var ctxHumidity = document.getElementById('humidityChart').getContext('2d');
        new Chart(ctxHumidity, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Độ ẩm (%)',
                    data: humidityData,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        reverse: true,  // Không đảo ngược trục X
                        ticks: {
                            autoSkip: true,  // Tự động bỏ qua các nhãn nếu trục X quá dài
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
    

</body>
</html>


