<?php

/**
 * @author OOI JIAN XIN
 *
 * Shared resource validation for lesson create and update requests.
 */

namespace App\Http\Requests\Concerns;

use App\Models\LessonResource;
use App\Support\LessonResourceRules;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesLessonResources
{
    /**
     * @return array<string, mixed>
     */
    public function resourceRules(): array
    {
        return LessonResourceRules::rules();
    }

    /**
     * Attach type-specific validation to every resource row.
     */
    protected function validateLessonResources(
        Validator $validator
    ): void {
        $validator->after(function ($validator) {
            $resources = $this->input('resources', []);
            $lesson = $this->route('lesson');

            foreach ($resources as $index => $resource) {
                $type = $resource['type'] ?? null;
                $resourceId = $resource['id'] ?? null;
                $url = trim((string) ($resource['url'] ?? ''));

                $hasFile = $this->hasFile(
                    "resources.{$index}.file"
                );

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

                if ($type === 'Link' && $url === '') {
                    $validator->errors()->add(
                        "resources.{$index}.url",
                        'Please enter a URL for this resource.'
                    );
                }

                if (
                    $type === 'Video'
                    && (
                        $url === ''
                        || LessonResource::parseYouTubeId($url) === null
                    )
                ) {
                    $validator->errors()->add(
                        "resources.{$index}.url",
                        'Please provide a valid YouTube URL.'
                    );
                }
            }
        });
    }
}