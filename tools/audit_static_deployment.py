"""Read-only pre-deployment audit of the actual ZIP and local WordPress routes."""
from pathlib import Path
from zipfile import ZipFile
from html.parser import HTMLParser
from urllib.request import urlopen
from urllib.error import HTTPError
from urllib.parse import urlsplit, urljoin, unquote
import json, re
from concurrent.futures import ThreadPoolExecutor
import xml.etree.ElementTree as ET

root=Path(__file__).resolve().parents[1]
origin='https://mediumturquoise-ram-805066.hostingersite.com'
local='http://figma-htmx-site.test'
class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.title=''; self.heading=None; self.headings=[]; self.links=[]; self.ids=set(); self.metas={}; self.canonical=[]; self.schemas=[]; self.ld=None; self.in_title=False; self.skip=0; self.text=[]; self.missing_alt=0; self.lang=None; self.main=0
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='html': self.lang=a.get('lang')
        if tag=='main': self.main+=1
        if a.get('id'): self.ids.add(a['id'])
        if tag=='title': self.in_title=True
        if tag in ('h1','h2','h3'): self.heading=[tag,'']
        if tag=='a': self.links.append(a.get('href',''))
        if tag=='img' and 'alt' not in a: self.missing_alt+=1
        if tag=='link' and a.get('rel')=='canonical': self.canonical.append(a.get('href'))
        if tag=='meta': self.metas.setdefault(a.get('name') or a.get('property'),[]).append(a.get('content',''))
        if tag=='script' and a.get('type')=='application/ld+json': self.ld=''
        if tag in ('script','style'): self.skip+=1
    def handle_data(self,data):
        if self.in_title: self.title+=data
        if self.heading: self.heading[1]+=data
        if self.ld is not None: self.ld+=data
        if not self.skip and data.strip(): self.text.append(data.strip())
    def handle_endtag(self,tag):
        if tag=='title': self.in_title=False
        if self.heading and tag==self.heading[0]: self.headings.append(self.heading); self.heading=None
        if tag=='script' and self.ld is not None: self.schemas.append(json.loads(self.ld)); self.ld=None
        if tag in ('script','style'): self.skip=max(0,self.skip-1)

report={'scope':'Actual deployment ZIP plus read-only local HTTP checks. No production deployment or ranking measurement.', 'pages':[], 'failures':[], 'warnings':[], 'link_failures':[], 'local_routes':[]}
pages={}; bodies={}
with ZipFile(root/'dist/rockpack-static-deployment-v2.zip') as z:
    assert z.testzip() is None
    for name in z.namelist():
        if name.startswith('wp-content/static-site/') and name.endswith('.html'):
            path='/'+name.removeprefix('wp-content/static-site/').removesuffix('index.html')
            html=z.read(name).decode(); p=Page(); p.feed(html); pages[path]=p; bodies[path]=html
            for condition,reason in ((p.canonical==[origin+path],'canonical'),(bool(p.title),'title'),(len(p.metas.get('description',[]))==1,'single description'),(p.main==1,'main landmark'),(bool(p.lang),'document language'),(not p.missing_alt,'image alt attributes'),('noindex' not in ' '.join(p.metas.get('robots',[])),'indexable meta'),('figma-htmx-site.test' not in html,'no local origin'),("classList.add('rp-inner-nav--enhanced')" in html,'early mobile navigation')):
                if not condition: report['failures'].append({'path':path,'check':reason})
            h1=[text for tag,text in p.headings if tag=='h1']
            if len(h1)!=1: report['failures'].append({'path':path,'finding':f'{len(h1)} H1 elements; expected one primary heading.'})
            if any(link=='#' for link in p.links): report['failures'].append({'path':path,'finding':'Placeholder # link remains.'})
            if any(name in html for name in ('The-Rock','Pledge Communications')): report['failures'].append({'path':path,'finding':'Old brand name remains.'})
            if re.search(r'<input[^>]+type="email"',html): report['failures'].append({'path':path,'finding':'Inactive newsletter email input remains.'})
            for schema in p.schemas:
                if schema.get('@type')=='Organization' and schema.get('name')!='The Rock Pack': report['failures'].append({'path':path,'finding':'Organization name is inconsistent.'})
            report['pages'].append({'path':path,'title':p.title,'description':p.metas.get('description'),'headings':p.headings,'schema_types':[s.get('@type') for s in p.schemas],'text_words':len(' '.join(p.text).split())})
    sitemap=ET.fromstring(z.read('sitemap.xml'))
    urls=[n.text for n in sitemap.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
    report['sitemap_count']=len(urls)
    assert len(urls)==len(set(urls)) and all(url.startswith(origin+'/') for url in urls)
    assert not any('sample-' in url or 'hello-world' in url for url in urls)
    report['robots']=z.read('robots.txt').decode()

links=set()
for path,p in pages.items():
    for href in p.links:
        url=urljoin(origin+path,href); u=urlsplit(url)
        if href and href!='#' and u.netloc==urlsplit(origin).netloc:
            links.add(u.path+('?' +u.query if u.query else '')+('#'+u.fragment if u.fragment else ''))
for url in urls: links.add(url.removeprefix(origin))

def fetch(route):
    u=urlsplit(route); target=u.path+('?' +u.query if u.query else '')
    try:
        with urlopen(local+target,timeout=20) as r: data=r.read().decode('utf-8','replace'); status=r.status; final=r.url; headers=dict(r.headers)
        p=Page(); p.feed(data)
        return target,p,status,final,headers,None
    except Exception as exc: return target,None,None,None,{},str(exc)
targets=sorted({urlsplit(route).path+('?' +urlsplit(route).query if urlsplit(route).query else '') for route in links})
responses={}
with ThreadPoolExecutor(max_workers=2) as executor:
    for target,p,status,final,headers,error in executor.map(fetch,targets):
        responses[target]=(p,error)
        report['local_routes'].append({'path':target,'status':status,'redirected':bool(final and final!=local+target),'error':error})
        if error: report['link_failures'].append({'path':target,'error':error})
for route in sorted(links):
    u=urlsplit(route); target=u.path+('?' +u.query if u.query else '')
    p,error=responses[target]
    actual=pages.get(u.path,p) if not u.query else p
    if actual and u.fragment and unquote(u.fragment) not in actual.ids:
        report['link_failures'].append({'path':route,'error':'Missing fragment target'})
for field in ('title','description'):
    values=[str(item[field]) for item in report['pages']]
    if len(set(values))!=len(values): report['failures'].append({'check':'duplicate '+field})
report['content_review']={'needs_verification':['Project documentation identifies contact details and map as needing business approval.','Provide verified social profile URLs to display social links.','Sample news articles remain intentionally noindex; replace with approved original articles before seeking search visibility.'], 'limits':['JSON-LD parsed locally; Google eligibility and external validator results are separate.','No AI citation, ranking, conversion, field Core Web Vitals or production crawlability measurement.']}
out=root/'dist/seo-aeo-geo-tests-v2.json'; out.write_text(json.dumps(report,indent=2,ensure_ascii=False),encoding='utf-8')
print(json.dumps({'snapshot_pages':len(pages),'sitemap_urls':len(urls),'local_routes':len(targets),'failures':report['failures'],'link_failures':report['link_failures'],'warnings':report['warnings'],'report':str(out)},indent=2))
