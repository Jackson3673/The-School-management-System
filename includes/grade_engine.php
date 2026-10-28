<?php
/**
 * Grade Engine
 * Handles grading logic for different curriculums.
 */

function calculateGrade($score, $curriculum = '8-4-4')
{
    $score = intval($score);
    $grade = 'E';
    $comment = 'Fail';

    switch ($curriculum) {
        case 'CBC':
            // Competency Based Curriculum (Exceeds, Meets, Approaching, Below)
            // Mapping approximate marks to CBC levels for hybrid systems
            if ($score >= 80) {
                $grade = 'EE'; // Exceeding Expectations
                $comment = 'Excellent Performance';
            } elseif ($score >= 60) {
                $grade = 'ME'; // Meeting Expectations
                $comment = 'Good Work';
            } elseif ($score >= 40) {
                $grade = 'AE'; // Approaching Expectations
                $comment = 'Room for Improvement';
            } else {
                $grade = 'BE'; // Below Expectations
                $comment = 'Needs Support';
            }
            break;

        case 'University':
            // Standard University Grading
            if ($score >= 70) {
                $grade = 'A';
                $comment = 'Excellent';
            } elseif ($score >= 60) {
                $grade = 'B';
                $comment = 'Good';
            } elseif ($score >= 50) {
                $grade = 'C';
                $comment = 'Average';
            } elseif ($score >= 40) {
                $grade = 'D';
                $comment = 'Pass';
            } else {
                $grade = 'E';
                $comment = 'Fail';
            }
            break;

        case '8-4-4':
        default:
            // Standard 8-4-4 Secondary School Grading
            if ($score >= 80) {
                $grade = 'A';
                $comment = 'Excellent';
            } elseif ($score >= 75) {
                $grade = 'A-';
                $comment = 'Very Good';
            } elseif ($score >= 70) {
                $grade = 'B+';
                $comment = 'Good';
            } elseif ($score >= 65) {
                $grade = 'B';
                $comment = 'Good';
            } elseif ($score >= 60) {
                $grade = 'B-';
                $comment = 'Fairly Good';
            } elseif ($score >= 55) {
                $grade = 'C+';
                $comment = 'Average';
            } elseif ($score >= 50) {
                $grade = 'C';
                $comment = 'Average';
            } elseif ($score >= 45) {
                $grade = 'C-';
                $comment = 'Below Average';
            } elseif ($score >= 40) {
                $grade = 'D+';
                $comment = 'Weak';
            } elseif ($score >= 35) {
                $grade = 'D';
                $comment = 'Weak';
            } elseif ($score >= 30) {
                $grade = 'D-';
                $comment = 'Very Weak';
            } else {
                $grade = 'E';
                $comment = 'Fail';
            }
            break;
    }

    return ['grade' => $grade, 'comment' => $comment];
}
?>