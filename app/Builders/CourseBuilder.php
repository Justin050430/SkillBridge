<?php

namespace App\Builders;

use App\Models\Course;

class CourseBuilder
{
    protected array $attributes = [];

    public function withMentorId(int $mentorId): self
    {
        $this->attributes['mentor_id'] = $mentorId;

        return $this;
    }

    public function withCategoryId(int $categoryId): self
    {
        $this->attributes['category_id'] = $categoryId;

        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->attributes['title'] = $title;

        return $this;
    }

    public function withSlug(string $slug): self
    {
        $this->attributes['slug'] = $slug;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->attributes['description'] = $description;

        return $this;
    }

    public function withThumbnail(?string $thumbnail): self
    {
        $this->attributes['thumbnail'] = $thumbnail;

        return $this;
    }

    public function withStatus(string $status): self
    {
        $this->attributes['status'] = $status;

        return $this;
    }

    public function build(): Course
    {
        return new Course($this->attributes);
    }
}
