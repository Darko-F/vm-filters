<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VmFilters\Module\SmartFilters\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class FiltersHelper
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    /** The VM category layout also serves the shop homepage (category ID zero). */
    public static function isFilterPage(CMSApplicationInterface $app, Registry $params): bool
    {
        $input = $app->getInput();
        return $input->getCmd('option') === 'com_virtuemart'
            && $input->getCmd('view') === 'category'
            && ($input->getInt('virtuemart_category_id', 0) > 0
                || (bool) $params->get('show_shop_home', 0));
    }

    /** Only positive integer IDs can enter SQL; keep administrator ordering. */
    public static function parseIds(string $input): array
    {
        $ids = [];
        foreach (preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if (ctype_digit($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            }
        }
        return array_values(array_unique($ids));
    }

    /** VM consumes scalar customfields[id] values, never nested arrays. */
    public static function normalizeSelection($values): array
    {
        $clean = [];
        if (!is_array($values) && !is_object($values)) {
            return $clean;
        }
        foreach ((array) $values as $id => $value) {
            if (ctype_digit((string) $id) && (int) $id > 0 && is_scalar($value)) {
                $value = trim((string) $value);
                if ($value !== '' && $value !== '0') {
                    $clean[(int) $id] = $value;
                }
            }
        }
        return $clean;
    }

    public function getData(CMSApplicationInterface $app, Registry $params): array
    {
        $config = JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
        if (!is_file($config)) {
            throw new \RuntimeException('VirtueMart is not installed.');
        }
        require_once $config;
        \VmConfig::loadConfig();
        $modelClass = class_exists('VirtueMart\\VmModel') ? 'VirtueMart\\VmModel' : 'VmModel';
        $productModel = $modelClass::getModel('product');
        $groups = array_map('intval', $productModel::getCurrentUserShopperGrps());
        $groups = array_values(array_unique(array_filter($groups, static fn($id) => $id > 0)));
        $category = max(0, $app->getInput()->getInt('virtuemart_category_id', 0));
        $ids = self::parseIds((string) $params->get('field_ids', ''));
        $propertyPluginEnabled = PluginHelper::isEnabled('system', 'vmpropertyfilters');
        $propertiesEnabled = (int) $params->get('property_filters', 1) && $propertyPluginEnabled;
        $stored = $propertyPluginEnabled ? (array) $app->getUserState('mod_vm_smartfilters.properties', []) : [];
        $propertyState = PropertyFilters::normalize(($stored['category'] ?? -1) === $category ? ($stored['ranges'] ?? []) : []);
        $propertyState['invalid'] = $propertyState['invalid'] || (($stored['category'] ?? -1) === $category && !empty($stored['invalid']));

        // Read VM's effective state after the component has handled a category change.
        $selected = self::normalizeSelection($app->getUserState('com_virtuemart.customfields', []));
        $query = $this->db->getQuery(true)
            ->select('c.virtuemart_custom_id, c.custom_title, c.ordering, c.field_type, f.customfield_value')
            ->from('#__virtuemart_customs AS c')
            ->join('INNER', '#__virtuemart_product_customfields AS f ON f.virtuemart_custom_id = c.virtuemart_custom_id')
            ->join('INNER', '#__virtuemart_products AS p ON p.virtuemart_product_id = f.virtuemart_product_id')
            ->where('c.published = 1 AND c.admin_only = 0 AND c.is_hidden = 0')
            ->where("((c.field_type = 'S' AND c.searchable = 1)" . ($propertiesEnabled ? " OR c.field_type = 'P'" : '') . ')')
            ->where("(c.virtuemart_shoppergroup_id IS NULL OR c.virtuemart_shoppergroup_id = '' OR c.virtuemart_shoppergroup_id = '0')")
            ->where('p.published = 1')
            ->where("TRIM(f.customfield_value) <> '' AND TRIM(f.customfield_value) <> '0'")
            ->where('(NOT EXISTS (SELECT 1 FROM #__virtuemart_product_shoppergroups AS sg WHERE sg.virtuemart_product_id = p.virtuemart_product_id)'
                . ($groups ? ' OR EXISTS (SELECT 1 FROM #__virtuemart_product_shoppergroups AS sg WHERE sg.virtuemart_product_id = p.virtuemart_product_id AND sg.virtuemart_shoppergroup_id IN (' . implode(',', $groups) . '))' : '') . ')')
            ->group('c.virtuemart_custom_id, c.custom_title, c.ordering, c.field_type, f.customfield_value')
            ->order('c.ordering ASC, c.virtuemart_custom_id ASC, f.customfield_value ASC');
        if ($ids) {
            $query->where('c.virtuemart_custom_id IN (' . implode(',', $ids) . ')');
        } elseif (trim((string) $params->get('field_ids', '')) !== '') {
            // A malformed allowlist must not unexpectedly expose every field.
            $query->where('1 = 0');
        }
        if ($category && (int) $params->get('category_options', 1)) {
            $query->where('EXISTS (SELECT 1 FROM #__virtuemart_product_categories AS pc WHERE pc.virtuemart_product_id = p.virtuemart_product_id AND pc.virtuemart_category_id = ' . $category . ')');
        }
        $rows = $this->db->setQuery($query)->loadObjectList();
        $filters = [];
        $propertyFilters = [];
        foreach ($rows as $row) {
            $id = (int) $row->virtuemart_custom_id;
            $value = trim((string) $row->customfield_value);
            if (($row->field_type ?? 'S') === 'P') {
                if (!$propertiesEnabled || !in_array($value, PropertyFilters::PROPERTIES, true)
                    || !(int) $params->get('show_' . $value, 1)) { continue; }
                $key = $id . '_' . $value;
                $defaultUnit = $value === 'product_weight' ? 'KG' : 'CM';
                $unit = (string) $params->get($value === 'product_weight' ? 'weight_unit' : 'dimension_unit', $defaultUnit);
                if (!isset(PropertyFilters::units($value)[$unit])) { $unit = $defaultUnit; }
                $range = $propertyState['ranges'][$key] ?? ['min' => '', 'max' => '', 'unit' => $unit];
                $propertyFilters[$key] = ['id' => $id, 'key' => $key, 'title' => (string) $row->custom_title, 'property' => $value] + $range;
                continue;
            }
            // VM sanitises these characters before matching. Do not offer values
            // which its native search cannot reliably round-trip.
            if ($value === '' || $value === '0' || preg_match('/[<>&"\x00-\x1F]/u', $value) || str_contains($value, "'")) {
                continue;
            }
            if (!isset($filters[$id])) {
                $filters[$id] = ['id' => $id, 'title' => (string) $row->custom_title, 'values' => [], 'selected' => ''];
            }
            if (!in_array($value, $filters[$id]['values'], true)) {
                $filters[$id]['values'][] = $value;
            }
        }
        if ($ids) {
            $ordered = [];
            foreach ($ids as $id) {
                if (isset($filters[$id])) {
                    $ordered[$id] = $filters[$id];
                }
            }
            $filters = $ordered;
            uksort($propertyFilters, static function ($a, $b) use ($propertyFilters, $ids) {
                return array_search($propertyFilters[$a]['id'], $ids, true) <=> array_search($propertyFilters[$b]['id'], $ids, true);
            });
        }
        foreach ($filters as $id => &$filter) {
            if (isset($selected[$id]) && in_array($selected[$id], $filter['values'], true)) {
                $filter['selected'] = $selected[$id];
            }
        }
        unset($filter);

        $hidden = [
            'option' => 'com_virtuemart', 'view' => 'category',
            'virtuemart_category_id' => $category,
            'Itemid' => max(0, $app->getInput()->getInt('Itemid', 0)),
            'limitstart' => 0, 'start' => 0, 'search' => 'true',
            'searchAllCats' => 0, 'combineTags' => 1,
            // Non-empty array sentinel replaces session filters even when clearing.
            'customfields[0]' => '',
            'vmfp[0]' => '',
        ];
        $manufacturer = max(0, $app->getInput()->getInt('virtuemart_manufacturer_id', 0));
        if ($manufacturer) {
            $hidden['virtuemart_manufacturer_id'] = $manufacturer;
        }
        // Read the original query bag: VM URL-encodes the main input's keyword
        // while building the product model. Reusing it would corrupt spaces/+.
        $keyword = $app->getInput()->get->getString('keyword', '');
        if ($keyword !== '') {
            $hidden['keyword'] = $keyword;
        }
        $language = $app->getInput()->getCmd('lang', '');
        if ($language !== '') {
            $hidden['lang'] = $language;
        }
        $visibleCount = count(array_filter(array_column($filters, 'selected'), static fn($v) => $v !== ''));
        $activeCount = count($selected);
        $visibleProperties = count(array_intersect_key($propertyState['ranges'], $propertyFilters));
        $activeProperties = count($propertyState['ranges']);

        return [
            'filters' => $filters, 'hidden' => $hidden,
            // GET + a bare index.php avoids SEF query loss and preserves subfolders.
            'action' => Uri::root(true) . '/index.php',
            'clearUrl' => Uri::root(true) . '/index.php?' . http_build_query($hidden, '', '&', PHP_QUERY_RFC3986),
            'propertyFilters' => $propertyFilters,
            'propertyInvalid' => $propertyState['invalid'],
            'activeCount' => $activeCount + $activeProperties + (int) $propertyState['invalid'],
            'otherCount' => $activeCount - $visibleCount + $activeProperties - $visibleProperties,
        ];
    }
}
