<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Logger;

abstract class ErrorFormatter
{
    public static function formatError(\WP_Error $error)
    {
        return sprintf('%s: %s', get_called_class(), $error->get_error_message());
    }
}
