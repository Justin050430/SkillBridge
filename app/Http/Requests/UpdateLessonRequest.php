<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Requests;

/**
 * Validates the update of a lesson within a course by an Admin or Mentor.
 *
 * The same rules apply as when creating a lesson.
 */
class UpdateLessonRequest extends StoreLessonRequest
{
}
