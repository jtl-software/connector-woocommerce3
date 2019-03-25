<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */


use jtl\Connector\Application\Application;
use JtlWooCommerceConnector\Connector;

/** @var Connector $connector */
$connector = Connector::getInstance();
/** @var Application $application */
$application = Application::getInstance();

$application->register($connector);
$application->run();
