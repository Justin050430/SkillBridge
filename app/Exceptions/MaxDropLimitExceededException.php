<?php

/**
 * @author OOI JIAN XIN
 *
 * Thrown by EnrollStudentStrategy when a student has already dropped the same
 * course the maximum allowed number of times (MAX_DROPS in the strategy) and
 * tries to enroll again. The controller translates this into a friendly
 * JSON (AJAX) or flash (redirect) response so the limit is enforced on the
 * backend and cannot be bypassed by crafting requests.
 */

namespace App\Exceptions;

use RuntimeException;

class MaxDropLimitExceededException extends RuntimeException
{
}
