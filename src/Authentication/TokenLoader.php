<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Authentication;

use jtl\Connector\Authentication\ITokenLoader;
use jtl\Connector\Core\Exception\ConnectorException;
use JtlConnectorAdmin;
use JtlWooCommerceConnector\Utilities\Config;

class TokenLoader implements ITokenLoader
{
    public function load()
    {
        $token = Config::get(Config::OPTIONS_TOKEN, false);

        if ($token === false) {
            throw new ConnectorException(__('There was no token found.', JTLWCC_TEXT_DOMAIN));
        }

        return $token;
    }
}
