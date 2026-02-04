<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('teacher');

$user = current_user();

$stmt = $mysqli->prepare('SELECT teacher_id, full_name FROM tb_teachers WHERE user_id = ?');
$stmt->bind_param('i', $user['user_id']);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacherId = (int)($teacher['teacher_id'] ?? 0);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$offeringId = (int)($_POST['offering_id'] ?? 0);
	$enrollmentId = (int)($_POST['enrollment_id'] ?? 0);

	$check = $mysqli->prepare('SELECT offering_id FROM tb_course_offerings WHERE offering_id = ? AND teacher_id = ?');
	$check->bind_param('ii', $offeringId, $teacherId);
	$check->execute();
	$isValid = (bool)$check->get_result()->fetch_row();
	$check->close();

	if ($isValid) {
		$prelim = $_POST['prelim'] !== '' ? (float)$_POST['prelim'] : null;
		$midterm = $_POST['midterm'] !== '' ? (float)$_POST['midterm'] : null;
		$finals = $_POST['finals'] !== '' ? (float)$_POST['finals'] : null;

		[$semestral, $status] = compute_semestral($prelim, $midterm, $finals);

		$stmt = $mysqli->prepare(
			'INSERT INTO tb_grades (enrollment_id, prelim, midterm, finals, semestral, status) VALUES (?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE prelim = VALUES(prelim), midterm = VALUES(midterm), finals = VALUES(finals), semestral = VALUES(semestral), status = VALUES(status)'
		);
		
		// Convert to strings for binding (mysqli requires variables, not expressions)
		$prelim_str = $prelim !== null ? (string)$prelim : null;
		$midterm_str = $midterm !== null ? (string)$midterm : null;
		$finals_str = $finals !== null ? (string)$finals : null;
		$semestral_str = $semestral !== null ? (string)$semestral : null;
		
		$stmt->bind_param(
			'isssss',
			$enrollmentId,
			$prelim_str,
			$midterm_str,
			$finals_str,
			$semestral_str,
			$status
		);
		$stmt->execute();
		$stmt->close();

		$message = 'Grades saved successfully.';
	} else {
		$message = 'Invalid course offering.';
	}
}

$offeringsStmt = $mysqli->prepare(
	'SELECT o.offering_id, c.course_code, c.course_name, sy.school_year, s.semester
	 FROM tb_course_offerings o
	 INNER JOIN tb_courses c ON c.course_id = o.course_id
	 INNER JOIN tb_school_years sy ON sy.sy_id = o.sy_id
	 INNER JOIN tb_semesters s ON s.sem_id = o.sem_id
	 WHERE o.teacher_id = ?
	 ORDER BY sy.school_year DESC, s.semester ASC, c.course_code ASC'
);
$offeringsStmt->bind_param('i', $teacherId);
$offeringsStmt->execute();
$offerings = $offeringsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$offeringsStmt->close();

$activeOfferingId = (int)($_GET['offering_id'] ?? 0);
$students = [];

if ($activeOfferingId) {
	$enrollStmt = $mysqli->prepare(
		'SELECT e.enrollment_id, st.student_no, st.full_name, g.prelim, g.midterm, g.finals, g.semestral, g.status
		 FROM tb_enrollments e
		 INNER JOIN tb_students st ON st.student_id = e.student_id
		 INNER JOIN tb_course_offerings o ON o.offering_id = e.offering_id
		 LEFT JOIN tb_grades g ON g.enrollment_id = e.enrollment_id
		 WHERE e.offering_id = ? AND o.teacher_id = ?
		 ORDER BY st.full_name'
	);
	$enrollStmt->bind_param('ii', $activeOfferingId, $teacherId);
	$enrollStmt->execute();
	$students = $enrollStmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$enrollStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Teacher Dashboard</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
	<header class="bg-white shadow">
		<div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
			<div>
				<h1 class="text-xl font-bold text-slate-800">Teacher Dashboard</h1>
				<p class="text-sm text-slate-500"><?php echo h($teacher['full_name'] ?? ''); ?></p>
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
			<h2 class="text-lg font-semibold text-slate-800">My Handled Courses</h2>
			<div class="mt-4 grid gap-3">
				<?php if (empty($offerings)): ?>
					<p class="text-slate-500">No courses assigned.</p>
				<?php else: ?>
					<?php foreach ($offerings as $offering): ?>
						<a class="border rounded-lg p-4 hover:bg-slate-50 <?php echo $activeOfferingId === (int)$offering['offering_id'] ? 'border-slate-800 bg-slate-50' : 'border-slate-200'; ?>"
						   href="?offering_id=<?php echo (int)$offering['offering_id']; ?>">
							<div class="font-semibold text-slate-800"><?php echo h($offering['course_code']); ?> - <?php echo h($offering['course_name']); ?></div>
							<div class="text-xs text-slate-500"><?php echo h($offering['school_year']); ?> • <?php echo h($offering['semester']); ?></div>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="bg-white shadow rounded-lg p-6">
			<h2 class="text-lg font-semibold text-slate-800">Enrolled Students</h2>
			<?php if (!$activeOfferingId): ?>
				<p class="text-slate-500 mt-3">Select a course to edit grades.</p>
			<?php elseif (empty($students)): ?>
				<p class="text-slate-500 mt-3">No students enrolled.</p>
			<?php else: ?>
				<div class="overflow-x-auto mt-4">
					<table class="min-w-full text-sm">
						<thead class="bg-slate-100 text-slate-700">
							<tr>
								<th class="px-4 py-3 text-left">Student</th>
								<th class="px-4 py-3 text-center">Prelim</th>
								<th class="px-4 py-3 text-center">Midterm</th>
								<th class="px-4 py-3 text-center">Finals</th>
								<th class="px-4 py-3 text-center">Semestral</th>
								<th class="px-4 py-3 text-center">Status</th>
								<th class="px-4 py-3 text-center">Action</th>
							</tr>
						</thead>
						<tbody class="divide-y">
							<?php foreach ($students as $st): ?>
								<tr>
									<td class="px-4 py-3">
										<div class="font-semibold text-slate-800"><?php echo h($st['full_name']); ?></div>
										<div class="text-xs text-slate-500"><?php echo h($st['student_no']); ?></div>
									</td>
									<form method="post">
										<td class="px-4 py-3 text-center">
											<input name="prelim" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($st['prelim'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center">
											<input name="midterm" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($st['midterm'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center">
											<input name="finals" type="number" step="0.01" class="w-20 border rounded px-2 py-1" value="<?php echo h($st['finals'] ?? ''); ?>" />
										</td>
										<td class="px-4 py-3 text-center font-semibold"><?php echo h($st['semestral'] ?? '-'); ?></td>
										<td class="px-4 py-3 text-center">
											<?php echo h($st['status'] ?? 'PENDING'); ?>
										</td>
										<td class="px-4 py-3 text-center">
											<input type="hidden" name="offering_id" value="<?php echo (int)$activeOfferingId; ?>" />
											<input type="hidden" name="enrollment_id" value="<?php echo (int)$st['enrollment_id']; ?>" />
											<button class="bg-slate-800 text-white px-3 py-1 rounded">Save</button>
										</td>
									</form>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
