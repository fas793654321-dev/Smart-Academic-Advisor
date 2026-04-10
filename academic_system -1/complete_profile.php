<?php
session_start();
include 'db.php';
$currentLang = 'en';

if (!isset($_SESSION['student_id'])) { 
    header("Location: index.html"); 
    exit(); 
}

$uid = $_SESSION['student_id']; 

$uid = $_SESSION['student_id']; 
// جلب البيانات من جدول students وليس users
$user_info = $conn->query("SELECT gpa, major FROM students WHERE student_id = '$uid'")->fetch_assoc();
$current_stored_gpa = $user_info['gpa'] ?? "0.00";
// ملاحظة: بما أن جدولك لا يحتوي على عمود للساعات، سنضع 0 مؤقتاً أو نضيف العمود لاحقاً
$current_stored_hrs = $user_info['completed_hours'] ?? "0";
$current_advisor = $user_info['advisor_name'] ?? ""; // جلب المرشد المحفوظ

// جلب المواد التي سبق حفظها لتفعيل الـ Checkbox
$done_courses = [];
$res = $conn->query("SELECT course_code FROM student_records WHERE student_id = '$uid'");
while($row = $res->fetch_assoc()){
    $done_courses[] = $row['course_code'];
}

// --- الجزء الثاني: معالجة ضغطة زر الحفظ (النسخة المتوافقة مع الدرجات) ---
if (isset($_POST['submit_record'])) {
    $final_gpa = $_POST['final_gpa_hidden']; 
    $final_hrs = $_POST['final_hrs_hidden'];
    $adv_info = $_POST['advisor_name'] ?? ""; // استلام القيمة من الفورم
    $selected_courses = $_POST['courses'] ?? [];
    $all_grades = $_POST['grades'] ?? []; // مهم جداً: لاستلام الدرجات المختار من القوائم

 // تحديث جدول الطلاب (تم تعديل id إلى student_id و current_gpa إلى gpa)
    // ملاحظة: إذا لم يكن لديك عمود completed_hours في الجدول، يرجى إضافته أو حذف الجزء الخاص به
   // في الجزء الخاص بـ if (isset($_POST['submit_record']))
// تحديث جدول الطلاب ليشمل الـ advisor_name
    $stmt = $conn->prepare("UPDATE students SET gpa = ?, completed_hours = ?, advisor_name = ? WHERE student_id = ?");
    $stmt->bind_param("sisi", $final_gpa, $final_hrs, $adv_info, $uid); 
    $stmt->execute();

    // 2. تحديث سجل المواد المنجزة مع درجاتها
    $conn->query("DELETE FROM student_records WHERE student_id = '$uid'");
    if (!empty($selected_courses)) {
        // تأكدي أن جدول student_records يحتوي على عمود اسمه grade
        $insert_stmt = $conn->prepare("INSERT INTO student_records (student_id, course_code, grade) VALUES (?, ?, ?)");
        foreach ($selected_courses as $code) {
            $grade_value = isset($all_grades[$code]) ? $all_grades[$code] : '0';
            $insert_stmt->bind_param("iss", $uid, $code, $grade_value);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
    }

    header("Location: dashboard.php?success=1");
    exit();
}
function renderCourse($code, $nameEn, $nameAr, $hours, $isElective = false) {
    global $done_courses, $conn, $uid; 
    
    // جلب الدرجة المخزنة
    $grade_query = $conn->query("SELECT grade FROM student_records WHERE student_id = '$uid' AND course_code = '$code'");
    $stored_grade = ($grade_query && $row = $grade_query->fetch_assoc()) ? $row['grade'] : "";

// --- ابحثي عن هذا الجزء في دالة renderCourse ---
$checked = in_array($code, $done_courses) ? "checked" : "";
$selectedClass = in_array($code, $done_courses) ? "selected" : "";

// --- استبدليه بهذا الكود المحدث ---
$checked = "";
$selectedClass = "";

if (in_array($code, $done_courses)) {
    $checked = "checked";
    $selectedClass = "selected";
    
    // إذا كانت الدرجة المخزنة هي 0 (التي تمثل F) أضف كلمة failed للكلاس
    if ($stored_grade === "0") {
        $selectedClass .= " failed";
    }
}
    echo "<div class='course-card $selectedClass' data-hours='$hours'>
            <div class='course-info'>
                <label class='custom-check'>
                    <input type='checkbox' name='courses[]' value='$code' onchange='updateGPA()' $checked>
                    <span class='checkmark'></span>
                </label>
                <div class='text-group'>";
                
 if ($isElective) {
        echo "<span class='code' style='color:#ca8a04;'>Faculty Elective (3H)</span>
              <select name='grades[$code]' class='grade-input' onchange='updateGPA()'>
                  <option value='' disabled selected>Select Course...</option>
                  <option value='M109'>M109 - NET Programming</option>
                  <option value='TM291'>TM291 - Web Projects</option>
              </select>";
    } else {
        echo "<span class='course-name' data-en='$code - $nameEn' data-ar='$code - $nameAr'>$code - $nameEn</span>";
    }
    
    // إغلاق قسم المعلومات وفتح قسم الأكشن (هذا الجزء يظهر لكل المواد)
    echo "      </div>
            </div>
            <div class='course-action'>
                <span class='hours-badge'>$hours H</span>
                <select name='grades[$code]' class='grade-input' onchange='updateGPA()'>
                    <option value='' disabled " . ($stored_grade == '' ? 'selected' : '') . ">Grade</option>";

                    // شرط اختيار الدرجات بناءً على رمز المادة
                    if ($code === 'MT099' || $code === 'EL099') {
                        // خيارات التمهيدي
                        echo "<option value='4' " . ($stored_grade == '4' ? 'selected' : '') . ">S (Pass)</option>
                              <option value='0' " . ($stored_grade == '0' ? 'selected' : '') . ">F (Fail)</option>";
                    } else {
                        // الخيارات العادية
                        echo "<option value='4' " . ($stored_grade == '4' ? 'selected' : '') . ">A</option>
                              <option value='3.5' " . ($stored_grade == '3.5' ? 'selected' : '') . ">B+</option>
                              <option value='3' " . ($stored_grade == '3' ? 'selected' : '') . ">B</option>
                              <option value='2.5' " . ($stored_grade == '2.5' ? 'selected' : '') . ">C+</option>
                              <option value='2' " . ($stored_grade == '2' ? 'selected' : '') . ">C</option>
                              <option value='1.5' " . ($stored_grade == '1.5' ? 'selected' : '') . ">D</option>
                              <option value='0' " . ($stored_grade == '0' ? 'selected' : '') . ">F</option>";
                    }

    // إغلاق الـ select والـ divs النهائية (لكل المواد)
    echo "      </select>
            </div>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Advisor | AOU Bahrain</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Cairo:wght@400;700&display=swap');
        
        :root {
            --primary-blue: #003060;
            --gold: #ca8a04;
            --bg-color: #f1f5f9;
        }

        body {
            font-family: 'Poppins', 'Cairo', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: #1e293b;
            transition: all 0.3s ease;
        }

        /* زر تبديل اللغة */
        .lang-switch-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            z-index: 100;
        }
        .lang-switch-btn:hover { background: white; color: var(--primary-blue); }
    
      /* تنسيق الشعار في صفحة السجل الأكاديمي */
.header-logo {
    height: 45px; /* حجم صغير يتناسب مع الهيدر النحيف 100px */
    width: auto;
    margin-bottom: 5px;
    filter: brightness(0) invert(1); /* تحويله للون الأبيض ليناسب الخلفية الزرقاء */
}

/* تعديل بسيط للهيدر ليتسع للعناصر عمودياً */
.header-section {
    padding: 10px 0;
    height: auto; /* نجعل الطول مرناً ليناسب الشعار والنص */
    min-height: 100px;
}  


       /* 1. تصغير الهيدر ومنع التغطية */
.header-section {
    background: var(--primary-blue);
    height: 100px; /* تم تصغيره من 220px */
    border-bottom-left-radius: 30px; /* تقليل الانحناء */
    border-bottom-right-radius: 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    position: relative;
}

.header-section h1 { margin: 0; font-size: 1.5rem; } /* تصغير حجم الخط */
.header-section p { margin: 2px 0 0; opacity: 0.8; font-size: 0.8rem; }

/* 2. ضبط المسافة العلوية للمحتوى */
.page-wrapper {
    max-width: 1300px;
    margin: 20px auto 100px; /* تغيير الهامش من -50 إلى 20 لرفع المحتوى */
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
}

/* 3. تصغير المسافات بين السنوات */
.year-title {
    background: white;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 5px solid var(--gold);
    color: var(--primary-blue);
    font-weight: 700;
    margin: 20px 0 10px; /* تقليل الهوامش */
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* 4. تعديل مكان زر اللغة ليتناسب مع الشريط الصغير */
.lang-switch-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    right: 20px;
    padding: 5px 12px;
    font-size: 0.8rem;
}

        /* لغة عربية: تغيير مكان الخط الجانبي */
        [dir="rtl"] .year-title { border-left: none; border-right: 6px solid var(--gold); }

        .semester-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .semester-grid { grid-template-columns: 1fr; } }

        .sem-box { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.02); }
        .sem-label { text-align: center; font-weight: 700; color: #64748b; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }

        .course-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            transition: 0.3s;
        }

  /* تأثير البطاقة عند الاختيار (اللون الأخضر) */
.course-card.selected { 
    border-color: #10b981 !important; 
    background: #f0fdf4 !important; 
    transition: all 0.3s ease;
}

.course-info { display: flex; align-items: center; }
.text-group .code { font-size: 0.7rem; font-weight: 700; color: var(--gold); display: block; }
.text-group .course-name { font-size: 0.85rem; font-weight: 600; color: var(--primary-blue); }

.hours-badge { background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; margin: 0 10px; /* الإضافة السحرية هنا */
    display: inline-flex; /* لجعل العناصر بجانب بعضها */
    align-items: center; 
    white-space: nowrap; /* لمنع حرف H من النزول لسطر جديد */
    gap: 2px; /* مسافة بسيطة جداً بين الرقم والحرف */}

.grade-input { padding: 4px; border-radius: 6px; border: 1px solid #cbd5e1; display: none; font-size: 0.8rem; }
.course-card.selected .grade-input { display: block; }

/* --- الجزء المسؤول عن علامة الصح (✓) --- */
.custom-check .checkmark:after {
    content: "";
    position: absolute;
    display: none; /* مخفية افتراضياً */
    left: 8px;
    top: 4px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

/* إظهار علامة الصح عند تفعيل المربع */
.custom-check input:checked ~ .checkmark:after {
    display: block;
}

/* تلوين المربع بالأخضر عند الاختيار */
.custom-check input:checked ~ .checkmark {
    background-color: #10b981;
    border-color: #10b981;
}
        .sidebar-stats {
            background: var(--primary-blue);
            color: white;
            padding: 35px;
            border-radius: 30px;
            height: fit-content;
            position: sticky;
            top: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            border-top: 5px solid var(--gold);
        }

        .stat-item { margin-bottom: 30px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 15px; }
        .stat-item label { font-size: 0.8rem; opacity: 0.7; display: block; }
        .stat-item span { font-size: 2.2rem; font-weight: 800; }

        .btn-save { background: var(--gold); color: white; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-save:hover { background: #b47b03; transform: translateY(-3px); }

        .custom-check { position: relative; width: 22px; height: 22px; cursor: pointer; margin: 0 12px; }
        .custom-check input { opacity: 0; width: 0; }
        .checkmark { position: absolute; top: 0; left: 0; height: 22px; width: 22px; background: #e2e8f0; border-radius: 6px; }
        .custom-check input:checked ~ .checkmark { background: #10b981; }


        /* مادة راسبة: حدود حمراء وخلفية وردية خفيفة */
.course-card.failed { 
    border-color: #ef4444 !important; 
    background-color: #fef2f2 !important; 
}
/* إخفاء علامة الصح للمادة الراسبة لتمييزها */
.course-card.failed .checkmark { background-color: #ef4444; border-color: #ef4444 !important; }


    </style>
</head>
<body>

<header class="header-section">
    <button type="button" class="lang-switch-btn" onclick="toggleLanguage()" id="lang-btn">عربي</button>
    
    <img src="logo.png.png" alt="AOU Logo" class="header-logo">
    
    <h1 id="main-title">Smart Academic Advisor</h1>
    <p id="sub-title">Information Technology and Computing (ITC) | AOU Bahrain</p>
</header>

<div class="page-wrapper">
    <form action="" method="POST" style="display: contents;">
        <div class="main-content">
           <div class="year-title" style="border-left-color: #64748b;" id="orient-text">Orientation Courses</div>
<div class="semester-grid" style="grid-template-columns: 1fr;">
    <div class="sem-box" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <?php renderCourse('EL099','Orientation English', 'تمهيدي إنجليزي', 0); ?>
        </div>
        <div style="flex: 1; min-width: 300px;">
            <?php renderCourse('MT099','Orientation Mathematics', 'تمهيدي رياضيات', 0); ?>
        </div>
    </div>
</div> 
            <div class="year-title" id="year1-text">First Year</div>
            <div class="semester-grid">
                <div class="sem-box">
                    <div class="sem-label first-sem-text">First Semester</div>
                    <?php renderCourse('EL111','English Communication Skills (I)', 'مهارات التواصل الإنجليزية (1)', 3); ?>
                    <?php renderCourse('GT101','Learning and Information Technology', 'التعلم وتكنولوجيا المعلومات', 3); ?>
                    <?php renderCourse('AR113','Arabic Communication Skills', 'مهارات التواصل بالعربية', 3); ?>
                    <?php renderCourse('GR131','History and Civilization of Bahrain', 'تاريخ وحضارة البحرين', 3); ?>
                </div>
                <div class="sem-box">
                    <div class="sem-label second-sem-text">Second Semester</div>
                    <?php renderCourse('EL112','English Communication Skills (II)', 'مهارات التواصل الإنجليزية (2)', 3); ?>
                    <?php renderCourse('MST129','Applied Calculus', 'الحسبان التطبيقي', 4); ?>
                    <?php renderCourse('MT131','Discrete Mathematics', 'الرياضيات المتقطعة', 4); ?>
                    <?php renderCourse('MT132','Linear Algebra', 'الجبر الخطي', 4); ?>
                </div>
            </div>

            <div class="year-title" id="year2-text">Second Year</div>
            <div class="semester-grid">
                <div class="sem-box">
                    <div class="sem-label first-sem-text">First Semester</div>
                    <?php renderCourse('GB102','Principles of Entrepreneurship', 'مبادئ ريادة الأعمال', 3); ?>
                    <?php renderCourse('M110','Python Programming', 'برمجة بايثون', 8); ?>
                    <?php renderCourse('LAW107','Human Rights', 'حقوق الإنسان', 2); ?>
                    <?php renderCourse('F_ELEC1','Faculty Elective (1)', 'اختياري كلية (1)', 3, true); ?>
                </div>
                <div class="sem-box">
                    <div class="sem-label second-sem-text">Second Semester</div>
                    <?php renderCourse('TM112','Intro to Computing and IT', 'مقدمة في الحوسبة وتقنية المعلومات', 8); ?>
                    <?php renderCourse('TM105','Introduction to Programming', 'مقدمة في البرمجة', 4); ?>
                    <?php renderCourse('TM103','Computer Organization and Architecture', 'تنظيم وهيكلة الحاسوب', 4); ?>
                    <?php renderCourse('F_ELEC2','Faculty Elective (2)', 'اختياري كلية (2)', 3, true); ?>
                </div>
            </div>

            <div class="year-title" id="year3-text">Third Year</div>
            <div class="semester-grid">
                <div class="sem-box">
                    <div class="sem-label first-sem-text">First Semester</div>
                    <?php renderCourse('M251','OOP using Java', 'البرمجة كائنية التوجه - جافا', 8); ?>
                    <?php renderCourse('TM255','Comm. and IT (Part I)', 'الاتصالات وتقنية المعلومات 1', 8); ?>
                </div>
                <div class="sem-box">
                    <div class="sem-label second-sem-text">Second Semester</div>
                    <?php renderCourse('M269','Algorithms and Data Struct.', 'الخوارزميات وهياكل البيانات', 8); ?>
                    <?php renderCourse('T215B','Comm. and IT (Part II)', 'الاتصالات وتقنية المعلومات 2', 8); ?>
                    <?php renderCourse('TM260','Ethics, Law and Governance in IT', 'الأخلاقيات والقانون والحوكمة', 4); ?>
                </div>
            </div>

            <div class="year-title" id="year4-text">Fourth Year</div>
            <div class="semester-grid">
                <div class="sem-box">
                    <div class="sem-label first-sem-text">First Semester</div>
                    <?php renderCourse('TM351','Data Management and Analysis', 'إدارة وتحليل البيانات', 8); ?>
                    <?php renderCourse('TM354','Software Engineering', 'هندسة البرمجيات', 8); ?>
                    <?php renderCourse('TM471-I','Graduation Project (I)', 'مشروع التخرج 1', 4); ?>
                </div>
                <div class="sem-box">
                    <div class="sem-label second-sem-text">Second Semester</div>
                    <?php renderCourse('TM356','Communications Technology', 'تكنولوجيا الاتصالات', 8); ?>
                    <?php renderCourse('TM471-II','Graduation Project (II)', 'مشروع التخرج 2', 4); ?>
                    <?php renderCourse('INT300','Internship', 'التدريب الميداني', 1); ?>
                </div>
            </div>
        </div>

<div class="sidebar-stats" style="background: #003366; padding: 20px; border-radius: 25px; border: 1.5px solid rgba(212, 175, 55, 0.4); box-shadow: 0 10px 30px rgba(0,0,0,0.25); max-width: 300px; margin: 0 auto;">
    <h3 id="sidebar-title" style="color: white; margin-bottom: 22px; font-size: 1.25rem; text-align: center; font-weight: 700; border-bottom: 2px solid var(--gold); padding-bottom: 10px;">
        <i class="fas fa-chart-pie" style="color: var(--gold); margin-right: 8px;"></i>My Progress
    </h3>

    <div class="stat-card" style="background: rgba(255, 255, 255, 0.07); border-radius: 15px; padding: 15px; margin-bottom: 15px; border: 1px solid rgba(212, 175, 55, 0.2);">
        <label id="lbl_select_advisor" style="font-size: 0.75rem; color: var(--gold); font-weight: bold; margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 0.5px;">
            <i class="fas fa-user-tie"></i> Academic Advisor
        </label>
        <select name="advisor_name" style="width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid var(--gold); background: white; color: #003366; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
            <option value="" disabled <?php echo ($current_advisor == "") ? "selected" : ""; ?>>Select Advisor...</option>
            <option value="Dr. Ashraf Ali|ashraf.ali@aou.org.bh" <?php echo ($current_advisor == "Dr. Ashraf Ali|ashraf.ali@aou.org.bh") ? "selected" : ""; ?>>Dr. Ashraf Ali</option>
            <option value="Dr. Omar Essa|omar.essa@aou.org.bh" <?php echo ($current_advisor == "Dr. Omar Essa|omar.essa@aou.org.bh") ? "selected" : ""; ?>>Dr. Omar Essa</option>
            <option value="Dr. M. Asdaque|m.asdaque@aou.org.bh" <?php echo ($current_advisor == "Dr. M. Asdaque|m.asdaque@aou.org.bh") ? "selected" : ""; ?>>Dr. M. Asdaque Hussain</option>
            <option value="Dr. Khaled Mansour|apas@aou.org.bh" <?php echo ($current_advisor == "Dr. Khaled Mansour|apas@aou.org.bh") ? "selected" : ""; ?>>Dr. Khaled Mansour</option>
            <option value="Dr. Elham Eskandarnia|elham.mohamed@aou.org.bh" <?php echo ($current_advisor == "Dr. Elham Eskandarnia|elham.mohamed@aou.org.bh") ? "selected" : ""; ?>>Dr. Elham Eskandarnia</option>
            <option value="Dr. Hasan Razzaqi|hasan.razzaqi@aou.org.bh" <?php echo ($current_advisor == "Dr. Hasan Razzaqi|hasan.razzaqi@aou.org.bh") ? "selected" : ""; ?>>Dr. Hasan Razzaqi</option>
            <option value="Dr. Irfan Alam|irfan.alam@aou.org.bh" <?php echo ($current_advisor == "Dr. Irfan Alam|irfan.alam@aou.org.bh") ? "selected" : ""; ?>>Dr. Irfan Alam</option>
            <option value="Dr. Meera Ramadas|meera.ramadas@aou.org.bh" <?php echo ($current_advisor == "Dr. Meera Ramadas|meera.ramadas@aou.org.bh") ? "selected" : ""; ?>>Dr. Meera Ramadas</option>
            <option value="Dr. Mohammad Riyaz|mohammad.riyaz@aou.org.bh" <?php echo ($current_advisor == "Dr. Mohammad Riyaz|mohammad.riyaz@aou.org.bh") ? "selected" : ""; ?>>Dr. Mohammad Riyaz</option>
            <option value="Mr. Ahmed Kananah|kananah@aou.org.bh" <?php echo ($current_advisor == "Mr. Ahmed Kananah|kananah@aou.org.bh") ? "selected" : ""; ?>>Mr. Ahmed Kananah</option>
            <option value="Mr. Omar Abahussain|omar.abahussain@aou.org.bh" <?php echo ($current_advisor == "Mr. Omar Abahussain|omar.abahussain@aou.org.bh") ? "selected" : ""; ?>>Mr. Omar Abahussain</option>
            <option value="Ms. Amina Al-Mowali|a.amina@aou.org.bh" <?php echo ($current_advisor == "Ms. Amina Al-Mowali|a.amina@aou.org.bh") ? "selected" : ""; ?>>Ms. Amina Al-Mowali</option>
            <option value="Mr. Sumit Gupta|sumit.gupta@aou.org.bh" <?php echo ($current_advisor == "Mr. Sumit Gupta|sumit.gupta@aou.org.bh") ? "selected" : ""; ?>>Mr. Sumit Gupta</option>
        </select>
    </div>

    <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 15px; margin-bottom: 15px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
        <label style="color: var(--gold); font-weight: bold; font-size: 0.75rem; display: block; margin-bottom: 5px; text-transform: uppercase;">
            <i class="fas fa-graduation-cap"></i> Completed Hours
        </label>
        <span id="hrs-view" style="font-size: 2.2rem; font-weight: 800; color: white;">27</span>
        <div style="width: 75%; height: 5px; background: rgba(255,255,255,0.1); border-radius: 10px; margin: 10px auto;">
            <div style="width: 20%; height: 100%; background: var(--gold); border-radius: 10px; box-shadow: 0 0 8px var(--gold);"></div>
        </div>
        <small style="color: rgba(255,255,255,0.5); font-size: 10px;">Target: 131 Credit Hours</small>
    </div>

    <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 15px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
        <label style="color: var(--gold); font-weight: bold; font-size: 0.75rem; display: block; margin-bottom: 5px; text-transform: uppercase;">
            <i class="fas fa-chart-line"></i> Estimated GPA
        </label>
        <span id="gpa-view" style="font-size: 2.2rem; font-weight: 800; color: white;">3.59</span>
    </div>

 <div style="text-align: center; padding: 0 10px;">
    <button type="submit" name="submit_record" style="background: linear-gradient(to right, #d4af37, #b47b03); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 85%; font-size: 0.8rem; text-transform: uppercase; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: 0.3s; margin-top: 5px;">
        <i class="fas fa-save" style="margin-right: 6px;"></i> Save Record
    </button>
</div>



        <input type="hidden" name="final_gpa_hidden" id="final-gpa-input" value="0.00">
        <input type="hidden" name="final_hrs_hidden" id="final-hrs-input" value="0">
    </form>
</div>

<script>
const prerequisites = {
    // السنة الأولى
    'EL111': ['EL112','MST129','MT131', 'MT132','M110','TM291','TM105', 'TM103'],
    
    
    // السنة الثانية
    'M110': ['M251','M269','TM291', 'TM112'],
    'TM112': ['TM351', 'TM356', 'TM255'], // TM112 تفتح أغلب مواد التخصص
    'TM105': ['M251'],
    'MT131': ['M269'],
    
    // السنة الثالثة
    'M251': ['TM354'],
    'TM255': ['T215B','TM260'], // الجزء الأول يفتح الثاني
    'M269': ['TM351'],
    
    // السنة الرابعة (المشروع والتدريب)
    'TM354': ['TM471-I', 'INT300'], // هندسة البرمجيات تفتح المشروع والتدريب
    'TM351': ['TM471-I','INT300'],           // إدارة البيانات متطلب للتدريب
    'TM471-I': ['TM471-II'],       // مشروع 1 يفتح مشروع 2
    'T215B': ['TM355']             // اتصالات 2 تفتح تكنولوجيا الاتصالات
};
// --- منطق تبديل اللغة ---
let currentLang = 'en';

function toggleLanguage() {
    currentLang = currentLang === 'en' ? 'ar' : 'en';
    const isAr = currentLang === 'ar';

    // 1. اتجاه الصفحة
    document.documentElement.dir = isAr ? 'rtl' : 'ltr';

    // 2. الهيدر والزر
    document.getElementById('lang-btn').innerText = isAr ? "English" : "عربي";
    document.getElementById('main-title').innerText = isAr ? "المستشار الأكاديمي الذكي" : "Smart Academic Advisor";
    document.getElementById('sub-title').innerText = isAr ? "تقنية المعلومات والحوسبة | الجامعة العربية المفتوحة - البحرين" : "Information Technology and Computing (ITC) | AOU Bahrain";

    // 3. أسماء المواد
    document.querySelectorAll('.course-name').forEach(el => {
        el.innerText = isAr ? el.getAttribute('data-ar') : el.getAttribute('data-en');
    });

    // 4. القوائم المنسدلة
    document.querySelectorAll('.lang-text').forEach(el => {
        el.innerText = isAr ? el.getAttribute('data-ar') : el.getAttribute('data-en');
    });

    // 5. عناوين السنوات
    document.getElementById('year1-text').innerText = isAr ? "السنة الأولى" : "First Year";
    document.getElementById('year2-text').innerText = isAr ? "السنة الثانية" : "Second Year";
    document.getElementById('year3-text').innerText = isAr ? "السنة الثالثة" : "Third Year";
    document.getElementById('year4-text').innerText = isAr ? "السنة الرابعة" : "Fourth Year";

    // 6. الفصول
    document.querySelectorAll('.first-sem-text').forEach(el => el.innerText = isAr ? "الفصل الدراسي الأول" : "First Semester");
    document.querySelectorAll('.second-sem-text').forEach(el => el.innerText = isAr ? "الفصل الدراسي الثاني" : "Second Semester");

    // 7. اللوحة الجانبية
    document.getElementById('sidebar-title').innerText = isAr ? "تقدمي الأكاديمي" : "My Progress";
    document.getElementById('label-completed').innerText = isAr ? "الساعات المنجزة" : "Completed Hours";
    document.getElementById('label-gpa').innerText = isAr ? "المعدل التراكمي المتوقع" : "Estimated GPA";
    document.getElementById('save-btn').innerHTML = isAr ? '<i class="fas fa-save"></i> حفظ السجل الأكاديمي' : '<i class="fas fa-save"></i> Save Academic Record';
}


window.onload = function() {
    // عرض البيانات المخزنة فور فتح الصفحة
    document.getElementById('gpa-view').innerText = "<?php echo $current_stored_gpa; ?>";
    document.getElementById('hrs-view').innerText = "<?php echo $current_stored_hrs; ?>";
    document.getElementById('final-gpa-input').value = "<?php echo $current_stored_gpa; ?>";
    document.getElementById('final-hrs-input').value = "<?php echo $current_stored_hrs; ?>";
    updateGPA();
};

function updateGPA() {
    let totalPoints = 0, gpaHours = 0, totalDoneHours = 0;
    const allCards = {}; 

    // المسح الأول: جمع البيانات وحساب القيم
    document.querySelectorAll('.course-card').forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const code = checkbox.value;
        const selectElements = card.querySelectorAll('.grade-input');
        const gradeSelect = selectElements[selectElements.length - 1]; 
        const hours = parseFloat(card.getAttribute('data-hours')) || 0;
        
        allCards[code] = { card, checkbox, gradeSelect, hours };

        if (checkbox.checked) {
            card.classList.add('selected');
            const gradeValue = gradeSelect.value;

            if (gradeValue === "0") { 
                card.classList.add('failed');
            } else {
                card.classList.remove('failed');
                if (gradeValue !== "" && gradeValue !== "Grade") {
                    totalDoneHours += hours;
                }
            }

            if (gradeValue !== "" && gradeValue !== "Grade") {
                totalPoints += (parseFloat(gradeValue) * hours);
                gpaHours += hours;
            }
        } else {
            card.classList.remove('selected', 'failed');
        }
    });

  // المسح الثاني: تطبيق قيود المتطلبات (منع التجاوز)
for (let parentCode in prerequisites) {
    const parent = allCards[parentCode];
    if (parent) {
        // نقوم بجلب القيمة الحالية للدرجة من القائمة المنسدلة
        const parentGrade = parent.gradeSelect.value;

        // التصحيح: المادة تعتبر "ناجحة" وتفتح ما بعدها فقط إذا:
        // 1. تم اختيارها (checked)
        // 2. ليست راسبة (no failed class)
        // 3. تم اختيار درجة فعلية (ليست فارغة وليست كلمة Grade)
        const isPassed = parent.checkbox.checked && 
                          !parent.card.classList.contains('failed') && 
                          parentGrade !== "" && 
                          parentGrade !== "Grade";
        
        prerequisites[parentCode].forEach(childCode => {
            const child = allCards[childCode];
            if (child) {
                if (!isPassed) {
                    // إذا لم ينجح في المتطلب، نغلق المادة التابعة ونصفر خياراتها
                    child.checkbox.checked = false;
                    child.checkbox.disabled = true;
                    child.gradeSelect.value = ""; // تصغير/تصفير الدرجة للمادة المغلقة
                    child.card.classList.remove('selected', 'failed');
                    child.card.style.opacity = "0.5";
                    child.card.style.pointerEvents = "none";
                } else {
                    // إذا نجح، نفتح المادة التابعة
                    child.checkbox.disabled = false;
                    child.card.style.opacity = "1";
                    child.card.style.pointerEvents = "auto";
                }
            }
        });
    }
}

    // التحديث النهائي للواجهة
    const finalGPA = (gpaHours > 0) ? (totalPoints / gpaHours).toFixed(2) : "0.00";
    
    document.getElementById('gpa-view').innerText = finalGPA;
    document.getElementById('hrs-view').innerText = totalDoneHours;
    document.getElementById('final-gpa-input').value = finalGPA;
    document.getElementById('final-hrs-input').value = totalDoneHours;
}
</script>
</body>
</html>