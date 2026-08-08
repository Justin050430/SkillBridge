<?php

namespace App\Http\Requests;

/**
 * Validates the update of a course by an Admin.
 *
 * The slug is auto-generated from the title by the model, so the same rules
 * apply as when creating a course.
 */
class UpdateCourseRequest extends StoreCourseRequest
{
}
