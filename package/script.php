<?php
/**
 * @package    VM Smart Filters
 * @copyright  Copyright (C) 2026 topoweryou.com
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class pkg_vm_smartfiltersInstallerScript
{
    private bool $pluginExists = false;
    private bool $keyPluginExists = false;

    public function preflight($type, $parent): bool
    {
        if ($type === 'uninstall') { return true; }
        if (version_compare(JVERSION, '4.4.0', '<') || version_compare(PHP_VERSION, '8.1.0', '<')) {
            Factory::getApplication()->enqueueMessage('VM Smart Filters requires Joomla 4.4+ and PHP 8.1+.', 'error');
            return false;
        }
        $path = JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.xml';
        if (!is_file($path)) { $path = JPATH_ADMINISTRATOR . '/components/com_virtuemart/com_virtuemart.xml'; }
        $xml = is_file($path) ? simplexml_load_file($path) : false;
        if (!$xml || version_compare((string) $xml->version, '4.8.4', '<')) {
            Factory::getApplication()->enqueueMessage('Install VirtueMart 4.8.4 or later first.', 'error');
            return false;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->select('extension_id')->from('#__extensions')
            ->where("type = 'plugin' AND folder = 'system' AND element = 'vmpropertyfilters'");
        $this->pluginExists = (bool) $db->setQuery($query)->loadResult();
        $query = $db->getQuery(true)->select('extension_id')->from('#__extensions')
            ->where("type = 'plugin' AND folder = 'installer' AND element = 'vmsmartfiltersupdatekey'");
        $this->keyPluginExists = (bool) $db->setQuery($query)->loadResult();
        return true;
    }

    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') { return; }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (!$this->pluginExists) {
            $query = $db->getQuery(true)->update('#__extensions')->set('enabled = 1')
                ->where("type = 'plugin' AND folder = 'system' AND element = 'vmpropertyfilters'");
            $db->setQuery($query)->execute();
        }
        if (!$this->keyPluginExists) {
            $query = $db->getQuery(true)->update('#__extensions')->set('enabled = 1')
                ->where("type = 'plugin' AND folder = 'installer' AND element = 'vmsmartfiltersupdatekey'");
            $db->setQuery($query)->execute();
        }
        // Package owns updates from 1.1.0. Detach only this module's old feed;
        // keep the site record so an administrator's old download key is not destroyed.
        $query = $db->getQuery(true)->select('extension_id')->from('#__extensions')
            ->where("type = 'module' AND client_id = 0 AND element = 'mod_vm_smartfilters'");
        $moduleId = (int) $db->setQuery($query)->loadResult();
        $query = $db->getQuery(true)->select('update_site_id')->from('#__update_sites')
            ->where('location = ' . $db->quote('https://shop.topoweryou.com/files/updatesxml/vm-smartfilters.xml'));
        $siteIds = array_map('intval', $db->setQuery($query)->loadColumn());
        if ($moduleId && $siteIds) {
            $query = $db->getQuery(true)->delete('#__update_sites_extensions')
                ->where('extension_id = ' . $moduleId)->where('update_site_id IN (' . implode(',', $siteIds) . ')');
            $db->setQuery($query)->execute();
            $query = $db->getQuery(true)->delete('#__updates')->where('extension_id = ' . $moduleId);
            $db->setQuery($query)->execute();
        }
        Factory::getApplication()->enqueueMessage('VM Smart Filters installed. For protected updates, enter your Download Key in Update Sites > VM Smart Filters Package Updates.', 'message');
    }
}
