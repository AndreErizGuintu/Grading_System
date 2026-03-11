<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');

$user = current_user();
$message = '';
$activeTab = $_GET['tab'] ?? 'grades';

// --- LOGIC: GRADE OVERRIDE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'override_grade') {
    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    $errors = [];
    $prelimError = null;
    $midtermError = null;
    $finalsError = null;

    $prelim = normalize_grade_input($_POST['prelim'] ?? null, $prelimError);
    $midterm = normalize_grade_input($_POST['midterm'] ?? null, $midtermError);
    $finals = normalize_grade_input($_POST['finals'] ?? null, $finalsError);

    if ($prelimError) { $errors[] = 'Prelim: ' . $prelimError; }
    if ($midtermError) { $errors[] = 'Midterm: ' . $midtermError; }
    if ($finalsError) { $errors[] = 'Finals: ' . $finalsError; }

    if (!empty($errors)) {
        $message = 'Invalid grade input. ' . implode(' ', $errors);
    } else {
        [$semestral, $status] = compute_semestral($prelim, $midterm, $finals);

        $mysqli->begin_transaction();
        $gradeStmt = $mysqli->prepare('SELECT grade_id, prelim, midterm, finals, semestral, status FROM tb_grades WHERE enrollment_id = ?');
        $gradeStmt->bind_param('i', $enrollmentId);
        $gradeStmt->execute();
        $gradeRow = $gradeStmt->get_result()->fetch_assoc();
        $gradeStmt->close();

        if (!$gradeRow) {
            $insert = $mysqli->prepare('INSERT INTO tb_grades (enrollment_id) VALUES (?)');
            $insert->bind_param('i', $enrollmentId);
            $insert->execute();
            $gradeId = $insert->insert_id;
            $gradeRow = ['prelim'=>null,'midterm'=>null,'finals'=>null,'semestral'=>null,'status'=>null];
        } else {
            $gradeId = (int)$gradeRow['grade_id'];
        }

        $update = $mysqli->prepare('UPDATE tb_grades SET prelim = ?, midterm = ?, finals = ?, semestral = ?, status = ? WHERE grade_id = ?');
        if (!$update) throw new Exception('Prepare error: ' . $mysqli->error);

        $update->bind_param('sssssi', $prelim, $midterm, $finals, $semestral, $status, $gradeId);
        if (!$update->execute()) throw new Exception('Update failed: ' . $update->error);

        $audit = $mysqli->prepare('INSERT INTO tb_grade_audit_logs (grade_id, changed_by, old_prelim, old_midterm, old_finals, old_semestral, old_status, new_prelim, new_midterm, new_finals, new_semestral, new_status, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$audit) throw new Exception('Prepare error: ' . $mysqli->error);

        $audit->bind_param('iisssssssssss', $gradeId, $user['user_id'], $gradeRow['prelim'], $gradeRow['midterm'], $gradeRow['finals'], $gradeRow['semestral'], $gradeRow['status'], $prelim, $midterm, $finals, $semestral, $status, $reason);
        if (!$audit->execute()) throw new Exception('Audit log failed: ' . $audit->error);

        $mysqli->commit();
        $message = 'Grade override successful and logged.';
    }
}

// --- LOGIC: OFFERING MANAGEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_offering') {
        $oCourseId  = (int)($_POST['course_id'] ?? 0);
        $oTeacherId = (int)($_POST['teacher_id'] ?? 0);
        $oSyId      = (int)($_POST['sy_id'] ?? 0);
        $oSemId     = (int)($_POST['sem_id'] ?? 0);
        if ($oCourseId && $oTeacherId && $oSyId && $oSemId) {
            $chk = $mysqli->prepare('SELECT offering_id FROM tb_course_offerings WHERE course_id=? AND teacher_id=? AND sy_id=? AND sem_id=?');
            $chk->bind_param('iiii', $oCourseId, $oTeacherId, $oSyId, $oSemId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $message = 'Error: That offering already exists for this teacher, course, school year and semester.';
            } else {
                $ins = $mysqli->prepare('INSERT INTO tb_course_offerings (course_id, teacher_id, sy_id, sem_id) VALUES (?,?,?,?)');
                $ins->bind_param('iiii', $oCourseId, $oTeacherId, $oSyId, $oSemId);
                $message = $ins->execute() ? 'Offering added successfully.' : 'Error adding offering: ' . $ins->error;
                $ins->close();
                $activeTab = 'subjects';
            }
            $chk->close();
        } else {
            $message = 'Error: All fields are required to add an offering.';
        }
        $activeTab = 'subjects';
    }

    if ($_POST['action'] === 'delete_offering') {
        $delId = (int)($_POST['offering_id'] ?? 0);
        if ($delId) {
            $enrolled = $mysqli->prepare('SELECT COUNT(*) FROM tb_enrollments WHERE offering_id = ?');
            $enrolled->bind_param('i', $delId);
            $enrolled->execute();
            $enrollCount = (int)$enrolled->get_result()->fetch_row()[0];
            $enrolled->close();
            if ($enrollCount > 0) {
                $message = 'Error: Cannot delete offering — it has ' . $enrollCount . ' enrolled student(s).';
            } else {
                $del = $mysqli->prepare('DELETE FROM tb_course_offerings WHERE offering_id = ?');
                $del->bind_param('i', $delId);
                $message = $del->execute() ? 'Offering deleted.' : 'Error deleting offering: ' . $del->error;
                $del->close();
            }
        }
        $activeTab = 'subjects';
    }

    // --- LOGIC: MASTER COURSE MANAGEMENT ---
    if ($_POST['action'] === 'add_course') {
        $cCode  = trim($_POST['course_code'] ?? '');
        $cName  = trim($_POST['course_name'] ?? '');
        $cUnits = (int)($_POST['units'] ?? 0);
        if ($cCode && $cName && $cUnits > 0) {
            $chk2 = $mysqli->prepare('SELECT course_id FROM tb_courses WHERE course_code = ?');
            $chk2->bind_param('s', $cCode);
            $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) {
                $message = 'Error: A course with that code already exists.';
            } else {
                $ins2 = $mysqli->prepare('INSERT INTO tb_courses (course_code, course_name, units) VALUES (?,?,?)');
                $ins2->bind_param('ssi', $cCode, $cName, $cUnits);
                $message = $ins2->execute() ? 'Course added successfully.' : 'Error adding course: ' . $ins2->error;
                $ins2->close();
            }
            $chk2->close();
        } else {
            $message = 'Error: Course code, name, and units are required.';
        }
        $activeTab = 'subjects';
    }

    if ($_POST['action'] === 'update_course') {
        $editId    = (int)($_POST['course_id'] ?? 0);
        $editCode  = trim($_POST['course_code'] ?? '');
        $editName  = trim($_POST['course_name'] ?? '');
        $editUnits = (int)($_POST['units'] ?? 0);
        if ($editId && $editCode && $editName && $editUnits > 0) {
            $upd = $mysqli->prepare('UPDATE tb_courses SET course_code=?, course_name=?, units=? WHERE course_id=?');
            $upd->bind_param('ssii', $editCode, $editName, $editUnits, $editId);
            $message = $upd->execute() ? 'Course updated.' : 'Error updating course: ' . $upd->error;
            $upd->close();
        } else {
            $message = 'Error: All course fields are required.';
        }
        $activeTab = 'subjects';
    }

    if ($_POST['action'] === 'delete_course') {
        $delCid = (int)($_POST['course_id'] ?? 0);
        if ($delCid) {
            $inUse = $mysqli->prepare('SELECT COUNT(*) FROM tb_course_offerings WHERE course_id = ?');
            $inUse->bind_param('i', $delCid);
            $inUse->execute();
            $useCount = (int)$inUse->get_result()->fetch_row()[0];
            $inUse->close();
            if ($useCount > 0) {
                $message = 'Error: Cannot delete course — it is used in ' . $useCount . ' offering(s).';
            } else {
                $delC = $mysqli->prepare('DELETE FROM tb_courses WHERE course_id = ?');
                $delC->bind_param('i', $delCid);
                $message = $delC->execute() ? 'Course deleted.' : 'Error deleting course: ' . $delC->error;
                $delC->close();
            }
        }
        $activeTab = 'subjects';
    }
}

// --- LOGIC: USER MANAGEMENT (UPDATE CREDENTIALS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $targetUserId = (int)$_POST['user_id'];
    $newUsername = trim($_POST['username']);
    $newPassword = $_POST['password'];

    try {
        if (!empty($newPassword)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('UPDATE tb_users SET username = ?, password_hash = ? WHERE user_id = ?');
            if (!$stmt) throw new Exception('Prepare error: ' . $mysqli->error);
            $stmt->bind_param('ssi', $newUsername, $hashed, $targetUserId);
        } else {
            $stmt = $mysqli->prepare('UPDATE tb_users SET username = ? WHERE user_id = ?');
            if (!$stmt) throw new Exception('Prepare error: ' . $mysqli->error);
            $stmt->bind_param('si', $newUsername, $targetUserId);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Update failed: ' . $stmt->error);
        }
        $message = 'User credentials updated successfully.';
        $stmt->close();
    } catch (Exception $e) {
        error_log('User update error: ' . $e->getMessage());
        $message = 'Error updating user: ' . $e->getMessage();
    }
}

// --- DATA FETCHING ---
$syId = $_GET['sy_id'] ?? '';
$semId = $_GET['sem_id'] ?? '';
$rows = [];
$studentUsers = [];
$teacherUsers = [];
$selectedUser = null;
$selectedStudent = null;
$selectedTeacher = null;
$studentEnrollments = [];
$teacherOfferings = [];

// For Subjects Tab
$allCourses      = [];
$allSyList       = [];
$allSemList      = [];
$allTeachersList = [];
$allOfferings    = [];
$editCourseId    = (int)($_GET['edit_course'] ?? 0);
$editCourse      = null;

$cResult = $mysqli->query("SELECT * FROM tb_courses ORDER BY course_code ASC");
if ($cResult) $allCourses = $cResult->fetch_all(MYSQLI_ASSOC);

$syListResult = $mysqli->query("SELECT * FROM tb_school_years ORDER BY school_year DESC");
if ($syListResult) $allSyList = $syListResult->fetch_all(MYSQLI_ASSOC);

$semListResult = $mysqli->query("SELECT * FROM tb_semesters ORDER BY sem_id ASC");
if ($semListResult) $allSemList = $semListResult->fetch_all(MYSQLI_ASSOC);

$tListResult = $mysqli->query("SELECT teacher_id, full_name FROM tb_teachers ORDER BY full_name ASC");
if ($tListResult) $allTeachersList = $tListResult->fetch_all(MYSQLI_ASSOC);

$allOfferResult = $mysqli->query(
    "SELECT o.offering_id, c.course_code, c.course_name, t.full_name AS teacher_name, sy.school_year, s.semester,
            COUNT(e.enrollment_id) AS roster_count
     FROM tb_course_offerings o
     INNER JOIN tb_courses c ON c.course_id = o.course_id
     INNER JOIN tb_teachers t ON t.teacher_id = o.teacher_id
     INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
     INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
     LEFT JOIN tb_enrollments e ON e.offering_id = o.offering_id
     GROUP BY o.offering_id, c.course_code, c.course_name, t.full_name, sy.school_year, s.semester
     ORDER BY sy.school_year DESC, s.semester ASC, c.course_code ASC"
);
if ($allOfferResult) $allOfferings = $allOfferResult->fetch_all(MYSQLI_ASSOC);

if ($editCourseId > 0) {
    $ecStmt = $mysqli->prepare('SELECT * FROM tb_courses WHERE course_id = ?');
    if ($ecStmt) {
        $ecStmt->bind_param('i', $editCourseId);
        $ecStmt->execute();
        $editCourse = $ecStmt->get_result()->fetch_assoc();
        $ecStmt->close();
    }
}

// Fetch Enrollments for Grades Tab
$enrollResult = $mysqli->query("SELECT e.enrollment_id, st.student_no, st.full_name as student_name, c.course_code, t.full_name as teacher_name, g.prelim, g.midterm, g.finals, g.semestral, g.status 
    FROM tb_enrollments e 
    JOIN tb_students st ON e.student_id = st.student_id 
    JOIN tb_course_offerings o ON e.offering_id = o.offering_id 
    JOIN tb_courses c ON o.course_id = c.course_id 
    JOIN tb_teachers t ON o.teacher_id = t.teacher_id 
    LEFT JOIN tb_grades g ON e.enrollment_id = g.enrollment_id
    ORDER BY st.full_name ASC");

if ($enrollResult === false) {
    error_log('Database error in admin enrollments query: ' . $mysqli->error);
    $message = 'Error loading enrollment data. Please refresh.';
} else {
    $rows = $enrollResult->fetch_all(MYSQLI_ASSOC);
}

$courseOptions = [];
$studentsByCourse = [];
$allStudents = [];
foreach ($rows as $row) {
    $courseCode = $row['course_code'] ?? '';
    $studentName = $row['student_name'] ?? '';

    if ($courseCode !== '') {
        if (!isset($courseOptions[$courseCode])) {
            $courseOptions[$courseCode] = $courseCode;
        }
        if (!isset($studentsByCourse[$courseCode])) {
            $studentsByCourse[$courseCode] = [];
        }
        if ($studentName !== '' && !in_array($studentName, $studentsByCourse[$courseCode], true)) {
            $studentsByCourse[$courseCode][] = $studentName;
        }
    }

    if ($studentName !== '' && !in_array($studentName, $allStudents, true)) {
        $allStudents[] = $studentName;
    }
}

ksort($courseOptions);
sort($allStudents, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($studentsByCourse as $course => $students) {
    sort($students, SORT_NATURAL | SORT_FLAG_CASE);
    $studentsByCourse[$course] = $students;
}

// Fetch Users for Accounts Tab
$studentResult = $mysqli->query("SELECT u.user_id, u.username, u.role, u.created_at, s.full_name, s.student_no, s.program
    FROM tb_users u
    INNER JOIN tb_students s ON s.user_id = u.user_id
    WHERE u.role = 'student'
    ORDER BY s.full_name ASC");

if ($studentResult === false) {
    error_log('Database error in admin student users query: ' . $mysqli->error);
    $message = 'Error loading student accounts. Please refresh.';
} else {
    $studentUsers = $studentResult->fetch_all(MYSQLI_ASSOC);
}

$teacherResult = $mysqli->query("SELECT u.user_id, u.username, u.role, u.created_at, t.full_name
    FROM tb_users u
    INNER JOIN tb_teachers t ON t.user_id = u.user_id
    WHERE u.role = 'teacher'
    ORDER BY t.full_name ASC");

if ($teacherResult === false) {
    error_log('Database error in admin teacher users query: ' . $mysqli->error);
    $message = 'Error loading teacher accounts. Please refresh.';
} else {
    $teacherUsers = $teacherResult->fetch_all(MYSQLI_ASSOC);
}

$selectedUserId = (int)($_GET['user_id'] ?? 0);
if ($selectedUserId > 0) {
    $userStmt = $mysqli->prepare("SELECT user_id, username, role, created_at FROM tb_users WHERE user_id = ? AND role != 'admin'");
    if ($userStmt) {
        $userStmt->bind_param('i', $selectedUserId);
        if ($userStmt->execute()) {
            $selectedUser = $userStmt->get_result()->fetch_assoc();
        }
        $userStmt->close();
    }

    if ($selectedUser && $selectedUser['role'] === 'student') {
        $studentStmt = $mysqli->prepare("SELECT student_id, student_no, full_name, program FROM tb_students WHERE user_id = ?");
        if ($studentStmt) {
            $studentStmt->bind_param('i', $selectedUserId);
            if ($studentStmt->execute()) {
                $selectedStudent = $studentStmt->get_result()->fetch_assoc();
            }
            $studentStmt->close();
        }

        if ($selectedStudent) {
            $enrollStmt = $mysqli->prepare("SELECT c.course_code, c.course_name, sy.school_year, s.semester, g.prelim, g.midterm, g.finals, g.semestral, g.status
                FROM tb_enrollments e
                INNER JOIN tb_course_offerings o ON o.offering_id = e.offering_id
                INNER JOIN tb_courses c ON c.course_id = o.course_id
                INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
                INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
                LEFT JOIN tb_grades g ON g.enrollment_id = e.enrollment_id
                WHERE e.student_id = ?
                ORDER BY sy.school_year DESC, s.semester ASC, c.course_code ASC");
            if ($enrollStmt) {
                $enrollStmt->bind_param('i', $selectedStudent['student_id']);
                if ($enrollStmt->execute()) {
                    $studentEnrollments = $enrollStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                }
                $enrollStmt->close();
            }
        }
    }

    if ($selectedUser && $selectedUser['role'] === 'teacher') {
        $teacherStmt = $mysqli->prepare("SELECT teacher_id, full_name FROM tb_teachers WHERE user_id = ?");
        if ($teacherStmt) {
            $teacherStmt->bind_param('i', $selectedUserId);
            if ($teacherStmt->execute()) {
                $selectedTeacher = $teacherStmt->get_result()->fetch_assoc();
            }
            $teacherStmt->close();
        }

        if ($selectedTeacher) {
            $offerStmt = $mysqli->prepare("SELECT o.offering_id, c.course_code, c.course_name, sy.school_year, s.semester, COUNT(e.enrollment_id) AS roster_count
                FROM tb_course_offerings o
                INNER JOIN tb_courses c ON c.course_id = o.course_id
                INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
                INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
                LEFT JOIN tb_enrollments e ON e.offering_id = o.offering_id
                WHERE o.teacher_id = ?
                GROUP BY o.offering_id, c.course_code, c.course_name, sy.school_year, s.semester
                ORDER BY sy.school_year DESC, s.semester ASC, c.course_code ASC");
            if ($offerStmt) {
                $offerStmt->bind_param('i', $selectedTeacher['teacher_id']);
                if ($offerStmt->execute()) {
                    $teacherOfferings = $offerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                }
                $offerStmt->close();
            }
        }
    }
}

// Fetch Audit Logs
$auditLogs = [];
$auditResult = $mysqli->query(
    "SELECT 
        al.audit_id,
        al.changed_by,
        u.username as admin_name,
        st.full_name as student_name,
        c.course_code,
        al.old_prelim, al.new_prelim,
        al.old_midterm, al.new_midterm,
        al.old_finals, al.new_finals,
        al.old_semestral, al.new_semestral,
        al.old_status, al.new_status,
        al.reason,
        al.changed_at
    FROM tb_grade_audit_logs al
    JOIN tb_users u ON u.user_id = al.changed_by
    JOIN tb_grades g ON g.grade_id = al.grade_id
    JOIN tb_enrollments e ON e.enrollment_id = g.enrollment_id
    JOIN tb_students st ON st.student_id = e.student_id
    JOIN tb_course_offerings o ON o.offering_id = e.offering_id
    JOIN tb_courses c ON c.course_id = o.course_id
    ORDER BY al.changed_at DESC
    LIMIT 500"
);

if ($auditResult === false) {
    error_log('Database error in audit logs query: ' . $mysqli->error);
} else {
    $auditLogs = $auditResult->fetch_all(MYSQLI_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HCC | Admin Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b0f1a; color: #f8fafc; height: 100vh; overflow: hidden; }
        .glass { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        .tab-active { background: #be0b0b; color: white; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }

        .admin-select {
            background-color: #111827;
            color: #e2e8f0;
            border-color: rgba(255, 255, 255, 0.1);
        }
        .admin-select option {
            background-color: #0b0f1a;
            color: #e2e8f0;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1c530e; }

        /* Fixed Table Height Logic */
        .table-container { height: calc(100vh - 280px); overflow-y: auto; }
        thead sticky { position: sticky; top: 0; z-index: 10; }

        /* Console Styles */
        .console { 
            font-family: 'JetBrains Mono', monospace; 
            background: #0a0e27; 
            border: 1px solid #1e3a5f;
            color: #00ff00;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .console-header {
            background: linear-gradient(to right, #1e3a5f, #0a0e27);
            border-bottom: 1px solid #1e3a5f;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .console-header .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #00ff00;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 49%, 100% { opacity: 1; } 50%, 99% { opacity: 0.3; } }
        .console-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        .log-entry {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(30, 58, 95, 0.3);
            font-size: 11px;
            line-height: 1.6;
            transition: background 0.2s ease;
        }
        .log-entry:hover { background: rgba(0, 255, 0, 0.05); }
        .log-time { color: #00ff00; opacity: 0.7; }
        .log-admin { color: #ff6b6b; font-weight: 600; }
        .log-student { color: #4ecdc4; }
        .log-course { color: #ffe66d; }
        .log-change { color: #a8e6cf; }
        .log-change-from { color: #ff8b8b; }
        .log-change-to { color: #90ee90; }
        .log-reason { color: #b19cd9; font-style: italic; }
        .empty-console { padding: 40px 20px; text-align: center; opacity: 0.5; }
    </style>
</head>
<body class="flex">

    <aside class="w-64 border-r border-white/5 bg-[#070b14] flex flex-col shrink-0">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-10">
                <img src="https://hccp-sms.holycrosscollegepampanga.edu.ph/public/assets/images/logo4.png" class="w-10 h-10">
                <h1 class="font-black text-xl tracking-tighter text-white">ADMIN<span class="text-red-500">CORE</span></h1>
            </div>
            <nav class="space-y-2">
                <a href="?tab=grades" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $activeTab == 'grades' ? 'tab-active' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                    <i class="fa-solid fa-shield-halved"></i> <span class="text-sm font-bold">Grade Overrides</span>
                </a>
                <a href="?tab=subjects" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $activeTab == 'subjects' ? 'tab-active' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                    <i class="fa-solid fa-book"></i> <span class="text-sm font-bold">Subjects</span>
                </a>
                <a href="?tab=accounts" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $activeTab == 'accounts' ? 'tab-active' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                    <i class="fa-solid fa-users-gear"></i> <span class="text-sm font-bold">User Accounts</span>
                </a>
                <a href="?tab=audit" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $activeTab == 'audit' ? 'tab-active' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                    <i class="fa-solid fa-server"></i> <span class="text-sm font-bold">Audit Console</span>
                </a>
            </nav>
        </div>
        <div class="mt-auto p-6">
            <a href="/Grading_System/logout.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-red-500/10 text-red-500 text-xs font-black hover:bg-red-500 hover:text-white transition-all">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-20 border-b border-white/5 flex items-center justify-between px-8 bg-[#0b0f1a]/80 backdrop-blur-md z-20">
            <div>
                <h2 class="text-xs font-black text-slate-500 uppercase tracking-widest"><?= $activeTab ?> Control</h2>
                <p class="text-lg font-bold text-white">System Management</p>
            </div>
            
            <div class="relative w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                  <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Quick search..." 
                       class="w-full bg-white/5 border border-white/10 rounded-full py-2 pl-10 pr-4 text-sm text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all">
            </div>
        </header>

        <main class="p-8 flex-1 overflow-hidden flex flex-col">
            <?php if ($message): ?>
                <?php $isError = str_starts_with($message, 'Invalid') || str_starts_with($message, 'Error') || str_contains($message, 'failed') || str_contains($message, 'Failed'); ?>
                <div class="mb-4 p-3 rounded-lg <?= $isError ? 'bg-red-500/10 border border-red-500/20 text-red-400' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' ?> text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid <?= $isError ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= h($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'grades'): ?>
                <!-- Grade Entry Guide -->
                <div class="mb-4 rounded-xl border border-amber-500/20 bg-amber-500/5 px-5 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                    <div class="flex items-center gap-2 text-amber-400 text-[10px] font-black uppercase tracking-widest shrink-0">
                        <i class="fa-solid fa-lightbulb"></i> Grade Codes
                    </div>
                    <div class="flex flex-wrap gap-3 text-[10px]">
                        <span class="font-black text-amber-400">INC</span><span class="text-slate-500">Incomplete</span>
                        <span class="mx-1 text-white/10">|</span>
                        <span class="font-black text-red-400">FA</span><span class="text-slate-500">Failed (Absence)</span>
                        <span class="mx-1 text-white/10">|</span>
                        <span class="font-black text-slate-400">UW</span><span class="text-slate-500">Unauthorized Withdrawal</span>
                        <span class="mx-1 text-white/10">|</span>
                        <span class="font-black text-slate-300">DRP</span><span class="text-slate-500">Authorized Withdrawal</span>
                        <span class="mx-1 text-white/10">|</span>
                        <span class="text-slate-500 italic">Numeric 0–100 auto-rounds to integer</span>
                    </div>
                </div>
                <div class="glass rounded-2xl overflow-hidden flex flex-col">
                    <div class="p-4 border-b border-white/5 bg-white/[0.02] flex flex-wrap items-center gap-3">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">Filters</div>
                                            <select id="courseFilter" class="admin-select border rounded-lg px-3 py-2 text-[10px] text-slate-200 font-bold uppercase tracking-wider">
                            <option value="">All Courses</option>
                            <?php foreach ($courseOptions as $code): ?>
                                <option value="<?= h($code) ?>"><?= h($code) ?></option>
                            <?php endforeach; ?>
                        </select>
                                            <select id="studentFilter" class="admin-select border rounded-lg px-3 py-2 text-[10px] text-slate-200 font-bold uppercase tracking-wider">
                            <option value="">All Students</option>
                            <?php foreach ($allStudents as $student): ?>
                                <option value="<?= h(strtolower($student)) ?>"><?= h($student) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="table-container">
                        <table class="w-full text-left border-collapse" id="dataTable">
                            <thead class="sticky top-0 bg-[#1e293b] shadow-md">
                                <tr class="text-[10px] uppercase font-black text-slate-400 tracking-widest">
                                    <th class="px-6 py-4">Student & Instructor</th>
                                    <th class="px-6 py-4 text-center">Grades</th>
                                    <th class="px-6 py-4">Reason for Override</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($rows as $row): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors searchable-row" data-student="<?= h(strtolower($row['student_name'])) ?>" data-course="<?= h($row['course_code']) ?>">
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-sm text-white"><?= h($row['student_name']) ?></div>
                                        <div class="text-[10px] text-slate-500"><?= h($row['course_code']) ?> • <span class="text-red-400"><?= h($row['teacher_name']) ?></span></div>
                                    </td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="override_grade">
                                        <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                        <td class="px-6 py-3 text-center">
                                            <div class="flex gap-2 justify-center">
                                                <input name="prelim" type="text" placeholder="P" class="admin-grade-input w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= h($row['prelim'] ?? '') ?>" onblur="adminFormatInput(this)">
                                                <input name="midterm" type="text" placeholder="M" class="admin-grade-input w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= h($row['midterm'] ?? '') ?>" onblur="adminFormatInput(this)">
                                                <input name="finals" type="text" placeholder="F" class="admin-grade-input w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= h($row['finals'] ?? '') ?>" onblur="adminFormatInput(this)">
                                            </div>
                                        </td>
                                        <td class="px-6 py-3">
                                            <input name="reason" required class="w-full bg-black/40 border border-white/10 rounded-md px-3 py-1.5 text-xs text-slate-300 focus:border-red-500 outline-none" placeholder="Type reason...">
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                                                Save
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($activeTab === 'subjects'): ?>
                <div class="flex-1 overflow-y-auto pr-2 space-y-8">

                    <!-- MASTER COURSES SECTION -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Master Courses</h3>
                                <p class="text-[10px] text-slate-500 mt-0.5"><?= count($allCourses) ?> courses in system</p>
                            </div>
                        </div>

                        <!-- Add / Edit Course Form -->
                        <div class="glass rounded-xl p-5 border border-white/10 mb-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">
                                <?= $editCourse ? 'Edit Course #' . $editCourse['course_id'] : 'Add New Course' ?>
                            </p>
                            <form method="POST" class="flex flex-wrap gap-3 items-end">
                                <input type="hidden" name="action" value="<?= $editCourse ? 'update_course' : 'add_course' ?>">
                                <?php if ($editCourse): ?>
                                    <input type="hidden" name="course_id" value="<?= $editCourse['course_id'] ?>">
                                <?php endif; ?>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Course Code</label>
                                    <input name="course_code" required maxlength="20"
                                           value="<?= h($editCourse['course_code'] ?? '') ?>"
                                           class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-36 outline-none focus:border-red-500"
                                           placeholder="e.g. CS101">
                                </div>
                                <div class="flex-1 min-w-48">
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Course Name</label>
                                    <input name="course_name" required maxlength="100"
                                           value="<?= h($editCourse['course_name'] ?? '') ?>"
                                           class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-full outline-none focus:border-red-500"
                                           placeholder="e.g. Data Structures">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Units</label>
                                    <input name="units" type="number" min="1" max="10" required
                                           value="<?= h((string)($editCourse['units'] ?? '3')) ?>"
                                           class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-20 outline-none focus:border-red-500">
                                </div>
                                <button class="bg-red-600 hover:bg-red-500 text-white px-5 py-2 rounded-lg text-[11px] font-black uppercase transition-all">
                                    <i class="fa-solid <?= $editCourse ? 'fa-floppy-disk' : 'fa-plus' ?>"></i>
                                    <?= $editCourse ? 'Save Changes' : 'Add Course' ?>
                                </button>
                                <?php if ($editCourse): ?>
                                    <a href="?tab=subjects" class="text-[11px] font-bold text-slate-400 hover:text-white px-4 py-2 rounded-lg border border-white/10 hover:bg-white/5 transition-all">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Courses Table -->
                        <div class="glass rounded-xl overflow-hidden border border-white/5">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-black/20">
                                        <th class="px-6 py-3">Code</th>
                                        <th class="px-6 py-3">Name</th>
                                        <th class="px-4 py-3 text-center">Units</th>
                                        <th class="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($allCourses as $c): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors <?= ($editCourseId === (int)$c['course_id']) ? 'bg-red-500/5 border-l-2 border-l-red-500' : '' ?>">
                                        <td class="px-6 py-3 font-black text-white text-xs"><?= h($c['course_code']) ?></td>
                                        <td class="px-6 py-3 text-xs text-slate-300"><?= h($c['course_name']) ?></td>
                                        <td class="px-4 py-3 text-center text-xs text-slate-400"><?= h((string)$c['units']) ?></td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="flex gap-2 justify-end">
                                                <a href="?tab=subjects&edit_course=<?= $c['course_id'] ?>" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 px-3 py-1 rounded border border-blue-500/20 hover:bg-blue-500/10 transition-all">
                                                    <i class="fa-solid fa-pen"></i> Edit
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Delete course \'<?= h(addslashes($c['course_code'])) ?>\'? This cannot be undone.')">
                                                    <input type="hidden" name="action" value="delete_course">
                                                    <input type="hidden" name="course_id" value="<?= $c['course_id'] ?>">
                                                    <button class="text-[10px] font-bold text-red-400 hover:text-white px-3 py-1 rounded border border-red-500/20 hover:bg-red-500 transition-all">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($allCourses)): ?>
                                        <tr><td colspan="4" class="px-6 py-8 text-center text-xs text-slate-500 italic">No courses found. Add one above.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- COURSE OFFERINGS SECTION -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Course Offerings</h3>
                                <p class="text-[10px] text-slate-500 mt-0.5"><?= count($allOfferings) ?> active offerings</p>
                            </div>
                        </div>

                        <!-- Add Offering Form -->
                        <div class="glass rounded-xl p-5 border border-white/10 mb-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-3">Assign New Offering</p>
                            <form method="POST" class="flex flex-wrap gap-3 items-end">
                                <input type="hidden" name="action" value="add_offering">
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Course</label>
                                    <select name="course_id" required class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-44 outline-none">
                                        <option value="">Select Course</option>
                                        <?php foreach ($allCourses as $c): ?>
                                            <option value="<?= $c['course_id'] ?>"><?= h($c['course_code']) ?> — <?= h($c['course_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Teacher</label>
                                    <select name="teacher_id" required class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-44 outline-none">
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($allTeachersList as $t): ?>
                                            <option value="<?= $t['teacher_id'] ?>"><?= h($t['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">School Year</label>
                                    <select name="sy_id" required class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-36 outline-none">
                                        <option value="">Select SY</option>
                                        <?php foreach ($allSyList as $sy): ?>
                                            <option value="<?= $sy['sy_id'] ?>"><?= h($sy['school_year']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-500 uppercase block mb-1">Semester</label>
                                    <select name="sem_id" required class="admin-select border rounded-lg px-3 py-2 text-[11px] text-slate-200 font-bold w-28 outline-none">
                                        <option value="">Select Sem</option>
                                        <?php foreach ($allSemList as $sem): ?>
                                            <option value="<?= $sem['sem_id'] ?>"><?= h($sem['semester']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="bg-red-600 hover:bg-red-500 text-white px-5 py-2 rounded-lg text-[11px] font-black uppercase transition-all">
                                    <i class="fa-solid fa-plus"></i> Add Offering
                                </button>
                            </form>
                        </div>

                        <!-- Offerings Table -->
                        <div class="glass rounded-xl overflow-hidden border border-white/5">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-black/20">
                                        <th class="px-6 py-3">Course</th>
                                        <th class="px-6 py-3">Teacher</th>
                                        <th class="px-4 py-3">School Year</th>
                                        <th class="px-4 py-3">Semester</th>
                                        <th class="px-4 py-3 text-center">Enrolled</th>
                                        <th class="px-6 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($allOfferings as $off): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-3">
                                            <div class="font-bold text-white text-xs"><?= h($off['course_code']) ?></div>
                                            <div class="text-[10px] text-slate-500"><?= h($off['course_name']) ?></div>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-slate-300"><?= h($off['teacher_name']) ?></td>
                                        <td class="px-4 py-3 text-xs text-slate-400"><?= h($off['school_year']) ?></td>
                                        <td class="px-4 py-3 text-xs text-slate-400"><?= h($off['semester']) ?></td>
                                        <td class="px-4 py-3 text-center text-xs text-white"><?= h((string)$off['roster_count']) ?></td>
                                        <td class="px-6 py-3 text-right">
                                            <form method="POST" onsubmit="return confirm('Delete this offering? Students enrolled must be removed first.')">
                                                <input type="hidden" name="action" value="delete_offering">
                                                <input type="hidden" name="offering_id" value="<?= $off['offering_id'] ?>">
                                                <button class="text-[10px] font-bold text-red-400 hover:text-white px-3 py-1 rounded border border-red-500/20 hover:bg-red-500 transition-all <?= $off['roster_count'] > 0 ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                                    <?= $off['roster_count'] > 0 ? 'disabled title="Cannot delete — has enrolled students"' : '' ?>>
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($allOfferings)): ?>
                                        <tr><td colspan="6" class="px-6 py-8 text-center text-xs text-slate-500 italic">No offerings found. Add one above.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            <?php elseif ($activeTab === 'accounts'): ?>
                <div class="flex-1 overflow-y-auto pr-2 space-y-8">
                    <div class="glass rounded-xl p-4 border border-white/10 flex flex-wrap items-center gap-3">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-500">Quick Jump</div>
                            <select class="admin-select border rounded-lg px-3 py-2 text-[10px] text-slate-200 font-bold uppercase tracking-wider" onchange="if (this.value) window.location='?tab=accounts&user_id=' + this.value;">
                            <option value="">Select Student</option>
                            <?php foreach ($studentUsers as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= h($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                            <select class="admin-select border rounded-lg px-3 py-2 text-[10px] text-slate-200 font-bold uppercase tracking-wider" onchange="if (this.value) window.location='?tab=accounts&user_id=' + this.value;">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teacherUsers as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= h($u['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selectedUser): ?>
                        <div class="glass rounded-2xl p-6 border border-white/10">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">User Details</p>
                                    <h3 class="text-xl font-black text-white mt-1"><?= h($selectedUser['username']) ?></h3>
                                    <p class="text-xs text-slate-400 mt-1">Role: <?= h($selectedUser['role']) ?> • Joined: <?= h($selectedUser['created_at']) ?></p>
                                </div>
                                <a href="?tab=accounts" class="text-xs font-bold text-slate-400 hover:text-white">
                                    <i class="fa-solid fa-xmark"></i> Close
                                </a>
                            </div>

                            <?php if ($selectedUser['role'] === 'student' && $selectedStudent): ?>
                                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                                    <div class="glass p-4 rounded-xl border border-white/10">
                                        <p class="text-[9px] font-black uppercase text-slate-500">Profile</p>
                                        <p class="text-sm font-bold text-white mt-2"><?= h($selectedStudent['full_name']) ?></p>
                                        <p class="text-xs text-slate-400 mt-1">Student No: <?= h($selectedStudent['student_no']) ?></p>
                                        <p class="text-xs text-slate-400">Program: <?= h($selectedStudent['program']) ?></p>
                                    </div>
                                    <div class="glass p-4 rounded-xl border border-white/10 lg:col-span-2">
                                        <p class="text-[9px] font-black uppercase text-slate-500 mb-3">Enrollments & Grades</p>
                                        <?php if (empty($studentEnrollments)): ?>
                                            <p class="text-xs text-slate-500">No enrollments found.</p>
                                        <?php else: ?>
                                            <div class="max-h-64 overflow-y-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead class="text-[10px] uppercase text-slate-500">
                                                        <tr>
                                                            <th class="py-2">Course</th>
                                                            <th class="py-2">SY</th>
                                                            <th class="py-2">Sem</th>
                                                            <th class="py-2 text-center">Semestral</th>
                                                            <th class="py-2 text-center">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-white/5">
                                                        <?php foreach ($studentEnrollments as $enr): ?>
                                                            <tr>
                                                                <td class="py-2">
                                                                    <div class="font-bold text-white"><?= h($enr['course_code']) ?></div>
                                                                    <div class="text-[10px] text-slate-500"><?= h($enr['course_name']) ?></div>
                                                                </td>
                                                                <td class="py-2 text-slate-400"><?= h($enr['school_year']) ?></td>
                                                                <td class="py-2 text-slate-400"><?= h($enr['semester']) ?></td>
                                                                <td class="py-2 text-center text-white">
                                                                    <?= $enr['semestral'] !== null ? (is_numeric($enr['semestral']) ? number_format($enr['semestral'], 0) : h(strtoupper($enr['semestral']))) : '-' ?>
                                                                </td>
                                                                <td class="py-2 text-center text-slate-300"><?= h($enr['status'] ?? 'PENDING') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php elseif ($selectedUser['role'] === 'teacher' && $selectedTeacher): ?>
                                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                                    <div class="glass p-4 rounded-xl border border-white/10">
                                        <p class="text-[9px] font-black uppercase text-slate-500">Profile</p>
                                        <p class="text-sm font-bold text-white mt-2"><?= h($selectedTeacher['full_name']) ?></p>
                                        <p class="text-xs text-slate-400 mt-1">Teacher ID: <?= h((string)$selectedTeacher['teacher_id']) ?></p>
                                    </div>
                                    <div class="glass p-4 rounded-xl border border-white/10 lg:col-span-2">
                                        <p class="text-[9px] font-black uppercase text-slate-500 mb-3">Course Offerings</p>
                                        <?php if (empty($teacherOfferings)): ?>
                                            <p class="text-xs text-slate-500">No offerings found.</p>
                                        <?php else: ?>
                                            <div class="max-h-64 overflow-y-auto">
                                                <table class="w-full text-left text-xs">
                                                    <thead class="text-[10px] uppercase text-slate-500">
                                                        <tr>
                                                            <th class="py-2">Course</th>
                                                            <th class="py-2">SY</th>
                                                            <th class="py-2">Sem</th>
                                                            <th class="py-2 text-center">Roster</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-white/5">
                                                        <?php foreach ($teacherOfferings as $off): ?>
                                                            <tr>
                                                                <td class="py-2">
                                                                    <div class="font-bold text-white"><?= h($off['course_code']) ?></div>
                                                                    <div class="text-[10px] text-slate-500"><?= h($off['course_name']) ?></div>
                                                                </td>
                                                                <td class="py-2 text-slate-400"><?= h($off['school_year']) ?></td>
                                                                <td class="py-2 text-slate-400"><?= h($off['semester']) ?></td>
                                                                <td class="py-2 text-center text-white"><?= h((string)$off['roster_count']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Students</h3>
                            <span class="text-[10px] text-slate-500"><?= count($studentUsers) ?> total</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="dataTable">
                            <?php foreach ($studentUsers as $u): ?>
                            <div class="glass p-5 rounded-xl group searchable-row border-l-4 border-l-emerald-500">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full bg-white/5 text-slate-400">student</span>
                                        <h3 class="text-sm font-bold text-white mt-1 name-field"><?= h($u['full_name']) ?></h3>
                                        <p class="text-[10px] text-slate-500"><?= h($u['student_no']) ?> • <?= h($u['program']) ?></p>
                                    </div>
                                    <a href="?tab=accounts&user_id=<?= $u['user_id'] ?>" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300">View</a>
                                </div>

                                <form method="POST" class="space-y-3">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 uppercase">Username</label>
                                            <input name="username" value="<?= h($u['username']) ?>" class="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:border-red-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 uppercase">New Password</label>
                                            <input name="password" type="password" placeholder="••••••" class="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:border-red-500 outline-none">
                                        </div>
                                    </div>
                                    <button class="w-full bg-white/5 hover:bg-white/10 text-white text-[10px] font-black uppercase py-2 rounded-lg transition-all border border-white/10">
                                        Update Account
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500">Teachers</h3>
                            <span class="text-[10px] text-slate-500"><?= count($teacherUsers) ?> total</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($teacherUsers as $u): ?>
                            <div class="glass p-5 rounded-xl group searchable-row border-l-4 border-l-blue-500">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full bg-white/5 text-slate-400">teacher</span>
                                        <h3 class="text-sm font-bold text-white mt-1 name-field"><?= h($u['full_name']) ?></h3>
                                        <p class="text-[10px] text-slate-500">@<?= h($u['username']) ?></p>
                                    </div>
                                    <a href="?tab=accounts&user_id=<?= $u['user_id'] ?>" class="text-[10px] font-bold text-blue-400 hover:text-blue-300">View</a>
                                </div>

                                <form method="POST" class="space-y-3">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 uppercase">Username</label>
                                            <input name="username" value="<?= h($u['username']) ?>" class="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:border-red-500 outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-500 uppercase">New Password</label>
                                            <input name="password" type="password" placeholder="••••••" class="w-full bg-black/20 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:border-red-500 outline-none">
                                        </div>
                                    </div>
                                    <button class="w-full bg-white/5 hover:bg-white/10 text-white text-[10px] font-black uppercase py-2 rounded-lg transition-all border border-white/10">
                                        Update Account
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeTab === 'audit'): ?>
                <div class="console rounded-2xl flex flex-col">
                    <div class="console-header">
                        <span class="console-header live-indicator"></span>
                        <span>SYSTEM AUDIT LOG [LIVE]</span>
                        <span style="margin-left: auto; opacity: 0.5;"><?= count($auditLogs) ?> TOTAL RECORDS</span>
                    </div>
                    <div class="console-content">
                        <?php if (empty($auditLogs)): ?>
                            <div class="empty-console">
                                <p style="font-size: 13px; margin-bottom: 8px;"><i class="fa-solid fa-inbox"></i> No audit logs found</p>
                                <p style="font-size: 10px; opacity: 0.5;">Grade overrides and admin actions will appear here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <div class="log-entry">
                                    <div><span class="log-time">[<?= date('Y-m-d H:i:s', strtotime($log['changed_at'])) ?>]</span> <span class="log-admin"><?= h($log['admin_name']) ?></span> modified grades</div>
                                    <div style="margin: 6px 0; padding-left: 20px;">
                                        <span class="log-student"><?= h($log['student_name']) ?></span> / <span class="log-course"><?= h($log['course_code']) ?></span>
                                    </div>
                                    <div style="margin: 6px 0; padding-left: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                        <?php if ($log['old_prelim'] !== $log['new_prelim']): ?>
                                            <div><span class="log-change">PRELIM:</span> <span class="log-change-from"><?= h($log['old_prelim'] ?? 'null') ?></span> <span style="opacity: 0.5;">→</span> <span class="log-change-to"><?= h($log['new_prelim'] ?? 'null') ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($log['old_midterm'] !== $log['new_midterm']): ?>
                                            <div><span class="log-change">MIDTERM:</span> <span class="log-change-from"><?= h($log['old_midterm'] ?? 'null') ?></span> <span style="opacity: 0.5;">→</span> <span class="log-change-to"><?= h($log['new_midterm'] ?? 'null') ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($log['old_finals'] !== $log['new_finals']): ?>
                                            <div><span class="log-change">FINALS:</span> <span class="log-change-from"><?= h($log['old_finals'] ?? 'null') ?></span> <span style="opacity: 0.5;">→</span> <span class="log-change-to"><?= h($log['new_finals'] ?? 'null') ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($log['old_semestral'] !== $log['new_semestral']): ?>
                                            <div><span class="log-change">SEMESTRAL:</span> <span class="log-change-from"><?= h($log['old_semestral'] ?? 'null') ?></span> <span style="opacity: 0.5;">→</span> <span class="log-change-to"><?= h($log['new_semestral'] ?? 'null') ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($log['old_status'] !== $log['new_status']): ?>
                                            <div><span class="log-change">STATUS:</span> <span class="log-change-from"><?= h($log['old_status'] ?? 'null') ?></span> <span style="opacity: 0.5;">→</span> <span class="log-change-to"><?= h($log['new_status'] ?? 'null') ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($log['reason'])): ?>
                                        <div style="margin: 6px 0; padding-left: 20px;"><span class="log-reason">REASON: "<?= h($log['reason']) ?>\"</span></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        let _gradeModalSilent = false; // suppress modal during page-load normalization

        function showGradeWarningModal(badValue, reason) {
            document.getElementById('gradeModalBadValue').textContent = badValue || '(empty)';
            document.getElementById('gradeModalReason').textContent = reason;
            const modal = document.getElementById('gradeWarningModal');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');
            modal.querySelector('.modal-box').classList.remove('scale-95');
            modal.querySelector('.modal-box').classList.add('scale-100');
        }

        function closeGradeWarningModal() {
            const modal = document.getElementById('gradeWarningModal');
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.querySelector('.modal-box').classList.remove('scale-100');
            modal.querySelector('.modal-box').classList.add('scale-95');
        }

        // Admin grade input formatter
        function adminFormatInput(input) {
            const allowedCodes = ['INC', 'FA', 'UW', 'DRP'];
            const raw = input.value.trim();
            const val = raw.toUpperCase();
            input.style.borderColor = '';
            input.style.color = '';
            input.title = '';

            if (val === '') {
                input.value = '';
                return;
            }
            if (allowedCodes.includes(val)) {
                input.value = val;
                if (val === 'INC') {
                    input.style.color = '#f59e0b';
                    input.style.borderColor = 'rgba(245, 158, 11, 0.5)';
                } else if (val === 'FA') {
                    input.style.color = '#f87171';
                    input.style.borderColor = 'rgba(248, 113, 113, 0.5)';
                } else {
                    input.style.color = '#94a3b8';
                    input.style.borderColor = 'rgba(148, 163, 184, 0.4)';
                }
                return;
            }
            if (!isNaN(raw) && raw !== '') {
                const num = parseFloat(raw);
                if (num >= 0 && num <= 100) {
                    input.value = String(Math.round(num));
                    input.style.color = '#818cf8';
                    return;
                }
                // Out-of-range numeric
                input.value = '';
                input.style.borderColor = 'rgba(251, 191, 36, 0.6)';
                if (!_gradeModalSilent) showGradeWarningModal(raw, 'Numeric grade must be between 0 and 100.');
                return;
            }
            // Invalid text
            input.value = '';
            input.style.borderColor = 'rgba(251, 191, 36, 0.6)';
            if (!_gradeModalSilent) showGradeWarningModal(raw, '\"' + raw + '\" is not a valid grade. Only numeric values (0–100) or the codes below are accepted.');
        }

        // Silently normalize all grade inputs on page load — no modal
        document.addEventListener('DOMContentLoaded', () => {
            _gradeModalSilent = true;
            document.querySelectorAll('.admin-grade-input').forEach(input => adminFormatInput(input));
            _gradeModalSilent = false;
        });

        // Close modal on Escape
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeGradeWarningModal(); });

        function applyFilters() {
            const input = document.getElementById("searchInput");
            const filter = input ? input.value.toUpperCase() : '';
            const courseSelect = document.getElementById("courseFilter");
            const studentSelect = document.getElementById("studentFilter");
            const courseValue = courseSelect ? courseSelect.value : '';
            const studentValue = studentSelect ? studentSelect.value.toLowerCase() : '';
            const rows = document.getElementsByClassName("searchable-row");

            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent || rows[i].innerText;
                const courseMatch = !courseValue || (rows[i].dataset.course || '') === courseValue;
                const studentMatch = !studentValue || (rows[i].dataset.student || '').includes(studentValue);

                if (text.toUpperCase().indexOf(filter) > -1 && courseMatch && studentMatch) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        const courseFilter = document.getElementById("courseFilter");
        const studentFilter = document.getElementById("studentFilter");
        if (courseFilter) courseFilter.addEventListener('change', applyFilters);
        if (studentFilter) studentFilter.addEventListener('change', applyFilters);

        function updateStudentOptions(courseCode) {
            const studentsByCourse = <?= json_encode($studentsByCourse) ?>;
            const select = document.getElementById('studentFilter');
            if (!select) return;
            const prev = select.value;
            select.innerHTML = '<option value="">All Students</option>';
            const list = courseCode ? (studentsByCourse[courseCode] || []) : <?= json_encode(array_values($allStudents)) ?>;
            list.forEach(name => {
                const opt = document.createElement('option');
                opt.value = name.toLowerCase();
                opt.textContent = name;
                if (opt.value === prev) opt.selected = true;
                select.appendChild(opt);
            });
            applyFilters();
        }
        const courseFilterEl = document.getElementById('courseFilter');
        if (courseFilterEl) courseFilterEl.addEventListener('change', function() { updateStudentOptions(this.value); });
    </script>

    <!-- Grade Warning Modal -->
    <div id="gradeWarningModal" onclick="if(event.target===this)closeGradeWarningModal()" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/70 backdrop-blur-sm transition-all duration-200 opacity-0 pointer-events-none">
        <div class="modal-box bg-[#0f1623] border border-amber-500/30 rounded-2xl shadow-2xl shadow-amber-500/10 w-full max-w-md mx-4 transition-transform duration-200 scale-95">
            <!-- Header -->
            <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-white/5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-500/70">Input Warning</p>
                    <h3 class="text-base font-black text-white mt-0.5">Invalid Grade Value</h3>
                </div>
                <button onclick="closeGradeWarningModal()" class="ml-auto text-slate-500 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="px-6 py-5 space-y-4">
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 flex items-center gap-3">
                    <i class="fa-solid fa-ban text-red-400 text-sm flex-shrink-0"></i>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-wider text-red-400 mb-0.5">Rejected Value</p>
                        <p class="text-sm font-black text-white font-mono" id="gradeModalBadValue"></p>
                    </div>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed" id="gradeModalReason"></p>
                <!-- Guide -->
                <div class="rounded-xl border border-white/10 bg-white/[0.02] p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-3">Accepted Values</p>
                    <div class="grid grid-cols-2 gap-2 text-[10px]">
                        <div class="flex items-center gap-2">
                            <span class="w-12 text-center font-black text-indigo-400 bg-indigo-500/10 rounded px-1.5 py-0.5">0–100</span>
                            <span class="text-slate-400">Numeric grade (auto-rounds)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 text-center font-black text-amber-400 bg-amber-500/10 rounded px-1.5 py-0.5">INC</span>
                            <span class="text-slate-400">Incomplete</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 text-center font-black text-red-400 bg-red-500/10 rounded px-1.5 py-0.5">FA</span>
                            <span class="text-slate-400">Failed (Absence)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-12 text-center font-black text-slate-400 bg-slate-500/10 rounded px-1.5 py-0.5">UW</span>
                            <span class="text-slate-400">Unauthorized Withdrawal</span>
                        </div>
                        <div class="flex items-center gap-2 col-span-2">
                            <span class="w-12 text-center font-black text-slate-300 bg-slate-500/10 rounded px-1.5 py-0.5">DRP</span>
                            <span class="text-slate-400">Authorized Withdrawal</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="px-6 pb-6 flex justify-end">
                <button onclick="closeGradeWarningModal()" class="bg-amber-500 hover:bg-amber-400 text-black font-black text-xs uppercase px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-amber-500/20">
                    <i class="fa-solid fa-check mr-2"></i>Understood
                </button>
            </div>
        </div>
    </div>

</body>
</html>