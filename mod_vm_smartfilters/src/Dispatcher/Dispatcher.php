<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VmFilters\Module\SmartFilters\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use VmFilters\Module\SmartFilters\Site\Helper\FiltersHelper;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $data['filters'] = [];
        $data['error'] = '';
        $data['visible'] = FiltersHelper::isFilterPage($this->app, $data['params']);

        if (!$data['visible']) {
            $this->module->showtitle = 0;
            return $data;
        }

        try {
            $helper = new FiltersHelper(Factory::getContainer()->get(DatabaseInterface::class));
            $data = array_merge($data, $helper->getData($this->app, $data['params']));
            if (!$data['filters'] && !$data['propertyFilters'] && !$data['activeCount']
                && !(int) $data['params']->get('show_empty', 0)) {
                $data['visible'] = false;
                $this->module->showtitle = 0;
                return $data;
            }
            $assets = $this->app->getDocument()->getWebAssetManager();
            $assets->registerAndUseStyle('mod_vm_smartfilters', 'mod_vm_smartfilters/filters.css', ['version' => '1.1.5']);
            $assets->registerAndUseScript('mod_vm_smartfilters', 'mod_vm_smartfilters/filters.js', ['version' => '1.1.5'], ['defer' => true]);
        } catch (\Throwable $exception) {
            Log::add('VM Smart Filters: ' . $exception->getMessage(), Log::ERROR, 'mod_vm_smartfilters');
            $data['error'] = Text::_('MOD_VM_SMARTFILTERS_UNAVAILABLE');
        }

        return $data;
    }
}
