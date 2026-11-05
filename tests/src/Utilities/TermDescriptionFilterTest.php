<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Utilities;

use JtlWooCommerceConnector\Tests\AbstractTestCase;
use JtlWooCommerceConnector\Utilities\TermDescriptionFilter;
use WP_Mock;

/**
 * @covers \JtlWooCommerceConnector\Utilities\TermDescriptionFilter
 */
class TermDescriptionFilterTest extends AbstractTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testRegisterSwitchesTermDescriptionFromStrictToPostAllowlist(): void
    {
        WP_Mock::userFunction('remove_filter')
            ->once()
            ->with('pre_term_description', 'wp_filter_kses');
        WP_Mock::expectFilterAdded('pre_term_description', 'wp_filter_post_kses');

        TermDescriptionFilter::register();

        $this->addToAssertionCount(1);
    }
}
