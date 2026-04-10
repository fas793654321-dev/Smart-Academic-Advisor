<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. جلب بيانات الطالب (المعدل والساعات السابقة)
$current_gpa      = isset($_SESSION['user_gpa']) ? (float)$_SESSION['user_gpa'] : 0.00; 
$completed_hours  = isset($_SESSION['completed_hours']) ? (int)$_SESSION['completed_hours'] : 0;

// 2. جلب المواد المقترحة من البوت
$suggested_courses = isset($_SESSION['suggested_courses']) ? $_SESSION['suggested_courses'] : [];

// --- نظام تبديل اللغة ---
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $current_page = explode('?', $_SERVER['REQUEST_URI'])[0];
    header("Location: " . $current_page);
    exit();
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';

function getLangText($ar, $en) {
    global $lang;
    return ($lang == 'ar') ? $ar : $en;
}
error_reporting(E_ERROR | E_PARSE);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo ($lang == 'ar') ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getLangText('محاكي المعدل | المستشار الذكي', 'GPA Simulator | Smart Advisor'); ?></title>

    <style>
.hero-banner {
    display: flex;
    flex-direction: column; /* ترتيب العناصر عمودياً */
    align-items: center;    /* توسيط العناصر أفقياً */
    justify-content: flex-start; /* البدء من أعلى البانر */
    padding-top: 40px;      /* مساحة من الحافة العلوية للبانر */
    min-height: 300px;      /* زيادة الارتفاع ليتسع لكل شيء بوضوح */
    background: linear-gradient(135deg, var(--primary-blue) 0%, #283a74 100%);
    position: relative;
    color: white;
}

.hero-logo {
    height: 70px;           /* حجم الشعار */
    width: auto;
    margin-bottom: 20px;    /* أهم خطوة: مسافة تحت الشعار لرفع العنوان عنه */
    filter: brightness(0) invert(1); /* جعله أبيض */
    z-index: 5;
}

.hero-banner h1 {
    margin: 0;              /* إزالة الهوامش التلقائية التي قد تسبب تداخل */
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1.2;
}

        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap');
        :root { --primary-blue: #182e69; --accent-yellow: #facc15; --bg-color: #f8fafc; --dark-navy: #192d5c; --white: #ffffff; --text-gray: #64748b; --success-green: #10b981; --danger-red: #ef4444; }
        * { font-family: 'Cairo', sans-serif !important; box-sizing: border-box; transition: all 0.2s ease; }
        body { background-color: var(--bg-color); margin: 0; padding: 0; color: #c3cbd7; }

        /* Layout */
        .hero-banner { background: linear-gradient(135deg, var(--primary-blue) 0%, #283a74 100%); height: 280px; padding: 10px 10px; text-align: center; color: white; position: relative; }
        .lang-btn { position: absolute; top: 30px; <?php echo ($lang == 'ar') ? 'left: 40px;' : 'right: 40px;'; ?> background: rgba(218, 208, 208, 0.15); color: white; text-decoration: none; padding: 8px 20px; border-radius: 50px; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(5px); font-weight: 600; }
        .main-wrapper { max-width: 1100px; margin: -80px auto 60px; padding: 0 20px; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 25px; position: relative; z-index: 10; }
        @media (max-width: 900px) { .main-wrapper { grid-template-columns: 1fr; } }

        .card { background: var(--white); border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgb(174, 173, 179); border: 1px solid #eef2f7; }
        .section-title { font-weight: 800; color: var(--primary-blue); margin-bottom: 25px; border-<?php echo ($lang == 'ar') ? 'right' : 'left'; ?>: 5px solid var(--accent-yellow); padding: 0 15px; }

        .course-item { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #f1f5f9; padding: 15px 20px; border-radius: 15px; margin-bottom: 12px; }
        .grade-select { padding: 8px; border-radius: 10px; border: 2px solid #e2e8f0; font-weight: 700; color: var(--primary-blue); }
        .result-card { text-align: center; border-top: 8px solid var(--accent-yellow); position: sticky; top: 20px; }
        .gpa-number { font-size: 4.5rem; font-weight: 900; color: var(--primary-blue); margin: 10px 0; }
        .status-badge { background: #f0f9ff; color: #0369a1; padding: 15px; border-radius: 15px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; border: 1px dashed #bae6fd; display: block; line-height: 1.5; }
        .semester-info { background: #f1f5f9; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; font-weight: 800; color: var(--primary-blue); }
        .nav-btn { display: block; text-decoration: none; background: var(--primary-blue); color: white; padding: 15px; border-radius: 15px; font-weight: 800; margin-top: 20px; text-align: center; }

/* تصميم الحاسبة المستقلة - نظيف ومرتب */
.dark-calculator { 
    background-color: #ffffff; 
    border-radius: 25px; 
    padding: 40px; 
    color: #182e69; 
    grid-column: 1 / -1; 
    position: relative; 
    margin-top: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
    border: 1px solid #eef2f7;
    border-top: 5px solid #facc15; /* لمسة ذهبية في الأعلى للتناسق */
}

/* علامة التعجب - واضحة ومرتبة بجانب العنوان */
.info-trigger { 
    background: #3b82f6; 
    color: white; 
    border: none;
    border-radius: 50%; 
    width: 24px; 
    height: 24px; 
    font-size: 14px; 
    font-weight: bold; 
    cursor: pointer;
    margin-inline-start: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
}

.dark-calculator h2 {
    color: #182e69;
    font-weight: 800;
    font-size: 1.5rem;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/*Labels - واضحة وغير مختصرة */
.input-label {
    display: block;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

/* الخانات - نفس طابع صورك */
.dark-input { 
    width: 100%; 
    background: #f8fafc; 
    border: 2px solid #e2e8f0; 
    border-radius: 12px; 
    padding: 12px 15px; 
    color: #182e69; 
    font-weight: 600; 
    outline: none;
}
.dark-input:focus { border-color: #3b82f6; background: #fff; }

/* حل مشكلة التداخل في صفوف المواد */
.subject-row { 
    display: flex; 
    gap: 15px; 
    margin-bottom: 20px; /* مسافة كافية بين الصفوف */
    align-items: flex-end; 
}

/* الأزرار بطابع جميل واحترافي */
.action-btn {
    padding: 12px 20px;
    border-radius: 12px;
    border: none;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-add-row { background: #f0f9ff; color: #0369a1; border: 1px dashed #0369a1; flex: 1; }
.btn-calculate-now { background: #182e69; color: white; flex: 2; }
.btn-reset-all { background: #fff1f2; color: #ef4444; flex: 1; }

.action-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        /* Modal */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); }
        .modal-content { background: white; margin: 3% auto; padding: 35px; border-radius: 25px; width: 95%; max-width: 850px; max-height: 85vh; overflow-y: auto; position: relative; color: #334155; }
        .close-modal { position: absolute; top: 20px; right: 20px; font-size: 30px; cursor: pointer; color: #64748b; font-weight: bold; }
        .modal-lang-toggle { display: flex; background: #f1f5f9; padding: 5px; border-radius: 12px; width: fit-content; margin-bottom: 20px; gap: 5px; }
        .m-lang-btn { padding: 8px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; background: transparent; color: #64748b; }
        .m-lang-btn.active { background: #3b82f6; color: white; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); }
        .info-section-title { font-weight: 900; color: var(--primary-blue); margin: 25px 0 10px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; }
        .grading-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.95rem; }
        .grading-table th, .grading-table td { padding: 12px; border: 1px solid #e2e8f0; text-align: center; }
        .grading-table th { background: #f8fafc; color: var(--primary-blue); }
        .faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        @media (max-width: 700px) { .faq-grid { grid-template-columns: 1fr; } }
        .faq-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 12px; }
        .faq-card strong { display: block; color: var(--primary-blue); margin-bottom: 8px; font-size: 0.95rem; }
        .faq-card p { margin: 0; font-size: 0.85rem; color: #64748b; line-height: 1.5; }
    </style>
</head>
<body>

<div class="hero-banner">
    <a href="?lang=<?php echo ($lang == 'ar') ? 'en' : 'ar'; ?>" class="lang-btn">
        <?php echo ($lang == 'ar') ? 'English ' : 'العربية'; ?>
    </a>
    
    <img src="logo.png.png" alt="AOU Logo" class="hero-logo">
    
    <h1><?php echo getLangText('محاكي المعدل الذكي', 'Smart GPA Simulator'); ?></h1>
    <p><?php echo getLangText('خطط لمعدلك وتفوق في مسيرتك الأكاديمية', 'Plan your GPA and excel in your academic journey'); ?></p>
</div>

<div class="main-wrapper">
    <div class="left-col">
        <div class="card">
            <div class="section-title"><?php echo getLangText('المواد المقترحة في خطتك', 'Suggested Courses in Plan'); ?></div>
            <?php if(empty($suggested_courses)): ?>
                <p style="text-align:center; color:var(--text-gray);"><?php echo getLangText('لا توجد مواد مقترحة حالياً.', 'No suggested courses yet.'); ?></p>
            <?php else: ?>
                <?php foreach ($suggested_courses as $course): ?>
                <div class="course-item">
                    <div>
                        <div style="font-weight: 800; color: var(--primary-blue);"><?php echo $course['code']; ?></div>
                        <div style="color: var(--text-gray); font-size: 0.8rem;"><?php echo $course['hours']; ?> <?php echo getLangText('ساعات', 'Hrs'); ?></div>
                    </div>
                    <select class="grade-select top-grade" data-h="<?php echo $course['hours']; ?>" onchange="updateMainGPA()">
                        <option value="4.0">A</option><option value="3.5">B+</option><option value="3.0">B</option>
                        <option value="2.5">C+</option><option value="2.0">C</option><option value="1.5">D</option><option value="0.0">F</option>
                    </select>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="right-col">
        <div class="card result-card">
            <div style="font-weight: 800; color: var(--text-gray); font-size: 0.9rem;"><?php echo getLangText('المعدل التراكمي المتوقع', 'Predicted Cumulative GPA'); ?></div>
            <div id="main-cgpa" class="gpa-number">0.00</div>
            <div class="status-badge" id="status-msg"></div>
            <div class="semester-info">
                <span><?php echo getLangText('معدل الفصل:', 'Semester GPA:'); ?></span>
                <span id="main-sgpa">0.00</span>
            </div>
            <a href="plan_semester.php" class="nav-btn"><?php echo getLangText('العودة للتخطيط 🗓️', 'Back to Planning 🗓️'); ?></a>
        </div>
    </div>
<div class="dark-calculator">
    <h2>
        <?php echo getLangText('حاسبة المعدل التراكمي المستقلة', 'Independent Cumulative GPA Calculator'); ?>
        <button class="info-trigger" onclick="toggleModal(true)">!</button>
    </h2>
    
    <div style="display:flex; gap:20px; margin-bottom:25px; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <label class="input-label"><?php echo getLangText('المعدل التراكمي الحالي (قبل الفصل الحالي)', 'Current Cumulative GPA'); ?></label>
            <input type="number" id="manual-prev-gpa" class="dark-input" placeholder="0.00" step="0.01">
        </div>
        <div style="flex:1; min-width:200px;">
            <label class="input-label"><?php echo getLangText('إجمالي الساعات المكتسبة السابقة', 'Total Completed Hours'); ?></label>
            <input type="number" id="manual-prev-hrs" class="dark-input" placeholder="0">
        </div>
    </div>

    <div id="manual-subjects-container">
        <div class="subject-row">
            <div style="flex:2">
                <label class="input-label" style="font-size:0.8rem"><?php echo getLangText('الدرجة المتوقعة', 'Expected Grade'); ?></label>
                <input type="text" class="dark-input m-grade" placeholder="A, B+, C...">
            </div>
            <div style="flex:1">
                <label class="input-label" style="font-size:0.8rem"><?php echo getLangText('ساعات المادة', 'Course Hours'); ?></label>
                <input type="number" class="dark-input m-hours" placeholder="3">
            </div>
            <div style="width:40px"></div> </div>
    </div>

    <div style="display:flex; gap:15px; margin-top:30px; flex-wrap:wrap;">
        <button class="action-btn btn-add-row" onclick="addNewSubjectRow()">+ <?php echo getLangText('إضافة مادة', 'Add Course'); ?></button>
        <button class="action-btn btn-calculate-now" onclick="calculateManualGPA()"><?php echo getLangText('حساب النتيجة الآن', 'Calculate Result Now'); ?></button>
        <button class="action-btn btn-reset-all" onclick="resetManualCalc()"><?php echo getLangText('مسح البيانات', 'Reset All'); ?></button>
    </div>
    
    <div id="manual-result-box" style="display:none; text-align:center; margin-top:35px; background:#f1f5f9; padding:25px; border-radius:15px;">
        <div style="color: #64748b; font-weight: 700;"><?php echo getLangText('المعدل التراكمي الجديد المتوقع:', 'Predicted New Cumulative GPA:'); ?></div>
        <div id="manual-cgpa-res" style="font-size:4rem; font-weight:900; color:#182e69;">0.00</div>
    </div>
</div>

<div id="infoModal" class="modal">
    <div class="modal-content" id="modalContent">
        <span class="close-modal" onclick="toggleModal(false)">&times;</span>
        
        <div class="modal-lang-toggle">
            <button onclick="updateModalLang('en')" class="m-lang-btn active" id="btn-en">English</button>
            <button onclick="updateModalLang('ar')" class="m-lang-btn" id="btn-ar">العربية</button>
        </div>

        <h2 id="m-header" style="color:var(--primary-blue); font-weight:900; margin-top:0;">GPA Information</h2>
        
        <div class="info-section-title"><span>ℹ️</span> <span id="m-title-about">About</span></div>
        <p id="m-text-about" style="line-height:1.6; color:#475569;"></p>

        <div class="info-section-title"><span>📏</span> <span id="m-title-scale">Grading Scale</span></div>
        <table class="grading-table">
            <thead>
                <tr>
                    <th id="m-th-letter">Grade</th>
                    <th id="m-th-points">Points</th>
                    <th id="m-th-percent">%</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>A</td><td>4.0</td><td>90-100%</td></tr>
                <tr><td>B+</td><td>3.5</td><td>82-89%</td></tr>
                <tr><td>B</td><td>3.0</td><td>74-81%</td></tr>
                <tr><td>C+</td><td>2.5</td><td>66-73%</td></tr>
                <tr><td>C</td><td>2.0</td><td>58-65%</td></tr>
                <tr><td>D</td><td>1.5</td><td>50-57%</td></tr>
                <tr><td>F</td><td>0.0</td><td id="m-td-below">Below 50%</td></tr>
            </tbody>
        </table>

        <div class="info-section-title"><span>❓</span> <span id="m-title-faq">FAQ</span></div>
        <div id="m-faq-area"></div>

        <div class="info-section-title"><span>💡</span> <span id="m-title-tips">Tips for Success</span></div>
        <ul id="m-tips-area" style="line-height:1.8; color:#475569; font-weight:600;"></ul>
    </div>
</div>

<script>
const pointsMap = {'A':4.0, 'B+':3.5, 'B':3.0, 'C+':2.5, 'C':2.0, 'D':1.5, 'F':0.0};

const modalContentData = {
    en: {
        header: "GPA Calculator Information",
        aboutTitle: "About This Calculator",
        aboutText: "This GPA calculator is specifically designed for Arab Open University (AOU) students. It helps you calculate both your semester GPA and cumulative GPA based on the university's grading system. The calculator uses the standard 4.0 scale and supports both English and Arabic interfaces.",
        scaleTitle: "Grading Scale",
        thLetter: "Letter Grade",
        thPoints: "Grade Points",
        thPercent: "Percentage",
        tdBelow: "Below 50%",
        faqTitle: "Frequently Asked Questions",
        faqHtml: `
            <div class="faq-grid">
                <div class="faq-card"><strong>How do I use this?</strong><p>1. Enter previous GPA/Hours.<br>2. Add current subjects.<br>3. Select grades and hours.<br>4. Click calculate.</p></div>
                <div class="faq-card"><strong>What if I'm new?</strong><p>Leave previous GPA/Hours at 0. It will compute your first semester GPA.</p></div>
                <div class="faq-card"><strong>Multiple semesters?</strong><p>Yes, use your total cumulative stats to see the new updated GPA.</p></div>
                <div class="faq-card"><strong>Difference?</strong><p>Semester: Current courses only. Cumulative: All courses from all semesters combined.</p></div>
            </div>
        `,
        tipsTitle: "Tips for Success",
        tipsHtml: `<li>Double-check credit hours before calculating.</li><li>Use this to plan future semester loads.</li><li>Aim for higher grades in major subjects.</li>`,
        dir: "ltr"
    },
    ar: {
        header: "معلومات حاسبة المعدل",
        aboutTitle: "حول هذه الحاسبة",
        aboutText: "تم تصميم حاسبة المعدل هذه خصيصاً لطلاب الجامعة العربية المفتوحة (AOU). تساعدك على حساب المعدل الفصلي والتراكمي بناءً على نظام الجامعة المعتمد (مقياس 4.0).",
        scaleTitle: "سلم الدرجات",
        thLetter: "الدرجة",
        thPoints: "النقاط",
        thPercent: "النسبة",
        tdBelow: "أقل من 50%",
        faqTitle: "الأسئلة الشائعة",
        faqHtml: `
            <div class="faq-grid">
                <div class="faq-card"><strong>كيف أستخدم الحاسبة؟</strong><p>1. أدخل المعدل والساعات السابقة.<br>2. أضف مواد الفصل الحالي.<br>3. اختر الدرجة والساعات.<br>4. اضغط احسب.</p></div>
                <div class="faq-card"><strong>لو لم يكن لدي معدل سابق؟</strong><p>اترك الخانات السابقة 0، وسيتم حساب معدل فصلك الأول فقط.</p></div>
                <div class="faq-card"><strong>حساب عدة فصول؟</strong><p>نعم، أدخل إجمالي الساعات والنقاط التراكمية السابقة للحصول على النتيجة المحدثة.</p></div>
                <div class="faq-card"><strong>ما الفرق؟</strong><p>الفصلي: مواد الفصل الحالي فقط. التراكمي: جميع المواد من كافة الفصول الدراسية.</p></div>
            </div>
        `,
        tipsTitle: "نصائح للنجاح",
        tipsHtml: `<li>تأكد من إدخال الساعات المعتمدة لكل مادة بدقة.</li><li>استخدم الحاسبة لتخطيط عبئك الدراسي القادم.</li><li>ركز على مواد التخصص لرفع المعدل التراكمي.</li>`,
        dir: "rtl"
    }
};

function updateModalLang(lang) {
    const data = modalContentData[lang];
    const modal = document.getElementById('modalContent');
    modal.style.direction = data.dir;
    modal.style.textAlign = (lang === 'ar') ? 'right' : 'left';
    document.getElementById('m-header').innerText = data.header;
    document.getElementById('m-title-about').innerText = data.aboutTitle;
    document.getElementById('m-text-about').innerText = data.aboutText;
    document.getElementById('m-title-scale').innerText = data.scaleTitle;
    document.getElementById('m-th-letter').innerText = data.thLetter;
    document.getElementById('m-th-points').innerText = data.thPoints;
    document.getElementById('m-th-percent').innerText = data.thPercent;
    document.getElementById('m-td-below').innerText = data.tdBelow;
    document.getElementById('m-title-faq').innerText = data.faqTitle;
    document.getElementById('m-faq-area').innerHTML = data.faqHtml;
    document.getElementById('m-title-tips').innerText = data.tipsTitle;
    document.getElementById('m-tips-area').innerHTML = data.tipsHtml;
    document.getElementById('btn-en').classList.toggle('active', lang === 'en');
    document.getElementById('btn-ar').classList.toggle('active', lang === 'ar');
}

function toggleModal(show) { 
    document.getElementById('infoModal').style.display = show ? 'block' : 'none'; 
    if(show) updateModalLang('<?php echo $lang; ?>'); 
}

// تشغيل الحسابات والربط
document.addEventListener("DOMContentLoaded", function() {
    const phpGPA = parseFloat("<?php echo $current_gpa; ?>") || 0;
    const phpHrs = parseInt("<?php echo $completed_hours; ?>") || 0;
    
    const gpaInput = document.getElementById('manual-prev-gpa');
    const hrsInput = document.getElementById('manual-prev-hrs');
    
    if (gpaInput) gpaInput.value = phpGPA;
    if (hrsInput) hrsInput.value = phpHrs;

    // إضافة مراقبين لضمان التحديث التلقائي الفوري
    if(gpaInput) gpaInput.addEventListener('input', updateMainGPA);
    if(hrsInput) hrsInput.addEventListener('input', updateMainGPA);
    
    document.querySelectorAll('.top-grade').forEach(select => {
        select.addEventListener('change', updateMainGPA);
    });

    updateMainGPA(); 
});

function updateMainGPA() {
    const gpaInput = document.getElementById('manual-prev-gpa');
    const hrsInput = document.getElementById('manual-prev-hrs');
    
    const baseGPA = parseFloat(gpaInput.value) || 0;
    const baseHrs = parseFloat(hrsInput.value) || 0;
    
    let previousTotalPoints = baseGPA * baseHrs;
    let currentSemesterPoints = 0;
    let currentSemesterHours = 0;

    document.querySelectorAll('.top-grade').forEach(select => {
        let h = parseFloat(select.dataset.h) || 0;
        let g = parseFloat(select.value) || 0;
        currentSemesterPoints += (g * h); 
        currentSemesterHours += h;
    });

    let totalCombinedHours = baseHrs + currentSemesterHours;
    let totalCombinedPoints = previousTotalPoints + currentSemesterPoints;
    
    let sGPA = currentSemesterHours > 0 ? (currentSemesterPoints / currentSemesterHours) : 0;
    let cGPA = totalCombinedHours > 0 ? (totalCombinedPoints / totalCombinedHours) : 0;

    document.getElementById('main-sgpa').innerText = sGPA.toFixed(2);
    document.getElementById('main-cgpa').innerText = cGPA.toFixed(2);
    
    const msgBox = document.getElementById('status-msg');
    let message = "", bgColor = "#f0f9ff", textColor = "#0369a1", borderColor = "#bae6fd";
    
    // --- نظام الرسائل التنبيهية الذكي ---
    
    if (totalCombinedHours === 0) {
        message = "<?php echo getLangText('ابدأ باختيار درجاتك المتوقعة لتحليل وضعك', 'Start selecting grades to analyze your status'); ?>";
    } 
    // حالة الإنذار الأكاديمي (أقل من 2.00)
    else if (cGPA < 2.00) {
        bgColor = "#fff1f2"; textColor = "#e11d48"; borderColor = "#fda4af";
        if (cGPA < baseGPA) {
            message = "<?php echo getLangText('⚠️ إنذار: معدلك في انخفاض وتحت الـ 2.00. تحتاج لرفع درجاتك لتجنب الفصل!', '⚠️ Warning: Your GPA is dropping and below 2.00. You need higher grades to avoid dismissal!'); ?>";
        } else {
            message = "<?php echo getLangText('🔍 تحسن طفيف: معدلك يرتفع ولكنه لا يزال تحت الـ 2.00 (حالة إنذار). استمر بالعمل!', '🔍 Slight improvement: Your GPA is rising but still below 2.00 (Warning status). Keep pushing!'); ?>";
        }
    }
    // حالة النطاق الآمن (بين 2.00 و 2.99)
    else if (cGPA >= 2.00 && cGPA < 3.00) {
        bgColor = "#f0f9ff"; textColor = "#0369a1"; borderColor = "#bae6fd";
        if (cGPA > baseGPA && baseGPA > 0) {
            message = "<?php echo getLangText('📈 رائع! معدلك في ارتفاع مستمر وهو الآن في النطاق الآمن.', '📈 Great! Your GPA is rising and is now in the safe range.'); ?>";
        } else if (cGPA < baseGPA) {
            message = "<?php echo getLangText('📉 انتباه: معدلك انخفض قليلاً ولكنه لا يزال في النطاق الآمن (فوق 2.00).', '📉 Note: Your GPA dropped slightly but is still in the safe range (above 2.00).'); ?>";
        } else {
            message = "<?php echo getLangText('✅ وضعك مستقر ومعدلك في النطاق الآمن حالياً.', '✅ Your status is stable and your GPA is currently in the safe range.'); ?>";
        }
    }
    // حالة الجيد جداً (بين 3.00 و 3.49)
    else if (cGPA >= 3.00 && cGPA < 3.50) {
        bgColor = "#f0fdf4"; textColor = "#15803d"; borderColor = "#bbf7d0";
        message = "<?php echo getLangText('✨ جيد جداً! أنت قريب جداً من قائمة لوحة الشرف.', '✨ Very Good! You are very close to the Honor Roll.'); ?>";
    }
    // حالة الامتياز ولوحة الشرف (3.50 فما فوق)
    else if (cGPA >= 3.50) {
        bgColor = "#faf5ff"; textColor = "#7e22ce"; borderColor = "#e9d5ff";
        message = "<?php echo getLangText('👑 مذهل! أنت الآن في نطاق لوحة الشرف (الامتياز). حافظ على هذا المستوى!', '👑 Amazing! You are now in the Honor Roll range (Excellent). Maintain this level!'); ?>";
    }

    // تطبيق التنسيقات على الصندوق
    msgBox.style.background = bgColor; 
    msgBox.style.color = textColor;
    msgBox.style.borderColor = borderColor;
    msgBox.style.borderStyle = "solid";
    msgBox.style.borderWidth = "1px";
    msgBox.innerHTML = message;
}

function calculateManualGPA() {
    const pGPA = parseFloat(document.getElementById('manual-prev-gpa').value) || 0;
    const pHrs = parseFloat(document.getElementById('manual-prev-hrs').value) || 0;
    let nPoints = 0, nHrs = 0;
    const gs = document.querySelectorAll('.m-grade'), hs = document.querySelectorAll('.m-hours');
    
    gs.forEach((g, i) => {
        let grade = g.value.toUpperCase().trim(), hrs = parseFloat(hs[i].value) || 0;
        if (pointsMap[grade] !== undefined) { 
            nPoints += (pointsMap[grade] * hrs); 
            nHrs += hrs; 
        }
    });
    
    if ((pHrs + nHrs) > 0) {
        let res = ((pGPA * pHrs) + nPoints) / (pHrs + nHrs);
        document.getElementById('manual-cgpa-res').innerText = res.toFixed(2);
        document.getElementById('manual-result-box').style.display = 'block';
    }
}

function addNewSubjectRow() {
    const container = document.getElementById('manual-subjects-container');
    const div = document.createElement('div');
    div.className = 'subject-row';
    div.innerHTML = `<input type="text" class="dark-input m-grade" style="flex:2" placeholder="Grade"><input type="number" class="dark-input m-hours" style="flex:1" placeholder="Hrs"><button onclick="this.parentElement.remove()" style="background:none; border:none; color:#f87171; cursor:pointer; font-weight:bold; width:40px">✕</button>`;
    container.appendChild(div);
}

function resetManualCalc() {
    document.getElementById('manual-prev-gpa').value = '';
    document.getElementById('manual-prev-hrs').value = '';
    document.getElementById('manual-subjects-container').innerHTML = `<div class="subject-row"><input type="text" class="dark-input m-grade" style="flex:2" placeholder="Grade"><input type="number" class="dark-input m-hours" style="flex:1" placeholder="Hrs"><div style="width:40px"></div></div>`;
    document.getElementById('manual-result-box').style.display = 'none';
    updateMainGPA();
}

window.onclick = function(e) { if(e.target == document.getElementById('infoModal')) toggleModal(false); }
</script>
</body>
</html>