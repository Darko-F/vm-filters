# VM Smart Filters update service

This project follows the existing BillHelper/Transport Accounting setup on **shop.topoweryou.com**. It does not use GitHub as the Joomla update server.

## Publish version 1.0.1

1. Run `python3 scripts/build.py` to generate the ZIP, SHA-256 file and feed together.
2. In VM Update Key Manager, configure the new VM Smart Filters extension/product entitlement and register the exact filename `mod_vm_smartfilters-1.0.1.zip`. Upload `dist/mod_vm_smartfilters-1.0.1.zip` to the configured protected downloads storage. Do not reuse BillHelper's product entitlement or filename.
3. Publish `updates/vm-smartfilters.xml` at `/files/updatesxml/vm-smartfilters.xml` on `shop.topoweryou.com`. This public XML contains no customer key.
4. Verify the feed returns HTTP 200 and XML. Test the feed's download URL with a valid test key and confirm the ZIP's SHA-256 matches the feed. Verify invalid/expired keys are handled by the existing server policy. Do not commit keys or URLs containing them.
5. Install an older module build on staging, enter the test key in **System → Update Sites → VM Smart Filters Updates → Download Key**, clear the Joomla update cache, find updates, and install 1.0.1. Verify its version and filters.

The manifest contains `<dlid prefix="key=" suffix="" />`. Joomla 4+ appends the administrator's Download Key to this service's `download.get` URL. No extra key-injection plugin is required.

The build generates the update filename, version, supported Joomla versions, PHP minimum and SHA-256 from the same package. Deploy the ZIP first and feed last. Retain previous releases for rollback; do not publish a feed pointing at an unavailable package.

Server deployment and a real paid-download/update test have not been performed by the local build. They require access to the shop's VM Update Key Manager and hosting storage.
