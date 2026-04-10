<?php
// نفس بياناتك بالضبط
$conn = new mysqli("localhost", "root", "", "university_db");

// التحقق من الاتصال (بأسلوب الكائنات)
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// دعم اللغة العربية (ضروري جداً لأسماء المواد)
$conn->set_charset("utf8mb4");
?>