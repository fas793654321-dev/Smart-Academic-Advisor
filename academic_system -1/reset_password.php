<?php
// تضمين ملف الاتصال بقاعدة البيانات المستخدم في مشروعك
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // استلام البيانات وتأمينها من الاختراق (نفس الطريقة المستخدمة في save.php)
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    // استعلام التحديث - تأكدي أن اسم الجدول 'students' كما يظهر في phpMyAdmin
    $sql = "UPDATE students SET password = '$new_password' WHERE student_id = '$student_id'";

    if (mysqli_query($conn, $sql)) {
        // التحقق مما إذا كان الرقم الجامعي موجوداً بالفعل وتم التحديث
        if (mysqli_affected_rows($conn) > 0) {
            echo "success";
        } else {
            // في حال لم يتغير شيء (الرقم غير موجود أو الباسورد هو نفسه القديم)
            echo "no_change";
        }
    } else {
        // عرض الخطأ في حال فشل الاستعلام
        echo "error: " . mysqli_error($conn);
    }
}
?>