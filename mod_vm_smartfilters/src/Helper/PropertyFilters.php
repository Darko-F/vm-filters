<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VmFilters\Module\SmartFilters\Site\Helper;

defined('_JEXEC') or die;

/** Numeric properties use an explicit allowlist; request data never names SQL columns. */
final class PropertyFilters
{
    public const PROPERTIES = ['product_length', 'product_width', 'product_height', 'product_weight'];
    public const DIMENSIONS = ['M' => 100, 'CM' => 1, 'MM' => 0.1, 'YD' => 91.44, 'FT' => 30.48, 'IN' => 2.54];
    public const WEIGHTS = ['KG' => 1, 'G' => 0.001, 'MG' => 0.000001, 'LB' => 0.45359237, 'OZ' => 0.028349523125];

    public static function units(string $property): array
    {
        return $property === 'product_weight' ? self::WEIGHTS : self::DIMENSIONS;
    }

    public static function number($value): ?string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        $value = str_replace(',', '.', trim((string) $value));
        if (!preg_match('/^\d{1,13}(?:\.\d{1,8})?$/D', $value) || (float) $value > 1.0e12) {
            return null;
        }
        return $value;
    }

    /** Empty bounds are allowed. Malformed active filters fail closed, not silently ignored. */
    public static function normalize($input): array
    {
        $result = ['ranges' => [], 'invalid' => false];
        if (!is_array($input) || count($input) > 65) {
            $result['invalid'] = true;
            return $result;
        }
        foreach ($input as $key => $range) {
            if ((string) $key === '0') { continue; } // Explicit reset sentinel.
            if (!is_array($range)) { $result['invalid'] = true; continue; }
            $min = $range['min'] ?? '';
            $max = $range['max'] ?? '';
            if ($min === '' && $max === '') { continue; }
            if (!preg_match('/^([1-9]\d{0,8})_(product_length|product_width|product_height|product_weight)$/D', (string) $key, $match)) {
                $result['invalid'] = true;
                continue;
            }
            $property = $match[2];
            $unit = is_string($range['unit'] ?? null) ? strtoupper($range['unit']) : '';
            $minimum = $min === '' ? '' : self::number($min);
            $maximum = $max === '' ? '' : self::number($max);
            if (!isset(self::units($property)[$unit]) || $minimum === null || $maximum === null
                || ($minimum !== '' && $maximum !== '' && (float) $minimum > (float) $maximum)) {
                $result['invalid'] = true;
                continue;
            }
            $result['ranges'][$key] = ['id' => (int) $match[1], 'property' => $property, 'unit' => $unit, 'min' => $minimum, 'max' => $maximum];
        }
        return $result;
    }

    public static function decimal(float $number): string
    {
        return rtrim(rtrim(number_format($number, 12, '.', ''), '0'), '.');
    }

    /** Expression returns centimetres or kilograms. Unknown units never match. */
    public static function expression(string $property): string
    {
        if (!in_array($property, self::PROPERTIES, true)) {
            throw new \InvalidArgumentException('Unsupported product property');
        }
        $unitColumn = $property === 'product_weight' ? 'product_weight_uom' : 'product_lwh_uom';
        $sql = '(p.`' . $property . '` * CASE UPPER(TRIM(p.`' . $unitColumn . '`))';
        foreach (self::units($property) as $unit => $factor) {
            $sql .= " WHEN '" . $unit . "' THEN " . self::decimal($factor);
        }
        return $sql . ' ELSE NULL END)';
    }

    /** Build AND predicates, retaining VM's publication, category and shopper restrictions. */
    public static function predicates(array $state): array
    {
        if (!empty($state['invalid'])) { return ['1 = 0']; }
        // Revalidate stored state too; callers cannot bypass the property/unit allowlists.
        $state = self::normalize($state['ranges'] ?? []);
        if ($state['invalid']) { return ['1 = 0']; }
        $where = [];
        foreach ($state['ranges'] as $range) {
            $property = $range['property'];
            $expression = self::expression($property);
            $factor = self::units($property)[$range['unit']];
            // The particular public Property field must be assigned to this product.
            $where[] = 'EXISTS (SELECT 1 FROM #__virtuemart_product_customfields AS vmfp'
                . ' INNER JOIN #__virtuemart_customs AS vmfc ON vmfc.virtuemart_custom_id = vmfp.virtuemart_custom_id'
                . ' WHERE vmfp.virtuemart_product_id = p.virtuemart_product_id'
                . ' AND vmfp.virtuemart_custom_id = ' . $range['id']
                . " AND vmfc.field_type = 'P' AND vmfc.published = 1 AND vmfc.admin_only = 0 AND vmfc.is_hidden = 0"
                . " AND (vmfc.virtuemart_shoppergroup_id IS NULL OR vmfc.virtuemart_shoppergroup_id = '' OR vmfc.virtuemart_shoppergroup_id = '0')"
                . " AND TRIM(vmfp.customfield_value) = '" . $property . "')";
            $where[] = 'p.`' . $property . '` > 0'; // Zero is VM's unfilled measurement default.
            if ($range['min'] !== '') {
                $where[] = $expression . ' >= ' . self::decimal((float) $range['min'] * $factor);
            }
            if ($range['max'] !== '') {
                $where[] = $expression . ' <= ' . self::decimal((float) $range['max'] * $factor);
            }
        }
        return $where;
    }
}
