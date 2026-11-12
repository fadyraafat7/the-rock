"""Read-only HTTP verification of the deployed Rockpack site."""
from urllib.request import urlopen, Request
from urllib.error import HTTPError
from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urlsplit, urljoin
from concurrent.futures import ThreadPoolExecutor
import json, re
import xml.etree.ElementTree as ET

base='https://mediumturquoise-ram-805066.hostingersite.com'
root=Path(__file__).resolve().parents[1]
out=root/'tmp/production-audit'; out.mkdir(exist_ok=True)
class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.title=''; self.in_title=False; self.metas={}; self.canonical=[]; self.schemas=[]; self.ld=None; self.h1=0; self.inputs=[]; self.forms=[]; self.assets=[]; self.links=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='title': self.in_title=True
        if tag=='meta': self.metas.setdefault(a.get('name') or a.get('property'),[]).append(a.get('content'))
        if tag=='link' and a.get('rel')=='canonical': self.canonical.append(a.get('href'))
        if tag=='script' and a.get('type')=='application/ld+json': self.ld=''
        if tag=='h1': self.h1+=1
        if tag=='form': self.forms.append(a)
        if tag in ('input','textarea','select'): self.inputs.append(a)
        if tag in ('img','script') and a.get('src'): self.assets.append(a['src'])
        if tag=='link' and a.get('rel')=='stylesheet': self.assets.append(a.get('href'))
        if tag=='a': self.links.append(a.get('href',''))
    def handle_data(self,data):
        if self.in_title: self.title+=data
        if self.ld is not None: self.ld+=data
    def handle_endtag(self,tag):
        if tag=='title': self.in_title=False
        if tag=='script' and self.ld is not None:
            self.schemas.append(json.loads(self.ld)); self.ld=None

def fetch(path):
    url=urljoin(base+'/',path)
    req=Request(url,headers={'User-Agent':'Rockpack-Deployment-Audit/1.0'})
    try: response=urlopen(req,timeout=30)
    except HTTPError as err: response=err
    except Exception as exc: return {'path':path,'error':str(exc)}
    with response as r: body=r.read(); headers={k.lower():v for k,v in r.headers.items()}; status=r.status; final=r.url
    key=re.sub('[^a-zA-Z0-9]+','_',path).strip('_') or 'home'
    (out/(key+'.body')).write_bytes(body)
    item={'path':path,'status':status,'final_url':final,'headers':headers,'bytes':len(body)}
    if 'text/html' in headers.get('content-type',''):
        html=body.decode('utf-8','replace'); p=Page()
        try: p.feed(html)
        except Exception as exc: item['parse_error']=str(exc)
        item.update(title=p.title,description=p.metas.get('description'),robots=p.metas.get('robots'),canonical=p.canonical,h1=p.h1,schemas=p.schemas,old_brand='The-Rock' in html,local_origin='figma-htmx-site.test' in html,early_menu="classList.add('rp-inner-nav--enhanced')" in html,dead_links=p.links.count('#'),newsletter_input=bool(re.search(r'<input[^>]+type="email"',html)))
        if path=='contact-us/': item.update(forms=p.forms,inputs=p.inputs,cf7_assets=[v for v in p.assets if 'contact-form-7' in v],cf7_config=re.findall(r'(?:var wpcf7|wpcf7 =).*?(?:</script>|;)',html,re.S))
    return item

paths=['','about-us/','history/','certifications/','product-portfolio/','planet/','contact-us/','news/','news/?page=2','careers/','?s=packaging','nonexistent-production-audit-20261108/','robots.txt','sitemap.xml','wp-sitemap.xml','wp-json/','wp-content/static-site/index.html','?release-audit=20261108']
with ThreadPoolExecutor(max_workers=2) as pool: results=list(pool.map(fetch,paths))
report={'origin':base,'checks':results}
by_path={r['path']:r for r in results}
for item in results:
    print(json.dumps({k:item.get(k) for k in ('path','status','error','title','canonical','h1','old_brand','local_origin','early_menu','dead_links')},ensure_ascii=True))
    print('HEADERS',json.dumps({k:v for k,v in item.get('headers',{}).items() if k in ('x-rockpack-page-cache','cache-control','x-robots-tag','x-hcdn-cache-status','last-modified','etag','link')},ensure_ascii=True))

try:
    xml=ET.fromstring((out/'sitemap_xml.body').read_bytes())
    urls=[n.text for n in xml.findall('.//{http://www.sitemaps.org/schemas/sitemap/0.9}loc')]
    report['sitemap_urls']=urls
    print('SITEMAP',json.dumps(urls))
except Exception as exc: report['sitemap_error']=str(exc)

# Confirm referenced public scripts, stylesheets and representative images.
p=Page(); p.feed((out/'home.body').read_text(encoding='utf-8'))
assets=sorted({v for v in p.assets if v and urlsplit(v).netloc==urlsplit(base).netloc and (v.endswith('.js') or '.css' in v or 'hero' in v)})
with ThreadPoolExecutor(max_workers=2) as pool: report['assets']=list(pool.map(fetch,assets))
print('ASSETS',json.dumps([{'url':v['path'],'status':v.get('status'),'error':v.get('error')} for v in report['assets']]))
(root/'dist/production-audit.json').write_text(json.dumps(report,indent=2,ensure_ascii=False),encoding='utf-8')
print('REPORT',str(root/'dist/production-audit.json'))
