<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Renderer;

use Jankx\Extensions\AdvancedSearch\Renderer\SearchResultsRenderer;
use Jankx\Extensions\AdvancedSearch\Tests\TestCase;

/**
 * @coversDefaultClass \Jankx\Extensions\AdvancedSearch\Renderer\SearchResultsRenderer
 */
class SearchResultsRendererTest extends TestCase
{
    protected function render(array $get = [], int $paged = 0, array $attributes = []): string
    {
        $_GET = $get;
        $GLOBALS['__paged'] = $paged;

        return SearchResultsRenderer::instance()->render($attributes);
    }

    public function test_render_includes_header_tabs_and_count()
    {
        $this->seedTour();
        $html = $this->render(['s' => 'Tràng An']);

        $this->assertStringContainsString('jankx-advanced-search', $html);
        $this->assertStringContainsString('Kết quả tìm kiếm', $html);
        $this->assertStringContainsString('cho &ldquo;Tràng An&rdquo;', $html);
        $this->assertStringContainsString('Tất cả', $html);
        $this->assertStringContainsString('Trải nghiệm', $html);
        $this->assertStringContainsString('Cẩm nang du lịch', $html);
        $this->assertStringContainsString('Ăn gì ở đâu', $html);
        $this->assertStringContainsString('Tour & Dịch vụ', $html);
        $this->assertStringContainsString('1 kết quả', $html);
    }

    public function test_render_marks_active_tab_and_sort()
    {
        $this->seedTour();
        $html = $this->render(['s' => 'Tràng An', 'type' => 'tour', 'orderby' => 'price_asc']);

        // Active tab link gets the is-active class and aria-current.
        $this->assertMatchesRegularExpression(
            '/<a[^>]*class="[^"]*is-active[^"]*"[^>]*aria-current="page"[^>]*>\s*Tour & Dịch vụ\s*<\/a>/',
            $html
        );
        // The sort select keeps the current orderby selected.
        $this->assertMatchesRegularExpression(
            "/value=\"price_asc\"\s+selected='selected'/",
            $html
        );
    }

    public function test_render_emits_result_card()
    {
        $this->seedTour();
        $html = $this->render(['s' => 'Tràng An']);

        $this->assertStringContainsString('jankx-advanced-search__card', $html);
        $this->assertStringContainsString('Tour Tràng An 1 ngày', $html);
        $this->assertStringContainsString('Từ', $html);
        $this->assertStringContainsString('1.500.000đ', $html);
        $this->assertStringContainsString('4.8', $html);
        $this->assertStringContainsString('1 ngày', $html);
        $this->assertStringContainsString('Tour phổ thông', $html);
    }

    public function test_render_show_empty_state_when_no_match()
    {
        $html = $this->render(['s' => 'không có gì']);

        $this->assertStringContainsString('jankx-advanced-search__empty', $html);
        $this->assertStringContainsString('Không tìm thấy kết quả', $html);
        $this->assertStringNotContainsString('jankx-advanced-search__grid', $html);
    }

    public function test_render_shows_pagination_when_multiple_pages()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->seedTour(['post_title' => 'Tour Tràng An ' . $i]);
        }

        $html = $this->render(['s' => 'Tràng An'], 2, ['perPage' => 2]);

        $this->assertStringContainsString('jankx-advanced-search__pagination', $html);
        $this->assertStringContainsString('paged=2', $html);
        $this->assertStringContainsString('is-current', $html);
        $this->assertStringContainsString('Tiếp', $html);
    }

    public function test_url_builds_home_search_link()
    {
        $renderer = SearchResultsRenderer::instance();

        $this->assertSame(
            'https://example.com/?s=Trang+An&type=tour',
            $renderer->url(['s' => 'Trang An', 'type' => 'tour'])
        );
    }

    public function test_pagination_items_builds_page_numbers_with_dots()
    {
        $renderer = SearchResultsRenderer::instance();

        $items = $renderer->pagination_items(5, 20);

        $pages = array_map(function ($item) {
            return $item['type'] === 'page' ? (int) $item['page'] : '…';
        }, $items);

        $this->assertSame([1, '…', 3, 4, 5, 6, 7, '…', 20], $pages);
        $current = array_values(array_filter($items, function ($item) {
            return isset($item['current']) && $item['current'];
        }));
        $this->assertCount(1, $current);
        $this->assertSame(5, $current[0]['page']);
    }
}
