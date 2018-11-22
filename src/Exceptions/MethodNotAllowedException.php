<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Exceptions;

class MethodNotAllowedException extends \Exception
{
    public function __construct()
    {
        parent::__construct("This method isn't allowed to be called. The Wawi handles the data.");
    }
}
