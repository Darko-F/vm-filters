<?php
/**
 * @copyright Copyright (C) 2026 topoweryou.com
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

final class PlgInstallerVmsmartfiltersupdatekey extends CMSPlugin
{
    public function onInstallerBeforePackageDownload(&$url, &$headers = []): bool
    {
        $event = is_object($url) && method_exists($url, 'getUrl') ? $url : null;
        if ($event !== null) { $url = $event->getUrl(); }
        if (!is_string($url) || $url === '') { return true; }
        $uri = new Uri($url);
        if (strtolower((string) $uri->getScheme()) !== 'https'
            || strtolower((string) $uri->getHost()) !== 'shop.topoweryou.com'
            || $uri->getPath() !== '/index.php'
            || $uri->getVar('option') !== 'com_vmupdatekeymanager'
            || $uri->getVar('task') !== 'download.get'
            || $uri->getVar('channel') !== 'update'
            || preg_match('/^pkg_vm_smartfilters_v[0-9]+\.[0-9]+\.[0-9]+\.zip$/D', (string) $uri->getVar('file', '')) !== 1) {
            return true;
        }
        $key = trim((string) $this->params->get('download_key', ''));
        if ((string) $uri->getVar('key', '') === '' && (string) $uri->getVar('dlid', '') === '' && $key !== '') {
            $uri->setVar('key', $key);
        }
        $domain = strtolower(rtrim((string) (new Uri(Uri::root()))->getHost(), '.'));
        if ($domain !== '') { $uri->setVar('domain', $domain); }
        $url = (string) $uri;
        if ($event !== null && method_exists($event, 'updateUrl')) { $event->updateUrl($url); }
        return true;
    }
}
