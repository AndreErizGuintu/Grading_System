<?php
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function compute_semestral(?float $prelim, ?float $midterm, ?float $finals): array
{
    if ($prelim === null || $midterm === null || $finals === null) {
        return [null, null];
    }

    $semestral = round(($prelim + $midterm + $finals) / 3, 2);
    $status = $semestral >= 75 ? 'PASSED' : 'FAILED';

    return [$semestral, $status];
}
