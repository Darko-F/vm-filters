# VM Smart Filters

Bootstrap 5 styled product filters for VirtueMart 4.8.4 and later compatible releases, targeting Joomla 4.4 / 5 / 6.

[Download version 1.0.1](dist/mod_vm_smartfilters-1.0.1.zip?raw=true) · [Setup instructions](mod_vm_smartfilters/README.md) · [JED scan details](JED-CHECK.md)

Searchable public String custom fields appear as labelled selects. Customers can combine fields, refresh the normal VirtueMart listing automatically, or clear the selection. Includes responsive sidebar/horizontal layouts and English/Slovenian translations. It uses native page reloads, one value per field and AND across fields.

Install the ZIP through Joomla's extension installer and publish the module on VirtueMart category pages. Mark supported fields Searchable and assign values to products. See the setup guide for field limitations and VM matching configuration.

## Updates

Uses the same `shop.topoweryou.com` feed hosting and VM Update Key Manager download endpoint as BillHelper. Joomla's native Download Key field supplies the subscriber key. See [server deployment instructions](DEPLOYMENT.md). GitHub does not host the Joomla update feed.

## Build and verify

```sh
python3 scripts/build.py
php tests/module.php
node --test tests/filters.test.cjs
```

The build creates a deterministic install ZIP, checksum and matching update XML. The released JED Checker 2.4.2 rules pass with no errors, warnings, compatibility findings or notices using the documented CLI adapter. Live Joomla/VirtueMart installation and server update delivery still require staging verification; this is not JED approval.

License: GNU General Public License version 2 or later. Copyright (C) 2026 topoweryou.com.
