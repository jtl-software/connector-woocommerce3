<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

use jtl\Connector\WooCommerce\Utility\Util;

class LocaleBaseMapper extends BaseMapper
{
    protected function languageISO(array $data)
    {
        return Util::getInstance()->mapLanguageIso($data['locale']);
    }
}
