<?php
// Kết nối cơ sở dữ liệu
$servername = "localhost"; // Địa chỉ server
$username = "root";        // Tên người dùng
$password = "";            // Mật khẩu
$dbname = "fire_alarm";  // Tên cơ sở dữ liệu

// Kết nối đến MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra dữ liệu gửi lên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ POST
    $alert_date = isset($_POST['alert_date']) ? $_POST['alert_date'] : '';
    $alert_time = isset($_POST['alert_time']) ? $_POST['alert_time'] : '';
    $temperature = isset($_POST['temperature']) ? $_POST['temperature'] : 0;
    $humidity = isset($_POST['humidity']) ? $_POST['humidity'] : 0;
    $alert_type = isset($_POST['alert_type']) ? $_POST['alert_type'] : 'Unknown';

    // Kiểm tra dữ liệu hợp lệ
    if (!empty($alert_date) && !empty($alert_time) && !empty($alert_type)) {
        // Chuẩn bị câu lệnh SQL
        $sql = "INSERT INTO fire_alerts (alert_date, alert_time, temperature, humidity, alert_type) 
                VALUES ('$alert_date', '$alert_time', '$temperature', '$humidity', '$alert_type')";

        // Thực thi câu lệnh SQL
        if ($conn->query($sql) === TRUE) {
            echo "Dữ liệu được lưu thành công!";
        } else {
            echo "Lỗi: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Dữ liệu không hợp lệ!";
    }
} else {
    echo "Chỉ hỗ trợ phương thức POST!";
}

// Đóng kết nối
$conn->close();
?>
