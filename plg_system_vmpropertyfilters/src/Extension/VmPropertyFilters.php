<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VmFilters\Plugin\System\VmPropertyFilters\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use VmFilters\Module\SmartFilters\Site\Helper\PropertyFilters;

final class VmPropertyFilters extends CMSPlugin implements SubscriberInterface
{
    private array $state = ['ranges' => [], 'invalid' => false];
    private bool $categoryQuery = false;
    private bool $active = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute' => 'prepare',
            'plgVmMySortSearchListProductsQuery' => 'identifyQuery',
            'plgVmBeforeProductSearch' => 'filterQuery',
        ];
    }

    public function prepare(): void
    {
        $app = $this->getApplication();
        $input = $app->getInput();
        if (!$app->isClient('site') || $input->getCmd('option') !== 'com_virtuemart' || $input->getCmd('view') !== 'category') {
            return;
        }
        $service = JPATH_SITE . '/modules/mod_vm_smartfilters/src/Helper/PropertyFilters.php';
        if (!is_file($service)) { return; }
        require_once $service;
        $category = max(0, $input->getInt('virtuemart_category_id', 0));
        $key = 'mod_vm_smartfilters.properties';
        $saved = (array) $app->getUserState($key, []);
        $query = $input->get->getArray();
        if (array_key_exists('vmfp', $query)) {
            $this->state = PropertyFilters::normalize($query['vmfp']);
        } elseif (($saved['category'] ?? -1) === $category) {
            $this->state = PropertyFilters::normalize($saved['ranges'] ?? []);
            $this->state['invalid'] = $this->state['invalid'] || !empty($saved['invalid']);
        }
        $app->setUserState($key, $this->state + ['category' => $category]);
        $this->active = $this->state['invalid'] || count($this->state['ranges']) > 0;
        if ($this->active && !$input->getInt('custom_parent_id', 0)) {
            // VM 4.8.4 gates its additive WHERE hook on this request value.
            // Keep any other plugin's nonzero value; no custom field is modified.
            $input->set('custom_parent_id', 1);
            $config = JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
            if (is_file($config)) {
                require_once $config;
                \VmConfig::loadConfig();
                $request = class_exists('VirtueMart\\vRequest') ? 'VirtueMart\\vRequest' : 'vRequest';
                if (class_exists($request)) { $request::setVar('custom_parent_id', 1); }
            }
        }
    }

    public function identifyQuery(Event $event): void
    {
        $args = $event->getArguments();
        $group = $args[4] ?? null;
        $category = $args[3] ?? null;
        $current = $this->getApplication()->getInput()->getInt('virtuemart_category_id', 0);
        // Leave featured, related, latest and other module queries alone.
        $this->categoryQuery = $this->active && empty($group)
            && ($category === null || (!is_array($category) && (int) $category === $current));
    }

    public function filterQuery(Event $event): void
    {
        if (!$this->categoryQuery) { return; }
        $where = $event->getArgument(2);
        if (!is_array($where)) { return; }
        $where = array_merge($where, PropertyFilters::predicates($this->state));
        // VM passes its SQL arrays by reference through Joomla's generic event.
        $event->setArgument(2, $where);
    }
}
