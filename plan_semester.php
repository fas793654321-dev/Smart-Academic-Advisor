<?php
session_start();
include 'db.php'; 

// التأكد من تسجيل الدخول - نستخدم student_id كما هو في قاعدة بياناتكِ
if (!isset($_SESSION['student_id'])) { 
    header("Location: index.html"); 
    exit(); 
}

$uid = $_SESSION['student_id'];

// جلب بيانات المرشد المختار لهذا الطالب
$adv_res = $conn->query("SELECT advisor_name FROM students WHERE student_id = '$uid'");
$adv_data = ($adv_res && $adv_res->num_rows > 0) ? $adv_res->fetch_assoc()['advisor_name'] : "";

// تفكيك الاسم والإيميل (لأننا خزناهما بصيغة الاسم|الإيميل)
$parts = explode('|', $adv_data);
$advisor_name = $parts[0] ?? "Not Assigned";
$advisor_email = $parts[1] ?? "";

// تصحيح الاستعلام: student_name بدلاً من full_name و gpa بدلاً من current_gpa
$user_res = $conn->query("SELECT student_name, gpa, completed_hours FROM students WHERE student_id = '$uid'");

if ($user_res && $user_res->num_rows > 0) {
    $user_data = $user_res->fetch_assoc();
    $user_name = $user_data['student_name'] ?? "Student"; 
    $current_gpa = number_format((float)($user_data['gpa'] ?? 0.00), 2); 
    $completed_hrs = $user_data['completed_hours'] ?? "0";
} else {
    $user_name = "Student";
    $current_gpa = "0.00";
    $completed_hrs = "0";
}

// تخزين البيانات في الجلسة لاستخدامها في الشات
$_SESSION['user_gpa'] = $current_gpa;
$_SESSION['completed_hours'] = $completed_hrs;

// دالة تحديد الحالة الأكاديمية
function getAcademicStatus($gpa) {
    $gpa = (float)$gpa;
    if ($gpa >= 3.67) return ["Excellent", "ممتاز", "#10b981"];
    if ($gpa >= 3.00) return ["Very Good", "جيد جداً", "#3b82f6"];
    if ($gpa >= 2.33) return ["Good", "جيد", "#f59e0b"];
    if ($gpa >= 2.00) return ["Satisfactory", "مقبول", "#64748b"];
    return ["Warning", "إنذار أكاديمي", "#ef4444"];
}
$status = getAcademicStatus($current_gpa);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" id="mainHtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Academic Advisor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* تنسيق الشعار في لوحة التحكم */
.header-logo {
    height: 60px; /* حجم مناسب للوحة التحكم */
    width: auto;
    margin-bottom: 10px;
    filter: brightness(0) invert(1); /* تحويله للون الأبيض ليناسب الخلفية الزرقاء */
}

/* التأكد من توسيط محتوى الهيدر */
.header-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 1100px;
    margin: 0 auto;
}
        :root {
            --main-blue: #102464;
            --bg-light: #f4f7fa;
            --white: #ffffff;
            --gold: #d4af37; 
            --status-color: <?php echo $status[2]; ?>;
            --text-dark: #1e293b;
        }

        body { font-family: 'Poppins', 'Segoe UI', sans-serif; background-color: var(--bg-light); margin: 0; }

 header {
    background-color: var(--main-blue);
    color: white;
    /* قللنا الرقم الأول (30px) لرفعه للأعلى، والثالث (90px) للحفاظ على مساحة خلفية */
    padding: 30px 20px 90px 20px; 
    text-align: center;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

#txt_title {
    /* تصغير الخط من 2.8 إلى 1.8 ليصبح أنعم وأرقى */
    font-size: 1.8rem; 
    font-weight: 600;
    margin-bottom: 5px; /* تقليل المسافة بين العنوان والوصف */
    letter-spacing: 0.5px;
}

#txt_subtitle {
    font-size: 0.9rem; /* تصغير الوصف أيضاً ليتناسب مع العنوان الجديد */
    opacity: 0.8;
    margin-top: 0;
}

        .header-content { max-width: 1100px; margin: 0 auto; }
.container {
            max-width: 1100px;
            margin: -60px auto 40px auto; 
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            position: relative;
            z-index: 10;
        }

        .lang-switch { position: absolute; top: 20px; right: 30px; }
        [dir="rtl"] .lang-switch { right: auto; left: 30px; }

        .btn-lang {
            background: rgba(255, 255, 255, 0.1); border: 1px solid var(--gold);
            color: white; padding: 5px 15px; border-radius: 20px; cursor: pointer;
        }

        .chat-section {
            background: var(--white); border-radius: 20px;
            border: 2px solid var(--gold); 
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.1);
            display: flex; flex-direction: column; height: 550px; overflow: hidden;
        }

        .chat-header { padding: 18px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .chat-body { flex: 1; padding: 25px; overflow-y: auto; background: #fff; }
        
        .msg { display: flex; gap: 12px; margin-bottom: 20px; align-items: flex-start; }
        .bot-avatar { width: 35px; height: 35px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--main-blue); border: 1px solid #e2e8f0; flex-shrink: 0; }
        
        .msg-text { padding: 12px 18px; font-size: 0.9rem; line-height: 1.6; max-width: 80%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        [dir="ltr"] .msg-text { border-radius: 0 18px 18px 18px; background: #f1f5f9; color: var(--text-dark); }
        [dir="rtl"] .msg-text { border-radius: 18px 0 18px 18px; background: #f1f5f9; color: var(--text-dark); }

        .user-msg { justify-content: flex-end; }
        .user-msg .msg-text { background: var(--main-blue) !important; color: white !important; }
        [dir="ltr"] .user-msg .msg-text { border-radius: 18px 18px 0 18px; }
        [dir="rtl"] .user-msg .msg-text { border-radius: 18px 18px 18px 0; }

        .chat-footer { padding: 15px 25px; background: #ffffff; border-top: 1px solid #f1f5f9; }
        
        .quick-replies { display: flex; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .btn-outline { 
            background: white; border: 1px solid var(--gold); color: var(--main-blue);
            padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; cursor: pointer; transition: 0.3s;
        }
        .btn-outline:hover { background: var(--gold); color: white; }

        .input-area { display: flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 25px; padding: 5px 5px 5px 15px; }
        .input-area input { flex: 1; border: none; background: transparent; outline: none; padding: 8px; font-family: inherit; }
        .btn-send { background: var(--gold); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; transition: 0.2s; }
        .btn-send:active { transform: scale(0.9); }

        .stats-sidebar { display: flex; flex-direction: column; gap: 15px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; border: 2px solid var(--gold); text-align: center; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card i { font-size: 1.5rem; color: var(--gold); margin-bottom: 8px; }
        .stat-card h2 { font-size: 2rem; margin: 5px 0; color: var(--main-blue); }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 15px; background: <?php echo $status[2]; ?>15; color: var(--status-color); font-weight: 600; }
    </style>
</head>
<body>

<header>
    <div class="lang-switch">
        <button class="btn-lang" onclick="toggleLang()" id="langBtn">العربية</button>
    </div>
    <div class="header-content">
        <img src="logo.png.png" alt="AOU Logo" class="header-logo">
        
        <h1 id="txt_title">Smart Academic Advisor</h1>
        <p id="txt_subtitle">Planning your academic future with confidence</p>
    </div>
</header>


<div class="container">
    <main class="chat-section">
        <div class="chat-header">
            <span style="font-weight: 600;"><i class="fas fa-robot"></i> <span id="txt_bot_status">Advisor is ready</span></span>
            <span id="txt_online" style="color: #10b981; font-size: 0.8rem;">Online</span>
        </div>
        <div class="chat-body" id="chatContainer">
            <div class="msg">
                <div class="bot-avatar"><i class="fas fa-robot"></i></div>
                <div class="msg-text" id="txt_welcome_msg">
                    Welcome, <strong><?php echo $user_name; ?></strong>! <br>
                    I am your advisor for IT and Computing.
                </div>
            </div>
        </div>
        <div class="chat-footer">
          <div class="quick-replies" id="quickReplies">
    <button class="btn-outline" onclick="sendMessage(this.innerText)">Next Semester Courses</button>
    <button class="btn-outline" onclick="sendMessage(this.innerText)">GPA Improvement</button>
</div>
            <div class="input-area">
                <input type="text" id="userQuery" placeholder="Ask advisor...">
                <button class="btn-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </main>

<aside class="stats-sidebar">
        <div class="stat-card" style="background: #102464; border: 2px solid var(--gold); padding: 18px;">
            <i class="fas fa-user-tie" style="color: var(--gold); font-size: 1.4rem; margin-bottom: 5px;"></i>
            
            <label id="lbl_adv_title" style="color: var(--gold); font-size: 0.75rem; font-weight: bold; display: block; text-transform: uppercase;">
                Academic Advisor
            </label>
            
            <h4 style="color: white; font-size: 0.95rem; margin: 8px 0 15px 0; font-weight: 500;">
                <?php echo $advisor_name; ?>
            </h4>
            
        <?php if (!empty($advisor_email)): ?>
        <a href="mailto:<?php echo $advisor_email; ?>" 
           style="display: flex !important; align-items: center !important; justify-content: center !important; text-align: center !important; background: var(--gold); color: #102464; text-decoration: none; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; width: 100%; box-sizing: border-box;">
            <i class="fas fa-envelope" style="margin: 0 5px; color: #102464;"></i> 
            <span id="txt_contact_word">Contact Now</span>
        </a>
    <?php endif; ?>
        </div>

        <div class="stat-card">
            <i class="fas fa-star"></i>
            <label id="lbl_gpa">GPA</label>
            <h2><?php echo $current_gpa; ?></h2>
        </div>

        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <label id="lbl_hrs">Completed Hours</label>
            <h2><?php echo $completed_hrs; ?></h2>
        </div>

        <div class="stat-card">
            <i class="fas fa-graduation-cap"></i>
            <label id="lbl_status">Status</label><br>
            <div class="status-badge" id="txt_status_val"><?php echo $status[0]; ?></div>
        </div>
    </aside> 
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentLang = 'en';

    $(document).ready(function() {
        // تفعيل زر Enter للإرسال
        $("#userQuery").on("keypress", function(e) {
            if (e.which == 13) {
                e.preventDefault();
                sendMessage();
            }
        });
    });

    window.toggleLang = function() {
        const html = document.getElementById('mainHtml');
        const btn = document.getElementById('langBtn');
        currentLang = (currentLang === 'en') ? 'ar' : 'en';
        html.dir = (currentLang === 'ar') ? 'rtl' : 'ltr';
        btn.innerText = (currentLang === 'ar') ? 'English' : 'العربية';
        updateText(currentLang);
    };

    function updateText(lang) {
        const t = {
            ar: {
                title: "المرشد الأكاديمي الذكي", subtitle: "تخطيط مستقبلك الأكاديمي بثقة",
                bot_status: "المستشار جاهز", online: "متصل",
                welcome: "مرحباً بكِ، <strong>فاطمة</strong>! <br>أنا مستشاركِ لتخصص تقنية المعلومات.",
                lbl_gpa: "المعدل", lbl_hrs: "الساعات المنجزة", lbl_status: "الحالة الأكاديمية",
                status_val: "<?php echo $status[1]; ?>", placeholder: "اسألي المستشار...",
                q1: "مواد الفصل القادم", q2: "رفع المعدل"
            },
            en: {
                title: "Smart Academic Advisor", subtitle: "Planning your academic future with confidence",
                bot_status: "Advisor is ready", online: "Online",
                welcome: "Welcome, <strong>Fatima</strong>! <br>I am your advisor for IT and Computing.",
                lbl_gpa: "GPA", lbl_hrs: "Completed Hours", lbl_status: "Status",
                status_val: "<?php echo $status[0]; ?>", placeholder: "Ask advisor...",
                q1: "Next Semester Courses", q2: "GPA Improvement"
            }
        }[lang];

        $("#txt_title").text(t.title);
        $("#txt_subtitle").text(t.subtitle);
        $("#txt_bot_status").text(t.bot_status);
        $("#txt_online").text(t.online);
        $("#txt_welcome_msg").html(t.welcome);
        $("#lbl_gpa").text(t.lbl_gpa);
        $("#lbl_hrs").text(t.lbl_hrs);
        $("#lbl_status").text(t.lbl_status);
        $("#txt_status_val").text(t.status_val);
        $("#userQuery").attr("placeholder", t.placeholder);
        
        // تحديث نصوص الأزرار السريعة
        const buttons = $("#quickReplies button");
        $(buttons[0]).text(t.q1);
        $(buttons[1]).text(t.q2);
    }

   window.sendMessage = function(specificText = null) {
    const inputField = $("#userQuery");
    const message = specificText || inputField.val().trim();
    const chatContainer = $("#chatContainer");

    if (message === "") return;

    // إظهار رسالة المستخدم
    chatContainer.append(`<div class="msg user-msg"><div class="msg-text">${message}</div></div>`);
    inputField.val("");
    chatContainer.scrollTop(chatContainer[0].scrollHeight);

    $.ajax({
        url: 'chat_process.php', // تأكدي 100% أن هذا الاسم هو اسم ملف الـ PHP للمعالجة
        method: 'POST',
        data: { message: message },
        success: function(response) {
            console.log("Response from server: ", response); // لمشاهدة الرد في الـ Console
            const botMsgHtml = `
                <div class="msg">
                    <div class="bot-avatar"><i class="fas fa-robot"></i></div>
                    <div class="msg-text">${response}</div>
                </div>`;
            chatContainer.append(botMsgHtml);
            chatContainer.scrollTop(chatContainer[0].scrollHeight);
        },
        error: function(xhr, status, error) {
            // هذا التنبيه سيخبرنا أين المشكلة بالضبط
            alert("خطأ فني: " + xhr.status + " - " + error);
            console.log(xhr.responseText);
        }
    });
};
</script>
</body>
</html>