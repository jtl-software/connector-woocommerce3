<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Traits;

use jtl\Connector\Core\Rpc\Error;
use jtl\Connector\Formatter\ExceptionFormatter;
use jtl\Connector\Result\Action;
use JtlWooCommerceConnector\Logger\ControllerLogger;

trait BaseControllerTrait
{
    /**
     * This method has to be called if an exception occurred in one of the actions.
     * At first the exception is logged. In the second step an error object is built and passed to the action which
     * is returned to the host.
     *
     * @param \Exception $exc The caught exception.
     * @param Action $action  The action for which the error has to be set.
     */
    protected function handleException($exc, &$action)
    {
        ControllerLogger::getInstance()->writeLog(ExceptionFormatter::format($exc));

        $err = new Error();
        $err->setCode($exc->getCode());
        $err->setMessage($exc->getFile() . ' (' . $exc->getLine() . '):' . $exc->getMessage());
        $action->setError($err);
    }
}
