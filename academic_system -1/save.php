<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_SESSION['user_id'];
    
    // استخدام mysqli_real_escape_string لحماية البيانات من الرموز الغريبة
    $major = mysqli_real_escape_string($conn, $_POST['major']);
    $hours = mysqli_real_escape_string($conn, $_POST['completed_hours']);
    $gpa = mysqli_real_escape_string($conn, $_POST['current_gpa']);

    $sql = "UPDATE users SET 
            major='$major', 
            completed_hours='$hours', 
            current_gpa='$gpa', 
            profile_completed=1 
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: dashboard.php");
        exit(); // يفضل دائماً وضع exit بعد header لضمان توقف السكريبت
    } else {
        echo "خطأ في تحديث البيانات: " . mysqli_error($conn);
    }
}
?>