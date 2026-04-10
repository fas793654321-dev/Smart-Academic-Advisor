<?php
include 'db.php';
session_start();

// 1. دالة لتحديد لغة نص المستخدم (تصحيح محددات النمط)
function isArabic($text) {
    return preg_match('/\p{Arabic}/u', $text);
}

// 2. الحصول على رسالة المستخدم وتحديد اللغة
$userMsg = $_POST['message'] ?? '';
$isAr = isArabic($userMsg);

// 3. دالة لجلب النص بناءً على اللغة المختارة
function getLangText($ar, $en) {
    global $isAr;
    return $isAr ? $ar : $en;
}
// --- 1. قاعدة المعرفة: شروحات المواد التفصيلية ---
$course_details = [
    // السنة الأولى
 'EL111' => [
        'desc' => 'هذه المادة هي الأساس في تطوير مهارات اللغة الإنجليزية الأكاديمية، وتركز على القراءة والكتابة الأساسية. ستتعلم كيفية تكوين جمل صحيحة، وفهم النصوص البسيطة، واستخدام المفردات الأكاديمية. تعتبر مهمة جدًا لأن أغلب مواد التخصص باللغة الإنجليزية. اجتهد فيها لأنها تؤثر على فهمك لباقي المواد.', 
        'desc_en' => 'This course is the foundation for developing academic English skills, focusing on basic reading and writing. You will learn how to form correct sentences, understand simple texts, and use academic vocabulary. It is very important because most major courses are in English. Work hard on it because it affects your understanding of the rest of the courses.',
        'hours' => 3
    ],
    'GT101' => [
        'desc' => 'تعرفك هذه المادة على أساسيات استخدام الحاسوب والتقنيات الحديثة في التعلم. ستتعلم مهارات البحث، استخدام البرامج، وإدارة المعلومات. تعتبر مادة تمهيدية لأي طالب في تخصص تقني. تساعدك على تنظيم دراستك بشكل احترافي.', 
        'desc_en' => 'This course introduces you to the basics of using computers and modern technologies in learning. You will learn research skills, using programs, and information management. It is considered an introductory course for any student in a technical major. It helps you organize your studies professionally.',
        'hours' => 3
    ],
    'AR113' => [
        'desc' => 'تركز على تحسين مهارات الكتابة والتعبير باللغة العربية بشكل أكاديمي. ستتعلم كيفية كتابة تقارير وأفكار منظمة. مهمة لكتابة المشاريع والتقارير لاحقًا.', 
        'desc_en' => 'Focuses on improving academic Arabic writing and expression skills. You will learn how to write organized reports and ideas. Important for writing projects and reports later.',
        'hours' => 3
    ],
    'GR131' => [
        'desc' => 'تعطيك نظرة عن تاريخ وثقافة مملكة البحرين. مادة خفيفة نسبيًا وتساعد على رفع المعدل. تعتمد على الحفظ والفهم البسيط. فرصة لتعويض أي ضعف في مواد أخرى.', 
        'desc_en' => 'Gives you an overview of the history and culture of the Kingdom of Bahrain. A relatively light course that helps raise your GPA. Relies on simple memorization and understanding. An opportunity to compensate for any weakness in other courses.',
        'hours' => 3
    ],
    'EL112' => [
        'desc' => 'تكملة للمادة السابقة ولكن بمستوى أعلى. تركز على الكتابة الأكاديمية والتعبير بشكل أفضل. ستتعلم كتابة مقالات وتقارير أكثر احترافية. مهمة جدًا لمشاريع التخرج.', 
        'desc_en' => 'A continuation of the previous course but at a higher level. Focuses on academic writing and better expression. You will learn to write more professional essays and reports. Very important for graduation projects.',
        'hours' => 3
    ],
    'MT129' => [
        'desc' => 'مادة رياضيات مهمة تتناول التفاضل والتكامل وتطبيقاتها. تحتاج تركيز وفهم عميق وليس حفظ فقط. تستخدم لاحقًا في تحليل البيانات والخوارزميات.', 
        'desc_en' => 'An important mathematics course covering calculus and its applications. Requires concentration and deep understanding, not just memorization. Used later in data analysis and algorithms.',
        'hours' => 4
    ],
    'MT131' => [
        'desc' => 'من أهم مواد التخصص لأنها أساس البرمجة والخوارزميات. تتعلم فيها المنطق، المجموعات، العلاقات، والرسوم البيانية. تحتاج تفكير منطقي قوي.', 
        'desc_en' => 'One of the most important major courses because it is the foundation of programming and algorithms. You learn logic, sets, relations, and graphs. Requires strong logical thinking.',
        'hours' => 4
    ],
    'MT132' => [
        'desc' => 'تغطي المصفوفات والمتجهات والعمليات عليها. مهمة في مجالات مثل الذكاء الاصطناعي والرسومات. تحتاج فهم تدريجي.', 
        'desc_en' => 'Covers matrices, vectors, and operations on them. Important in fields like artificial intelligence and graphics. Requires gradual understanding.',
        'hours' => 4
    ],

    // السنة الثانية
    'GB102' => [
        'desc' => 'تعلمك أساسيات ريادة الأعمال وكيف تبدأ مشروعك الخاص. مادة ممتعة ومفيدة للحياة العملية. تساعدك على التفكير بطريقة إبداعية. سهلة نسبيًا وتعتمد على الفهم.', 
        'desc_en' => 'Teaches you the basics of entrepreneurship and how to start your own business. A fun and useful course for practical life. Helps you think creatively. Relatively easy and relies on understanding.',
        'hours' => 3
    ],
    'M110'  => [
        'desc' => 'من أهم مواد البرمجة في الخطة. تتعلم فيها أساسيات البرمجة باستخدام لغة Python. تشمل المتغيرات، الشروط، الحلقات، والدوال. مادة عملية جدًا وتحتاج تدريب مستمر.', 
        'desc_en' => 'One of the most important programming courses in the plan. You learn the basics of programming using Python. Includes variables, conditions, loops, and functions. A very practical course and requires continuous practice.',
        'hours' => 8
    ],
    'LAW107'=> [
        'desc' => 'مادة نظرية تتناول حقوق الإنسان والقوانين الأساسية. سهلة وتعتمد على الفهم والحفظ. تساعدك على توسيع معرفتك العامة. فرصة جيدة لرفع المعدل.', 
        'desc_en' => 'A theoretical course dealing with human rights and basic laws. Easy and relies on understanding and memorization. Helps you expand your general knowledge. A good opportunity to raise your GPA.',
        'hours' => 2
    ],
    'TM112' => [
        'desc' => 'مادة شاملة تعطيك نظرة عامة عن عالم الحوسبة. تشمل الشبكات، البيانات، البرمجة، وأساسيات التقنية. تعتبر من أهم المواد التأسيسية.', 
        'desc_en' => 'A comprehensive course that gives you an overview of the computing world. Includes networks, data, programming, and technology basics. Considered one of the most important foundational courses.',
        'hours' => 8
    ],
    'TM105' => [
        'desc' => 'مقدمة في البرمجة بأسلوب مبسط. تتعلم التفكير البرمجي قبل كتابة الكود. مادة أساسية لفهم البرمجة بشكل صحيح. سهلة إذا فهمت المنطق.', 
        'desc_en' => 'An introduction to programming in a simplified manner. You learn programmatic thinking before writing code. An essential course to understand programming correctly. Easy if you understand the logic.',
        'hours' => 4
    ],
    'TM103' => [
        'desc' => 'تشرح كيف يعمل الحاسوب من الداخل. تشمل المعالج، الذاكرة، ونظام التشغيل. مادة مهمة لكنها تحتاج تركيز. تساعدك على فهم الأداء البرمجي.', 
        'desc_en' => 'Explains how the computer works from the inside. Includes the processor, memory, and operating system. An important course but requires concentration. Helps you understand programming performance.',
        'hours' => 4
    ],

    // السنة الثالثة
    'M251'  => [
        'desc' => 'تركز على البرمجة الكائنية باستخدام Java. تتعلم مفاهيم مثل الكائنات، الوراثة، والتغليف. مادة مهمة جدًا في سوق العمل. تعتبر من أصعب مواد البرمجة.', 
        'desc_en' => 'Focuses on object-oriented programming using Java. You learn concepts like objects, inheritance, and encapsulation. A very important course in the job market. Considered one of the hardest programming courses.',
        'hours' => 8
    ],
    'TM255' => [
        'desc' => 'تغطي الشبكات والاتصالات والإنترنت. مادة مهمة لفهم كيف تنتقل البيانات. تشمل مفاهيم مثل البروتوكولات والأمان. مفيدة جدًا في مجال الشبكات.', 
        'desc_en' => 'Covers networks, communications, and the internet. An important course to understand how data travels. Includes concepts like protocols and security. Very useful in the networking field.',
        'hours' => 8
    ],
    'M269'  => [
        'desc' => 'من أهم وأصعب مواد التخصص. تتعلم الخوارزميات وهياكل البيانات مثل القوائم والأشجار. مادة أساسية للمقابلات الوظيفية. ركز عليها جدًا.', 
        'desc_en' => 'One of the most important and difficult major courses. You learn algorithms and data structures like lists and trees. An essential course for job interviews. Focus on it a lot.',
        'hours' => 8
    ],
    'T215B' => [
        'desc' => 'تكملة لمادة الشبكات ولكن بشكل أعمق. تتناول تقنيات متقدمة في الاتصال. تحتاج فهم جيد للمادة السابقة.', 
        'desc_en' => 'A continuation of the networking course but deeper. Deals with advanced communication technologies. Requires a good understanding of the previous course.',
        'hours' => 8
    ],
    'TM260' => [
        'desc' => 'تتناول أخلاقيات استخدام التقنية والقوانين. مادة نظرية لكنها مهمة. تساعدك على فهم المسؤوليات المهنية. سهلة نسبيًا.', 
        'desc_en' => 'Deals with the ethics of using technology and laws. A theoretical course but important. Helps you understand professional responsibilities. Relatively easy.',
        'hours' => 4
    ],

    // السنة الرابعة
    'TM351' => [
        'desc' => 'تتعلم فيها قواعد البيانات وتحليل البيانات. تشمل SQL والتصميم. مادة مهمة جدًا لسوق العمل. تحتاج تطبيق عملي.', 
        'desc_en' => 'You learn databases and data analysis. Includes SQL and design. A very important course for the job market. Requires practical application.',
        'hours' => 8
    ],
    'TM354' => [
        'desc' => 'تشرح كيفية تطوير الأنظمة والبرمجيات بشكل احترافي. تشمل مراحل التطوير وإدارة المشاريع. مادة مهمة جدًا للمشاريع.', 
        'desc_en' => 'Explains how to develop systems and software professionally. Includes development phases and project management. A very important course for projects.',
        'hours' => 8
    ],
    'TM471' => [
        'desc' => 'مشروع التخرج الذي يطبق كل ما تعلمته. يحتاج جهد كبير وتنظيم. أهم مادة في التخصص. فرصتك لإثبات مهاراتك.', 
        'desc_en' => 'The graduation project that applies everything you have learned. Requires great effort and organization. The most important course in the major. Your chance to prove your skills.',
        'hours' => 8 // جمعت الساعات للمشروع كامل
    ], 
    'TM355' => [
        'desc' => 'تغطي تقنيات الاتصال الحديثة. تكملة لمواد الشبكات. مادة متقدمة. تحتاج فهم جيد للأساسيات.', 
        'desc_en' => 'Covers modern communication technologies. A continuation of networking courses. An advanced course. Requires a good understanding of the basics.',
        'hours' => 8
    ],
    'INT300'=> [
        'desc' => 'تدريب عملي في شركة. يعطيك خبرة حقيقية. مهم جدًا لسوق العمل. حاول تستفيد منه قدر الإمكان.', 
        'desc_en' => 'Practical training in a company. Gives you real experience. Very important for the job market. Try to benefit from it as much as possible.',
        'hours' => 1
    ],
    
    // --- إضافة المواد الاختيارية الجديدة هنا ---
    'M109'  => [
        'desc' => 'تتعلم في هذه المادة مهارات تطوير التطبيقات باستخدام إطار عمل .NET ولغة C#. ستركز على البرمجة كائنية التوجه (OOP)، وكيفية استخدام بيئة Visual Studio لبناء برامج وحل مشكلات برمجية حقيقية. مادة عملية وممتازة لمن يريد دخول مجال تطوير تطبيقات سطح المكتب والشركات.', 
        'desc_en' => 'In this course, you learn application development skills using the .NET framework and C#. It will focus on Object-Oriented Programming (OOP) and how to use the Visual Studio environment to build programs and solve real programming problems. A practical and excellent course for those who want to enter the field of desktop and enterprise application development.',
        'hours' => 3
    ],
    'TM291' => [
        'desc' => 'تركز هذه المادة على "نظم المعلومات الإدارية" وكيف تخدم التكنولوجيا قطاع الأعمال. ستتعلم كيف تساعد الأنظمة المدراء في اتخاذ القرارات، وإدارة المشاريع التقنية، وكيفية استخدام التكنولوجيا لتحقيق ميزة تنافسية للمؤسسات. مادة تجمع بين الإدارة والتقنية.', 
        'desc_en' => 'This course focuses on "Management Information Systems" and how technology serves the business sector. You will learn how systems help managers make decisions, manage technical projects, and how to use technology to achieve a competitive advantage for organizations. A course that combines management and technology.',
        'hours' => 3
    ],
];
// --- بداية جزئية السؤال عن مادة محددة ---
$found_code = null;
foreach ($course_details as $code => $info) {
    // نتحقق إذا كان رمز المادة موجوداً في رسالة المستخدم
    if (stripos($userMsg, $code) !== false) {
        $found_code = $code;
        $found_info = $info;
        break;
    }
}

if ($found_code) {
    ?>
    <div class="adv-container">
        <div class="adv-card" style="border-top: 5px solid #4f46e5; max-width: 500px;">
            <div class="header-md">
                <h4><?php echo getLangText('📖 معلومات المادة', '📖 Course Information'); ?></h4>
            </div>
            <div class="list-area">
                <div class="course-item" style="border:none; background:none;">
                    <div class="top-info">
                        <span class="code" style="font-size: 1.2rem;"><?php echo $found_code; ?></span>
                        <span class="hours-tag"><?php echo $found_info['hours'] . " " . getLangText('ساعة', 'Hrs'); ?></span>
                    </div>
                    <div class="course-desc" style="font-size: 1rem; color: #334155; border:none;">
                        <?php echo $isAr ? $found_info['desc'] : $found_info['desc_en']; ?>
                    </div>
                </div>
            </div>
            <div class="footer-md" style="background: #f8fafc; color: #64748b; font-size: 0.8rem;">
                <?php echo getLangText('هل لديك سؤال آخر عن مادة أخرى؟', '?Any other questions about courses'); ?>
            </div>
        </div>
    </div>
    <?php
     
     
    exit();// إنهاء التنفيذ هنا لكي لا يظهر جدول الاقتراحات بالأسفل
}
// --- نهاية جزئية السؤال عن مادة محددة ---

// 5. المنطق البرمجي (جلب البيانات من السجل الأكاديمي)
if (!isset($_SESSION['student_id'])) { die("Login Required"); }
$uid = $_SESSION['student_id'];


$completed_h = $_SESSION['completed_hours']; // تأكد من المسمى الصحيح للمفتاح في السايشن
if ($completed_h <= 18) {
    $target_sem = 1;
} elseif ($completed_h <= 36) {
    $target_sem = 2;
} elseif ($completed_h <= 54) {
    $target_sem = 3;
} elseif ($completed_h <= 72) {
    $target_sem = 4;
} elseif ($completed_h <= 90) {
    $target_sem = 5;
} elseif ($completed_h <= 108) {
    $target_sem = 6;
} elseif ($completed_h <= 126) {
    $target_sem = 7;
} else {
    $target_sem = 8; // مرحلة التخرج
}

// إذا قام المستخدم باختيار فصل يدوياً من واجهة أخرى، نعطي الأولوية لـ POST
if (isset($_POST['semester'])) {
    $target_sem = $_POST['semester'];
}
// جلب المعدل الحالي
$u_info = mysqli_query($conn, "SELECT gpa FROM students WHERE student_id = '$uid'");
$u_data = mysqli_fetch_assoc($u_info);
$current_gpa = (float)($u_data['gpa'] ?? 0.00);

// جلب الساعات المنجزة (تعديل الاستعلام واستخراج النتيجة)
$h_q = mysqli_query($conn, "SELECT SUM(c.credits) as total FROM student_records r 
       JOIN courses c ON r.course_code = c.course_code 
       WHERE r.student_id = '$uid' AND r.grade NOT IN ('F', '0', '')");

$h_data = mysqli_fetch_assoc($h_q);
$completed_h = (int)($h_data['total'] ?? 0); // الآن المتغير معرف وجاهز

// حساب الساعات المتبقية
$remaining_hours = 131 - $completed_h;

// الحالة 1: الطالب خريج (المتبقي له 21 ساعة أو أقل)
if ($remaining_hours <= 21) {
    $max_h = 21; 
    $status_label = getLangText("خريج - مسموح حتى 21 ساعة", "Graduating - Up to 21 Hrs allowed");
} 
// الحالة 2: الطالب متفوق (معدله 3.5 فأكثر) وليس خريجاً
elseif ($current_gpa >= 3.67) {
    $max_h = 20; 
    $status_label = getLangText("متفوق (أقصى حد 20)", "Honor Student (Max 20)");
}
// الحالة 3: الطالب معدله جيد (3.0 فأكثر)
elseif ($current_gpa >= 3.0) {
    $max_h = 19; 
    $status_label = getLangText("معدل جيدجدا (أقصى حد 19)", "Good GPA (Max 19)");
}
// الحالة 4: الطالب العادي أو المنذر
else {
    $max_h = ($current_gpa < 2.0) ? 13 : 16; 
    $status_label = ($current_gpa < 2.0) ? getLangText("إنذار أكاديمي (حد أدنى)", "Academic Probation") : getLangText("طالب منتظم", "Regular Student");
}

// --- 6. خوارزمية اختيار المواد الذكية المطورة ---
$suggested = [];
$fast_track_item = null;
$total_h = 0;

// أ. تعريف المتطلبات السابقة
$prerequisites = [
    // مواد السنة الأولى والثانية
    'EL112'   => ['EL111'],
    'MST129'  => ['EL111'],
    'MT131'   => ['EL111'],
    'MT132'   => ['EL111'],
    'M110'    => ['EL111'],
    'TM291'   => ['EL111', 'M110'], // تتطلب إنجليزي وبرمجة 1
     'M109'   => ['EL111'],  
     'TM105'   => ['EL111'],
     'TM103'   => ['EL111'],  
     'TM112'   => ['EL111'],
    // مواد التخصص المتوسطة
    'M251'    => ['M110', 'TM105'], // تتطلب بايثون ومقدمة البرمجة
    'M269'    => ['M110', 'MT131'], // هياكل البيانات تتطلب بايثون ورياضيات متقطعة
    'TM255'   => ['TM112'],
    'TM351'   => ['TM112', 'M269'], // إدارة البيانات تتطلب حوسبة وهياكل بيانات
    'TM356'   => ['TM112'],
    
    // مواد السنة الأخيرة
    'TM354'   => ['M251'],          // هندسة البرمجيات تتطلب جاوا
    'T215B'   => ['TM255'],         // شبكات 2 تتطلب شبكات 1
    'TM260'   => ['TM255'],
    'TM355'   => ['T215B'],
    
    // مشروع التخرج والتدريب
    'TM471-I' => ['TM351', 'TM354'], // مشروع 1 يتطلب قواعد بيانات وهندسة برمجيات
    'TM471-II'=> ['TM471-I'],        // مشروع 2 يتطلب النجاح في مشروع 1
    'INT300'  => ['TM351', 'TM354']  // التدريب الميداني يتطلب إنهاء مواد المستوى الثالث
];

// --- 1. حساب مستوى الطالب الحالي (تلقائياً) ---
// هنا ننادي المواد التي رسب فيها الطالب مهما كان رقم فصلها
$fail_query = "SELECT c.* FROM courses c 
               JOIN student_records r ON c.course_code = r.course_code 
               WHERE r.student_id = '$uid' 
               AND (r.grade = 'F' OR r.grade = '0' OR r.grade IS NULL)";

$fail_result = mysqli_query($conn, $fail_query);

while ($row = mysqli_fetch_assoc($fail_result)) {
    if (($total_h + $row['credits']) <= $max_h) {
        $total_h += $row['credits'];
        $row['tag'] = getLangText("إعادة مادة (رسوب)", "Repeat (Failed)");
        $row['tag_color'] = "#fee2e2"; // لون تنبيهي
        $suggested[] = $row;
    }
}

// 2. المرحلة الثانية: استدعاء مواد الفصل الدراسي الحالي (تكملة الجدول)
$exclude_codes = !empty($suggested) ? "'" . implode("','", array_column($suggested, 'course_code')) . "'" : "''";

// التعديل: إزالة حصر الفصل وجعل الكود يرى الخطة كاملة ويرتبها من الأقدم للأحدث
$plan_query = "SELECT * FROM courses 
               WHERE course_code NOT IN (SELECT course_code FROM student_records WHERE student_id = '$uid' AND grade > 0)
               AND course_code NOT IN ($exclude_codes)
               ORDER BY semester_num ASC, course_type DESC";

$plan_result = mysqli_query($conn, $plan_query);

while ($row = mysqli_fetch_assoc($plan_result)) {
    $code = $row['course_code'];
    $can_take = true;

    // فحص المتطلب السابق (Prerequisite)
    if (isset($prerequisites[$code])) {
        foreach ($prerequisites[$code] as $pre_item) {
            $pre_check = mysqli_query($conn, "SELECT id FROM student_records WHERE student_id = '$uid' AND course_code = '$pre_item' AND grade > 0");
            if (mysqli_num_rows($pre_check) == 0) {
                $can_take = false; 
                break;
            }
        }
    }

    if ($can_take && ($total_h + $row['credits']) <= $max_h) {
        $total_h += $row['credits'];
        $row['tag'] = getLangText("مادة بالخطة", "Plan Course");
        $row['tag_color'] = "#f3f4f6";
        $suggested[] = $row;
    }
}
?>

<style>
    .adv-container {
        display: flex;
        justify-content: center;
        padding: 15px;
    }

    .adv-card {
        direction: <?php echo $isAr ? 'rtl' : 'ltr'; ?>;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        /* الحجم الوسط 500px */
        max-width: 500px; 
        width: 100%;
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .header-md { 
        padding: 20px; 
        background: #f8fafc; 
        border-bottom: 1px solid #f1f5f9; 
        text-align: center; 
    }
    .header-md h4 { margin: 0; font-size: 1.1rem; color: #1e293b; }
    
    .stats-row { 
        display: flex; 
        background: #fff; 
        padding: 15px; 
        border-bottom: 1px solid #f1f5f9; 
    }
    .stat-box { 
        flex: 1; 
        text-align: center; 
        border-left: <?php echo $isAr ? '1px solid #f1f5f9' : 'none'; ?>; 
        border-right: <?php echo !$isAr ? '1px solid #f1f5f9' : 'none'; ?>; 
    }
    .stat-box:last-child { border: none; }
    .lbl { display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
    .val { font-weight: 700; font-size: 1rem; color: #4f46e5; }

    .list-area { padding: 15px; }
    .course-item { 
        padding: 15px; 
        border-radius: 12px; 
        border: 1px solid #f1f5f9; 
        margin-bottom: 12px; 
        background: #fcfcfc;
    }

    .top-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .code { font-weight: 800; font-size: 0.95rem; color: #0f172a; }
    .hours-tag { 
        font-size: 0.75rem; 
        color: #4f46e5; 
        background: #eef2ff; 
        padding: 3px 10px; 
        border-radius: 8px; 
        font-weight: 600; 
    }
    
    .course-name { display: block; font-size: 0.95rem; color: #334155; margin-bottom: 8px; font-weight: 600; }
    .course-desc { 
        font-size: 0.85rem; 
        color: #64748b; 
        line-height: 1.6;
        border-top: 1px solid #f1f5f9; 
        padding-top: 10px; 
        margin-top: 5px;
    }

    .footer-md { 
        background: #1e293b; 
        color: #fff; 
        padding: 15px; 
        text-align: center; 
        font-size: 0.9rem; 
    }
    .footer-md strong { color: #818cf8; }
</style>

<div class="adv-container">
    <div class="adv-card">
        <div class="header-md">
            <h4><?php echo getLangText('🤖 المستشار الأكاديمي الذكي', '🤖 Smart Academic Advisor'); ?></h4>
        </div>

<div style="margin: 20px 0; text-align: center; width: 100%; display: block;">
    <span style="
        background: #ffffff; 
        color: #0369a1; 
        
        padding: 8px 18px; 
        border-radius: 50px; 
        font-size: 0.85rem; 
        font-weight: 700; 
        border: 1px solid #bae6fd;
        
        
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
        
        
        position: relative;
        transform: translateY(-5px);
    ">
        <span style="font-size: 1rem;"></span> 
        <?php echo $status_label; ?>
    </span>
</div>
<?php 
// 1. الكلمات المفتاحية (لا تغيير هنا)
$gpa_keywords = ['معدل', 'المعدل', 'رفع', 'تعديل', 'إنذار', 'الانذار', 'تحسين', 'gpa', 'raise', 'improve', 'probation', 'حذف انذار'];
$is_asking_about_gpa = false;

if (isset($userMsg)) {
    foreach ($gpa_keywords as $kw) {
        if (stripos($userMsg, $kw) !== false) {
            $is_asking_about_gpa = true;
            break;
        }
    }
}

// 2. بداية تنفيذ عرض الصندوق
if ($is_asking_about_gpa): 
?>

<div style="clear: both; height: 5px;"></div>

<div style="padding: 20px; background: #fefce8; border: 1px solid #fef3c7; border-left: 5px solid #facc15; margin: 15px 0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); direction: <?php echo $isAr ? 'rtl' : 'ltr'; ?>; text-align: <?php echo $isAr ? 'right' : 'left'; ?>;">
    <div style="display: flex; gap: 12px; align-items: flex-start;">
        <span style="font-size: 1.5rem;">🎓</span>
        <div style="font-size: 0.9rem; color: #854d0e; line-height: 1.6; width: 100%;">
            <strong style="font-size: 1rem; color: #713f12; display: block; margin-bottom: 5px;">
                <?php echo getLangText('تحليل المستشار الأكاديمي الذكي:', 'Smart Academic Advisor Analysis:'); ?>
            </strong>
            
            <div style="margin-top: 8px;">
            <?php 
                // الجزء الأول: التحليل الاستراتيجي
                if ($current_gpa < 2.0) {
                    echo getLangText(
                        "بصفتي مستشارك الأكاديمي، أرى أن الأولوية القصوى الآن هي إخراجك من دائرة الإنذار. أنصحك بالتركيز الكامل على إعادة مواد الرسوب لرفع المعدل بسرعة.",
                        "As your Academic Advisor, my top priority is helping you clear your probation. I strongly suggest focusing on repeating failed courses to boost your GPA quickly."
                    );
                } 
                elseif ($current_gpa >= 3.5 && $target_sem <= 4) {
                    echo getLangText(
                        "أحييك على هذا الانطلاق القوي! الحفاظ على هذا المستوى الآن هو مفتاح نجاحك المستقبلي. ركز على إتقان لغات البرمجة والرياضيات المتقطعة.",
                        "I applaud your strong start! Maintaining this level now is key. Focus on mastering coding languages and discrete math."
                    );
                }
                elseif ($current_gpa >= 3.5 && $target_sem > 4) {
                    echo getLangText(
                        "أنت الآن في مرحلة النضج التقني. أنصحك بالانتقال من مجرد 'طالب' إلى 'مطور محترف'. حان الوقت للبدء جدياً في العصف الذهني لمشروع التخرج.",
                        "You are now in the stage of technical maturity. I suggest transitioning from a 'student' to a 'professional developer.' Start brainstorming your final project."
                    );
                }
                elseif ($remaining_hours <= 21) {
                    echo getLangText(
                        "لقد اقتربت من خط النهاية! ركز كل طاقتك في مشروع التخرج، فهو هويتك التي ستدخل بها سوق العمل. نحن فخورون بما وصلت إليه!",
                        "You are close to the finish line! Focus all your energy on your graduation project; it is your professional identity. We are proud of you!"
                    );
                } 
                else {
                    echo getLangText(
                        "مسارك الأكاديمي يسير بشكل منتظم وجيد. نصيحتي لك هي 'الاستمرارية'. لا تستهن بالمواد الاختيارية فهي وسيلة ممتازة لرفع المعدل.",
                        "Your academic path is moving steadily and well. My advice is 'consistency.' Don't underestimate elective courses to boost your GPA."
                    );
                }

                // الجزء الثاني: خطة العمل
                echo "<div style='margin-top: 15px; padding-top: 10px; border-top: 1px dashed #facc15; color: #713f12;'>";
                echo "<strong style='display:block; margin-bottom:5px;'>💡 " . getLangText("خطة عمل لتحسين المعدل:", "Action Plan to Improve GPA:") . "</strong>";
                
                if ($current_gpa < 3.0) {
                    echo getLangText(
                        "أسرع طريقة لرفع المعدل هي إعادة المواد التي حصلت فيها على (F) أو (D). أيضاً، اهتم بمواد التخصص (8 ساعات) لأن تأثيرها مضاعف.",
                        "The fastest way to raise your GPA is repeating (F) or (D) courses. Also, focus on 8-credit major courses as they have double the impact."
                    );
                } else {
                    echo getLangText(
                        "للوصول إلى مرتبة الشرف، ركز على حصد الدرجات الكاملة في الواجبات (TMAs) لضمان دخول الاختبار النهائي بأمان.",
                        "To reach honor status, focus on full marks in TMAs to secure high continuous assessment marks before the final exam."
                    );
                }
                echo "</div>";
            ?>
            </div>
        </div>
    </div>
</div>

<?php 
// التعديل الأهم: إنهاء التنفيذ لضمان عدم ظهور أي رد آخر مع الصندوق
exit(); 

endif; 
?>

        <div class="stats-row">
            <div class="stat-box">
                <span class="lbl"><?php echo getLangText('المعدل', 'GPA'); ?></span>
                <span class="val"><?php echo number_format($current_gpa, 2); ?></span>
            </div>
            <div class="stat-box">
                <span class="lbl"><?php echo getLangText('المنجز', 'Done'); ?></span>
                <span class="val"><?php echo $completed_h; ?> hour</span>
            </div>
            <div class="stat-box">
                <span class="lbl"><?php echo getLangText('الفصل', 'Semester'); ?></span>
                <span class="val"><?php echo $target_sem; ?></span>
            </div>
        </div>

        <div class="list-area">
            <?php foreach ($suggested as $item): 
                $det = $course_details[$item['course_code']] ?? null; ?>
                <div class="course-item">
                    <div class="top-info">
                        <span class="code"><?php echo $item['course_code']; ?></span>
                        <span class="hours-tag"><?php echo $item['credits'] . " " . getLangText('ساعة', 'Hrs'); ?></span>
                    </div>
                    
                   <span class="course-name">
                        <?php 
                        if (!$isAr) {
                            // إذا السؤال إنجليزي: اظهر الاسم من عمود الإنجليزي في DB
                            echo !empty($item['course_name_en']) ? $item['course_name_en'] : $item['course_name'];
                        } else {
                            // إذا السؤال عربي: اظهر الاسم من المصفوفة اليدوية أو عمود العربي في DB
                            if (!empty($det['name_ar'])) {
                                echo $det['name_ar'];
                            } else {
                                echo $item['course_name'];
                            }
                        }
                        ?>
                    </span>

                    <div class="course-desc">
                        💡 <?php 
                        if ($det) {
                            echo $isAr ? $det['desc'] : $det['desc_en'];
                        } else {
                            echo getLangText('مادة تخصصية مطلوبة في خطتك الدراسية.', 'A major course required in your degree plan.');
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
<?php

$_SESSION['suggested_courses'] = [];
foreach ($suggested as $item) {
    $_SESSION['suggested_courses'][] = [
        'code'  => $item['course_code'],
        'hours' => $item['credits']
    ];
}
?>
        <div class="footer-md">
            <?php echo getLangText('إجمالي الساعات المقترحة: ', 'Total Suggested Hours: '); ?> 
            <strong><?php echo $total_h; ?></strong>

        </div>
    </div>
</div>