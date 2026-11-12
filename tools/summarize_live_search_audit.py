"""Summarize freshly downloaded production HTML; no network or mutations."""
from pathlib import Path
from html.parser import HTMLParser
from datetime import datetime, timezone
from urllib.robotparser import RobotFileParser
import json, re, sys
sys.stdout.reconfigure(encoding='utf-8')

ROOT = Path(__file__).resolve().parents[1]
raw = json.loads((ROOT / 'dist/production-audit.json').read_text(encoding='utf-8'))
ORIGIN = raw['origin']
routes = ['', 'about-us/', 'history/', 'certifications/', 'product-portfolio/',
          'planet/', 'contact-us/', 'news/', 'news/?page=2', 'careers/']

class Content(HTMLParser):
    def __init__(self):
        super().__init__()
        self.skip = 0; self.heading = None; self.headings = []; self.text = []
        self.lang = ''; self.main = 0; self.images = 0; self.missing_alt = 0
        self.meta = {}; self.questions = []
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'html': self.lang = a.get('lang', '')
        if tag == 'main': self.main += 1
        if tag == 'img':
            self.images += 1
            self.missing_alt += int('alt' not in a)
        if tag == 'meta': self.meta[a.get('name') or a.get('property')] = a.get('content')
        if tag in ('h1', 'h2', 'h3'): self.heading = [tag, '']
        if tag in ('script', 'style', 'noscript'): self.skip += 1
    def handle_data(self, data):
        if self.heading: self.heading[1] += data
        if not self.skip and data.strip(): self.text.append(data.strip())
    def handle_endtag(self, tag):
        if self.heading and tag == self.heading[0]:
            self.headings.append(self.heading); self.heading = None
        if tag in ('script', 'style', 'noscript'): self.skip = max(0, self.skip - 1)

def nodes(value):
    if isinstance(value, list):
        for v in value: yield from nodes(v)
    elif isinstance(value, dict):
        if '@type' in value: yield value
        for v in value.values(): yield from nodes(v)

pages = []
for route in routes:
    item = next(i for i in raw['checks'] if i['path'] == route)
    if item.get('error'): raise RuntimeError('Fresh network check failed: ' + route)
    key = re.sub('[^a-zA-Z0-9]+', '_', route).strip('_') or 'home'
    parser = Content()
    parser.feed((ROOT / 'tmp/production-audit' / (key + '.body')).read_text(encoding='utf-8'))
    schema_nodes = list(nodes(item.get('schemas', [])))
    page = dict(path='/' + route, status=item['status'], title=item['title'],
                description=item.get('description'), h1=item['h1'], canonical=item['canonical'],
                meta_robots=item.get('robots'), header_robots=item['headers'].get('x-robots-tag'),
                lang=parser.lang, main=parser.main, images=parser.images,
                missing_alt=parser.missing_alt, headings=parser.headings,
                word_count=len(' '.join(parser.text).split()),
                schema_types=sorted({str(n['@type']) for n in schema_nodes}),
                organization=[n for n in schema_nodes if n['@type'] == 'Organization'],
                og_title=parser.meta.get('og:title'), og_description=parser.meta.get('og:description'),
                og_image=parser.meta.get('og:image'),
                old_brand=item['old_brand'], local_origin=item['local_origin'],
                placeholder_links=item['dead_links'],
                cache=item['headers'].get('x-rockpack-page-cache'))
    pages.append(page)
    if route in ('', 'about-us/', 'product-portfolio/', 'certifications/', 'contact-us/'):
        print('\nCONTENT /' + route + '\n' + ' | '.join(parser.text)[:11000])

robots = (ROOT / 'tmp/production-audit/robots_txt.body').read_text(encoding='utf-8')
rp = RobotFileParser(); rp.parse(robots.splitlines())
report = dict(tested_at=datetime.now(timezone.utc).isoformat(), origin=ORIGIN,
              scope='Live public HTTP and HTML checks, not ranking or AI citation measurement.',
              pages=pages, robots=robots,
              crawler_access={bot:rp.can_fetch(bot, ORIGIN + '/') for bot in
                              ['Googlebot', 'Bingbot', 'OAI-SearchBot', 'GPTBot', 'PerplexityBot']},
              sitemap_urls=raw.get('sitemap_urls', []),
              validation_limit='JSON-LD syntax parsed; Google Rich Results and Schema.org validators not run.',
              performance_limit='No fresh Lighthouse, PageSpeed Insights, or field Core Web Vitals run.',
              ai_limit='No measured presence or citations in ChatGPT, Gemini, Perplexity, or Google AI Overviews.')
target = ROOT / 'dist/seo-aeo-geo-live-results.json'
target.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
print('\nSUMMARY ' + json.dumps({
    'pages':len(pages), 'http_200':sum(p['status']==200 for p in pages),
    'one_h1':sum(p['h1']==1 for p in pages),
    'canonical_correct':sum(p['canonical']==[ORIGIN+p['path']] for p in pages),
    'single_description':sum(len(p['description'] or [])==1 for p in pages),
    'lang_present':sum(bool(p['lang']) for p in pages),
    'main_landmark':sum(p['main']==1 for p in pages),
    'missing_alt':sum(p['missing_alt'] for p in pages),
    'crawler_access':report['crawler_access'],
    'schema_types':{p['path']:p['schema_types'] for p in pages},
    'report':str(target)}, ensure_ascii=True))
