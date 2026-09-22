<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class mod_vm_smartfiltersInstallerScript
{
    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') {
            return true;
        }
        if (version_compare(JVERSION, '4.4.0', '<') || version_compare(PHP_VERSION, '8.1.0', '<')) {
            Factory::getApplication()->enqueueMessage('VM Smart Filters requires Joomla 4.4 or later and PHP 8.1 or later.', 'error');
            return false;
        }
        $path = JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.xml';
        if (!is_file($path)) {
            $path = JPATH_ADMINISTRATOR . '/components/com_virtuemart/com_virtuemart.xml';
        }
        $xml = is_file($path) ? simplexml_load_file($path) : false;
        if (!$xml || version_compare((string) $xml->version, '4.8.4', '<')) {
            Factory::getApplication()->enqueueMessage('Install VirtueMart 4.8.4 or later before installing VM Smart Filters.', 'error');
            return false;
        }
        return true;
    }
}
