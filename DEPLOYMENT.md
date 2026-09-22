# VM Smart Filters update service

This project follows the existing BillHelper/Transport Accounting setup on **shop.topoweryou.com**. It does not use GitHub as the Joomla update server.

## Publish version 1.1.6

1. Run `python3 scripts/build.py` to generate the ZIP, SHA-256 file and feed together.
2. In VM Update Key Manager, configure the new VM Smart Filters extension/product entitlement and register the exact filename `pkg_vm_smartfilters-1.1.6.zip`. Upload `dist/pkg_vm_smartfilters-1.1.6.zip` to the configured protected downloads storage. Do not reuse BillHelper's product entitlement or filename.
3. Publish `updates/vm-smartfilters-package.xml` at `/files/updatesxml/vm-smartfilters-package.xml` on `shop.topoweryou.com`. This public XML contains no customer key.
4. Verify the feed returns HTTP 200 and XML. Test the feed's download URL with a valid test key and confirm the ZIP's SHA-256 matches the feed. Verify invalid/expired keys are handled by the existing server policy. Do not commit keys or URLs containing them.
5. Install the complete 1.1.6 package on staging, enter the test key in **System → Update Sites → VM Smart Filters Package Updates → Download Key**, and test a subsequent package update through Joomla. Verify both the module and plugin versions and filtering behavior.

The manifest contains `<dlid prefix="key=" suffix="" />`. Joomla 4+ appends the administrator's Download Key to this service's `download.get` URL. No extra key-injection plugin is required.

The build generates the update filename, version, supported Joomla versions, PHP minimum and SHA-256 from the same package. Deploy the ZIP first and feed last. Retain previous releases for rollback; do not publish a feed pointing at an unavailable package.

Server deployment and a real paid-download/update test have not been performed by the local build. They require access to the shop's VM Update Key Manager and hosting storage.

## Migration from 1.0.x

Version 1.1.0 added a required system plugin for numeric filters, so updates now belong to `pkg_vm_smartfilters`. Existing 1.0.x installations must install the complete 1.1.6 package once through Joomla's upload installer. The package preserves module settings, enables the new plugin, and detaches the old module update-feed association. Enter the Download Key in the new **VM Smart Filters Package Updates** site.

The old `updates/vm-smartfilters.xml` is intentionally retained at 1.0.1 for old module installations. Do not change its module entry to a package entry or distribute the inner module ZIP by itself. Publish the new package feed separately on the same update service.

Existing 1.1.x package installations can install 1.1.6 normally. Version 1.1.6 moves Clear filters above the controls, removes the bottom action bar and range instruction, and retains per-filter apply actions for numeric ranges, manual mode and JavaScript-disabled browsing.
