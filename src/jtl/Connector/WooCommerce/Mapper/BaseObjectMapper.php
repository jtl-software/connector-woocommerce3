<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @author    Daniel Hoffmann <daniel.hoffmann@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

class BaseObjectMapper extends BaseMapper
{
    protected function getValue($data, $key)
    {
        return isset($data->{$key}) ? $data->{$key} : null;
    }
}
