<?php
// expects $rows array
?>
<style>
    .table-header {
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
    }
    .table-body {
        background: var(--bg-card);
    }
    .table-row-hover:hover {
        background: var(--accent-glow);
    }
    .grade-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        font-weight: 700;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        transition: all 0.2s ease;
    }
    .grade-pass {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
        border-color: rgba(34, 197, 94, 0.25);
    }
    .grade-fail {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.25);
    }
    .grade-empty {
        background: var(--bg-main);
        color: var(--text-muted);
        border-color: var(--border);
    }
    .semestral-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.5rem;
        font-weight: 800;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    .semestral-pass {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 2px solid rgba(34, 197, 94, 0.3);
    }
    .semestral-fail {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 2px solid rgba(239, 68, 68, 0.3);
    }
    .semestral-empty {
        background: var(--bg-main);
        color: var(--text-muted);
        border: 2px solid var(--border);
    }
    .status-passed {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    .status-failed {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    .status-pending {
        background: rgba(234, 179, 8, 0.12);
        color: #eab308;
        border: 1px solid rgba(234, 179, 8, 0.2);
    }
    .status-incomplete {
        background: rgba(168, 85, 247, 0.12);
        color: #a855f7;
        border: 1px solid rgba(168, 85, 247, 0.2);
    }
    .status-dropped {
        background: rgba(148, 163, 184, 0.12);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }
    .avatar-course {
        width: 2.5rem;
        height: 2.5rem;
        min-width: 2.5rem;
        border-radius: 0.5rem;
    }
    .avatar-teacher {
        width: 2rem;
        height: 2rem;
        min-width: 2rem;
        border-radius: 0.5rem;
    }
</style>
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="table-header">
            <tr>
                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-book text-xs"></i>
                        <span>Course</span>
                    </div>
                </th>
                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-chalkboard-teacher text-xs"></i>
                        <span>Instructor</span>
                    </div>
                </th>
                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">Prelim</th>
                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">Midterm</th>
                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">Finals</th>
                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">Semestral</th>
                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted)">Status</th>
            </tr>
        </thead>
        <tbody class="table-body" style="border-top: 1px solid var(--border)">
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-16 text-center">
                        <div style="color: var(--text-muted)">
                            <i class="fas fa-inbox text-4xl mb-3 opacity-30"></i>
                            <p class="text-base font-semibold mb-1">No grades found</p>
                            <p class="text-xs opacity-60">Try adjusting your filters or contact your instructor</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="table-row-hover" style="border-bottom: 1px solid var(--border)">
                        <!-- Course Column -->
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="avatar-course bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($row['course_code'], 0, 1)); ?>
                                </div>
                                <div class="flex flex-col justify-center min-w-0">
                                    <div class="font-bold text-sm leading-tight truncate" style="color: var(--text-main)"><?php echo h($row['course_code']); ?></div>
                                    <div class="text-xs leading-tight truncate mt-0.5" style="color: var(--text-muted)"><?php echo h($row['course_name']); ?></div>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Instructor Column -->
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="avatar-teacher bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                    <?php echo strtoupper(substr($row['teacher_name'], 0, 1)); ?>
                                </div>
                                <span class="text-sm font-medium truncate" style="color: var(--text-main)"><?php echo h($row['teacher_name']); ?></span>
                            </div>
                        </td>
                        
                        <!-- Prelim Grade -->
                        <td class="px-4 py-4">
                            <div class="flex justify-center">
                                <?php 
                                $isNumeric = $row['prelim'] !== null && is_numeric($row['prelim']);
                                $gradeClass = $isNumeric 
                                    ? ($row['prelim'] >= 75 ? 'grade-pass' : 'grade-fail')
                                    : 'grade-empty';
                                $displayGrade = $row['prelim'] !== null 
                                    ? ($isNumeric ? number_format($row['prelim'], 0) : strtoupper($row['prelim']))
                                    : '-';
                                ?>
                                <span class="grade-box <?php echo $gradeClass; ?>">
                                    <?php echo $displayGrade; ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- Midterm Grade -->
                        <td class="px-4 py-4">
                            <div class="flex justify-center">
                                <?php 
                                $isNumeric = $row['midterm'] !== null && is_numeric($row['midterm']);
                                $gradeClass = $isNumeric 
                                    ? ($row['midterm'] >= 75 ? 'grade-pass' : 'grade-fail')
                                    : 'grade-empty';
                                $displayGrade = $row['midterm'] !== null 
                                    ? ($isNumeric ? number_format($row['midterm'], 0) : strtoupper($row['midterm']))
                                    : '-';
                                ?>
                                <span class="grade-box <?php echo $gradeClass; ?>">
                                    <?php echo $displayGrade; ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- Finals Grade -->
                        <td class="px-4 py-4">
                            <div class="flex justify-center">
                                <?php 
                                $isNumeric = $row['finals'] !== null && is_numeric($row['finals']);
                                $gradeClass = $isNumeric 
                                    ? ($row['finals'] >= 75 ? 'grade-pass' : 'grade-fail')
                                    : 'grade-empty';
                                $displayGrade = $row['finals'] !== null 
                                    ? ($isNumeric ? number_format($row['finals'], 0) : strtoupper($row['finals']))
                                    : '-';
                                ?>
                                <span class="grade-box <?php echo $gradeClass; ?>">
                                    <?php echo $displayGrade; ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- Semestral Average -->
                        <td class="px-4 py-4">
                            <div class="flex justify-center">
                                <?php 
                                $isNumeric = $row['semestral'] !== null && is_numeric($row['semestral']);
                                $gradeClass = $isNumeric 
                                    ? ($row['semestral'] >= 75 ? 'semestral-pass' : 'semestral-fail')
                                    : 'semestral-empty';
                                $displayGrade = $row['semestral'] !== null 
                                    ? ($isNumeric ? number_format($row['semestral'], 0) : strtoupper($row['semestral']))
                                    : '-';
                                ?>
                                <span class="semestral-badge <?php echo $gradeClass; ?>">
                                    <?php echo $displayGrade; ?>
                                </span>
                            </div>
                        </td>
                        
                        <!-- Status -->
                        <td class="px-4 py-4">
                            <div class="flex justify-center">
                                <?php if ($row['status'] === 'PASSED'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide status-passed">
                                        <i class="fas fa-check text-[10px]"></i>
                                        <span>Passed</span>
                                    </span>
                                <?php elseif ($row['status'] === 'FAILED'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide status-failed">
                                        <i class="fas fa-times text-[10px]"></i>
                                        <span>Failed</span>
                                    </span>
                                <?php elseif ($row['status'] === 'INCOMPLETE'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide status-incomplete">
                                        <i class="fas fa-hourglass-half text-[10px]"></i>
                                        <span>Incomplete</span>
                                    </span>
                                <?php elseif ($row['status'] === 'DROPPED'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide status-dropped">
                                        <i class="fas fa-ban text-[10px]"></i>
                                        <span>Dropped</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wide status-pending">
                                        <i class="fas fa-clock text-[10px]"></i>
                                        <span>Pending</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>