# VM Smart Filters update service

This project follows the existing BillHelper/Transport Accounting setup on **shop.topoweryou.com**. It does not use GitHub as the Joomla update server.

## Publish version 1.1.8

1. Run `python3 scripts/build.py` to generate the ZIP, SHA-256 file and feed together.
2. In VM Update Key Manager, configure the new VM Smart Filters extension/product entitlement and register the exact filename `pkg_vm_smartfilters_v1.1.8.zip`. Upload `dist/pkg_vm_smartfilters_v1.1.8.zip` to the configured protected downloads storage. Do not reuse BillHelper's product entitlement or filename.
3. Publish `updates/vm-smartfilters-package.xml` at `/files/updatesxml/vm-smartfilters-package.xml` on `shop.topoweryou.com`. This public XML contains no customer key.
4. Verify the feed returns HTTP 200 and XML. Test the feed's download URL with a valid test key and confirm the ZIP's SHA-256 matches the feed. Verify invalid/expired keys are handled by the existing server policy. Do not commit keys or URLs containing them.
5. Install the complete 1.1.8 package on staging, enter the test key in **System → Update Sites → VM Smart Filters Package Updates → Download Key**, and test a subsequent package update through Joomla. Verify both the module and plugin versions and filtering behavior.

The manifest contains `<dlid prefix="key=" suffix="" />`. Joomla 4+ appends the administrator's Download Key to this service's `download.get` URL. The bundled Installer - VM Smart Filters Update Key plugin supplies the current site domain for domain-limited licences. Keep it enabled. Customers may enter their key there or in the Update Sites Download Key field.

The build generates the update filename, version, supported Joomla versions, PHP minimum and SHA-256 from the same package. Deploy the ZIP first and feed last. Retain previous releases for rollback; do not publish a feed pointing at an unavailable package.

Server deployment and a real paid-download/update test have not been performed by the local build. They require access to the shop's VM Update Key Manager and hosting storage.

## Migration from 1.0.x

Version 1.1.0 added a required system plugin for numeric filters, so updates now belong to `pkg_vm_smartfilters`. Existing 1.0.x installations must install the complete 1.1.8 package once through Joomla's upload installer. The package preserves module settings, enables the new plugin, and detaches the old module update-feed association. Enter the Download Key in the new **VM Smart Filters Package Updates** site.

The old `updates/vm-smartfilters.xml` is intentionally retained at 1.0.1 for old module installations. Do not change its module entry to a package entry or distribute the inner module ZIP by itself. Publish the new package feed separately on the same update service.

Existing 1.1.x package installations can install 1.1.8 normally. Version 1.1.8 removes the outer filter card border and refresh instruction, replaces the visible heading with a filter icon and moves the clear-all icon to the right. The bottom divider and accessible labels remain.

## VM Update Key Manager 1.2.2 product mapping

Reference: https://builder.topoweryou.com/vmupdatekeymanager/documentation

Create a published Plugin custom field using VirtueMart Update Key Manager. Set Cart Attribute to Yes, Cart Input to No and Administrator only to No. Choose Maximum update domains on this reusable field (0 unlimited, 1 single site, or your chosen number). Assign it once to the VM Smart Filters product with these values:

| Setting | Value |
| --- | --- |
| Download type | Extension package (ZIP + updates) |
| Protected product folder | vm-smartfilters |
| Package prefix | pkg_vm_smartfilters_v |
| Update XML filename | vm-smartfilters-package.xml |
| Renewal product URL | Leave blank to use the product URL, or enter its actual URL |

With the default protected base folder, upload the final ZIP to `SAFE_PATH/salefiles/vm-smartfilters/pkg_vm_smartfilters_v1.1.8.zip`. The `_v` prefix is required for the manager's release discovery. Previous 1.1.7 and older local filenames do not follow that convention; do not rename any file already tied to a paid purchase. Keep historical purchase files in their original storage.

Use the manager's **Publish update XML files** action after uploading. Its generated feed targets Joomla 5/6; the local build feed also supports Joomla 4.4, which this filter package supports. Use one publishing workflow consistently. The selling shop running Update Key Manager requires Joomla 5/6 independently of the customer's filter installation.

Set the paid order statuses, licence duration, initial download limit and renewal terms in the manager before launch. Test a paid staging order and verify the email, initial download, automatic update with a valid key, and domain-limit enforcement on a second site. The key controls paid downloads and updates; installed filtering continues after the update entitlement expires.

Version 1.1.8 adds the third child, Installer - VM Smart Filters Update Key, and enables it on first installation. Existing plugin key settings and administrator enablement choices are preserved on upgrades. The plugin accepts only HTTPS updates at the exact shop endpoint and matching package prefix.
