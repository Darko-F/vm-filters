# VM Smart Filters 1.1.2

Installable Joomla package (site module and companion system plugin) for VirtueMart **4.8.4 and later compatible releases**, using searchable String custom fields and numeric Property custom fields for length, width, height and weight. Designed for Joomla **4.4 / 5 / 6**, with PHP **8.1+** (also meet your installed Joomla/VirtueMart PHP requirements). Future releases need regression testing; compatibility with every future release cannot be guaranteed.

## Install and configure

1. In Joomla administration, open **System → Install → Extensions → Upload Package File** and upload `pkg_vm_smartfilters-1.1.2.zip`. Install VirtueMart first.
2. In **VirtueMart → Products → Custom Fields**, create or edit a **String (S)** field, such as Colour, Size, or Material. Set **Published = Yes**, **Searchable = Yes**, **Admin only = No**, and **Hidden = No**. Use a public field without custom-field shopper-group restrictions.
3. Edit products and assign actual values on the **Custom Fields** tab, for example Colour = Blue and Size = Large. Defining a field alone does not create filter options.
4. In **Content → Site Modules**, open **VM Smart Filters** (create a module of this type if necessary). Publish it in your template's sidebar or above-products position and assign it to the shop's **VirtueMart Category Layout** menu item(s). The module renders on `com_virtuemart` category views only.
5. Leave **Custom field IDs** empty for automatic discovery, or enter IDs such as `7,12,18` to choose fields and their order. Select vertical or horizontal layout.
6. Open that category in the frontend and select a value. The page reloads with matching products using your existing VirtueMart product cards, prices, sorting and pagination. Selecting Colour and Size requires both to match. **Clear filters** clears custom-field selections while retaining the category, keyword and manufacturer context.

The package installs the module and **System - VM Property Filters**, enabling that plugin on first installation. No core files or product values are changed. Uninstall the **VM Smart Filters package** through Joomla's extension manager. Install the complete package when upgrading from 1.0.x; existing module settings are retained. An intentionally disabled existing plugin stays disabled during upgrades.

## Dimensions and weight (Property fields)

1. Fill in the product's **Product Dimensions and Weight** tab, including the dimension and weight units.
2. Create a published **Property (P)** custom field (for example Width) with **Admin only = No**, **Hidden = No**, and no field-level shopper-group restriction. Add it to each relevant product and select `product_width`. The other supported properties are `product_length`, `product_height` and `product_weight`.
3. Leave the module's **Enable dimension and weight Property filters** enabled. Choose each selector independently with **Show product length**, **Show product width**, **Show product height** and **Show product weight** (all enabled by default). Leave **Custom field IDs** blank or include the Property field IDs in the list. The native **Searchable** flag is required for String fields only; Property filters use their own numeric query.
4. Numeric filter headings use the actual property name (Length, Width, Height or Weight), translated into the site language, rather than the custom-field title. Choose the module's **Dimension filter unit** (default cm) and **Weight filter unit** (default kg).
5. Enter a minimum, maximum, or both, then press **Show products**. Numeric inputs wait for submission so you can finish entering both bounds. String selects retain their optional automatic submission.

If Height or Weight is missing, assign a Property field selecting `product_height` or `product_weight` to the relevant published products, and fill in their measurements and units. The module switches control visibility; they do not create product assignments. Category scope and the Custom field IDs list still apply. After hiding an already active filter, use **Clear filters** to remove its saved selection.

Example: Width 60–80 cm matches products stored as 70 cm, 0.7 m or 700 mm. Add Weight maximum 10 kg to require both conditions. Boundaries are inclusive. Enter equal minimum and maximum values for an exact measurement. Decimal dots and commas are accepted, with up to eight decimal places.

Supported dimension units: mm, cm, m, in, ft, yd. Weight units: mg, g, kg, lb, oz. Comparison uses centimetres/kilograms internally. Unknown units and zero/unfilled product measurements are excluded when a range is active. Measurements and Property assignments must be on the matched product itself; inherited child-product values are not resolved by this extension.

Ranges persist across pagination and sorting, reset when changing category, and clear with **Clear filters**. Invalid or reversed ranges show a validation message and return no results. Published/private field checks are also enforced when processing manually edited filter URLs.

## Updates and download keys

The module uses the same update service as BillHelper / Transport Accounting:

- Feed: `https://shop.topoweryou.com/files/updatesxml/vm-smartfilters-package.xml`
- Download handler: `com_vmupdatekeymanager`, task `download.get`, with `channel=update`.
- If your subscription requires a key, open **System → Update Sites → VM Smart Filters Package Updates** and enter it in **Download Key**. Joomla sends the key as `key=...` using the manifest's native `dlid` support. Keys are not stored in this repository or embedded in the package.

The release maintainer must upload the generated feed and register/upload the ZIP with VM Update Key Manager before automatic updates can work. GitHub commits do not deploy this service. See the repository's `DEPLOYMENT.md`.

## Behaviour and scope

- One value per String field and one inclusive range per numeric Property field, combined with AND across all fields. This is not a checkbox multi-select/OR or AJAX extension.
- Bootstrap 5 classes, responsive layout, labelled native selects, visible keyboard focus and a manual submit button. The submit button works with JavaScript disabled. Native automatic selection refresh can be disabled in module settings.
- Scoped CSS provides a baseline style even without Bootstrap. No CDN, jQuery, external font, or Bootstrap JavaScript is loaded. The template's Bootstrap colour variables are used when available.
- English and Slovenian language files. Field titles and value language constants pass through Joomla translation.
- Controls come from published products and published, public searchable String or supported Property fields. Product shopper-group restrictions are considered. Admin-only, hidden, and shopper-group-restricted field definitions are excluded.
- With **Options from current category only = Yes**, values come from products directly assigned to the category. Set it to **No** if your category includes descendant-category products. Options do not dynamically shrink after another filter is chosen; combinations can correctly produce no results.
- String fields and the four dimension/weight Property fields are supported. Plugin-backed fields (including Custom Fields for All), other Property values such as SKU, child-variant selectors, price sliders, stock filters and custom inheritance are outside this version's scope. Matching follows the native VM product query and direct custom-field assignments.
- Native VM search ignores the literal value `0`. Its request sanitization also prevents dependable matching for values containing `<`, `>`, `&`, single/double quotes or control characters; this module excludes these values. Use plain filter labels such as `Small`, `Blue`, `Cotton`, or `10 mm`.
- VirtueMart's **strictCustomfieldTags** setting controls exact versus substring matching. By default VM can match `Blue` within `Dark Blue`. For exact shop facets, set `strictCustomfieldTags=1` in your VM configuration using your normal supported configuration workflow. The module does not silently change shop-wide configuration.
- **changeCategoryRemoveFilter** controls native String filters in VirtueMart; numeric ranges always reset on category changes. The module operates on the current category rather than redirecting to a different category, and reads VM's effective selection state.
- Use one filtering module per category page. Submitting it replaces native custom-field selections; avoid publishing another native/custom-field filtering form on the same page.
- Module caching is disabled. Exclude shop category pages from full-page/CDN caching when necessary to prevent cached session-dependent shop content. Normal VM product caching remains under VM's control.

## Troubleshooting

**Module not visible:** check its publication, access, menu assignment, template position, and that the page is a VirtueMart category view. Turn on **Show message when no filters exist**. Check field type, searchable/public flags, product publication and assigned values.

**Filter appears but gives no results:** check the exact product assignment, current category/manufacturer/keyword, shopper group and VM matching settings. A field with value `Blue` on a parent does not necessarily match a child without that assignment in VM's native search.

**Filters temporarily unavailable:** inspect Joomla's log for the `mod_vm_smartfilters` category and confirm VirtueMart is installed and enabled. Technical database errors are never shown to shoppers.

## Validation status

The native `customfields[id]`, `combineTags`, category-reset and matching contract was inspected in VirtueMart SVN revision **11374** (the 4.8.4 build) and trunk revision **11449**. Joomla service-provider/module patterns were checked against official documentation. The project includes PHP linting, isolated helper/template tests, browser-script event tests and ZIP/manifest verification.

**A live Joomla + VirtueMart installation was not available in the build workspace.** Installation, real database queries, SEF routing, pagination and theme rendering still need the staging checks below. The ZIP is not a claim of live-site certification. Numeric predicates were executed against a SQLite fixture, and hook reference propagation was checked with actual Joomla Event classes and the VM 4.8.4 request helper; these do not replace a real MySQL/MariaDB + Joomla integration test.

### Staging checklist

1. Install ZIP; confirm version guard and module settings.
2. Create searchable Colour and Size String fields. Assign Blue/Small, Blue/Large and Red/Large to three published products in one category.
3. Blue returns two; Blue + Large returns one; Clear returns the category's unfiltered results. Repeat with auto-submit disabled and JavaScript disabled.
4. Test pagination, sorting, browser Back, category changes, multiple languages, Joomla in a subfolder and SEF on/off.
5. Confirm hidden/unpublished products and fields do not leak options. Test guest and shopper-group accounts.
6. Test your actual template at mobile and desktop widths; check keyboard interaction and no-results combinations.
7. Add Width/Weight Property fields to the same products. Test 70 cm, 0.7 m and 700 mm against a 60–80 cm range, and 8 kg / 8000 g against a maximum 10 kg. Combine with Colour. Check zero measurements, private fields, missing Property assignments, and reversed/invalid ranges.
8. Upgrade from the old 1.0.x module using the full package; check module settings are preserved and the new plugin is enabled. Verify the package update site and key, then uninstall the package on a disposable copy.
9. Repeat core checks after upgrading Joomla or VirtueMart.

## Sources

- [VirtueMart: String customfields the right way](https://docs.virtuemart.net/tutorials/product-creation/vm-string-customfields-the-right-way)
- [VirtueMart: Custom Fields Edit](https://docs.virtuemart.net/manual/products-menu/custom-field-edit)
- [VirtueMart product model at revision 11374](https://dev.virtuemart.net/svn/virtuemart/!svn/bc/11374/trunk/virtuemart/administrator/components/com_virtuemart/models/product.php)
- [Joomla module dependency injection](https://manual.joomla.org/docs/building-extensions/modules/module-development-tutorial/step8-dependency-injection/)

License: GPL-2.0-or-later. See LICENSE.txt.
