<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use VmFilters\Plugin\System\VmPropertyFilters\Extension\VmPropertyFilters;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container) {
            $plugin = new VmPropertyFilters($container->get(DispatcherInterface::class), (array) PluginHelper::getPlugin('system', 'vmpropertyfilters'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
