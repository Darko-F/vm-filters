<?php
/** @license GNU General Public License version 2 or later; see ../LICENSE */
define('_JEXEC', 1);
require __DIR__ . '/../mod_vm_smartfilters/src/Helper/PropertyFilters.php';
use VmFilters\Module\SmartFilters\Site\Helper\PropertyFilters as P;
$checks = 0;
function check($value, $message) {
    global $checks;
    if (!$value) { throw new RuntimeException($message); }
    $checks++;
}
$range = static fn($min, $max, $unit = 'CM') => ['min' => $min, 'max' => $max, 'unit' => $unit];
$state = P::normalize(['15_product_width' => $range('60', '80'), '16_product_weight' => $range('', '10', 'KG')]);
check(!$state['invalid'] && count($state['ranges']) === 2, 'Two independent properties');
check(P::normalize(['0' => '']) === ['ranges' => [], 'invalid' => false], 'Explicit clear sentinel');
check(P::normalize(['15_product_width' => $range('', '')])['ranges'] === [], 'Empty range is inactive');
check(P::normalize(['15_product_width' => $range('1,5', '2,5')])['ranges']['15_product_width']['min'] === '1.5', 'Decimal comma');
foreach (['-1', '1e3', 'NaN', 'Infinity', '1 OR 1=1', '1; DROP TABLE x', [], '1000000000001'] as $invalid) {
    check(P::normalize(['15_product_width' => $range($invalid, '')])['invalid'], 'Reject malformed numeric input');
}
check(P::normalize(['15_product_width' => $range('80', '60')])['invalid'], 'Reject reversed ranges');
check(P::normalize(['15_product_width' => $range('60', '80', 'KG')])['invalid'], 'Reject unit of wrong dimension');
check(P::normalize(['15_product_price' => $range('1', '2')])['invalid'], 'Reject unapproved SQL column');
check(P::normalize(['15_product_width' => 'bad'])['invalid'], 'Reject scalar range');
check(P::normalize('bad')['invalid'], 'Reject scalar input');
check(P::predicates(['ranges' => [], 'invalid' => true]) === ['1 = 0'], 'Invalid state fails closed');
$sql = implode(' AND ', P::predicates($state));
check(str_contains($sql, "vmfp.virtuemart_custom_id = 15"), 'Require matching field assignment');
check(str_contains($sql, "vmfc.field_type = 'P'"), 'Require Property definition');
check(str_contains($sql, "vmfc.admin_only = 0"), 'Respect field visibility');
check(str_contains($sql, 'ELSE NULL END'), 'Unrecognized units never silently treated as centimetres');
check(str_contains($sql, "WHEN 'M' THEN 100"), 'Metres to centimetres');
check(str_contains($sql, "WHEN 'G' THEN 0.001"), 'Grams to kilograms');
check(str_contains($sql, "WHEN 'IN' THEN 2.54"), 'Inches to centimetres');
check(str_contains($sql, "WHEN 'LB' THEN 0.45359237"), 'Pounds to kilograms');
check(P::decimal(0.000001) === '0.000001', 'Small unit factor is not scientific notation');
check(P::decimal(0.0) === '0', 'Zero bound is retained');
if (($argv[1] ?? '') === '--sql') {
    echo json_encode(['combined' => P::predicates($state), 'inch' => P::predicates(P::normalize(['15_product_width' => $range('1', '1', 'IN')])), 'invalid' => P::predicates(['ranges' => [], 'invalid' => true])]);
} else {
    echo "$checks property validation and conversion checks passed\n";
}
