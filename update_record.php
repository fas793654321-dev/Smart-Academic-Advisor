<?php
include 'db.php'; 
session_start();

$student_id = $_SESSION['user_id'] ?? 1; 

if (isset($_POST['submit_record'])) {
    // 1. استلام البيانات العامة
    $gpa = $_POST['final_gpa_hidden']; 
    $hrs = $_POST['final_hrs_hidden'];
    $selected_courses = $_POST['courses'] ?? []; // رموز المواد المختارة
    $all_grades = $_POST['grades'] ?? [];        // مصفوفة الدرجات لكل المواد

    // 2. تحديث جدول المستخدمين (المعدل والساعات الكلية)
    $stmt = $conn->prepare("UPDATE users SET current_gpa = ?, completed_hours = ? WHERE id = ?");
    $stmt->bind_param("sii", $gpa, $hrs, $student_id);
    $stmt->execute();

    // 3. تحديث سجل المواد المنجزة مع درجاتها
    $conn->query("DELETE FROM student_records WHERE student_id = '$student_id'");
    
    if (!empty($selected_courses)) {
        // نجهز جملة الإدخال لتشمل خانة الدرجة (grade)
        $insert_stmt = $conn->prepare("INSERT INTO student_records (student_id, course_code, grade) VALUES (?, ?, ?)");
        
        foreach ($selected_courses as $code) {
            // نأخذ الدرجة الخاصة بهذه المادة من مصفوفة الدرجات
            $grade_value = isset($all_grades[$code]) ? $all_grades[$code] : '0';
            
            $insert_stmt->bind_param("iss", $student_id, $code, $grade_value);
            $insert_stmt->execute();
        }
    }

    header("Location: chatbot.php?status=success");
    exit();
}
?>