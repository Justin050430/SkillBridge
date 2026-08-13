<?php

/**
 * @author ZHI FOOK CHUA
 */

namespace App\Http\Requests\Mentor;

/**
 * Validates the update of a course by a Mentor.
 *
 * The same rules apply as when creating a course (mentor_id stays fixed to
 * the authenticated mentor and is never supplied by the client).
 */
class UpdateCourseRequest extends StoreCourseRequest
{
}
