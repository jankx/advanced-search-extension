<?php

namespace Jankx\Extensions\AdvancedSearch\Tests;

use Brain\Monkey;
use Jankx\Extensions\AdvancedSearch\Search\SearchProvider;
use Jankx\Extensions\AdvancedSearch\Search\SearchQuery;
use Jankx\Extensions\AdvancedSearch\Tests\Support\PostStore;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for the advanced search extension.
 *
 * Boots Brain Monkey, stubs the WP functions (see tests/bootstrap.php) and
 * seeds a clean in-memory post store for every test.
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();
        advanced_search_test_stub_wp_functions();

        unset($_GET);
        SearchProvider::reset_instance();
        \Jankx\Extensions\AdvancedSearch\Renderer\SearchResultsRenderer::reset_instance();
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['__registered_filters'],
            $GLOBALS['__registered_actions'],
            $GLOBALS['__fired_actions'],
            $GLOBALS['__routes'],
            $GLOBALS['__search_query'],
            $GLOBALS['__paged']
        );

        Monkey\tearDown();
        parent::tearDown();
    }

    protected function provider(): SearchProvider
    {
        return SearchProvider::instance();
    }

    protected function query(): SearchQuery
    {
        return new SearchQuery($this->provider());
    }

    protected function seed(array $data): int
    {
        return PostStore::insert($data);
    }

    protected function seedTour(array $overrides = []): int
    {
        return $this->seed(array_merge([
            'post_type' => 'tour',
            'post_status' => 'publish',
            'post_title' => 'Tour Tràng An 1 ngày',
            'post_content' => 'Khám phá Tràng An bằng thuyền.',
            'meta_input' => [
                '_tour_price' => 1500000,
                '_tour_price_is_from' => 1,
                'jankx_rating_average' => 4.8,
                'jankx_rating_count' => 12,
                '_tour_duration_days' => 1,
                '_tour_duration_nights' => 0,
            ],
            'terms_input' => [
                'tour_category' => ['Tour phổ thông'],
            ],
        ], $overrides));
    }

    /**
     * Declare the tour-only meta the travel extension owns, through the same
     * filter TravelExtension uses. Tests that care about the "starting from"
     * flag or the duration must call this.
     */
    protected function declareTourSearchMeta(): void
    {
        add_filter('jankx/advanced_search/post_type_config', function ($config, $postType) {
            if ($postType !== 'tour') {
                return $config;
            }

            $config['price_from_meta'] = '_tour_price_is_from';
            $config['days_meta'] = '_tour_duration_days';
            $config['nights_meta'] = '_tour_duration_nights';

            return $config;
        }, 10, 2);
    }

    protected function titles(array $result): array
    {
        return array_map(function ($item) {
            return $item['title'];
        }, $result['items']);
    }
}
