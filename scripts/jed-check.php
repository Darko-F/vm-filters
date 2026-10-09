<?php
/**
 * CLI adapter for the unmodified, released JED Checker 2.4.4 rules.
 * @license GNU General Public License version 2 or later; see ../LICENSE
 * This is an isolated static scan, not a Joomla installation test.
 * Usage: php scripts/jed-check.php /path/to/jedchecker-2.4.4 /path/to/extracted-zip
 */
namespace Joomla\Registry {
    class Registry {
        private array $data = [];
        public function __construct($unused = null) {}
        public function loadObject($data) { $this->data = (array) $data; }
        public function get($key, $default = null) { return $this->data[$key] ?? $default; }
    }
}
namespace {
    if (PHP_SAPI !== 'cli') { exit(1); }
    if (count($argv) !== 3) { exit("Usage: php scripts/jed-check.php CHECKER_ROOT EXTRACTED_ZIP\n"); }
    define('_JEXEC', 1);
    define('JVERSION', '4.4.0');
    define('JPATH_ROOT', realpath($argv[1]));
    define('JPATH_ADMINISTRATOR', JPATH_ROOT . '/administrator');
    define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_jedchecker');
    $target = realpath($argv[2]);
    if (!$target || !is_file(JPATH_COMPONENT_ADMINISTRATOR . '/models/rule.php')) { exit("Invalid paths\n"); }
    class JObject {
        public function __construct($properties = null) {
            foreach ((array) $properties as $key => $value) { $this->$key = $value; }
        }
        public function get($key, $default = null) { return $this->$key ?? $default; }
        public function set($key, $value) { $this->$key = $value; }
    }
    class JFolder {
        public static function files($path, $filter = '.', $recurse = false, $full = false, $exclude = [], $excludeFilter = []) {
            return self::scan($path, $filter, $recurse, $full, false);
        }
        public static function folders($path, $filter = '.', $recurse = false, $full = false, $exclude = [], $excludeFilter = []) {
            return self::scan($path, $filter, $recurse, $full, true);
        }
        private static function scan($path, $filter, $recurse, $full, $dirs) {
            if (!is_dir($path)) { return []; }
            $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $iterator = $recurse ? new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST) : $directory;
            $result = [];
            foreach ($iterator as $file) {
                if ($file->isDir() === $dirs && preg_match('#' . str_replace('#', '\\#', $filter) . '#', $file->getFilename())) {
                    $result[] = $full ? $file->getPathname() : $file->getFilename();
                }
            }
            sort($result);
            return $result;
        }
    }
    class JFilterInput {
        public static function getInstance() { return new self(); }
        public function clean($value, $type) { return preg_replace('/[^A-Za-z0-9_.-]/', '', $value); }
    }
    class ScanLanguage {
        private array $strings = [];
        public function _($key, ...$unused) { return $this->strings[$key] ?? $key; }
        protected function loadLanguage($file, $extension) {
            $this->strings = array_merge($this->strings, parse_ini_file($file, false, INI_SCANNER_RAW) ?: []);
            return true;
        }
    }
    class JFactory {
        public static function getLanguage() { static $language; return $language ??= new ScanLanguage(); }
    }
    class JText {
        public static function _($key, ...$unused) { return JFactory::getLanguage()->_($key); }
        public static function sprintf($key, ...$args) {
            $text = self::_($key);
            return str_contains($text, '%') ? sprintf($text, ...$args) : $text . ': ' . implode(', ', $args);
        }
    }
    class_alias(JObject::class, 'Joomla\\CMS\\Object\\CMSObject');
    class_alias(JFolder::class, 'Joomla\\CMS\\Filesystem\\Folder');
    class_alias(JFilterInput::class, 'Joomla\\CMS\\Filter\\InputFilter');
    class_alias(JText::class, 'Joomla\\CMS\\Language\\Text');
    class_alias(JFactory::class, 'Joomla\\CMS\\Factory');
    $translation = JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_jedchecker.ini';
    if (is_file($translation)) {
        $method = new \ReflectionMethod(ScanLanguage::class, 'loadLanguage');
        $method->setAccessible(true);
        $method->invoke(JFactory::getLanguage(), $translation, 'com_jedchecker');
    }
    $results = [];
    $failures = 0;
    foreach (glob(JPATH_COMPONENT_ADMINISTRATOR . '/libraries/rules/*.php') as $file) {
        require_once $file;
        $class = 'JedcheckerRules' . basename($file, '.php');
        $rule = new $class(['basedir' => $target]);
        $rule->check();
        $report = $rule->get('report')->get('data');
        $counts = (array) $report['count'];
        $failures += $counts['error'] + $counts['warning'] + $counts['compatibility'];
        $results[basename($file, '.php')] = $report;
        printf("%-16s errors=%d warnings=%d compatibility=%d notices=%d\n", basename($file, '.php'), $counts['error'], $counts['warning'], $counts['compatibility'], $counts['notice']);
        foreach (['error', 'warning', 'compatibility', 'notice'] as $level) {
            foreach ($report[$level] as $item) {
                echo "  $level $item->location:$item->line $item->text\n";
            }
        }
    }
    $destination = __DIR__ . '/../tests/jed-report.json';
    $encoded = json_encode(['checker' => '2.4.4', 'mode' => 'Unmodified upstream rules with CLI Joomla-service adapters', 'rules' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($destination, str_replace($target, '<package>', $encoded) . "\n");
    exit($failures ? 1 : 0);
}
