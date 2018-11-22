<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Exceptions;

class DataAlreadyFetchedException extends \Exception
{
    public function __construct()
    {
        parent::__construct("This method do not has to be called as the required data is already there. Please check
        which database calls are already made");
    }
}
