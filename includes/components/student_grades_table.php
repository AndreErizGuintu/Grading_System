<?php
// expects $rows array
?>
<div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700">
            <tr>
                <th class="px-4 py-3 text-left">Course</th>
                <th class="px-4 py-3 text-left">Instructor</th>
                <th class="px-4 py-3 text-center">Prelim</th>
                <th class="px-4 py-3 text-center">Midterm</th>
                <th class="px-4 py-3 text-center">Finals</th>
                <th class="px-4 py-3 text-center">Semestral</th>
                <th class="px-4 py-3 text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (empty($rows)): ?>
                <tr>
                    <td class="px-4 py-6 text-center text-slate-500" colspan="7">No records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800"><?php echo h($row['course_code']); ?></div>
                            <div class="text-slate-500 text-xs"><?php echo h($row['course_name']); ?></div>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?php echo h($row['teacher_name']); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo h($row['prelim'] ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo h($row['midterm'] ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo h($row['finals'] ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center font-semibold"><?php echo h($row['semestral'] ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($row['status'] === 'PASSED'): ?>
                                <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs">PASSED</span>
                            <?php elseif ($row['status'] === 'FAILED'): ?>
                                <span class="px-2 py-1 rounded bg-rose-100 text-rose-700 text-xs">FAILED</span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded bg-slate-100 text-slate-500 text-xs">PENDING</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
