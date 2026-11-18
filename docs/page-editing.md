# Rockpack page editing

Implemented in the existing `rockpack` theme on 20 October 2026. No plugin installation, content migration command, or frontend build is required.

Page, hero and section headings are now fixed literals in `wp-content/themes/rockpack/includes/section-titles.php`. These title controls are omitted from the page editor, Hero slide repeater, legacy settings and Header & Footer editor. Existing heading text is preserved. Stored title options are retained but ignored. Card and timeline titles, news titles and other content continue to use their editing controls.

Unused-field cleanup reduced the legacy settings screen from 193 to 87 scalar controls by removing repeater replacements and moving shared footer editing exclusively to Appearance > Header & Footer. Removed controls also include the obsolete search-dialog title, the unused Certifications subtitle and Home sustainability eyebrow, and certificate background abbreviations. Duplicate footer destinations use the footer link repeaters. The breadcrumb label is offered only on About Us and Certifications. Existing option/meta values are retained, including fallback data still needed for unsaved collections.

## Editing a page

1. Sign in to WordPress and visit one of the seven pages.
2. Choose **Edit Page** from the toolbar, or open the page under **Pages**.
3. Expand the **Rockpack page content** sections below the standard editor.
   Components appear in the same order as the public page. Each component contains its own fields, images and repeaters; Hero comes first. The theme keeps this sequence when WordPress has an older saved meta-box order.
4. Change labeled fields. For repeated content, use **Add Item**, **Move up**, **Move down**, and **Remove**. Nested navigation links and ESG points have their own controls.
5. Use **Select image** to open the WordPress Media Library. Existing asset URLs continue to work. **Clear image** removes an optional image.
6. Choose WordPress **Update** or **Publish**. Validation errors appear as Rockpack admin notices. A stale shared repeater is rejected so another editor's changes are preserved.

The ordinary editor body is not used by these templates. Native meta boxes work with the WordPress editor; JavaScript is required for media selection and repeater controls. If a section was hidden in editor preferences, enable it again in the editor's panel preferences / Screen Options.

Shared sections are labeled: changes affect every page using that section. Removed shared CPT records go to the existing post type's **Trash** and can be restored there. Unsaved removal is reversed by reloading. Blank optional values saved through the page editor remain blank; an intentionally empty repeater stays empty rather than restoring sample items.

See [the complete field catalog](page-editing-fields.md) for every page's actual labels, field keys, repeater fields, and nested structures.

## What the audit found

- Rockpack was the active theme. Existing content lived in the `rockpack_settings` option, PHP fallback arrays, theme image assets, and normal WordPress posts. No custom-fields framework or SEO plugin was active, so native WordPress APIs were used.
- Five existing private custom post types had native admin menus: `rp_stat`, `rp_history`, `rp_cert`, `rp_product`, and `rp_logo`. None had published items in the audited installation; two history auto-drafts were preserved.
- Most scalar text already had settings definitions, accessible only through **Settings > Rockpack**. Numbered bullets, tabs, cards, quality steps, material steps, ESG points, galleries, and fallback articles had fixed sizes. Some navigation/footer labels, links and image descriptions were hardcoded.
- Home had a four-slide fallback history carousel; History had a different three-milestone fallback timeline. Both are preserved using the history item's **Placement** field. Initial values include all seven with their original placements. Administrators can add shared slides or choose one page.
- News uses published, public Posts and excludes the untouched WordPress sample post. The retired fallback article repeater is no longer used. News displays six articles per page, with URLs such as `/news/?page=2`, and Home displays the latest three. Both are ordered by publication date descending, then ID descending. Ten clearly marked sample news entries are present in the local database for layout testing and can be replaced through Posts.
- Rockpack Local Page Cache flushes on post and Rockpack option changes. SSG generated on content lifecycle events, but needed an additional refresh after shared options and CPT metadata finished saving.
- Contact Us uses Contact Form 7 for validation and mail. Its page editor selects the form and edits surrounding presentation; fields, messages and recipient are maintained under Contact > Contact Forms. Dynamic privacy headers remain in place.

## Page coverage

Shared navigation labels, search presentation, brand image, footer contact details, newsletter presentation and copyright text are managed through Appearance > Header & Footer. The newsletter heading is fixed in the theme. Four shared repeaters manage navigation dropdowns with nested links, footer page links, footer service links, and social links.

| Page | Scalar content | Page-specific / reused repeaters |
| --- | --- | --- |
| Home | Hero eyebrow/description/CTA; introduction image/CTA; history and certification CTAs; product CTA; process intro; sustainability copy/CTA; news intro/image; careers copy/image/CTA; closing CTA | Hero images, statistics, introduction bullets/tabs, history slides, certifications, products, process cards, localization cards, sustainability features, partner logos. News and opportunities come from their native post editors. |
| About Us | Hero image/subtitle; introduction; images/descriptions; foundation body/background; partnership body/images; closing copy/CTA | Introduction bullets/tabs, foundation features/icons, partnership points/icons, growth cards/icons |
| History | Hero eyebrow/image/subtitle and introduction | History slides with title, body, image, icon and placement |
| Certifications | Hero image/subtitle; intro; production copy/images/descriptions; quality body; partner copy/image/description; closing statement | Shared certifications with icon images; quality steps |
| Product Portfolio | Hero image/subtitle; gallery intro; quality copy; materials intro/image/description; shared process intro | Product gallery/links, material steps, process cards, localization cards |
| Planet | Hero image/subtitle; sourcing and vision copy/images/descriptions; ESG intro; closing statement | ESG pillars with nested points |
| Contact Us | Hero image/subtitle; address/phone/email card labels and values; form introduction and CF7 selector; map/image description | Form fields, button, messages and recipient are managed in Contact Form 7 |

There are **23 top-level repeater definitions**, with nested ESG points and navigation links. Repeaters expose relevant text, title, image, icon, statistic, link, placement or appearance controls. The interface provides accessible move buttons instead of requiring drag-and-drop. News now comes from published Posts, and Careers from published open `rp_job` entries. The Home components link directly to those editors; fallback news controls and the old generic Careers destination field are retired. See [News and Careers](../README.md#news-and-careers) for editing and routing details.

## Storage, compatibility and security

- Existing scalar option keys are reused. New non-CPT lists live in `rockpack_settings['_repeaters']`; explicit blanks and attachment IDs use small companion maps. No separate JSON editing UI or duplicate page metadata was introduced.
- Stats, History Slides, Certifications, Products and Partner Logos remain the sources of truth. Embedded repeaters update those same records and their `menu_order`; published content is never copied into another page list. Existing CPT admin menus remain available.
- Initial fallback items are written only on an explicit page save. No startup migration seeds, deletes, or replaces existing content. Legacy numbered settings remain fallback inputs until their repeater is saved. Existing CPT records and unrelated metadata/settings are preserved.
- History's distinct original Home and History content is retained through placement. Certifications previously used a different fallback order on Home and its full page; the first save establishes the edited shared order for both views.
- Nonces, `edit_post`, CPT creation/publication/edit/delete capabilities, field allowlists, URL/email validation, image attachment checks and output escaping protect edits. Contact Form 7 controls access to its form/mail editor. Revisions and autosaves do not write shared content.
- Validation runs before content updates. Malformed input, invalid values, stale collection versions and incomplete/truncated forms are rejected. A database failure during multiple CPT writes can still require a retry; this is not a transaction-based publishing system.
- Native page **Update / Publish** writes shared content immediately. Shared settings do not have WordPress per-page revisions, draft isolation or preview history. Use database backups for restoring earlier shared scalar/list values; CPT removals have Trash recovery.
- The default forms are below PHP's usual 1,000-input limit. Lists are limited to 150 items per repeater; very large forms may require the host to raise `max_input_vars`. An end-of-form marker rejects ordinary truncated submissions.
- Content saves flush the local HTML cache and coalesce dependent snapshot refreshes at request shutdown, after shared options/CPT metadata are final. The six public Rockpack pages refresh through the SSG plugin's existing generator/retry mechanism. No rewrite/static delivery switch is enabled.
- Contact Us and pages assigned its template are excluded from SSG eligibility. The SSG request policy and documented Apache example explicitly bypass `/contact-us/` and its `index.php` form. Private/no-store headers remain intact; Contact Form 7 now handles validation, spam checks and mail processing.

The legacy **Settings > Rockpack** screen remains compatible for scalar settings. Use the page editor for repeaters after they are saved: editing an old numbered fallback setting does not replace a saved repeater. Navigation still starts with the same destinations and dropdown styles, while its links can now be managed in nested repeaters. Published URLs and root routing are unchanged. No SEO fields were added because no SEO integration was active.

## Files created or modified

Created in `wp-content/themes/rockpack/`:

- `includes/page-editor.php`: sectioned meta boxes, scoped validation/saving, metadata registration, concurrency protection and cache/SSG hooks.
- `includes/editor-schema.php`: page resolution, repeater schemas, legacy defaults, CPT adapters and attachment resolution.
- `includes/editor-layout.php`: page-specific component ordering and grouping, including saved WordPress editor-layout compatibility.
- `includes/editor-defaults.php`: exact existing scalar defaults for populated editor fields.
- `includes/section-titles.php`: fixed page, hero, section and newsletter heading text, preserved from the current website.
- `includes/editor-extra-fields.php`: additional editable presentation labels and image descriptions.
- `includes/navigation-defaults.php`: existing dropdown definitions retained as compatible defaults.
- `assets/js/page-editor.js`, `assets/css/page-editor.css`: nested add/remove/move controls, media selection and admin styling; no build step.
- `tests/page-editor.php`: local transaction/rollback integration suite.
- `tests/static-pages.php`: live HTTP snapshot/privacy/routing checks.

Modified in that theme:

- `functions.php`: loads the editing feature and preserves unrelated settings during sanitization.
- `includes/data.php`: explicit blank/media support and shared product collection adapter.
- `assets/css/home.css`, `assets/css/product-components.css`: preserve image sizing when optional partner/product links are added.
- All six inner page templates: `page-about-us.php`, `page-history.php`, `page-certifications.php`, `page-product-portfolio.php`, `page-planet.php`, `page-contact-us.php`.
- Shared parts: `about.php`, `careers.php`, `certs.php`, `cta.php`, `footer-section.php`, `hero.php`, `history.php`, `inner-hero.php`, `inner-navigation.php`, `local.php`, `logos.php`, `news.php`, `planet.php`, `process.php`, `product-gallery.php`, `products.php`, `stats.php`.

Also modified `wp-content/plugins/rockpack-static-ssg/rockpack-static-ssg.php`, its `tests/integration.php`, and its `README.md` for the explicit Contact Us request exclusion. Updated root `README.md`; created this guide and `docs/page-editing-fields.md`. Six generated public snapshots under `wp-content/static-site/` were refreshed through the existing plugin. No root rewrite configuration was changed.

## Executed verification

Component-order checks cover the native rendered meta-box sequence on all seven pages, placement of repeaters in their sections, and compatibility with saved WordPress layouts while retaining unrelated editor boxes. Complete save tests submit every grouped repeater.

On the existing local installation:

- **333 page-editor integration checks passed** with the current 23 repeaters: all seven page schemas, rendered component order and native edit links; scalar/repeater saves and rendering; complete editor submissions retaining text/images; nested points and attachment lookup; recoverable CPT removal; blank optional content; nonce/capability/validation/stale-form guards; cache invalidation; fixed/unused field removal; dedicated footer saves; SSG exclusions. Fixture changes were rolled back with InnoDB.
- **36 News/Careers checks passed**: published news, draft/default-sample exclusions, six articles per News page, `?page=n` query handling and pagination links, the latest three on Home in date order, open/closed/protected job handling, native article/job templates, validated application links, default job enquiries, nonce/capability controls and dynamic News index exclusion from snapshots. Test content was rolled back; zero fixture entries remain and no applications or email were sent. Live HTTP checks confirmed six articles on `/news/`, the remaining four on `/news/?page=2`, correct ordering with no duplicates, current-page highlighting, next/previous links, direct loading without redirects, compatibility with `/news/page/2/`, HTTP 404 for `?page=3`, and three recent articles on Home. PHP syntax checks passed for the changed theme files.
- Following the unused-field cleanup, live HTTP comparisons across all seven pages confirmed unchanged visible text, image sources/descriptions and link destinations. All nine changed PHP files passed syntax checks. The field catalog was regenerated from the active schemas.
- A fresh live comparison of all seven pages confirmed every rendered heading matched the capture taken before the section-title change. All six changed PHP files passed syntax checks.
- **29 Contact Form 7 checks passed** with all mail intercepted and zero real emails: plugin configuration validation, native markup, required/optional fields, invalid input, recipient/Reply-To (including configurations with an automatic reply enabled), mail failure, career message prefill, missing-form fallback, preserved CF7 edits, validated form selection and replacement of obsolete theme fields. Fixture changes were rolled back. Live Contact Us returned HTTP 200 with one CF7 form, plugin scripts/styles, no legacy handler/script and page-cache bypass. The live CF7 REST endpoint returned four required-field errors for an empty submission before attempting mail.
- **9 live HTTP/SSG checks passed**: six public snapshots regenerated successfully, Contact Us rejected before generation, Contact Us HTTP response remained private/no-store/BYPASS, and `.htaccess` stayed unchanged.
- All seven public routes returned **HTTP 200**, with no PHP warnings/fatal errors in their HTML. A comparison against pre-change captures found the same rendered words on each page. Complete save roundtrips also compared main-content text and image sources.
- PHP syntax checks passed for all theme PHP files and the changed SSG PHP files. JavaScript syntax checks passed for all theme scripts.

Commands, with PHP and Node available:

```powershell
php wp-content/themes/rockpack/tests/page-editor.php
php wp-content/themes/rockpack/tests/contact-form.php
php wp-content/themes/rockpack/tests/static-pages.php
```

The page-editor suite refuses non-InnoDB storage and runs only on the configured local `.test` site. It temporarily writes fixtures/settings inside a rollback and clears cache entries. The static-pages suite intentionally refreshes snapshots and their plugin metadata. Review these effects before running them on another installation.

No browser surface was available to the agent. Actual toolbar clicks, Add/Move/Remove clicks, Media Library selection/upload clicks, editor save feedback, desktop/mobile visual appearance and interactive keyboard behavior were **not browser-tested**. The server-rendered controls and save/render paths were tested. Perform those manual UI checks in the local WordPress admin. Real inbox delivery was not tested. The SSG plugin's broader destructive staging suite was not run against this existing database; the focused live integration checks above were executed instead.

## Content intentionally left in code

- SVG path geometry, arrows, separators, decorative accents/badges and empty decorative image descriptions: these are design assets or accessibility structure. Content images, applicable icons and brand image have editing controls.
- CSS classes, DOM IDs, carousel/tab wiring, form actions/field names, nonce fields, capability checks and cache guards: changing these as page copy would break behavior or security.
- Form success/error text, validation instructions, required/optional accessibility suffixes and control ARIA labels: these are application feedback. Public form labels, headings and submit text are editable; private submission data is not page content.
- Copyright year, numbered material steps and ESG counters: generated automatically from current year or saved order. Copyright wording is editable with `{year}`.
- Legacy default text and arrays in the schema/templates: retained only to preserve existing content when nothing has been saved. Their rendered content is exposed through the editor and repeaters.
- Newsletter controls remain presentation-only because the existing site has no subscription integration. No mailing-list service was added.

No installation or migration steps remain. Open a page's editor to use the feature; existing content remains in place until deliberately changed.
