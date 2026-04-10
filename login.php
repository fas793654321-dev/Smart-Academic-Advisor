<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // تأمين البيانات
    $sid = mysqli_real_escape_string($conn, $_POST['student_id']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    // البحث في جدول students (كما يظهر في صورتك)
    $sql = "SELECT * FROM students WHERE student_id = '$sid' AND password = '$pass'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // تخزين البيانات في الجلسة (Session) لربطها بالشات والداشبورد
        $_SESSION['student_id'] = $user['student_id']; 
        $_SESSION['user_name'] = $user['student_name']; 

        /* بما أن عمود 'profile_completed' قد لا يكون موجوداً في جدولك الجديد،
           سنعتمد على 'gpa'. إذا كان 0 فهو مستخدم جديد (new_user).
        */
        if (isset($user['profile_completed'])) {
            // إذا أضفتِ العمود يدوياً في القاعدة، سيعمل هذا السطر:
            echo ($user['profile_completed'] == 0) ? "new_user" : "old_user";
        } else {
            // إذا لم يكن موجوداً، نعتمد على المعدل (إذا 0 يعني لم يكمل السجل)
            echo ($user['gpa'] == 0) ? "new_user" : "old_user";
        }

    } else { 
        echo "error"; 
    }
}
?>