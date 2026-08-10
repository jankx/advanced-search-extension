<?php

namespace Jankx\Extensions\AdvancedSearch\Tests\Rest;

use WP_REST_Request;
use Jankx\Extensions\AdvancedSearch\Rest\AdvancedSearchController;
use Jankx\Extensions\AdvancedSearch\Tests\TestCase;

/**
 * @coversDefaultClass \Jankx\Extensions\AdvancedSearch\Rest\AdvancedSearchController
 */
class AdvancedSearchControllerTest extends TestCase
{
    protected function controller(): AdvancedSearchController
    {
        return new AdvancedSearchController();
    }

    public function test_register_routes_registers_results_endpoint()
    {
        $this->controller()->register_routes();

        $this->assertCount(1, $GLOBALS['__routes']);
        $route = $GLOBALS['__routes'][0];

        $this->assertSame('jankx/advanced-search/v1', $route['namespace']);
        $this->assertSame('/results', $route['route']);
        $this->assertSame('GET', $route['args']['methods']);
        $this->assertSame('__return_true', $route['args']['permission_callback']);
    }

    public function test_get_results_returns_formatted_data()
    {
        $this->seedTour([
            'post_title' => 'Tour Tràng An 1 ngày',
            'meta_input' => [
                '_tour_price' => 1500000,
                '_tour_price_is_from' => 1,
                '_tour_rating' => 4.8,
                '_tour_review_count' => 12,
                '_tour_duration_days' => 1,
                '_tour_duration_nights' => 0,
            ],
        ]);

        $request = new WP_REST_Request();
        $request->set_param('s', 'Tràng An');
        $request->set_param('type', 'tour');
        $request->set_param('orderby', 'price_asc');
        $request->set_param('page', 1);
        $request->set_param('per_page', 12);

        $response = $this->controller()->get_results($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame('Tràng An', $data['keyword']);
        $this->assertSame('tour', $data['tab']);
        $this->assertSame('price_asc', $data['orderby']);
        $this->assertSame(1, $data['total']);
        $this->assertSame(1, $data['total_pages']);
        $this->assertArrayHasKey('tabs', $data);
        $this->assertArrayHasKey('sort_options', $data);

        $item = $data['items'][0];
        $this->assertSame('Tour Tràng An 1 ngày', $item['title']);
        $this->assertSame('tour', $item['post_type']);
        $this->assertSame(1500000.0, $item['price']);
        $this->assertTrue($item['price_from']);
        $this->assertSame(4.8, $item['rating']);
        $this->assertSame(12, $item['review_count']);
        $this->assertSame('1 ngày', $item['duration']);
    }

    public function test_get_results_normalizes_invalid_input()
    {
        $this->seedTour();

        $request = new WP_REST_Request();
        $request->set_param('type', 'bogus');
        $request->set_param('orderby', 'nope');
        $request->set_param('page', 0);
        $request->set_param('per_page', 500);

        $data = $this->controller()->get_results($request)->get_data();

        $this->assertSame('all', $data['tab']);
        $this->assertSame('recommended', $data['orderby']);
        $this->assertSame(1, $data['page']);
        $this->assertLessThanOrEqual(24, count($data['items']));
    }
}
