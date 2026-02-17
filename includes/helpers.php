<?php
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function normalize_grade_input($grade, ?string &$error = null): ?string
{
    if ($grade === null) {
        return null;
    }

    $trimmed = trim((string)$grade);
    if ($trimmed === '') {
        return null;
    }

    if (is_numeric($trimmed)) {
        $numeric = (float)$trimmed;
        if ($numeric < 0 || $numeric > 100) {
            $error = 'Numeric grades must be between 0 and 100.';
            return null;
        }
        return (string)$numeric;
    }

    $code = strtoupper($trimmed);
    $allowedCodes = ['INC', 'FA', 'UW', 'DRP'];
    if (!in_array($code, $allowedCodes, true)) {
        $error = 'Allowed codes: INC, FA, UW, DRP.';
        return null;
    }

    return $code;
}

function compute_semestral($prelim, $midterm, $finals): array
{
    $allowedCodes = ['INC', 'FA', 'UW', 'DRP'];

    foreach ([$prelim, $midterm, $finals] as $grade) {
        if ($grade !== null && !is_numeric($grade)) {
            $upperGrade = strtoupper((string)$grade);
            if (!in_array($upperGrade, $allowedCodes, true)) {
                return [null, 'PENDING'];
            }

            $status = $upperGrade === 'INC'
                ? 'INCOMPLETE'
                : ($upperGrade === 'FA' ? 'FAILED' : 'DROPPED');

            return [$upperGrade, $status];
        }
    }

    $prelimNum = $prelim !== null && is_numeric($prelim) ? (float)$prelim : null;
    $midtermNum = $midterm !== null && is_numeric($midterm) ? (float)$midterm : null;
    $finalsNum = $finals !== null && is_numeric($finals) ? (float)$finals : null;

    if ($prelimNum === null || $midtermNum === null || $finalsNum === null) {
        return [null, 'PENDING'];
    }

    $semestral = (int)round(($prelimNum + $midtermNum + $finalsNum) / 3);
    $status = $semestral >= 75 ? 'PASSED' : 'FAILED';

    return [(string)$semestral, $status];
}
