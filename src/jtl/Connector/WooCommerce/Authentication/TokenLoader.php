<?php
/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace jtl\Connector\WooCommerce\Authentication;

use jtl\Connector\Authentication\ITokenLoader;
use jtl\Connector\Core\Exception\ConnectorException;
use JtlConnectorAdmin;

class TokenLoader implements ITokenLoader
{
    public function load()
    {
        $token = \get_option(JtlConnectorAdmin::OPTIONS_TOKEN, false);

        if ($token === false) {
            throw new ConnectorException(__('There was no token found.', TEXT_DOMAIN));
        }

        return $token;
    }
}
