# Rockpack Static Site Generator

This plugin creates anonymous HTML snapshots of published, publicly viewable WordPress pages and posts while leaving WordPress as the content source and renderer. It does not depend on Simply Static or alter WordPress core or the active theme. Static-delivery rules are active only in the local `ssg.test` staging `.htaccess`; production routing has not been changed. The plugin uses synchronous PHP/WordPress hooks for content changes and has no scheduled/background runner.

## Installation and first-page trial

1. In WordPress Admin, open **Plugins** and activate **Rockpack Static Site Generator**. Activation creates `wp-content/static-site/`; it does not change visitor routing.
2. Open **Tools > Static Site Generation**.
3. Enter one published page's ID and click **Generate selected page**. Find the ID in its editor URL (`post=...`). Confirm its output under `wp-content/static-site/` before enabling static delivery.
4. Use **Start full generation** to generate all published pages and posts, three at a time. Click **Run next generation batch** until the status says `complete`. HTMX updates the maintenance status when available; ordinary form submissions and page reloads work as a fallback.
5. Use **Retry failed operations** after resolving the cause of a failed render or file deletion. Use **Reconcile snapshots** to regenerate missing/current URLs, remove orphan HTML and old temporary files, and queue any failed removals. **Retry failed file cleanup** retries only queued deletions.

Simply Static is not installed or active in the current staging site. Its remaining compatibility-cache options were removed after confirming they belonged to that plugin; no Simply Static cron hooks were present. The custom plugin operates independently.

## Architecture and components

- `init()` attaches the plugin's callbacks to WordPress; `register_activation_hook()` calls `activate()` to create and check the dedicated output directory.
- `generate()` accepts only public, password-free published `page` and `post` records, verifies that the permalink uses the site's same origin and a path-based URL, requests the anonymous public frontend with `X-Rockpack-SSG-Render: 1`, rejects responses setting cookies, marked private/no-store, or containing form nonces, and atomically saves suitable HTML. Each post has a generation version and an advisory file lock: a render superseded by an edit, withdrawal, or deletion cannot replace newer output, and a withdrawal waits for an earlier render before removing its file. The render header makes Apache fall through to WordPress even when an old snapshot already exists, so an update is rendered from current content. Rendering through the frontend lets the active theme, WordPress enqueue hooks, SEO output, CSS, and JavaScript produce the captured document. `rockpack_static_ssg_is_public_content` can further restrict eligible content; it cannot override the core public/password/status checks.
- `relative_path_from_url()` maps a URL path to an `index.html` file. For example, `/` becomes `static-site/index.html`, and `/index.php/sample-page/` becomes `static-site/index.php/sample-page/index.html`. Query-based permalinks are rejected because multiple posts can share the same path; use path-based permalinks.
- `target_path()` rejects traversal, Windows-reserved names, and symlinked directories, creates needed directories, and ensures resolved paths stay inside the dedicated output directory.
- `_rockpack_static_ssg_path` post meta records each post's generated path. `delete_file()` validates that stored path before removal; `remove_post_file()` connects the safe file deletion to post lifecycle events.
- `record_success()` stores the latest successful operation for the admin screen. `record_error()` stores and logs failures so a failed loopback request or filesystem action is visible rather than silently ignored.
- **Files in `wp-content/static-site/` contain public output.** The staging root `.htaccess` now denies direct requests to that directory (verified as HTTP 404), but serves eligible pages through their public URLs. Without an equivalent production rule or a separate static document root, anyone who knows an output-file URL can request it. Only generate content safe for anonymous publication.
- Use the `rockpack_static_ssg_snapshot_changed` action to purge both the generated page URL and any old URL from a CDN/proxy after a write or deletion. Hook an authenticated purge client into this action; do not put cache credentials in page content.

## Hooks and admin behavior

- `transition_post_status` invokes `on_transition_post_status()`: published posts immediately refresh their own snapshot and the configured front/posts page; unpublish of a post also refreshes that listing. Unpublished, password-protected, or otherwise ineligible content has its tracked output removed. Parent unpublish removes generated descendant pages; republish immediately refreshes eligible descendants.
- `wp_after_insert_post` invokes `on_page_structure_change()`: it compares a page's previous slug and parent and immediately refreshes its published descendants when structure changes.
- `before_delete_post` invokes `on_before_delete_post()` to remove the parent's snapshot and retain descendant IDs; `deleted_post` runs after deletion and immediately refreshes children using their new permalinks.
- Failures are recorded in durable generation and deletion queues. They are not automatically retried. **Retry failed operations** manually retries generation and deletion; **Retry failed file cleanup** retries deletion only.
- **Reconcile snapshots** checks current public pages/posts against metadata and disk, regenerates missing snapshots, removes orphan HTML and old temporary files, and records removal failures for manual retry. It does not run periodically.
- `admin_menu` and `admin_post_rockpack_static_ssg_maintenance` provide the **Tools > Static Site Generation** interface. All maintenance requests require `manage_options` and a WordPress nonce. Full generation uses a three-record ID-cursor batch per request; the next batch runs only after another administrator request.
- HTMX 2.0.4 is loaded from its pinned unpkg URL on this admin screen only. No custom JavaScript is used. If the CDN is unavailable or HTMX is blocked, native POST forms still perform the operation and return to the screen.
- `generate_scheduled_post()` remains the shared synchronous generation routine. It is called directly by lifecycle hooks and records failures; no WP-Cron event is registered or scheduled.
- `should_serve_static_request()` is the testable request policy mirrored by the Apache example: only anonymous, query-free GET/HEAD page requests are eligible; admin/API/system routes, the output directory, cookies, authorization headers, POSTs, and query strings stay dynamic.
- `queue_generation()` performs generation immediately. Repeated generations atomically replace the same output path under a per-post lock, and cleanup checks current public metadata/permalinks before deleting a queued path so an obsolete retry cannot remove a valid snapshot.
- The plugin clears legacy SSG cron events once after this version is loaded and again on deactivation. It does not define new scheduled events.

## Serving static files

The local `ssg.test` staging root `.htaccess` has the tested rules below enabled, including direct-output denial and WordPress fallbacks. Production `.htaccess` remains untouched; do not copy these rules to production until deployment is explicitly approved. The current installation's URLs include `/index.php/`.

**Safest deployment:** keep the WordPress CMS private and deploy approved generated files to a separate static web root or static host/CDN. If files remain under `wp-content/static-site/`, regard them as public; install a direct-path deny before exposing static rewrites, and verify Apache's internal rewrite behavior on staging. Do not generate member-only, password-protected, personalized, or nonce-bearing pages.

For Apache 2.4 with WordPress at the document root, back up the root `.htaccess` and place the following rules before the `# BEGIN WordPress` section. These rules have been exercised on the local `ssg.test` staging site; they are not a production change. They serve only anonymous, query-free GET/HEAD page requests; reject direct requests to the snapshot storage URL; exclude both REST URL forms; bypass requests with cookies, Authorization, or the generator's `X-Rockpack-SSG-Render: 1` loopback header; and fall through to WordPress for everything else. The render-header bypass is necessary so updates are rendered by WordPress rather than reading the old snapshot being replaced. Missing snapshots also fall through.

```apache
<IfModule mod_rewrite.c>
RewriteEngine On

RewriteRule ^wp-content/static-site(?:/|$) - [R=404,END,NC]

RewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$
RewriteCond %{QUERY_STRING} ^$
RewriteCond %{HTTP:Cookie} ^$
RewriteCond %{HTTP:Authorization} ^$
RewriteCond %{HTTP:X-Rockpack-SSG-Render} ^$
RewriteCond %{REQUEST_URI} !^/(?:index\.php/)?(?:contact-us(?:/|$)|wp-admin(?:/|$)|wp-login\.php$|wp-json(?:/|$)|wp-cron\.php$|wp-comments-post\.php$|xmlrpc\.php$|wp-content(?:/|$)|wp-includes(?:/|$)) [NC]
RewriteCond %{DOCUMENT_ROOT}/wp-content/static-site/index.html -f
RewriteRule ^$ /wp-content/static-site/index.html [END,E=ROCKPACK_STATIC:1]

RewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$
RewriteCond %{QUERY_STRING} ^$
RewriteCond %{HTTP:Cookie} ^$
RewriteCond %{HTTP:Authorization} ^$
RewriteCond %{HTTP:X-Rockpack-SSG-Render} ^$
RewriteCond %{REQUEST_URI} !^/(?:index\.php/)?(?:contact-us(?:/|$)|wp-admin(?:/|$)|wp-login\.php$|wp-json(?:/|$)|wp-cron\.php$|wp-comments-post\.php$|xmlrpc\.php$|wp-content(?:/|$)|wp-includes(?:/|$)) [NC]
RewriteCond %{DOCUMENT_ROOT}/wp-content/static-site/$1/index.html -f
RewriteRule ^(.+?)/?$ /wp-content/static-site/$1/index.html [END,E=ROCKPACK_STATIC:1]

<IfModule mod_headers.c>
Header always set Cache-Control "public, max-age=0, s-maxage=300, must-revalidate" "expr=%{ENV:ROCKPACK_STATIC} == '1' || %{ENV:REDIRECT_ROCKPACK_STATIC} == '1'"
Header always set Vary "Cookie, Authorization" "expr=%{ENV:ROCKPACK_STATIC} == '1' || %{ENV:REDIRECT_ROCKPACK_STATIC} == '1'"
</IfModule>
</IfModule>
```

Fifteen live `ssg.test` Apache route checks passed: homepage and nested `index.php` snapshots map correctly and receive the configured headers; direct snapshot URLs return 404; both REST routes return dynamic JSON; admin paths, arbitrary cookies, Authorization, query strings, POST, and the SSG-render-header request stay dynamic and uncached; HEAD is supported; and a missing snapshot falls through to WordPress's dynamic 404. A separate valid-login-cookie check rendered the logged-in WordPress toolbar without shared-cache headers. `/wp-admin/` redirects to the login page; this install's `/index.php/wp-admin/` redirects to `/`, and neither response is statically cached. These rules assume a root install, Apache 2.4 `mod_rewrite`, and `mod_headers`. Ensure any CDN bypasses cache whenever Cookie, Authorization, or a query string is present and never caches authenticated/personalized responses. Have the host adapt equivalent rules for subdirectory or Nginx deployments. Do not remove WordPress fallbacks.

Anonymous static HTML uses `Cache-Control: public, max-age=0, s-maxage=300, must-revalidate`: browsers revalidate, and a shared cache may retain it for at most five minutes. The plugin fires `rockpack_static_ssg_snapshot_changed( $url, $relative_path, $operation )` after generation/deletion. Staging instrumentation observed both `generated` and `deleted` notifications after actual snapshot writes and removals. No CDN or reverse-proxy cache is configured on this staging host, so cache purge/invalidation beyond the origin hook was not applicable or verified. The five-minute shared-cache lifetime is the fallback if a production purge integration fails; configure monitoring and a purge failure alert.

## Limitations and deployment considerations

- Static snapshots cannot execute PHP. Requests with query strings, cookies, Authorization, POST methods, REST/AJAX/admin paths, or no generated file must remain dynamic. The generator rejects detected HTML form nonces and responses marked private/no-store or setting cookies, but custom authentication/personalization logic needs a site-specific review and may require the public-content filter to reject additional pages.
- Content changes trigger snapshots, but theme/template, menu, customizer, plugin, and site-wide SEO-setting changes do not necessarily change a post status. Run a full rebuild after those changes.
- There is deliberately no system cron, external scheduler, WP-Cron runner, or automatic background recovery. Lifecycle generation/deletion runs inline with the WordPress content event and can increase that save/delete request's duration. Full generation is bounded to three records per manual admin request. Failed operations, missed events during downtime, and stale output cannot be guaranteed to recover until an administrator runs **Retry failed operations**, **Retry failed file cleanup**, or **Reconcile snapshots**.
- The public URL must be reachable from the WordPress server over HTTP(S), use the same origin as `home_url()`, and return an HTML 200 response without a redirect. A loopback firewall, authentication wall, maintenance page, redirect, or slow frontend can prevent generation.
- PHP needs write permission on `wp-content/static-site/`. Set the narrowest permissions that permit the WordPress process to write there; do not make the directory writable by everyone.
- Generated HTML is public content. Review pages for private material, expiring nonces, and data personalized at request time before serving snapshots publicly.
- The cleanup queue retains failed removals and their last error/attempt count until an administrator retries them. Reconciliation is also manual and removes orphan `.html` files and stale temporary files; it records failed deletions for a later cleanup retry. After a permission or loopback issue is fixed, run **Reconcile snapshots**, then **Retry failed operations**, and confirm that both failure counts are zero and the last operation succeeded. If a full generation is interrupted, the stored cursor/progress remains visible: run its next batch again, or start a new full generation to reset it.
- Snapshot changes fire `rockpack_static_ssg_snapshot_changed` for integration with a cache/CDN purge client. No purge provider or CDN is included. A purge failure is not retried by this plugin; monitor the integration and use the documented shared-cache lifetime as a fallback.
- The output directory is owned by this plugin: do not store manually managed HTML there.
