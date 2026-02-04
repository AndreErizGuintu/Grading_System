<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');

$user = current_user();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
	$reason = trim($_POST['reason'] ?? '');

	$prelim = $_POST['prelim'] !== '' ? (float)$_POST['prelim'] : null;
	$midterm = $_POST['midterm'] !== '' ? (float)$_POST['midterm'] : null;
	$finals = $_POST['finals'] !== '' ? (float)$_POST['finals'] : null;
	[$semestral, $status] = compute_semestral($prelim, $midterm, $finals);

	$mysqli->begin_transaction();

	$gradeStmt = $mysqli->prepare('SELECT grade_id, prelim, midterm, finals, semestral, status FROM tb_grades WHERE enrollment_id = ?');
	$gradeStmt->bind_param('i', $enrollmentId);
	$gradeStmt->execute();
	$gradeRow = $gradeStmt->get_result()->fetch_assoc();
	$gradeStmt->close();

	if ($gradeRow) {
		$gradeId = (int)$gradeRow['grade_id'];
	} else {
		$insert = $mysqli->prepare('INSERT INTO tb_grades (enrollment_id, prelim, midterm, finals, semestral, status) VALUES (?, NULL, NULL, NULL, NULL, NULL)');
		$insert->bind_param('i', $enrollmentId);
		$insert->execute();
		$gradeId = $insert->insert_id;
		$insert->close();

		$gradeRow = [
			'prelim' => null,
			'midterm' => null,
			'finals' => null,
			'semestral' => null,
			'status' => null,
		];
	}

	// Prepare variables for binding (mysqli requires references, not expressions)
	$prelim_str = $prelim !== null ? (string)$prelim : null;
	$midterm_str = $midterm !== null ? (string)$midterm : null;
	$finals_str = $finals !== null ? (string)$finals : null;
	$semestral_str = $semestral !== null ? (string)$semestral : null;

	$update = $mysqli->prepare('UPDATE tb_grades SET prelim = ?, midterm = ?, finals = ?, semestral = ?, status = ? WHERE grade_id = ?');
	$update->bind_param(
		'sssssi',
		$prelim_str,
		$midterm_str,
		$finals_str,
		$semestral_str,
		$status,
		$gradeId
	);
	$update->execute();
	$update->close();

	$audit = $mysqli->prepare(
		'INSERT INTO tb_grade_audit_logs (grade_id, changed_by, old_prelim, old_midterm, old_finals, old_semestral, old_status, new_prelim, new_midterm, new_finals, new_semestral, new_status, reason)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
	);
	$audit->bind_param(
		'iisssssssssss',
		$gradeId,
		$user['user_id'],
		$gradeRow['prelim'],
		$gradeRow['midterm'],
		$gradeRow['finals'],
		$gradeRow['semestral'],
		$gradeRow['status'],
		$prelim_str,
		$midterm_str,
		$finals_str,
		$semestral_str,
		$status,
		$reason
	);
	$audit->execute();
	$audit->close();

	$mysqli->commit();

	$message = 'Override saved and logged.';
}

$syList = $mysqli->query('SELECT sy_id, school_year FROM tb_school_years ORDER BY school_year DESC')->fetch_all(MYSQLI_ASSOC);
$semList = $mysqli->query('SELECT sem_id, semester FROM tb_semesters ORDER BY sem_id ASC')->fetch_all(MYSQLI_ASSOC);

$syId = $_GET['sy_id'] ?? '';
$semId = $_GET['sem_id'] ?? '';

$sql = "
	SELECT
		e.enrollment_id,
		st.student_no,
		st.full_name AS student_name,
		c.course_code,
		c.course_name,
		t.full_name AS teacher_name,
		sy.school_year,
		s.semester,
		g.prelim,
		g.midterm,
		g.finals,
		g.semestral,
		g.status
	FROM tb_enrollments e
	INNER JOIN tb_students st ON st.student_id = e.student_id
	INNER JOIN tb_course_offerings o ON o.offering_id = e.offering_id
	INNER JOIN tb_courses c ON c.course_id = o.course_id
	INNER JOIN tb_teachers t ON t.teacher_id = o.teacher_id
	INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
	INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
	LEFT JOIN tb_grades g ON g.enrollment_id = e.enrollment_id
	WHERE 1=1
";

$params = [];
$types = '';

if ($syId) {
	$sql .= ' AND o.sy_id = ?';
	$params[] = (int)$syId;
	$types .= 'i';
}
if ($semId) {
	$sql .= ' AND o.sem_id = ?';
	$params[] = (int)$semId;
	$types .= 'i';
}

$sql .= ' ORDER BY sy.school_year DESC, s.semester ASC, c.course_code ASC, st.full_name ASC';

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
	$stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Dashboard</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
	<header class="bg-white shadow">
		<div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
			<div>
				<h1 class="text-xl font-bold text-slate-800">Admin Dashboard</h1>
				<p class="text-sm text-slate-500">Full access with audit logging</p>
			</div>
			<a href="/Grading_System/logout.php" class="text-sm text-slate-600 hover:text-slate-800">Logout</a>
		</div>
	</header>

	<main class="max-w-6xl mx-auto px-6 py-8 space-y-6">
		<?php if ($message): ?>
			<div class="p-3 rounded bg-emerald-100 text-emerald-700 text-sm">
				<?php echo h($message); ?>
			</div>
		<?php endif; ?>

		<section class="bg-white shadow rounded-lg p-6">
			<h2 class="text-lg font-semibold text-slate-800">Filter Records</h2>
			<form method="get" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
				<div>
					<label class="block text-sm font-medium text-slate-700">School Year</label>
					<select name="sy_id" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
						<option value="">All</option>
						<?php foreach ($syList as $sy): ?>
							<option value="<?php echo (int)$sy['sy_id']; ?>" <?php echo ((string)$syId === (string)$sy['sy_id']) ? 'selected' : ''; ?>>
								<?php echo h($sy['school_year']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="block text-sm font-medium text-slate-700">Semester</label>
					<select name="sem_id" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
						<option value="">All</option>
						<?php foreach ($semList as $sem): ?>
							<option value="<?php echo (int)$sem['sem_id']; ?>" <?php echo ((string)$semId === (string)$sem['sem_id']) ? 'selected' : ''; ?>>
								<?php echo h($sem['semester']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="flex items-end">
					<button class="w-full bg-slate-800 text-white py-2 rounded hover:bg-slate-700">Apply Filter</button>
				</div>
			</form>
		</section>

		<section class="bg-white shadow rounded-lg p-6">
			<h2 class="text-lg font-semibold text-slate-800">All Enrollments</h2>
			<div class="overflow-x-auto mt-4">
				<table class="min-w-full text-sm">
					<thead class="bg-slate-100 text-slate-700">
						<tr>
							<th class="px-4 py-3 text-left">Student</th>
							<th class="px-4 py-3 text-left">Course</th>
							<th class="px-4 py-3 text-left">Instructor</th>
							<th class="px-4 py-3 text-center">Prelim</th>
							<th class="px-4 py-3 text-center">Midterm</th>
							<th class="px-4 py-3 text-center">Finals</th>
							<th class="px-4 py-3 text-center">Semestral</th>
							<th class="px-4 py-3 text-center">Status</th>
							<th class="px-4 py-3 text-center">Override</th>
						</tr>
					</thead>
					<tbody class="divide-y">
						<?php if (empty($rows)): ?>
							<tr>
								<td colspan="9" class="px-4 py-6 text-center text-slate-500">No records found.</td>
							</tr>
						<?php else: ?>
							<?php foreach ($rows as $row): ?>
								<tr>
									<td class="px-4 py-3">
										<div class="font-semibold text-slate-800"><?php echo h($row['student_name']); ?></div>
										<div class="text-xs text-slate-500"><?php echo h($row['student_no']); ?></div>
									</td>
									<td class="px-4 py-3">
										<div class="font-semibold text-slate-800"><?php echo h($row['course_code']); ?></div>
										<div class="text-xs text-slate-500"><?php echo h($row['course_name']); ?></div>
										<div class="text-xs text-slate-400"><?php echo h($row['school_year']); ?> • <?php echo h($row['semester']); ?></div>
									</td>
									<td class="px-4 py-3 text-slate-700"><?php echo h($row['teacher_name']); ?></td>
									<form method="post">
										<td class="px-4 py-3 text-center">
											<input name="prelim" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($row['prelim'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center">
											<input name="midterm" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($row['midterm'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center">
											<input name="finals" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($row['finals'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center font-semibold"><?php echo h($row['semestral'] ?? '-'); ?></td>
										<td class="px-4 py-3 text-center"><?php echo h($row['status'] ?? 'PENDING'); ?></td>
										<td class="px-4 py-3 text-center">
											<input type="hidden" name="enrollment_id" value="<?php echo (int)$row['enrollment_id']; ?>" />
											<input name="reason" required class="w-40 border rounded px-2 py-1 text-xs" placeholder="Reason" />
											<button class="mt-2 bg-slate-800 text-white px-3 py-1 rounded">Save</button>
										</td>
									</form>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	</main>
</body>
</html>
