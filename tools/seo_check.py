#!/usr/bin/env python3
"""Read-only post-deployment checks: python tools/seo_check.py https://example.com"""
import sys, json, urllib.request, urllib.parse, xml.etree.ElementTree as ET
from html.parser import HTMLParser

class Head(HTMLParser):
    def __init__(self): super().__init__(); self.canonical=[]; self.robots=[]; self.jsonld=[]; self.script=False; self.text=''
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag=='link' and a.get('rel')=='canonical': self.canonical.append(a.get('href',''))
        if tag=='meta' and a.get('name')=='robots': self.robots.append(a.get('content',''))
        if tag=='script' and a.get('type')=='application/ld+json': self.script=True; self.text=''
    def handle_data(self, data):
        if self.script: self.text+=data
    def handle_endtag(self, tag):
        if tag=='script' and self.script: self.jsonld.append(self.text); self.script=False

def get(url):
    req=urllib.request.Request(url,headers={'User-Agent':'Rockpack-SEO-Check/1.0'})
    with urllib.request.urlopen(req, timeout=20) as r: return r.status,r.read(),r.headers

def main():
    if len(sys.argv)!=2 or urllib.parse.urlsplit(sys.argv[1]).scheme not in ('http','https'): sys.exit('Usage: python tools/seo_check.py https://example.com')
    base=sys.argv[1].rstrip('/')+'/'; errors=[]
    try:
        _, robots,_=get(urllib.parse.urljoin(base,'robots.txt'))
        if 'sitemap:' not in robots.decode('utf-8','replace').lower(): errors.append('robots.txt has no Sitemap directive')
        _, body,_=get(urllib.parse.urljoin(base,'sitemap.xml'))
        xml=ET.fromstring(body); ns={'s':'http://www.sitemaps.org/schemas/sitemap/0.9'}
        urls=[x.text for x in xml.findall('s:url/s:loc',ns)]
        if not urls: errors.append('sitemap contains no URLs')
        if len(urls)!=len(set(urls)): errors.append('sitemap contains duplicate URLs')
        origin=urllib.parse.urlsplit(base)
        for url in urls:
            parsed=urllib.parse.urlsplit(url)
            if (parsed.scheme,parsed.netloc)!=(origin.scheme,origin.netloc): errors.append(f'{url}: different origin'); continue
            try:
                _,data,headers=get(url); page=Head(); page.feed(data.decode('utf-8','replace'))
                if page.canonical != [url]: errors.append(f'{url}: canonical {page.canonical}')
                if 'noindex' in headers.get('X-Robots-Tag','').lower(): errors.append(f'{url}: noindex HTTP header')
                if any('noindex' in s.lower() for s in page.robots): errors.append(f'{url}: noindex in sitemap')
                for item in page.jsonld: json.loads(item)
            except Exception as e: errors.append(f'{url}: {e}')
        print(f'Checked {len(urls)} sitemap URLs; {len(errors)} issue(s).')
    except Exception as e: errors.append(str(e))
    for error in errors: print('FAIL:',error)
    return 1 if errors else 0
if __name__=='__main__': sys.exit(main())
