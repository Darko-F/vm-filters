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
        $data['visible'] = $this->input->getCmd('option') === 'com_virtuemart'
            && $this->input->getCmd('view') === 'category';

        if (!$data['visible']) {
            return $data;
        }

        try {
            $helper = new FiltersHelper(Factory::getContainer()->get(DatabaseInterface::class));
            $data = array_merge($data, $helper->getData($this->app, $data['params']));
            $assets = $this->app->getDocument()->getWebAssetManager();
            $assets->registerAndUseStyle('mod_vm_smartfilters', 'mod_vm_smartfilters/filters.css', ['version' => '1.1.0']);
            $assets->registerAndUseScript('mod_vm_smartfilters', 'mod_vm_smartfilters/filters.js', ['version' => '1.1.0'], ['defer' => true]);
        } catch (\Throwable $exception) {
            Log::add('VM Smart Filters: ' . $exception->getMessage(), Log::ERROR, 'mod_vm_smartfilters');
            $data['error'] = Text::_('MOD_VM_SMARTFILTERS_UNAVAILABLE');
        }

        return $data;
    }
}
