<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Post extends Model
{
    protected static string $table = 'posts';
    protected array $fillable = [
        'id', 'tenant_id', 'author_id', 'title', 'slug', 'excerpt', 'content',
        'featured_image', 'status', 'published_at', 'views', 'reading_time',
        'meta_title', 'meta_description', 'meta_keywords', 'created_at', 'updated_at',
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'author_id' => 'int',
        'views' => 'int',
        'reading_time' => 'int',
        'published_at' => 'string',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }

    public static function getPublished(int $limit = 20): array
    {
        return static::where('status', 'published')
            ->where('published_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function getFeatured(int $limit = 5): array
    {
        return static::where('status', 'published')
            ->where('featured_image', '!=', null)
            ->where('published_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('views', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function getRecent(int $limit = 10): array
    {
        return static::where('status', 'published')
            ->where('published_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    public function publish(): bool
    {
        $this->status = 'published';
        $this->published_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function unpublish(): bool
    {
        $this->status = 'draft';
        $this->published_at = null;
        return $this->save();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at <= date('Y-m-d H:i:s');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'published' && $this->published_at > date('Y-m-d H:i:s');
    }

    public function incrementViews(): bool
    {
        $this->views = ($this->views ?? 0) + 1;
        return $this->save();
    }

    public function calculateReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        $readingTime = (int)ceil($wordCount / 200); // Average reading speed: 200 wpm
        return max(1, $readingTime); // Minimum 1 minute
    }

    public function getAuthor(): ?User
    {
        return User::find($this->author_id);
    }

    public function getCategories(): array
    {
        $qb = new \Zen\Database\QueryBuilder();
        $categories = $qb->table('categories')
            ->select('categories.*')
            ->join('category_post', 'categories.id', '=', 'category_post.category_id')
            ->where('category_post.post_id', $this->id)
            ->get();
        return $categories;
    }

    public function getTags(): array
    {
        $qb = new \Zen\Database\QueryBuilder();
        $tags = $qb->table('tags')
            ->select('tags.*')
            ->join('post_tag', 'tags.id', '=', 'post_tag.tag_id')
            ->where('post_tag.post_id', $this->id)
            ->get();
        return $tags;
    }

    public function getExcerpt(int $maxLength = 200): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        $content = strip_tags($this->content ?? '');
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength) . '...';
    }
}
