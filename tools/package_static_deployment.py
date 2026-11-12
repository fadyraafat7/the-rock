"""Validate rebuilt snapshots and package a WordPress deployment overlay."""
from pathlib import Path
from urllib.parse import urlsplit, unquote, urljoin
from html.parser import HTMLParser
import hashlib, json, re, zipfile
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
manifest = json.loads((root / 'tmp/static-deployment-manifest.json').read_text())
origin = manifest['origin']
files = {}
checks = []
def add(path):
    path = path.resolve()
    path.relative_to(root)
    if not path.is_file(): raise RuntimeError(f'Missing asset: {path.relative_to(root)}')
    files[path.relative_to(root).as_posix()] = path

class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.canonical=[]; self.descriptions=[]; self.robots=[]; self.schemas=[]; self.ld=None; self.assets=[]; self.breadcrumb=False; self.parents=[]
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='link' and a.get('rel')=='canonical': self.canonical.append(a.get('href'))
        if tag=='meta' and a.get('name')=='description': self.descriptions.append(a.get('content'))
        if tag=='meta' and a.get('name')=='robots': self.robots.append(a.get('content',''))
        if tag=='script' and a.get('type')=='application/ld+json': self.ld=''
        if tag=='nav' and a.get('class')=='rp-inner-breadcrumb': self.breadcrumb=True
        if tag=='a' and self.breadcrumb: self.parents.append(a.get('href'))
        if tag in ('img','script','source','video'): self.assets.append(a.get('src',''))
        if tag=='link' and a.get('rel') in ('stylesheet','icon','apple-touch-icon','manifest','preload'): self.assets.append(a.get('href',''))
        if a.get('srcset'): self.assets.extend(part.strip().split()[0] for part in a['srcset'].split(','))
    def handle_endtag(self, tag):
        if tag=='nav': self.breadcrumb=False
        if tag=='script' and self.ld is not None: self.schemas.append(json.loads(self.ld)); self.ld=None
    def handle_data(self, data):
        if self.ld is not None: self.ld+=data

assets=[]
for relative in manifest['snapshots']:
    path=root/'wp-content/static-site'/relative
    html=path.read_text(encoding='utf-8')
    assert 'figma-htmx-site.test' not in html, f'Local origin in {relative}'
    assert 'wp-admin-bar' not in html and not re.search(r'name=[\"\'][^\"\']*nonce',html), relative
    assert "document.currentScript.parentElement.classList.add('rp-inner-nav--enhanced')" in html, relative
    page=Page(); page.feed(html)
    expected=origin+'/'+relative.removesuffix('index.html')
    assert page.canonical==[expected], (relative,page.canonical)
    assert len(page.descriptions)==1 and page.descriptions[0], relative
    assert not any('noindex' in value for value in page.robots), relative
    if relative in ('about-us/index.html','certifications/index.html'):
        assert page.parents==[origin+'/'], (relative,page.parents)
        schema=next(s for s in page.schemas if s.get('@type')=='BreadcrumbList')
        assert schema['itemListElement'][0]['item']==origin+'/'
    if relative=='index.html':
        hero=re.search(r'<img class="rp-hero-bg"[^>]+>',html).group()
        assert 'fetchpriority="high"' in hero and 'loading="lazy"' not in hero
    add(path); assets.extend(page.assets)
    checks.append({'snapshot':relative,'canonical':expected,'json_ld_types':[s.get('@type') for s in page.schemas]})

for path in (root/'wp-content/themes/rockpack').rglob('*'):
    if path.is_file() and 'tests' not in path.relative_to(root/'wp-content/themes/rockpack').parts: add(path)
for name in ('portable-seo.php','README.md'): add(root/'wp-content/plugins/portable-seo'/name)

seen=set()
while assets:
    url=assets.pop()
    if not url or url.startswith('data:'): continue
    absolute=urljoin(origin+'/',url)
    parsed=urlsplit(absolute)
    if parsed.netloc!=urlsplit(origin).netloc: continue
    relative=unquote(parsed.path).lstrip('/')
    if relative in seen or not relative.startswith(('wp-content/','wp-includes/')): continue
    seen.add(relative)
    path=root/relative
    add(path)
    if path.suffix=='.css':
        css=path.read_text(encoding='utf-8')
        assets.extend(urljoin(absolute, match.strip(' \"\'')) for match in re.findall(r'url\(([^)]+)\)',css))
    if path.suffix=='.webmanifest':
        assets.extend(urljoin(absolute,item['src']) for item in json.loads(path.read_text())['icons'])

for name in ('sitemap.xml','robots.txt'): add(root/'wp-content/static-site'/name)
tree=ET.parse(root/'wp-content/static-site/sitemap.xml')
urls=[node.text for node in tree.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
assert len(urls)==len(set(urls)) and all(url.startswith(origin+'/') for url in urls)
assert not any('sample-' in url or 'hello-world' in url for url in urls)
snapshot_urls={item['canonical'] for item in checks}
report={'origin':origin,'snapshots':checks,'sitemap_urls':urls,'wordpress_routes_in_sitemap':[url for url in urls if url not in snapshot_urls], 'verified_local_asset_files':len(seen), 'excluded':manifest['excluded']}
instructions='''Deploy this overlay to the existing WordPress document root at ORIGIN.

1. Back up the destination files, then extract this archive into the WordPress root.
   It includes six snapshots under wp-content/static-site, the current Rockpack
   theme, Portable SEO plugin, and assets referenced by the snapshots.
2. Keep Rockpack and Portable SEO active. Configure the destination WordPress URLs
   and Static Site Generation production domain as ORIGIN.
3. Keep Contact Us, News (including ?page=2), Careers, search, forms, REST/AJAX,
   logged-in requests, cookies, query strings and missing snapshots on WordPress.
   This is a WordPress deployment overlay and requires those dynamic routes.
4. Existing static-serving rules must serve only eligible anonymous GET/HEAD
   requests from wp-content/static-site and fall through to WordPress otherwise.
   This archive does not change .htaccess or enable static routing. See the
   project plugin README for the Apache rules; have the host adapt them as needed.
5. sitemap.xml and robots.txt are included at the archive root for public serving
   and in the snapshot directory. The sitemap intentionally includes dynamic
   indexable routes; all must resolve on WordPress at this same origin.
6. Purge hosting/CDN/page caches after uploading. Verify canonical URLs, breadcrumb
   links, mobile menu, CSS/images, contact submission, news pagination, search,
   robots.txt and sitemap.xml; rerun Lighthouse against the deployed site.

The archive excludes old snapshot fixtures and intentionally noindex sample
snapshots. If the destination already contains stale generated HTML, reconcile
or remove obsolete snapshots through the generator. It does not include database
credentials, configuration, accounts, or a database migration. No upload or
production settings change was performed by this build.
'''.replace('ORIGIN',origin)
archive=root/'dist/rockpack-static-deployment-v2.zip'
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as bundle:
    for relative,path in sorted(files.items()): bundle.write(path,relative)
    for name in ('sitemap.xml','robots.txt'): bundle.write(root/'wp-content/static-site'/name,name)
    bundle.writestr('DEPLOY.txt',instructions)
    bundle.write(root/'dist/rockpack-static-rules.txt','rockpack-static-rules.txt')
    bundle.writestr('validation.json',json.dumps(report,indent=2))
digest=hashlib.sha256(archive.read_bytes()).hexdigest()
(archive.with_suffix('.zip.sha256')).write_text(f'{digest}  {archive.name}\n')
(root/'dist/static-deployment-validation.json').write_text(json.dumps(report,indent=2))
print(f'PASS: {len(checks)} snapshots; {len(urls)} sitemap URLs; {len(seen)} referenced local asset files; JSON-LD, breadcrumbs, metadata, priority image, mobile initializer, no local origin leaks.')
print(f'Archive: {archive} ({archive.stat().st_size:,} bytes)')
print('WordPress routes in sitemap: '+', '.join(report['wordpress_routes_in_sitemap']))
