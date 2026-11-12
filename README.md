# The Rock Pack website

WordPress website for The Rock Pack, implemented with the custom **Rockpack** PHP theme using the supplied design references and PDF assets. The theme uses CSS, JavaScript, and a locally bundled HTMX library; it has no npm, Composer, or frontend build step.

HTMX powers live search suggestions in the shared header and enhances search, news, and careers pagination by replacing results without a full page reload. WordPress renders the HTML, and the normal links and search form work without JavaScript. Navigation, tabs, carousels, and Contact Form 7 retain their existing behavior.

## Local development

### Local MCP for Portable SEO

WSP MCP 2.9.7 and the project plugin **Portable SEO MCP (Local)** expose two authenticated tools: `portable_seo_get` and `portable_seo_update`. The adapter only permits SEO title/description access on the eight published Rockpack pages at `http://figma-htmx-site.test`. It checks the caller's page-editing permissions, preserves social images and indexing settings, and flushes the page cache after saves. Other WSP tools are disabled in this local setup.

The project `.codex/config.toml` registers `rockpack_local_seo` using `tools/local_mcp_client.php --stdio`. The bridge forwards MCP JSON-RPC to WordPress's HTTP endpoint; it reads the API key inside WordPress without placing credentials in the config or logs. Laragon's web server and database must be running. A new Codex session for this trusted project can load the configured server. To disable the scoped tools, deactivate **Portable SEO MCP (Local)**; deactivate **WSP MCP** to stop its HTTP endpoint too.

The eight page titles/descriptions were saved through MCP `tools/call` and read back on 2026-11-05. Inspect **MCP > Audit Log** for the eight successful `portable_seo_update` entries. `tmp/seo-mcp-report.json` records page results without credentials. The CLI client supports `--apply-seo` to reapply the prepared copy over MCP. `tools/setup_local_mcp.php` is the one-time setup helper, not the mechanism used to write SEO metadata.

The existing development installation uses Laragon on Windows, Apache, MySQL, and PHP 8.3.

1. Open the project at `C:\laragon\www\figma-htmx-site`.
2. Start Apache and MySQL in Laragon.
3. Open [the website](http://figma-htmx-site.test/) or [WordPress Admin](http://figma-htmx-site.test/wp-admin/).
4. In **Appearance > Themes**, confirm that **Rock Pack** (`rockpack`) is active.
5. Use **Settings > Permalinks > Post name** for clean URLs without `index.php`. If necessary, save that screen again to refresh the rewrite rules.

If the hostname does not resolve, reload Laragon's virtual hosts and check that the document root points to this directory. Pretty permalinks require Apache rewrite support and permission to read the root `.htaccess`.

For a fresh installation or migration, provision a compatible WordPress database and configure your own local `wp-config.php`. Import the site's database and uploads if you need the existing content and settings. Copying theme files alone does not recreate WordPress pages, saved settings, or Media Library records. Do not publish database credentials, authentication salts, or database backups.

## Pages

| Page | URL | Rockpack template |
| --- | --- | --- |
| Home | `/` | `front-page.php` |
| About Us | `/about-us/` | `page-about-us.php` |
| History | `/history/` | `page-history.php` |
| Certifications | `/certifications/` | `page-certifications.php` |
| Products & Services | `/product-portfolio/` | `page-product-portfolio.php` |
| Planet & Societal Impact | `/planet/` | `page-planet.php` |
| Contact Us | `/contact-us/` | `page-contact-us.php` |
| Page not found | Any missing URL | `404.php` |
| Search | `/?s=keyword` | `search.php` |
| News | `/news/` | `home.php` (WordPress Posts page) |
| News article | WordPress post permalink | `single.php` |
| Careers | `/careers/` | `archive-rp_job.php` |
| Job opportunity | `/careers/{slug}/` | `single-rp_job.php` |

The shared navigation and 404 search forms open the search results template. It includes a query field, result counts, linked titles, available excerpts, pagination, and empty/no-match states. Search uses WordPress's built-in public content search; content stored only in Rockpack settings or private section custom post types is not indexed.

Administrators can edit the News and Careers archive hero titles and images under **Pages > News & Careers Heroes**. Each hero has a live preview; choose images from the Media Library or enter an image URL, then select **Save heroes** to publish the changes.

The header search icon expands an inline search field to its left without shifting the menu or header buttons. Enter or the search icon submits the query; Escape, moving focus away, or clicking elsewhere collapses the field.

The favicon and touch icons use the header logo automatically. Update **Appearance > Header & Footer > Brand image** to change that artwork throughout the site.

At widths of **850px and below**, the shared header uses a menu button and a drawer that slides in from the right. It contains the same editable navigation groups and a Get Started link, with accordion dropdowns and scrolling for long menus. Close it with the close button, backdrop, Escape, or a navigation link. Opening locks background scrolling and keeps keyboard focus inside; closing restores focus and scroll position. The menu respects reduced-motion preferences and returns to the desktop layout when resized. Without JavaScript, mobile links remain visible as a normal navigation list. Behavior checks: `node --test wp-content/themes/rockpack/tests/navigation.cjs`.

On a new database, create and publish the seven original content pages under **Pages** using their slugs. WordPress automatically selects the matching `page-{slug}.php` template. Named templates are also available in the page editor. The Home layout is supplied by `front-page.php`. News uses a page assigned as the **Posts page** under **Settings > Reading**; Careers uses a job archive and needs no separate WordPress page.

These custom templates render structured content from theme settings, repeaters and existing custom post types. Click **Edit Page** to edit the labeled Rockpack sections below the ordinary editor body.

## Editing content

Use **Pages > Edit** or the frontend toolbar's **Edit Page** for all seven Rockpack pages. The editor provides sectioned fields, Media Library controls, and 23 repeater definitions with **Add Item**, **Remove**, **Move up** and **Move down**. Navigation dropdown links and ESG points support nested repeaters. Save with the normal **Update / Publish** button; no build command or new plugin is required.

Each supported section has a live HTMX preview of the public template. It loads when scrolled into view and refreshes after field, image, or repeater changes, including unsaved values. The preview is read-only and isolated; Update / Publish is still required to save. The editor requires an active WordPress connection for previews, and the contact form is not interactive inside them.

Editor components follow their appearance on each page, from Hero to the final section. Descriptions, images and repeaters are grouped with their matching component. The ordering map lives in `wp-content/themes/rockpack/includes/editor-layout.php` and also handles saved WordPress editor layouts.

Page, hero and section headings are hardcoded in [the section-title map](wp-content/themes/rockpack/includes/section-titles.php). Their current visible text is preserved, and their input fields are removed from page editors, Hero slides, legacy settings and Header & Footer settings. Change those literals in the theme to update a heading. Card, news and timeline item titles, descriptions, images, links and navigation labels remain editable. Old saved heading options remain in the database but no longer control the displayed headings.

Shared sections update all pages that use them. Stats, History Slides, Certifications, Products and Partner Logos remain stored in their existing custom post types; removing an embedded item sends its record to Trash. Home and History's different original timeline content is preserved through slide placement. Blank optional fields and intentionally empty repeaters remain empty after saving.

See the [implementation and editing guide](docs/page-editing.md) for the audit, files changed, security/cache behavior, verification results and limitations, and the [page-by-page field catalog](docs/page-editing-fields.md) for all fields and repeaters. Existing content is preserved without running a migration. The ordinary editor body does not control these layouts. Shared values use the existing options architecture and do not have per-page revision/draft isolation.

### Legacy settings

Open **Settings > Rockpack** ([local settings screen](http://figma-htmx-site.test/wp-admin/options-general.php?page=rockpack-settings)). Changes require an administrator account. This screen exposes active scalar controls; duplicate repeater inputs and unused fields have been removed. Edit repeated content under **Pages > Edit**, shared navigation/footer content under **Appearance > Header & Footer**, and contact form fields/mail under **Contact > Contact Forms**.

- Remaining Home scalar controls are grouped by section. Hero slides, product galleries, localization cards and other repeated content use the page editor's repeaters.
- Full-page content has separate groups: `about_page`, `portfolio_page`, `planet_page`, and `contact_page`.
- **Appearance > Header & Footer** controls the shared footer; `contact_page` controls Contact Us presentation and the selected Contact Form 7 form. Its recipient is managed in the selected form's **Mail** tab.
- Existing unsaved blank settings fall back to defaults. The page editor distinguishes intentional blanks so optional content can be removed.
- The legacy screen accepts image URLs. The page editor also provides **Select image** and **Clear image** controls using WordPress Media Library attachment IDs.
- Product gallery images are shared between Home and Product Portfolio. The portfolio's operating cards reuse the Home Process and Localization components.

Additional structured content is available through the theme's **Stats**, **History Slides**, **Certifications**, **Products**, and **Partner Logos** admin menus. These custom post types supply supported sections, with default content used where implemented when no records exist. Published WordPress posts supply News; published open job opportunities supply Careers.

## News and Careers

- **News:** use **Posts > Add New** for the article title, body, excerpt, author and featured image. `/news/` displays **6 articles per page**, ordered by publication date (newest first). Pagination updates the address to `/news/?page=2`, `/news/?page=3`, etc.; those URLs can be opened directly or refreshed. Home displays the **three most recent** in the same order; ID descending resolves matching dates. Drafts, password-protected posts and WordPress's unchanged “Hello world!” sample are excluded. The old fallback news repeater is retired; an empty state appears when there are no eligible articles.
- **Careers:** use **Careers > Add job opportunity** for the role title, description, excerpt and optional featured image. **Opportunity details** provides location, employment type, Open/Closed status and an optional application URL or `mailto:` link. Published open roles appear on `/careers/` and Home. Closed roles retain their detail page with a closed notice and no application button; drafts stay unpublished.
- A job's **Apply now** link uses its configured destination. Without one, Contact Us opens with the selected job identified in the message. This uses the existing contact form and mail recipient. No separate application inbox or CV-upload system is added.
- Home's News and Careers components retain editable presentation copy/images and provide direct links to the article/job editors. The Careers button automatically links to the Careers archive. Standard navigation/footer links now point to the listing pages; custom destinations are preserved.
- The local installation already has a News Posts page and job rewrite rules. Theme activation runs `rockpack_setup_content_routes()` for a fresh setup. When copying code onto an already-active theme, create/assign the News Posts page under **Settings > Reading** and save **Settings > Permalinks**. Keep the existing Home page selected as the static front page.
- The listing pages and job details remain dynamic. News index snapshots are excluded; saving job content refreshes the homepage cache/snapshot after metadata has been saved. The homepage shows truthful empty states when there are no published entries.
- The local installation contains **10 sample news articles**, dated October 11–20, 2026, with featured images from the supplied theme assets. Their excerpts and bodies identify them as samples; edit or replace them through **Posts** with approved company news. These entries are stored in the local WordPress database. No jobs have been seeded.

Local integration checks: `php wp-content/themes/rockpack/tests/news-careers.php`. Fixture posts and metadata are created inside an InnoDB transaction and rolled back; no application or email is sent.

Shared navigation dropdowns and their nested links are editable under **Appearance > Header & Footer**. Their original defaults resolve published destination pages and existing anchors; saved destinations use the administrator's selected URLs. Changing a WordPress menu alone does not replace this custom navigation. After a repeater is saved, its old numbered settings are retained only as legacy fallback data and do not override the repeater.

## Project structure

Primary implementation files:

- [Rockpack theme](wp-content/themes/rockpack/): the current website implementation.
- [Section registry](wp-content/themes/rockpack/includes/home-sections.php): Home section order, reusable templates, and editable settings definitions.
- [Data helpers](wp-content/themes/rockpack/includes/data.php): settings lookup, custom post type queries, gallery data, and defaults.
- `wp-content/themes/rockpack/includes/*-page.php`: editable content definitions for full pages. `includes/contact-form7.php` connects Contact Us to Contact Form 7.
- `wp-content/themes/rockpack/template-parts/`: shared navigation, hero, footer, decorative elements, galleries, and Home sections.
- `wp-content/themes/rockpack/assets/css/`: shared and page-specific styles. `company-page.css` supplies navigation, hero, search, and footer styles; `product-components.css` supplies shared product/process styles.
- `wp-content/themes/rockpack/assets/js/`: navigation, search, tabs and carousels. Contact Form 7 supplies contact form scripts.
- `wp-content/themes/rockpack/assets/images/`: supplied images and icons grouped by page or section.
- [Theme setup](wp-content/themes/rockpack/functions.php): asset loading, body classes, admin settings, custom post types, and metadata controls.
- [Static generator plugin](wp-content/plugins/rockpack-static-ssg/): optional HTML snapshot generation.

The separate [Figma HTMX theme](wp-content/themes/figma-htmx/README.md) remains in the workspace. Its Customizer controls, HTMX behavior, and README describe that theme, not the active Rockpack implementation. Editing `themes/figma-htmx/inc/homepage.php` will not change the Rockpack site while Rockpack is active.

## Contact form

The form at `/contact-us/#contact-form` uses **Contact Form 7** and its native validation, AJAX/REST submission, accessible feedback and mail handling. Keep Contact Form 7 active.

- The local installation uses **Contact Us - Rock Pack** (form 671). Edit it under **Contact > Contact Forms**. The pre-existing default form is preserved.
- The current **Mail > To** recipient is `fadyrafat999@gmail.com`. Change it in Contact Form 7's **Mail** tab; form fields and success/error messages are also managed there.
- Local outgoing mail is configured in **ASE > Utilities > Email Delivery** for `smtp.gmail.com`, port `587`, TLS, using `noha82678@gmail.com` as the authenticated sender. The existing encrypted credential stays in ASE's settings. Use ASE's email test to confirm real Gmail authentication/delivery; mocked form tests do not establish external delivery.
- First name, last name, email, and message are required; phone is optional.
- The page retains its responsive two-column form design. **Pages > Contact Us > Contact form** selects the form and edits its introduction; obsolete field-label, button and recipient controls are removed. Existing legacy values were used for initial setup and are no longer editing controls.
- Messages use the configured site sender, with `Reply-To: [your-email]`. Visitor input cannot select the recipient. Automatic visitor reply mail is disabled by default.
- Career application links continue to prefill `your-message` for a published, open job. Other CF7 forms are unaffected.
- The old `rockpack_contact` endpoint and custom submit JavaScript are retired. CF7 manages validation, spam checks and submission feedback; the old theme honeypot/rate limiter is no longer used. This integration adds no submissions inbox.

Configure and verify the hosting mail transport or SMTP before relying on delivery. A successful `wp_mail()` result means the mail transport accepted the request, not that the recipient received it. The automated tests intercept email; they do not verify Gmail delivery or send real messages.

The Contact Us page retains `DONOTCACHEPAGE` and private/no-store headers. Keep it dynamic and excluded from shared page caches and static delivery. If CF7 is inactive or the selected form is missing, the page directs visitors to the public email/phone details.

## Static site generator

If the custom plugin is active, use **Tools > Static Site Generation** to generate a selected published page, run a full generation in manual batches, retry failures, or reconcile snapshots.

- Output is stored under `wp-content/static-site/` and contains public HTML snapshots, not a standalone WordPress replacement or an automatic asset export.
- The plugin runs synchronously on supported content lifecycle events. Full generation advances through administrator requests; there is no background or scheduled runner.
- Rockpack page, shared option and CPT edits flush local HTML and refresh dependent public Rockpack snapshots after all fields finish saving. Other theme/code changes may still require a full rebuild.
- Admin routes, forms, query requests, authenticated requests, and nonce-bearing/private pages must remain dynamic.
- The current root `.htaccess` contains ordinary WordPress routing, not static-serving rewrites. Generating files does not, by itself, switch this site to static delivery.
- Treat generated files as public. Review access restrictions, cache bypasses, and WordPress fallbacks before enabling static delivery on any host.

See the [plugin README](wp-content/plugins/rockpack-static-ssg/README.md) for architecture, maintenance actions, and a separate `ssg.test` staging rewrite example. That example is not the current `figma-htmx-site.test` routing configuration and should not be copied to production without review. The plugin admin screen uses HTMX with ordinary form submission fallbacks.

## Local full-page cache

The local installation uses the **Rockpack Local Page Cache** plugin, a deliberately small file-backed full-page cache for this site's anonymous public pages. It is enabled through `WP_CACHE` in `wp-config.php` and its early `wp-content/advanced-cache.php` drop-in. The plugin owns only `wp-content/cache/rockpack-full-page/` and adds an `X-Rockpack-Page-Cache` response header:

- `MISS` means WordPress rendered a cacheable public page and wrote a new entry.
- `HIT` means the early drop-in served that entry before WordPress loaded.
- `BYPASS` means the request remained dynamic.

Home, About Us, History, Certifications, Products & Services, and Planet are cacheable for anonymous `GET`/`HEAD` requests without cookies, authorization, or query strings. A post/page save, menu update, Rockpack setting change, theme change, or static-snapshot change clears the local page cache. Each response captures a cache generation at its start, and a flush advances that generation under a file lock; an older in-flight response therefore cannot repopulate the cache after invalidation. The cache deliberately bypasses all logged-in, cookie-bearing, authorized, query-string, admin, REST, XML-RPC, static-generator render, and Contact Us requests. Contact Us additionally keeps its existing `no-store, private` response because its form contains a nonce.

This is independent of the Rockpack Static Site Generator. Snapshots may exist under `wp-content/static-site/`, but the current root `.htaccess` has no static-serving rewrites, so normal public URLs are still served by WordPress (and, where eligible, this local page cache). No Redis object cache is installed or required; no Redis service is configured locally.

To enable the cache after copying this project to another local WordPress installation, keep `WP_CACHE` enabled, copy `wp-content/advanced-cache.php` and the `rockpack-local-page-cache` plugin, then activate **Rockpack Local Page Cache** in **Plugins**. Deactivating the plugin removes its `.enabled` marker so the drop-in stops serving entries. You can clear entries by saving any page or Rockpack setting during development.

## Checks

Run from the project root in PowerShell, with PHP and Node.js on `PATH`. Node.js is only needed for JavaScript syntax checks, not to serve the website.

PHP syntax:

```powershell
Get-ChildItem -LiteralPath 'wp-content/themes/rockpack' -Filter '*.php' -Recurse |
    ForEach-Object { php -l $_.FullName }
```

JavaScript syntax:

```powershell
Get-ChildItem -LiteralPath 'wp-content/themes/rockpack/assets/js' -Filter '*.js' |
    ForEach-Object { node --check $_.FullName }
```

Contact form checks on the local WordPress database:

```powershell
php wp-content/themes/rockpack/tests/contact-form.php
```

Page-editor integration checks and live snapshot/privacy checks:

```powershell
php wp-content/themes/rockpack/tests/page-editor.php
php wp-content/themes/rockpack/tests/static-pages.php
```

The editor suite uses an InnoDB rollback for temporary content changes. The live suite refreshes the six public snapshots; Contact Us remains excluded. See [test results and manual UI checks](docs/page-editing.md#executed-verification). Browser interaction and real mail delivery must be checked separately.

The contact CLI suite tests the installed Contact Form 7 pipeline, required/optional fields, invalid input, configured recipient/Reply-To, mail failure, job prefill, missing-form fallback and preservation of CF7 edits. All mail is intercepted and fixture changes are rolled back. Do not treat a mocked mail pass as a delivery test.

The plugin also has [staging integration tests](wp-content/plugins/rockpack-static-ssg/tests/integration.php). These create/delete fixture posts and generated files and require an active plugin and a local staging host. Use a backed-up disposable database, review the test first, and set `ROCKPACK_SSG_TEST_HOST` and `ROCKPACK_SSG_TEST_SCHEME` for that environment. They are not read-only production checks.

Before release, manually verify all seven page routes, desktop/mobile layouts, dropdowns and keyboard navigation, search, tabs/carousels, contact validation, and real email delivery using an authorized test message. Recheck affected pages after rebuilding static output.

## Known content and deployment notes

- The contact design includes a Los Angeles map while the address refers to Cairo. The map is a reference image, not a live office-location map. Replace it and its description in `contact_page` with approved location assets before launch.
- Review the sample contact address/phone, duplicate public email lines, and all business copy before publishing. Footer contact details are configured separately.
- The shared footer links to Contact Us instead of showing an unconnected newsletter form. Header & Footer controls its contact heading, text and link label. Social profiles appear only when their saved URLs are absolute HTTP(S) URLs; placeholder `#` values remain stored but are hidden.
- Inter fonts are requested from Google Fonts; no self-hosted font bundle is included in Rockpack. Consider privacy, offline behavior, and hosting requirements before launch.
- Back up the database, uploads, custom theme/plugin files, and server configuration before migration. Keep development PDFs, inspection directories, test browser profiles, credentials, and database exports out of public deployments.
- Deploy using the appropriate site URL and HTTPS configuration for the target host. No production hosting, DNS, SMTP account, or CDN configuration is supplied by the theme.


## SEO and static publishing

The active **Portable SEO** plugin manages site-wide and per-page SEO. Configure it under **Settings > Portable SEO**; the previous Rockpack-specific SEO metadata was copied without deleting the originals. The cross-framework reference is [SEO_GUIDE.md](docs/SEO_GUIDE.md). See the [plugin README](wp-content/plugins/portable-seo/README.md) for installation, conflict detection, breadcrumbs, limitations and integration.


Rockpack provides editable SEO title, description and social image fields on pages, posts and jobs. Configure the default description and share image under **Settings > Portable SEO**; edit the home page SEO box for its individual description; use an actual 1200 × 630 social image when available. A site description and the bundled social cover are fallbacks. Review page-specific descriptions and social images before launch. WordPress controls indexing under **Settings > Reading**; keep staging private or send `X-Robots-Tag: noindex` from its server, and do not publish staging snapshots.

Before publishing static snapshots, set the real public HTTPS origin under **Tools > Static Site Generation > Production domain**, then run a complete generation. The generator writes `sitemap.xml` and `robots.txt` at the snapshot root after a completed rebuild; without a production domain it uses the host used for generation. The sitemap includes public WordPress pages, posts, job pages and the News and Careers listings, including paginated listing URLs. This assumes the deployment routes dynamic-only URLs (including the contact form) to WordPress; a standalone static deployment must supply equivalent routes or use a sitemap limited to URLs actually served. Review the sitemap for test content and remove or unpublish unwanted sample pages. Deploy the refreshed output and check canonical, social images, sitemap and robots on the public domain. Submit the sitemap in Search Console and Bing Webmaster Tools, and verify performance and structured data after deployment. Static snapshots are not automatically served by the current WordPress routing.


SEO maintenance: the page/post/job SEO editor has a **Search indexing** checkbox. It adds `noindex` and excludes the item from both WordPress and Rockpack sitemaps; it does not make content private. Review and uncheck it for sample content that should be indexed. The default social image and browser icons are in `wp-content/themes/rockpack/assets/seo/`; editors can override the share image in Rockpack settings or per item. The bundled manifest assumes the public site is mounted at `/`; adjust `start_url` for a subdirectory installation. Run `python tools/seo_check.py https://your-public-site.example` after deployment to check sitemap URLs, canonicals, robots directives and JSON-LD syntax.

WordPress uses the configured database URLs unless `ROCKPACK_WP_HOME` and `ROCKPACK_WP_SITEURL` are explicitly set to trusted absolute URLs. Configure the correct values per deployment and proxy; do not use the incoming Host header as the canonical source. Set `WP_ENVIRONMENT_TYPE` to `staging`, `development`, or `local` for non-production environments to add a response-level `X-Robots-Tag: noindex, nofollow`. Header-level controls must also be configured at the web server for static files and for any deployment bypassing WordPress. Verify both the headers and that production responses do not inherit staging noindex settings. Do not block crawling of a URL in robots.txt if a search engine needs to see its noindex directive.


### WebP display images

The theme uses a smaller `original.jpg.webp` or `original.png.webp` sibling for local display images when that sibling exists, is newer than the original and is smaller. WordPress attachment metadata, originals and social-share image URLs remain unchanged; unknown, remote, stale and unconverted images use their original URLs. Responsive attachment candidates are handled individually. Theme assets and the existing local Media Library were processed with `python tools/convert_webp.py` (requires Pillow and WebP support): JPEGs use quality 88 and PNGs use lossless WebP; files are created only when at least 10% smaller, and originals and existing siblings are never overwritten. New uploads need the script to be run again. If an original is replaced, its older sibling is ignored; remove that obsolete generated sibling before rerunning the script. Deploy both the generated siblings and original images, and check the production server returns `image/webp`. Refresh static snapshots after converting images or changing their sources.
