<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fire_alarm"; // Cơ sở dữ liệu mà bạn đã tạo

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Nhận dữ liệu từ Arduino
$temperature = $_POST['temperature'];
$humidity = $_POST['humidity'];
$date = $_POST['date'];
$time = $_POST['time'];

// Thêm dữ liệu vào bảng
$sql = "INSERT INTO sensor_data (temperature, humidity, date, time) VALUES ('$temperature', '$humidity', '$date', '$time')";

if ($conn->query($sql) === TRUE) {
    echo "Dữ liệu đã được lưu!";

    // Kiểm tra số lượng bản ghi trong bảng sensor_data
    $sql_check = "SELECT COUNT(*) AS count FROM sensor_data";
    $result = $conn->query($sql_check);
    $row = $result->fetch_assoc();
    $record_count = $row['count'];

    // Nếu số lượng bản ghi vượt quá 100, xóa bản ghi cũ nhất
    if ($record_count > 100) {
        // Xóa bản ghi có thời gian cũ nhất
        $sql_delete = "DELETE FROM sensor_data WHERE id = (SELECT id FROM sensor_data ORDER BY date ASC, time ASC LIMIT 1)";
        if ($conn->query($sql_delete) === TRUE) {
            echo " Bản ghi cũ nhất đã được xóa!";
        } else {
            echo " Lỗi khi xóa bản ghi cũ: " . $conn->error;
        }
    }
} else {
    echo "Lỗi: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
