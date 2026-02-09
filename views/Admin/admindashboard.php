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
    
    // Handle both numeric and text grades  
    $prelim = trim($_POST['prelim']) !== '' ? trim($_POST['prelim']) : null;
    $midterm = trim($_POST['midterm']) !== '' ? trim($_POST['midterm']) : null;
    $finals = trim($_POST['finals']) !== '' ? trim($_POST['finals']) : null;
    
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
    $update->bind_param('sssssi', $prelim, $midterm, $finals, $semestral, $status, $gradeId);
    $update->execute();

    $audit = $mysqli->prepare('INSERT INTO tb_grade_audit_logs (grade_id, changed_by, old_prelim, old_midterm, old_finals, old_semestral, old_status, new_prelim, new_midterm, new_finals, new_semestral, new_status, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $audit->bind_param('iisssssssssss', $gradeId, $user['user_id'], $gradeRow['prelim'], $gradeRow['midterm'], $gradeRow['finals'], $gradeRow['semestral'], $gradeRow['status'], $prelim, $midterm, $finals, $semestral, $status, $reason);
    $audit->execute();

    $mysqli->commit();
    $message = 'Grade override successful and logged.';
}

// --- LOGIC: USER MANAGEMENT (UPDATE CREDENTIALS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $targetUserId = (int)$_POST['user_id'];
    $newUsername = trim($_POST['username']);
    $newPassword = $_POST['password'];

    if (!empty($newPassword)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare('UPDATE tb_users SET username = ?, password_hash = ? WHERE user_id = ?');
        $stmt->bind_param('ssi', $newUsername, $hashed, $targetUserId);
    } else {
        $stmt = $mysqli->prepare('UPDATE tb_users SET username = ? WHERE user_id = ?');
        $stmt->bind_param('si', $newUsername, $targetUserId);
    }
    
    if ($stmt->execute()) {
        $message = 'User credentials updated successfully.';
    }
    $stmt->close();
}

// --- DATA FETCHING ---
$syId = $_GET['sy_id'] ?? '';
$semId = $_GET['sem_id'] ?? '';

// Fetch Enrollments for Grades Tab
$rows = $mysqli->query("SELECT e.enrollment_id, st.student_no, st.full_name as student_name, c.course_code, t.full_name as teacher_name, g.prelim, g.midterm, g.finals, g.semestral, g.status 
    FROM tb_enrollments e 
    JOIN tb_students st ON e.student_id = st.student_id 
    JOIN tb_course_offerings o ON e.offering_id = o.offering_id 
    JOIN tb_courses c ON o.course_id = c.course_id 
    JOIN tb_teachers t ON o.teacher_id = t.teacher_id 
    LEFT JOIN tb_grades g ON e.enrollment_id = g.enrollment_id
    ORDER BY st.full_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch Users for Accounts Tab
$allUsers = $mysqli->query("SELECT user_id, username, role, created_at FROM tb_users WHERE role != 'admin' ORDER BY role DESC")->fetch_all(MYSQLI_ASSOC);
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b0f1a; color: #f8fafc; height: 100vh; overflow: hidden; }
        .glass { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05); }
        .tab-active { background: #be0b0b; color: white; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1c530e; }

        /* Fixed Table Height Logic */
        .table-container { height: calc(100vh - 280px); overflow-y: auto; }
        thead sticky { position: sticky; top: 0; z-index: 10; }
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
                <a href="?tab=accounts" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $activeTab == 'accounts' ? 'tab-active' : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>">
                    <i class="fa-solid fa-users-gear"></i> <span class="text-sm font-bold">User Accounts</span>
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
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Quick search..." 
                       class="w-full bg-white/5 border border-white/10 rounded-full py-2 pl-10 pr-4 text-sm text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all">
            </div>
        </header>

        <main class="p-8 flex-1 overflow-hidden flex flex-col">
            <?php if ($message): ?>
                <div class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'grades'): ?>
                <div class="glass rounded-2xl overflow-hidden flex flex-col">
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
                                <tr class="hover:bg-white/[0.02] transition-colors searchable-row">
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-sm text-white"><?= h($row['student_name']) ?></div>
                                        <div class="text-[10px] text-slate-500"><?= h($row['course_code']) ?> • <span class="text-red-400"><?= h($row['teacher_name']) ?></span></div>
                                    </td>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="override_grade">
                                        <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                        <td class="px-6 py-3 text-center">
                                            <div class="flex gap-2 justify-center">
                                                <input name="prelim" type="text" placeholder="P" class="w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= $row['prelim'] ?>">
                                                <input name="midterm" type="text" placeholder="M" class="w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= $row['midterm'] ?>">
                                                <input name="finals" type="text" placeholder="F" class="w-14 bg-black/40 border border-white/10 rounded-md px-2 py-1 text-center text-xs text-white focus:border-red-500 outline-none" value="<?= $row['finals'] ?>">
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

            <?php elseif ($activeTab === 'accounts'): ?>
                <div class="flex-1 overflow-y-auto pr-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="dataTable">
                        <?php foreach ($allUsers as $u): ?>
                        <div class="glass p-5 rounded-xl group searchable-row border-l-4 <?= $u['role'] == 'teacher' ? 'border-l-blue-500' : 'border-l-emerald-500' ?>">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><?= $u['role'] ?></span>
                                    <h3 class="text-sm font-bold text-white mt-1 name-field"><?= h($u['username']) ?></h3>
                                </div>
                                <i class="fa-solid fa-user-gear text-slate-700"></i>
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
            <?php endif; ?>
        </main>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const rows = document.getElementsByClassName("searchable-row");

            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent || rows[i].innerText;
                if (text.toUpperCase().indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>