# JED preparation

Version 1.1.1 was checked with the **unmodified official JED Checker 2.4.2 rule files**, tag commit `336d2c8`, using `scripts/jed-check.php` to supply filesystem, registry and language services outside Joomla. All 12 released rule classes are run; no rule or finding is suppressed. This is an isolated static scan, not a scan through JED Checker's Joomla administrator UI.

Results are recorded in `tests/jed-report.json`. The final scan runs on the extracted package and both extracted child ZIPs, not development scripts/tests. The package owns the update server, so its child module/plugin do not declare separate feeds.

## Reproduce

Requires PHP 8.1+ with SimpleXML and ctype, Python 3, Node.js and Git.

```sh
git clone --branch 2.4.2 --depth 1 https://github.com/joomla-extensions/jedchecker.git /tmp/jedchecker-2.4.2
python3 scripts/build.py
python3 scripts/extract-check.py dist/pkg_vm_smartfilters-1.1.1.zip /tmp/vm-smartfilters-check
php scripts/jed-check.php /tmp/jedchecker-2.4.2 /tmp/vm-smartfilters-check
php tests/module.php
php tests/properties.php
python3 tests/property_sql.py
node --test tests/filters.test.cjs
```

The CLI exits nonzero for errors, warnings or compatibility findings. Notices are printed for review.

## Changes for compliance

- Full recognised GNU General Public License headers and copyright metadata.
- Complete GPL v2 licence text, matching the existing repository's GPL v2-or-later licensing.
- Direct-access guards on all installed PHP files.
- Complete module manifest, translation files and declared installer files.
- Native Joomla update server and Download Key support for the existing shop service.
- Versioned update XML with a SHA-256 matching the built ZIP.

## Remaining release gates

1. Deploy and verify the public feed and protected download through the existing shop service (`DEPLOYMENT.md`). A syntactically correct update URL alone does not prove the service works.
2. Install and exercise the extension on Joomla/VirtueMart staging, including upgrade, uninstall and the checks in the module README.
3. Run the ZIP through the latest released JED Checker installed in Joomla before submission.
4. Supply an accurate JED listing, working download/support URLs and any reviewer access needed for paid downloads.

Passing a static checker does not guarantee JED editorial approval or live-site compatibility.

References: [official checker](https://github.com/joomla-extensions/jedchecker/tree/2.4.2), [JED GPL requirements](https://extensions.joomla.org/support/knowledgebase/submission-requirements/the-gpl-the-jed/), [JED update requirement](https://extensions.joomla.org/support/knowledgebase/submission-requirements/joomla-update-system-requirement/).

## Query-hook test

`tests/property_plugin.php` accepts a checkout of `joomla-framework/event` and VirtueMart SVN revision 11374's `helpers/vrequest.php`. Run `php tests/property_plugin.php /path/to/event /path/to/vrequest.php` to check actual event reference propagation, VM request cache activation, state persistence, category changes, reset, invalid ranges and query scope. Application/plugin lifecycle services are isolated adapters; the event and VM request implementations are upstream code.
