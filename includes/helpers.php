<?php
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function compute_semestral($prelim, $midterm, $finals): array
{
    // If any grade is a text value (INC, DROP, etc.), return that status
    $textGrades = ['INC', 'DROP', 'WP', 'WF', 'ABS', 'NA'];
    
    // Check if any grade is text
    foreach ([$prelim, $midterm, $finals] as $grade) {
        if ($grade !== null && !is_numeric($grade)) {
            // Return the text value as both semestral and status
            $upperGrade = strtoupper($grade);
            $status = in_array($upperGrade, ['INC']) ? 'INCOMPLETE' : 
                     (in_array($upperGrade, ['DROP', 'WP', 'WF']) ? 'DROPPED' : 'PENDING');
            return [$upperGrade, $status];
        }
    }
    
    // Convert to float for numeric calculation
    $prelimNum = $prelim !== null && is_numeric($prelim) ? (float)$prelim : null;
    $midtermNum = $midterm !== null && is_numeric($midterm) ? (float)$midterm : null;
    $finalsNum = $finals !== null && is_numeric($finals) ? (float)$finals : null;
    
    // If any numeric grade is missing, return null
    if ($prelimNum === null || $midtermNum === null || $finalsNum === null) {
        return [null, 'PENDING'];
    }

    // Calculate average
    $semestral = round(($prelimNum + $midtermNum + $finalsNum) / 3, 2);
    $status = $semestral >= 75 ? 'PASSED' : 'FAILED';

    return [(string)$semestral, $status];
}
