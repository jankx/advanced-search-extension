<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Support;

/**
 * In-memory WordPress post/meta/terms store used by the unit tests.
 *
 * Mirrors how WordPress stores posts, post meta and taxonomy terms so the
 * search query logic can be tested without a real database.
 */
class PostStore
{
    protected static $posts = [];
    protected static $meta = [];
    protected static $terms = [];
    protected static $nextId = 1;

    public static function reset(): void
    {
        self::$posts = [];
        self::$meta = [];
        self::$terms = [];
        self::$nextId = 1;
    }

    public static function insert(array $data): int
    {
        $id = self::$nextId++;

        $post = new \WP_Post();
        $post->ID = $id;
        $post->post_type = $data['post_type'] ?? 'post';
        $post->post_status = $data['post_status'] ?? 'publish';
        $post->post_title = $data['post_title'] ?? '';
        $post->post_excerpt = $data['post_excerpt'] ?? '';
        $post->post_content = $data['post_content'] ?? '';
        $post->post_date = $data['post_date'] ?? '2026-01-01 00:00:00';
        $post->post_name = $data['post_name'] ?? ('post-' . $id);

        self::$posts[$id] = $post;

        if (!empty($data['meta_input'])) {
            foreach ($data['meta_input'] as $key => $value) {
                self::updateMeta($id, $key, $value);
            }
        }

        if (!empty($data['terms_input'])) {
            foreach ($data['terms_input'] as $taxonomy => $terms) {
                self::$terms[$id][$taxonomy] = (array) $terms;
            }
        }

        return $id;
    }

    public static function all(): array
    {
        return self::$posts;
    }

    public static function get(int $id): ?\WP_Post
    {
        return self::$posts[$id] ?? null;
    }

    public static function meta(int $id, string $key)
    {
        if (!isset(self::$meta[$id])) {
            return null;
        }

        return array_key_exists($key, self::$meta[$id]) ? self::$meta[$id][$key] : null;
    }

    public static function updateMeta(int $id, string $key, $value): void
    {
        if (!isset(self::$meta[$id])) {
            self::$meta[$id] = [];
        }

        self::$meta[$id][$key] = $value;
    }

    public static function terms(int $id, string $taxonomy): array
    {
        return self::$terms[$id][$taxonomy] ?? [];
    }

    public static function setThumbnail(int $id, bool $has = true): void
    {
        self::updateMeta($id, '_thumbnail_id', $has ? 1 : 0);
    }
}
