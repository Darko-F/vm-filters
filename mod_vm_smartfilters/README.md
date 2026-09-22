# VM Smart Filters 1.0.1

Installable Joomla site module for VirtueMart **4.8.4 and later compatible releases**, using native searchable String custom fields. Designed for Joomla **4.4 / 5 / 6**, with PHP **8.1+** (also meet your installed Joomla/VirtueMart PHP requirements). Future releases need regression testing; compatibility with every future release cannot be guaranteed.

## Install and configure

1. In Joomla administration, open **System → Install → Extensions → Upload Package File** and upload `mod_vm_smartfilters-1.0.1.zip`. Install VirtueMart first.
2. In **VirtueMart → Products → Custom Fields**, create or edit a **String (S)** field, such as Colour, Size, or Material. Set **Published = Yes**, **Searchable = Yes**, **Admin only = No**, and **Hidden = No**. Use a public field without custom-field shopper-group restrictions.
3. Edit products and assign actual values on the **Custom Fields** tab, for example Colour = Blue and Size = Large. Defining a field alone does not create filter options.
4. In **Content → Site Modules**, open **VM Smart Filters** (create a module of this type if necessary). Publish it in your template's sidebar or above-products position and assign it to the shop's **VirtueMart Category Layout** menu item(s). The module renders on `com_virtuemart` category views only.
5. Leave **Custom field IDs** empty for automatic discovery, or enter IDs such as `7,12,18` to choose fields and their order. Select vertical or horizontal layout.
6. Open that category in the frontend and select a value. The page reloads with matching products using your existing VirtueMart product cards, prices, sorting and pagination. Selecting Colour and Size requires both to match. **Clear filters** clears custom-field selections while retaining the category, keyword and manufacturer context.

There is no core edit or system plugin. Uninstall through Joomla's extension manager; shop data is not changed. Reinstall a newer ZIP to upgrade.

## Updates and download keys

The module uses the same update service as BillHelper / Transport Accounting:

- Feed: `https://shop.topoweryou.com/files/updatesxml/vm-smartfilters.xml`
- Download handler: `com_vmupdatekeymanager`, task `download.get`, with `channel=update`.
- If your subscription requires a key, open **System → Update Sites → VM Smart Filters Updates** and enter it in **Download Key**. Joomla sends the key as `key=...` using the manifest's native `dlid` support. Keys are not stored in this repository or embedded in the package.

The release maintainer must upload the generated feed and register/upload the ZIP with VM Update Key Manager before automatic updates can work. GitHub commits do not deploy this service. See the repository's `DEPLOYMENT.md`.

## Behaviour and scope

- One value per field, combined with AND across fields. This is not a checkbox multi-select/OR or AJAX extension.
- Bootstrap 5 classes, responsive layout, labelled native selects, visible keyboard focus and a manual submit button. The submit button works with JavaScript disabled. Native automatic selection refresh can be disabled in module settings.
- Scoped CSS provides a baseline style even without Bootstrap. No CDN, jQuery, external font, or Bootstrap JavaScript is loaded. The template's Bootstrap colour variables are used when available.
- English and Slovenian language files. Field titles and value language constants pass through Joomla translation.
- Options come from published products and published, public searchable String fields. Product shopper-group restrictions are considered. Admin-only, hidden, and shopper-group-restricted field definitions are excluded.
- With **Options from current category only = Yes**, values come from products directly assigned to the category. Set it to **No** if your category includes descendant-category products. Options do not dynamically shrink after another filter is chosen; combinations can correctly produce no results.
- Standard String fields only. Plugin-backed fields (including Custom Fields for All), child-variant selectors, numeric ranges, price sliders, stock filters and custom inheritance are outside this version's scope. Matching follows the native VM product query and direct custom-field assignments.
- Native VM search ignores the literal value `0`. Its request sanitization also prevents dependable matching for values containing `<`, `>`, `&`, single/double quotes or control characters; this module excludes these values. Use plain filter labels such as `Small`, `Blue`, `Cotton`, or `10 mm`.
- VirtueMart's **strictCustomfieldTags** setting controls exact versus substring matching. By default VM can match `Blue` within `Dark Blue`. For exact shop facets, set `strictCustomfieldTags=1` in your VM configuration using your normal supported configuration workflow. The module does not silently change shop-wide configuration.
- **changeCategoryRemoveFilter** remains controlled by VirtueMart. The module operates on the current category rather than redirecting to a different category, and reads VM's effective selection state.
- Use one filtering module per category page. Submitting it replaces native custom-field selections; avoid publishing another native/custom-field filtering form on the same page.
- Module caching is disabled. Exclude shop category pages from full-page/CDN caching when necessary to prevent cached session-dependent shop content. Normal VM product caching remains under VM's control.

## Troubleshooting

**Module not visible:** check its publication, access, menu assignment, template position, and that the page is a VirtueMart category view. Turn on **Show message when no filters exist**. Check field type, searchable/public flags, product publication and assigned values.

**Filter appears but gives no results:** check the exact product assignment, current category/manufacturer/keyword, shopper group and VM matching settings. A field with value `Blue` on a parent does not necessarily match a child without that assignment in VM's native search.

**Filters temporarily unavailable:** inspect Joomla's log for the `mod_vm_smartfilters` category and confirm VirtueMart is installed and enabled. Technical database errors are never shown to shoppers.

## Validation status

The native `customfields[id]`, `combineTags`, category-reset and matching contract was inspected in VirtueMart SVN revision **11374** (the 4.8.4 build) and trunk revision **11449**. Joomla service-provider/module patterns were checked against official documentation. The project includes PHP linting, isolated helper/template tests, browser-script event tests and ZIP/manifest verification.

**A live Joomla + VirtueMart installation was not available in the build workspace.** Installation, real database queries, SEF routing, pagination and theme rendering still need the staging checks below. This ZIP is a first release, not a claim of live-site certification.

### Staging checklist

1. Install ZIP; confirm version guard and module settings.
2. Create searchable Colour and Size String fields. Assign Blue/Small, Blue/Large and Red/Large to three published products in one category.
3. Blue returns two; Blue + Large returns one; Clear returns the category's unfiltered results. Repeat with auto-submit disabled and JavaScript disabled.
4. Test pagination, sorting, browser Back, category changes, multiple languages, Joomla in a subfolder and SEF on/off.
5. Confirm hidden/unpublished products and fields do not leak options. Test guest and shopper-group accounts.
6. Test your actual template at mobile and desktop widths; check keyboard interaction and no-results combinations.
7. Repeat core checks after upgrading Joomla or VirtueMart.

## Sources

- [VirtueMart: String customfields the right way](https://docs.virtuemart.net/tutorials/product-creation/vm-string-customfields-the-right-way)
- [VirtueMart: Custom Fields Edit](https://docs.virtuemart.net/manual/products-menu/custom-field-edit)
- [VirtueMart product model at revision 11374](https://dev.virtuemart.net/svn/virtuemart/!svn/bc/11374/trunk/virtuemart/administrator/components/com_virtuemart/models/product.php)
- [Joomla module dependency injection](https://manual.joomla.org/docs/building-extensions/modules/module-development-tutorial/step8-dependency-injection/)

License: GPL-2.0-or-later. See LICENSE.txt.
