<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

class CrossSelling extends BaseMapper
{
    protected $pull = [
        'id'        => 'post_id',
        'productId' => 'post_id',
    ];
}
