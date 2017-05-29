<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper\Product;

use jtl\Connector\WooCommerce\Mapper\LocaleBaseObjectMapper;

class ProductI18n extends LocaleBaseObjectMapper
{
    protected $push = [
        'post_title'   => 'name',
        'post_name'    => 'urlPath',
        'post_content' => 'description',
        'post_excerpt' => 'shortDescription',
    ];
}
