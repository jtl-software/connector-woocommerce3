<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

$loader = require_once(__DIR__ . '/../vendor/autoload.php');
$loader->add('', CONNECTOR_DIR . '/plugins');

use jtl\Connector\Application\Application;
use jtl\Connector\WooCommerce\Connector;

/** @var Connector $connector */
$connector = Connector::getInstance();
/** @var Application $application */
$application = Application::getInstance();

$application->register($connector);
$application->run();
