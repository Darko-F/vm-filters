<?php
/** Isolated contract tests; no live Joomla or database is simulated as certification. */
namespace Joomla\Database {
    interface DatabaseInterface {}
}
namespace Joomla\CMS\Application {
    interface CMSApplicationInterface {}
}
namespace Joomla\CMS\Uri {
    class Uri { public static function root($pathOnly) { return '/shop'; } }
}
namespace Joomla\CMS\Language {
    class Text {
        public static function _($text) { return $text; }
        public static function sprintf($text, ...$args) { return $text . implode(',', $args); }
    }
}
namespace Joomla\CMS\Plugin {
    class PluginHelper {
        public static bool $enabled = true;
        public static function isEnabled($group, $name) { return self::$enabled; }
    }
}
namespace Joomla\Registry {
    class Registry {
        public function __construct(private array $values = []) {}
        public function get($key, $default = null) { return $this->values[$key] ?? $default; }
    }
}
namespace {
    define('_JEXEC', 1);
    define('JPATH_ADMINISTRATOR', sys_get_temp_dir() . '/vmfilters-test-' . getmypid());
    mkdir(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers', 0777, true);
    file_put_contents(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php', '<?php');
    register_shutdown_function(static function () {
        unlink(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
        rmdir(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers');
        rmdir(JPATH_ADMINISTRATOR . '/components/com_virtuemart');
        rmdir(JPATH_ADMINISTRATOR . '/components');
        rmdir(JPATH_ADMINISTRATOR);
    });
    class VmConfig { public static function loadConfig() {} }
    class VmModel { public static function getModel($name) { return new Product(); } }
    class Product { public static function getCurrentUserShopperGrps() { return [1, 2]; } }
    class Query {
        public array $clauses = [];
        public function __call($method, $arguments) { $this->clauses[] = [$method, $arguments]; return $this; }
        public function __toString() { return json_encode($this->clauses); }
    }
    class Database implements \Joomla\Database\DatabaseInterface {
        public Query $query;
        public array $rows = [];
        public function getQuery($new) { return new Query(); }
        public function quote($value) { return "'" . str_replace("'", "''", $value) . "'"; }
        public function setQuery($query) { $this->query = $query; return $this; }
        public function loadObjectList() { return $this->rows; }
    }
    class Input {
        public Input $get;
        public function __construct() { $this->get = $this; }
        public array $values = ['virtuemart_category_id' => 9, 'Itemid' => 42, 'keyword' => 'shirt'];
        public function getInt($key, $default = 0) { return (int) ($this->values[$key] ?? $default); }
        public function getString($key, $default = '') { return (string) ($this->values[$key] ?? $default); }
        public function getCmd($key, $default = '') { return (string) ($this->values[$key] ?? $default); }
    }
    class Application implements \Joomla\CMS\Application\CMSApplicationInterface {
        public Input $input;
        public array $state = [7 => 'Blue', 12 => 'Large'];
        public function __construct() { $this->input = new Input(); }
        public function getInput() { return $this->input; }
        public function getUserState($key, $default = null) { return $this->state; }
    }
    require __DIR__ . '/../mod_vm_smartfilters/src/Helper/PropertyFilters.php';
    require __DIR__ . '/../mod_vm_smartfilters/src/Helper/FiltersHelper.php';
    use VmFilters\Module\SmartFilters\Site\Helper\FiltersHelper;
    use Joomla\Registry\Registry;

    $checks = 0;
    function check($condition, $message) {
        global $checks;
        if (!$condition) { throw new \RuntimeException($message); }
        $checks++;
    }
    check(FiltersHelper::parseIds('12,7,12; -4, 0, 3 OR 1=1') === [12, 7, 3], 'IDs must be positive integers, unique and ordered');
    check(FiltersHelper::normalizeSelection([7 => ['nested'], 8 => '0', 9 => ' Blue ', 'bad' => 'Red']) === [9 => 'Blue'], 'Reject nested, zero and invalid keys');
    $db = new Database();
    foreach ([[7, '<script>Colour</script>', 'Blue'], [7, 'Colour', 'Blue'], [7, 'Colour', '0'], [7, 'Colour', 'A&B'], [12, 'Size', 'Small'], [12, 'Size', 'Large']] as [$id, $title, $value]) {
        $db->rows[] = (object) ['virtuemart_custom_id' => $id, 'custom_title' => $title, 'customfield_value' => $value];
    }
    $helper = new FiltersHelper($db);
    $app = new Application();
    $app->input->values['option'] = 'com_virtuemart';
    $app->input->values['view'] = 'category';
    check(FiltersHelper::isFilterPage($app, new Registry()), 'Selected category allows filters');
    $app->input->values['virtuemart_category_id'] = 0;
    check(!FiltersHelper::isFilterPage($app, new Registry()), 'Shop homepage hidden by default');
    check(!FiltersHelper::isFilterPage($app, new Registry(['show_empty' => 1])), 'Empty diagnostic cannot override homepage restriction');
    check(FiltersHelper::isFilterPage($app, new Registry(['show_shop_home' => 1])), 'Shop homepage can explicitly enable filters');
    $app->input->values['view'] = 'productdetails';
    check(!FiltersHelper::isFilterPage($app, new Registry(['show_shop_home' => 1])), 'Homepage option does not enable product detail filters');
    $app->input->values['option'] = 'com_content';
    $app->input->values['view'] = 'category';
    check(!FiltersHelper::isFilterPage($app, new Registry(['show_shop_home' => 1])), 'Homepage option excludes non-VM pages');
    $app->input->values['option'] = 'com_virtuemart';
    $app->input->values['virtuemart_category_id'] = 9;
    $params = new Registry(['field_ids' => '12,7']);
    $data = $helper->getData($app, $params);
    check(array_keys($data['filters']) === [12, 7], 'Configured field order');
    check($data['filters'][7]['values'] === ['Blue'], 'Deduplicate and omit values unsupported by VM');
    check($data['filters'][12]['selected'] === 'Large', 'Use VM effective session state');
    check($data['activeCount'] === 2, 'Active selection count');
    check(str_contains((string) $db->query, 'c.searchable = 1'), 'Searchable fields only');
    check(str_contains((string) $db->query, 'p.published = 1'), 'Published products only');
    check(str_contains((string) $db->query, 'c.admin_only = 0'), 'No admin-only fields');
    check(str_contains((string) $db->query, 'sg.virtuemart_shoppergroup_id IN (1,2)'), 'Product shopper-group visibility');
    check(str_contains((string) $db->query, 'pc.virtuemart_category_id = 9'), 'Current category options');
    check($data['hidden']['combineTags'] === 1, 'AND combination');
    check($data['hidden']['limitstart'] === 0 && $data['hidden']['start'] === 0, 'Reset pagination');
    check($data['action'] === '/shop/index.php', 'Subdirectory-safe action');
    parse_str(parse_url($data['clearUrl'], PHP_URL_QUERY), $clear);
    check($clear['customfields'] === [0 => ''], 'Clear sends non-empty filter array to replace session');
    check($clear['keyword'] === 'shirt' && $clear['virtuemart_category_id'] === '9', 'Clear retains shop context');
    $helper->getData($app, new Registry(['category_options' => 0]));
    check(!str_contains((string) $db->query, 'pc.virtuemart_category_id'), 'Optional catalog-wide options');
    $helper->getData($app, new Registry(['field_ids' => 'bad']));
    check(str_contains((string) $db->query, '1 = 0'), 'Invalid allowlist must fail closed');

    function render($data, $params, $id) {
        extract($data);
        $visible = true;
        $error = '';
        $module = (object) ['id' => $id];
        ob_start();
        require __DIR__ . '/../mod_vm_smartfilters/tmpl/default.php';
        return ob_get_clean();
    }
    $html = render($data, $params, 22);
    check(!str_contains($html, '<script>Colour'), 'Escape field titles');
    check(str_contains($html, '&lt;script&gt;Colour'), 'Display escaped title as text');
    check(str_contains($html, 'name="customfields[12]"'), 'Native VM request field names');
    check(str_contains($html, 'value="Large" selected'), 'Keep selected option visible');
    check(str_contains($html, 'id="vmfilters-22-12"'), 'Instance-scoped label IDs');
    check(str_contains($html, 'type="submit"'), 'No-JavaScript submission');
    check(str_contains($html, 'customfields%5B0%5D='), 'Clear URL contains native sentinel');
    check(str_contains($html, '&amp;view='), 'Escape URL attributes');
    $app->input->values['keyword'] = 'cotton shirt + blue';
    $app->input->values['lang'] = 'sl';
    $app->state[99] = 'Unavailable';
    $data = $helper->getData($app, $params);
    check($data['hidden']['keyword'] === 'cotton shirt + blue', 'Preserve keyword spaces and plus');
    check($data['hidden']['lang'] === 'sl', 'Preserve language');
    check($data['otherCount'] === 1 && $data['activeCount'] === 3, 'Report native selections outside controls');
    $db->rows = [];
    $data = $helper->getData($app, $params);
    check(str_contains(render($data, $params, 23), 'customfields%5B0%5D='), 'Offer clearing even when no options remain');
    $db->rows = [(object) ['virtuemart_custom_id' => 15, 'custom_title' => 'Width', 'field_type' => 'P', 'customfield_value' => 'product_width']];
    $data = $helper->getData($app, new Registry());
    check(isset($data['propertyFilters']['15_product_width']), 'Discover Property custom field');
    $html = render($data, new Registry(), 24);
    check(str_contains($html, 'name="vmfp[15_product_width][min]"'), 'Render minimum width bound');
    check(str_contains($html, 'name="vmfp[15_product_width][max]"'), 'Render maximum width bound');
    check(str_contains($html, 'value="CM"'), 'Default dimension filter unit');
    check(!str_contains($html, 'name="customfields[15]"'), 'Never send a Property field through native String matching');
    foreach (['length', 'width', 'height', 'weight'] as $offset => $property) {
        $db->rows[$offset] = (object) ['virtuemart_custom_id' => 20 + $offset, 'custom_title' => 'Properties', 'field_type' => 'P', 'customfield_value' => 'product_' . $property];
    }
    check(count($helper->getData($app, new Registry())['propertyFilters']) === 4, 'All four measurements enabled by default');
    foreach (['length', 'width', 'height', 'weight'] as $offset => $property) {
        $selection = new Registry(['show_product_' . $property => 0]);
        $selected = $helper->getData($app, $selection);
        check(count($selected['propertyFilters']) === 3 && !isset($selected['propertyFilters'][(20 + $offset) . '_product_' . $property]), 'Independently disable ' . $property);
        check(!str_contains(render($selected, $selection, 25), 'name="vmfp[' . (20 + $offset) . '_product_' . $property . '][min]"'), 'Disabled measurement absent from form: ' . $property);
    }
    check($helper->getData($app, new Registry(['property_filters' => 0]))['propertyFilters'] === [], 'Master switch disables all measurements');
    $horizontal = new Registry(['orientation' => 'horizontal']);
    $horizontalHtml = render($helper->getData($app, $horizontal), $horizontal, 26);
    check(substr_count($horizontalHtml, '<details ') === 4, 'Horizontal measurements use native expandable controls');
    check(str_contains($horizontalHtml, 'vm-smartfilters-horizontal'), 'Horizontal styling is scoped');
    check(str_contains($horizontalHtml, 'name="vmfp[23_product_weight][max]"'), 'Horizontal weight range preserves query contract');
    check(!str_contains(render($helper->getData($app, new Registry()), new Registry(), 27), '<details '), 'Vertical sidebar keeps visible fields');
    \Joomla\CMS\Plugin\PluginHelper::$enabled = false;
    check($helper->getData($app, new Registry())['propertyFilters'] === [], 'Hide Property controls if companion plugin disabled');
    echo "$checks PHP contract checks passed\n";
}
