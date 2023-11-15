<?php

namespace Wordpress;

use DI\Container;
use Jtl\Connector\Core\Plugin\PluginInterface;
use Noodlehaus\ConfigInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Bootstrap implements PluginInterface
{
	public function registerListener(ConfigInterface $config, Container $container, EventDispatcher $dispatcher)
	{
		do_action('jtlwoo_events_listener',$config, $container, $dispatcher);
	}
}
