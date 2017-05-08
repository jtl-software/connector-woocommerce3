<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Mapper;

use jtl\Connector\Core\Utilities\Language as LanguageUtil;
use jtl\Connector\WooCommerce\Utility\Util;

class LocaleBaseObjectMapper extends BaseObjectMapper
{
    protected function languageISO($data)
    {
        return Util::getInstance()->mapLanguageIso($data->locale);
    }
}
