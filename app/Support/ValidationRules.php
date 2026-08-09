<?php

namespace App\Support;

/**
 * Shared, field-appropriate validation rules.
 *
 * Centralises rules that would otherwise be duplicated across Form Requests
 * so the character set for each field stays consistent. Fields that represent
 * different data (names vs titles vs descriptions) deliberately use different
 * rules - there is no single "letters only" rule for the whole application.
 */
class ValidationRules
{
    /**
     * A personal name: Unicode letters + spaces + common name punctuation
     * (hyphen, apostrophe). No digits, no arbitrary symbols, no HTML.
     * Unicode-aware so accented / non-Latin names remain valid.
     */
    public static function name(): array
    {
        return [
            'required',
            'string',
            'max:100',
            'regex:/^[\p{L}\p{M}\' \-]+$/u',
        ];
    }

    /**
     * A short title: plain text with normal punctuation but no angle
     * brackets (defence-in-depth against HTML/script injection).
     */
    public static function title(): array
    {
        return [
            'required',
            'string',
            'max:150',
            'regex:/^[^<>]+$/',
        ];
    }

    /**
     * A longer required content block (e.g. course description, lesson
     * content). Rendered escaped, so only a length limit is enforced.
     */
    public static function longText(int $max = 20000): array
    {
        return [
            'required',
            'string',
            'max:'.$max,
        ];
    }

    /**
     * An optional long text field (e.g. assessment description / explanation).
     */
    public static function optionalLongText(int $max = 5000): array
    {
        return [
            'nullable',
            'string',
            'max:'.$max,
        ];
    }
}
