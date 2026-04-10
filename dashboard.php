<?php
session_start();
include 'db.php';

// التأكد من تسجيل الدخول
if (!isset($_SESSION['student_id'])) { 
    header("Location: index.html"); 
    exit(); 
}

// تعريف المتغير الذي كان يسبب المشكلة
$sid = $_SESSION['student_id'];

// جلب البيانات من جدول students (تأكدي أن اسم الجدول students كما في الصور)
// جلب البيانات مع العمود الجديد completed_hours
$query = "SELECT student_name, gpa, major, completed_hours FROM students WHERE student_id='$sid'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $user_name = $user['student_name'];
    $display_gpa = $user['gpa'];
    $display_major = $user['major'];
    $display_hours = (int)($user['completed_hours'] ?? 0);
} else {
    $user_name = "Student";
    $display_gpa = "0.00";
    $display_major = "IT and Computing";
    $display_hours = 0;
}


$total_plan_hours = 131; 
$remaining_hours_total = max(0, $total_plan_hours - $display_hours);
$progress_percent = ($total_plan_hours > 0) ? round(($display_hours / $total_plan_hours) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" id="htmlPage">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AOU Advisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Poppins:wght@400;600;700&display=swap');
        
        :root {
            --primary: #1e3a8a;   
            --secondary: #3b82f6; 
            --gold: #facc15;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --shadow: 0 10px 30px rgba(30, 58, 138, 0.08);
        }

        body { 
            font-family: 'Poppins', 'Tajawal', sans-serif; 
            background: var(--bg-light); 
            margin: 0; 
            color: var(--text-dark); 
            transition: all 0.3s ease;
        }

        /* زر تبديل اللغة - يظهر في الجهة المقابلة للاتجاه */
        .lang-switch {
            position: absolute; top: 20px; right: 20px;
            background: white; color: var(--primary);
            border: 1px solid #ddd; padding: 8px 16px; border-radius: 50px;
            font-weight: 700; cursor: pointer; z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        [dir="rtl"] .lang-switch { right: auto; left: 20px; }

        .hero { 
            background: linear-gradient(135deg, var(--primary) 0%, #1a2857 100%);
            padding: 60px 20px 100px; color: white; text-align: center; 
        }

        /* تنسيق الشعار في لوحة التحكم */
        .dashboard-logo {
            width: 180px;
            height: auto;
            margin-bottom: 20px;
            filter: brightness(0) invert(1); /* جعل الشعار أبيض */
        }

        .hero h1 { font-size: 2rem; margin: 0 0 10px; font-weight: 700; }

        /* تنسيق الترحيب لضمان عدم التداخل */
        .welcome-msg { 
            font-size: 1.2rem; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 25px;
        }
        .welcome-msg b { color: var(--gold); }

        .quick-stats {
            background: rgba(255, 255, 255, 0.15); 
            backdrop-filter: blur(10px);
            border-radius: 50px; 
            padding: 12px 30px; 
            display: inline-flex;
            flex-wrap: wrap; 
            justify-content: center; 
            gap: 25px; 
            border: 1px solid rgba(255,255,255,0.3);
        }

        .stat-item { display: flex; align-items: center; gap: 8px; white-space: nowrap; font-size: 0.9rem; }

        .main-container {
            max-width: 1000px; margin: -50px auto 50px; padding: 0 20px;
            display: grid; grid-template-columns: 1.6fr 1fr; gap: 30px;
        }

        .menu-item {
            background: var(--white); padding: 20px; border-radius: 20px;
            display: flex; align-items: center; text-decoration: none;
            color: var(--text-dark); box-shadow: var(--shadow); transition: 0.3s;
            margin-bottom: 15px; border: 1px solid #edf2f7;
        }
        .menu-item:hover { transform: translateY(-5px); border-color: var(--secondary); }

        .icon-box {
            min-width: 50px; height: 50px; background: #f1f5f9; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-right: 18px; font-size: 20px; color: var(--primary);
        }
        [dir="rtl"] .icon-box { margin-right: 0; margin-left: 18px; }

        .text-box h3 { font-size: 1.1rem; margin: 0 0 5px; color: var(--primary); font-weight: 600; }
        .text-box p { font-size: 0.85rem; margin: 0; color: #64748b; line-height: 1.4; }

        .chart-section {
            background: var(--white); padding: 30px; border-radius: 25px;
            box-shadow: var(--shadow); text-align: center; height: fit-content;
        }

        .chart-container { width: 180px; height: 180px; margin: 0 auto; position: relative; }
        .percentage-label {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            font-size: 1.8rem; font-weight: 800; color: var(--primary);
        }

        .logout-btn { 
            grid-column: span 2; margin: 20px auto; color: #ef4444; 
            text-decoration: none; font-weight: 700; display: flex; 
            align-items: center; gap: 10px; font-size: 0.9rem;
        }

        @media (max-width: 850px) {
            .main-container { grid-template-columns: 1fr; }
            .chart-section { order: -1; }
        }
    </style>
</head>
<body>

<button class="lang-switch" onclick="toggleLang()" id="btnLang">العربية</button>

<div class="hero">

<img src="logo.png.png" alt="AOU Logo" class="dashboard-logo">
    <h1 id="txt-title">Smart Academic Advisor</h1>
    
    <div class="welcome-msg">
        <span id="txt-welcome">Welcome,</span> 
        <b><?php echo htmlspecialchars($user_name); ?></b> 
        <span>👋</span>
    </div>
    
    <div class="quick-stats">
        <div class="stat-item"><i class="fas fa-microchip"></i> <span id="txt-major">IT and Computing</span></div>
        <div class="stat-item"><i class="fas fa-award"></i> <span id="txt-gpa-label">GPA:</span> <b><?php echo $display_gpa; ?></b></div>
        <div class="stat-item"><i class="fas fa-book-reader"></i> <span id="txt-hrs-label">Completed:</span> <b><?php echo $display_hours; ?></b></div>
    </div>
</div>

<div class="main-container">
    <div class="left-col">
        
        <div class="menu-grid">
            <a href="complete_profile.php" class="menu-item">
                <div class="icon-box"><i class="fas fa-user-graduate"></i></div>
                <div class="text-box">
                    <h3 id="btn-record-title">Academic Record (ITC)</h3>
                    <p id="btn-record-desc">Update your core and elective courses progress</p>
                </div>
            </a>

            <a href="plan_semester.php" class="menu-item">
                <div class="icon-box"><i class="fas fa-calendar-alt"></i></div>
                <div class="text-box">
                    <h3 id="btn-plan-title">Semester Planner</h3>
                    <p id="btn-plan-desc">Plan your upcoming schedule based on requirements</p>
                </div>
            </a>

            <a href="gpa_simulator.php" class="menu-item">
                <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                <div class="text-box">
                    <h3 id="btn-sim-title">GPA Simulator</h3>
                    <p id="btn-sim-desc">Simulate future results to improve your standing</p>
                </div>
            </a>
        </div>
    </div>

    <div class="chart-section">
        <h3 id="txt-progress-title" style="color: var(--primary); margin-bottom: 20px;">Study Plan Progress</h3>
        <div class="chart-container">
            <canvas id="progressChart"></canvas>
            <div class="percentage-label"><?php echo $progress_percent; ?>%</div>
        </div>
        <p style="margin-top: 20px; font-size: 0.9rem; color: #64748b;">
            <span id="txt-hrs-done">Completed</span> <b><?php echo $display_hours; ?></b> <span id="txt-hrs-of">hours out of</span> 131
        </p>
    </div>

    <a href="logout.php" class="logout-btn">
        <i class="fas fa-power-off"></i> <span id="txt-logout">Logout</span>
    </a>
</div>

<script>
let currentLang = 'en'; // البداية إنجليزي
const translations = {
    en: {
        title: "Smart Academic Advisor", welcome: "Welcome,", major: "IT and Computing",
        gpa: "GPA:", hrs: "Completed:", recordT: "Academic Record (ITC)", recordD: "Update your core and elective courses progress",
        planT: "Semester Planner", planD: "Plan your upcoming schedule based on requirements",
        simT: "GPA Simulator", simD: "Simulate future results to improve your standing",
        progT: "Study Plan Progress", done: "Completed", of: "hours out of", logout: "Logout", btn: "العربية"
    },
    ar: {
        title: "المرشد الأكاديمي الذكي", welcome: "مرحباً بكِ،", major: "تقنية المعلومات والحوسبة",
        gpa: "المعدل:", hrs: "المنجز:", recordT: "السجل الأكاديمي (ITC)", recordD: "تحديث المواد التخصصية والاختيارية التي اجتزتها",
        planT: "مخطط الفصل الدراسي", planD: "تنظيم جدولك القادم بناءً على متطلبات الحوسبة",
        simT: "توقع المعدل المستقبلي", simD: "محاكاة النتائج المتوقعة لتحسين ترتيبك الأكاديمي",
        progT: "تقدمك في الخطة الدراسية", done: "تم إنجاز", of: "ساعة من", logout: "تسجيل الخروج", btn: "English"
    }
};

function toggleLang() {
    currentLang = currentLang === 'en' ? 'ar' : 'en';
    const html = document.getElementById('htmlPage');
    const t = translations[currentLang];
    
    html.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    html.lang = currentLang;
    
    document.getElementById('txt-title').innerText = t.title;
    document.getElementById('txt-welcome').innerText = t.welcome;
    document.getElementById('txt-major').innerText = t.major;
    document.getElementById('txt-gpa-label').innerText = t.gpa;
    document.getElementById('txt-hrs-label').innerText = t.hrs;
    document.getElementById('btn-record-title').innerText = t.recordT;
    document.getElementById('btn-record-desc').innerText = t.recordD;
    document.getElementById('btn-plan-title').innerText = t.planT;
    document.getElementById('btn-plan-desc').innerText = t.planD;
    document.getElementById('btn-sim-title').innerText = t.simT;
    document.getElementById('btn-sim-desc').innerText = t.simD;
    document.getElementById('txt-progress-title').innerText = t.progT;
    document.getElementById('txt-hrs-done').innerText = t.done;
    document.getElementById('txt-hrs-of').innerText = t.of;
    document.getElementById('txt-logout').innerText = t.logout;
    document.getElementById('btnLang').innerText = t.btn;
}

const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?php echo $display_hours; ?>, <?php echo $remaining_hours_total; ?>],
            backgroundColor: ['#1e3a8a', '#facc15'], 
            borderWidth: 0
        }]
    },
    options: { cutout: '82%', plugins: { legend: { display: false } } }
});
</script>
</body>
</html>