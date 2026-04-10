<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // تنظيف البيانات
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $sid  = mysqli_real_escape_string($conn, $_POST['student_id']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // التعديل الجذري: اسم الجدول 'students' وأسماء الأعمدة الصحيحة
    $sql = "INSERT INTO students (student_id, student_name, password) VALUES ('$sid', '$name', '$pass')";
    
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        // سيظهر لك سبب الخطأ الحقيقي إذا فشل الإرسال
        echo "error: " . mysqli_error($conn);
    }
}
?>