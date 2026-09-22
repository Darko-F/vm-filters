<?php
/**
 * Run with the Joomla Event source checkout and VM 4.8.4 vrequest.php paths.
 * @license GNU General Public License version 2 or later; see ../LICENSE
 */
namespace VirtueMart {
    function vmSetStartTime(...$args) {}
}
namespace Joomla\CMS\Plugin {
    class CMSPlugin {
        private $application;
        public function setApplication($app) { $this->application = $app; }
        protected function getApplication() { return $this->application; }
    }
}
namespace {
    if (count($argv) !== 3) { exit("Usage: php tests/property_plugin.php JOOMLA_EVENT_ROOT VM_VREQUEST_PHP\n"); }
    define('_JEXEC', 1);
    define('JPATH_SITE', sys_get_temp_dir() . '/vmfp-plugin-' . getmypid());
    define('JPATH_ADMINISTRATOR', JPATH_SITE . '/administrator');
    mkdir(JPATH_SITE . '/modules', 0777, true);
    mkdir(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers', 0777, true);
    symlink(realpath(__DIR__ . '/../mod_vm_smartfilters'), JPATH_SITE . '/modules/mod_vm_smartfilters');
    $config = JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
    file_put_contents($config, '<?php');
    register_shutdown_function(static function () use ($config) {
        unlink(JPATH_SITE . '/modules/mod_vm_smartfilters');
        unlink($config);
        foreach (['/modules', '/administrator/components/com_virtuemart/helpers', '/administrator/components/com_virtuemart', '/administrator/components', '/administrator', ''] as $path) {
            rmdir(JPATH_SITE . $path);
        }
    });
    class VmConfig { public static function loadConfig() {} }
    spl_autoload_register(static function ($class) use ($argv) {
        $prefix = 'Joomla\\Event\\';
        if (str_starts_with($class, $prefix)) {
            require $argv[1] . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        }
    });
    require $argv[2];
    require __DIR__ . '/../plg_system_vmpropertyfilters/src/Extension/VmPropertyFilters.php';
    use Joomla\Event\Event;
    use VmFilters\Plugin\System\VmPropertyFilters\Extension\VmPropertyFilters;
    class Bag {
        public array $values = [];
        public function getArray() { return $this->values; }
    }
    class Input {
        public Bag $get;
        public array $values = ['option' => 'com_virtuemart', 'view' => 'category', 'virtuemart_category_id' => 9];
        public function __construct() { $this->get = new Bag(); }
        public function getCmd($name, $default = '') { return $this->values[$name] ?? $default; }
        public function getInt($name, $default = 0) { return (int) ($this->values[$name] ?? $default); }
        public function set($name, $value) { $this->values[$name] = $value; }
    }
    class App {
        public Input $input;
        public array $state = [];
        public string $client = 'site';
        public function __construct() { $this->input = new Input(); }
        public function getInput() { return $this->input; }
        public function getUserState($key, $default) { return $this->state[$key] ?? $default; }
        public function setUserState($key, $value) { $this->state[$key] = $value; }
        public function isClient($client) { return $this->client === $client; }
    }
    $checks = 0;
    function check($value, $message) {
        global $checks;
        if (!$value) { throw new \RuntimeException($message); }
        $checks++;
    }
    function prepare($app) {
        $plugin = new VmPropertyFilters();
        $plugin->setApplication($app);
        $plugin->prepare();
        return $plugin;
    }
    function query($plugin, $group = null, $category = 9) {
        $result = [];
        $plugin->identifyQuery(new Event('plgVmMySortSearchListProductsQuery', [&$result, '', true, $category, $group, 0, []]));
        $select = ['p.virtuemart_product_id'];
        $joins = [];
        $where = ['p.published = 1', 'pc.virtuemart_category_id = 9'];
        $groupBy = '';
        $orderBy = 'p.product_sku';
        $joinLang = false;
        $event = new Event('plgVmBeforeProductSearch', [&$select, &$joins, &$where, &$groupBy, &$orderBy, &$joinLang]);
        $plugin->filterQuery($event);
        return $where; // Verify original by-reference array, not merely the event copy.
    }
    $app = new App();
    $app->input->get->values['vmfp'] = ['15_product_width' => ['min' => '60', 'max' => '80', 'unit' => 'CM']];
    \VirtueMart\vRequest::setVar('custom_parent_id', 0);
    check(\VirtueMart\vRequest::getInt('custom_parent_id') === 0, 'Prime VM request cache');
    $plugin = prepare($app);
    check($app->input->getInt('custom_parent_id') === 1, 'Activate VM additive search hook');
    check(\VirtueMart\vRequest::getInt('custom_parent_id') === 1, 'Update actual VM cached request too');
    $where = query($plugin);
    check(count($where) > 2 && str_contains(implode(' ', $where), 'product_width'), 'Real Joomla Event propagates WHERE array back to VM');
    check(array_slice($where, 0, 2) === ['p.published = 1', 'pc.virtuemart_category_id = 9'], 'Preserve core restrictions');
    check(count(query($plugin, 'featured')) === 2, 'Do not filter featured module');
    check(count(query($plugin, null, 10)) === 2, 'Do not filter another category');
    $app->input->get->values = []; // Sorting/pagination URLs may omit our parameters.
    check(count(query(prepare($app))) > 2, 'Persist range across pagination/sorting');
    $app->input->values['virtuemart_category_id'] = 10;
    check(count(query(prepare($app), null, 10)) === 2, 'Changing category clears ranges');
    $app->input->values['virtuemart_category_id'] = 9;
    $app->input->get->values['vmfp'] = ['15_product_width' => ['min' => '80', 'max' => '60', 'unit' => 'CM']];
    check(in_array('1 = 0', query(prepare($app)), true), 'Invalid range produces no unfiltered results');
    $app->input->get->values['vmfp'] = [0 => ''];
    check(count(query(prepare($app))) === 2, 'Clear sentinel removes property state');
    $app->client = 'administrator';
    check(count(query(prepare($app))) === 2, 'Never alter administrator queries');
    echo "$checks plugin lifecycle checks passed using upstream Joomla Event and VM vRequest\n";
}
