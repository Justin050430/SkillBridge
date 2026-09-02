<?php

/**
 * @author OOI JIAN XIN
 *
 * Shared resource validation for the lesson create/update requests.
 * Applies type-specific requirements (a file for File/Image, a URL for
 * Link, a valid YouTube URL for Video) after the base array rules.
 */

namespace App\Http\Requests\Concerns;

use App\Models\LessonResource;
use App\Support\LessonResourceRules;

trait ValidatesLessonResources
{
    /**
     * The resource array rules (shared between store and update).
     *
     * @return array<string, mixed>
     */
    public function resourceRules(): array
    {
        return LessonResourceRules::rules();
    }

    /**
     * Attach type-specific, per-row validation.
     */
    protected function validateLessonResources(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $resources = $this->input('resources', []);

            foreach ($resources as $index => $resource) {
                $type = $resource['type'] ?? null;
$resourceId = $resource['id'] ?? null;
$lesson = $this->route('lesson');

$hasFile = $this->hasFile("resources.{$index}.file");

$hasExistingFile = $resourceId
    && $lesson
    && LessonResource::query()
        ->whereKey($resourceId)
        ->where('lesson_id', $lesson->id)
        ->whereNotNull('file_path')
        ->exists();

if (
    in_array($type, ['File', 'Image'], true)
    && ! $hasFile
    && ! $hasExistingFile
) {
    $validator->errors()->add(
        "resources.{$index}.file",
        'Please choose a file for this resource.'
    );
}
                $url = trim((string) ($resource['url'] ?? ''));

                if (
    in_array($type, ['File', 'Image'], true)
    && ! $hasId
    && ! $hasFile
) {
    $validator->errors()->add(
        "resources.{$index}.file",
        'Please choose a file for this resource.'
    );
}

                if ($type === 'Link' && $url === '') {
                    $validator->errors()->add(
                        "resources.{$index}.url",
                        'A URL is required for a link resource.'
                    );
                }

                if ($type === 'Video' && ($url === '' || LessonResource::parseYouTubeId($url) === null)) {
                    $validator->errors()->add(
                        "resources.{$index}.url",
                        'Please provide a valid YouTube URL for the video.'
                    );
                }
            }
        });
    }
}
