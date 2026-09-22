# Version Summary

## VM Smart Filters 1.1.6 — 22 September 2026

- Removed the main bottom **Prikaži izdelke / Show products** button and moved **Počisti filtre / Clear filters** to the upper left, above the filter controls.
- Removed the long minimum/maximum range instruction from the frontend.
- Retained an apply button for each measurement filter so customers can enter both bounds before refreshing. String filters submit automatically by default; manual mode and JavaScript-disabled browsing retain per-field apply buttons.
- Preserved individual clear icons, horizontal and sidebar layouts, homepage visibility settings and separate measurement switches.
- Passed 60 PHP contract checks, the JavaScript tests and all 12 official JED Checker 2.4.2 rule classes through the documented CLI adapter, with zero findings. This is a static scan, not JED editorial approval.
- Package, site module and companion system plugin: **1.1.6**.

[Download the installation package](dist/pkg_vm_smartfilters-1.1.6.zip?raw=true). Upgrade an existing 1.1.x installation in place through Joomla's extension installer; module settings are retained. Existing 1.0.x installations must upload the complete package once and enter their Download Key in the new package update site. See [setup instructions](mod_vm_smartfilters/README.md) and [update deployment](DEPLOYMENT.md).

## Update distribution

- The package update feed is `https://shop.topoweryou.com/files/updatesxml/vm-smartfilters-package.xml`.
- Protected ZIP downloads use the existing VirtueMart Update Key Manager on `shop.topoweryou.com`, with task `download.get` and `channel=update`, following the BillHelper update-service setup.
- Subscriber keys are entered in Joomla's native **System → Update Sites → VM Smart Filters Package Updates → Download Key** field.
- The package owns updates for the module and plugin. The legacy module feed remains at 1.0.1 for old installations.
- Publishing to GitHub does not deploy the update feed or protected download. Upload/register both through the existing shop service as described in [DEPLOYMENT.md](DEPLOYMENT.md).

## VM Smart Filters 1.1.5 — 22 September 2026

- Added a **×** clear icon to each nonempty filter in both layouts, with translated tooltips and accessible labels.
- Clearing a measurement resets both its minimum and maximum; clearing a String field resets that selection only.
- Individual clear actions immediately submit the form while preserving other current values, including in manual-submit mode. The icons require JavaScript.
- Verified individual clearing in the browser, including preservation of other filters and measurement units.

## VM Smart Filters 1.1.4 — 22 September 2026

- Replaced the large horizontal form with compact rounded filter controls and expandable panels, using Joomla's Bootstrap 5 form/button classes and scoped styling.
- Added selected-value summaries, mobile panels that expand within the page, Escape-key closing and outside-click dismissal. Vertical layout retains visible fields.
- Preserved native select/range submission and accessible labels; expandable controls also work without JavaScript.
- Checked five viewport widths from 320px to 1440px using the staging site's Bootstrap 5.3.8 stylesheet, including panel boundaries, keyboard closing and submitted range values.

## VM Smart Filters 1.1.3 — 22 September 2026

- Hid filters on the VirtueMart shop homepage (category ID 0) by default.
- Added **Show on shop homepage / Prikaži na začetni strani trgovine** for shops that display products there. Homepage options come from the full catalog.
- Kept rendering limited to VirtueMart category layouts; the setting does not enable filters on article or product-detail pages.
- Suppressed the module title when the module is hidden by its page or empty-state checks.

## VM Smart Filters 1.1.2 — 22 September 2026

- Added independent module switches for Product Length, Width, Height and Weight, all enabled by default.
- Retained category scope, field-ID selection and public Property-field discovery. Visibility switches do not create product measurements or custom-field assignments.
- Added English and Slovenian setting labels and configuration guidance.

## VM Smart Filters 1.1.1 — 22 September 2026

- Changed numeric filter headings to the translated product property name instead of the custom-field title, avoiding repeated generic headings such as **Lastnosti**.
- Displayed **Dolžina izdelka**, **Širina izdelka**, **Višina izdelka** and **Teža izdelka**, with the selected display unit.

## VM Smart Filters 1.1.0 — 22 September 2026

- Added minimum/maximum filters for public VirtueMart Property custom fields assigned as `product_length`, `product_width`, `product_height` or `product_weight`.
- Added conversion between supported dimension and weight units, inclusive bounds, decimal-dot/comma input and invalid-range validation.
- Combined numeric ranges with native searchable String filters while retaining VirtueMart's normal product listing, sorting and pagination.
- Added the companion **System - VM Property Filters** plugin and a complete Joomla installation package. A newly installed plugin is enabled automatically; an existing disabled plugin remains disabled during upgrades.
- Moved update ownership to the package and detached the previous module update association during migration.

## VM Smart Filters 1.0.1 — 22 September 2026

- Added a Joomla site module for public, published, searchable VirtueMart String custom fields, with native product filtering and AND matching across selections.
- Added configurable field IDs/order, category-scoped options, automatic submission, horizontal/sidebar layouts and English/Slovenian translations.
- Added Bootstrap 5 styling, GPL notices, installer manifests, deterministic ZIP builds, SHA-256 checksums and the shop update-service integration.
- Targeted VirtueMart 4.8.4 and later compatible releases on Joomla 4.4 / 5 / 6, with PHP 8.1 or later. Future platform releases require regression testing.
