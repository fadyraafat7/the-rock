/* Dependency-free behavior checks for the real navigation script using a small DOM test double. */
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');
const source = readFileSync(join(__dirname, '../assets/js/inner-page.js'), 'utf8');

function setup(isDesktop = false) {
  let document;
  class Element {
    constructor(tag, classes = '') {
      this.tag = tag; this.children = []; this.attrs = new Map(); this.events = {};
      this.hidden = false; this.disabled = false; this.dataset = {}; this.style = {};
      const names = new Set(classes.split(' ').filter(Boolean));
      this.classList = { add: name => names.add(name), contains: name => names.has(name), toggle: (name, on) => on ? names.add(name) : names.delete(name) };
    }
    append(...children) { children.forEach(child => { child.parentElement = this; this.children.push(child); }); return this; }
    setAttribute(name, value) { this.attrs.set(name, String(value)); }
    getAttribute(name) { return this.attrs.get(name) ?? null; }
    removeAttribute(name) { this.attrs.delete(name); }
    hasAttribute(name) { return this.attrs.has(name); }
    set inert(value) { value ? this.setAttribute('inert', '') : this.removeAttribute('inert'); }
    get inert() { return this.hasAttribute('inert'); }
    set open(value) { const changed = this._open !== !!value; this._open = !!value; if (changed) this.fire('toggle'); }
    get open() { return !!this._open; }
    contains(element) { return this === element || this.children.some(child => child.contains(element)); }
    matches(selector) {
      if (selector === '[inert]') return this.inert;
      if (selector.startsWith('.')) return this.classList.contains(selector.slice(1));
      if (selector === 'a[href]') return this.tag === 'a' && this.hasAttribute('href');
      if (selector === 'button:not([disabled])') return this.tag === 'button' && !this.disabled;
      if (selector === 'input:not([disabled])') return this.tag === 'input' && !this.disabled;
      if (selector === 'input[type="search"]') return this.tag === 'input';
      if (selector === '[tabindex]:not([tabindex="-1"])') return this.hasAttribute('tabindex') && this.getAttribute('tabindex') !== '-1';
      return this.tag === selector;
    }
    closest(selector) { return this.matches(selector) ? this : this.parentElement?.closest(selector) ?? null; }
    querySelectorAll(selector) {
      const selectors = selector.split(',').map(value => value.trim());
      return this.children.flatMap(child => [...(selectors.some(value => child.matches(value)) ? [child] : []), ...child.querySelectorAll(selector)]);
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] ?? null; }
    getClientRects() {
      for (let child = this, parent = this.parentElement; parent; child = parent, parent = parent.parentElement) {
        if (parent.hidden || (parent.tag === 'details' && !parent.open && child.tag !== 'summary')) return [];
      }
      return this.hidden ? [] : [{}];
    }
    addEventListener(type, callback) { (this.events[type] ??= []).push(callback); }
    fire(type, extra = {}, bubble = false) {
      const event = { target: this, prevented: false, preventDefault() { this.prevented = true; }, ...extra };
      for (let current = this; current; current = bubble ? current.parentElement : null) (current.events[type] ?? []).forEach(callback => callback(event));
      if (bubble) (document.events[type] ?? []).forEach(callback => callback(event));
      return event;
    }
    click() { const event = this.fire('click', {}, true); if (this.tag === 'summary' && !event.prevented) this.parentElement.open = !this.parentElement.open; return event; }
    focus() {
      const previous = document.activeElement;
      document.activeElement = this;
      if (previous !== this) previous?.fire('focusout', { relatedTarget: this }, true);
      (document.events.focusin ?? []).forEach(callback => callback({ target: this }));
    }
  }
  const element = (tag, classes) => new Element(tag, classes);
  const link = classes => { const result = element('a', classes); result.setAttribute('href', '/target/'); return result; };
  const html = element('html'); const body = element('body'); const main = element('main');
  const nav = element('nav', 'rp-inner-nav'); const brand = link('rp-inner-nav__brand');
  const toggle = element('button', 'rp-inner-nav__toggle'); const backdrop = element('div', 'rp-inner-nav__backdrop');
  const drawer = element('div', 'rp-inner-nav__drawer'); const close = element('button', 'rp-inner-nav__close');
  const home = link(); const summary = element('summary'); const sublink = link(); const details = element('details', 'rp-inner-nav__dropdown').append(summary, element('ul').append(sublink));
  const otherSummary = element('summary'); const otherDetails = element('details', 'rp-inner-nav__dropdown').append(otherSummary, element('ul').append(link()));
  const cta = link('rp-inner-nav__mobile-cta'); drawer.append(close, home, details, otherDetails, cta);
  const search = element('form', 'rp-header-search'); const field = element('div', 'rp-header-search__field');
  const input = element('input'); field.hidden = true; input.disabled = true; field.append(input);
  const searchToggle = element('button', 'rp-inner-nav__search'); searchToggle.setAttribute('aria-label', 'Search website'); searchToggle.dataset.submitLabel = 'Search'; searchToggle.dataset.closeLabel = 'Close search';
  search.append(field, searchToggle); const actions = element('div').append(search);
  nav.append(brand, toggle, backdrop, drawer, actions); const hero = element('section'); main.append(nav, hero);
  const footer = element('footer'); const previouslyInert = element('aside'); previouslyInert.inert = true;
  body.append(main, footer, previouslyInert); html.append(body);
  document = { body, documentElement: html, activeElement: body, events: {}, querySelector: selector => html.querySelector(selector), addEventListener(type, callback) { (this.events[type] ??= []).push(callback); } };
  const desktop = { matches: isDesktop, callbacks: [], addEventListener(type, callback) { this.callbacks.push(callback); } };
  const window = { scrollX: 0, scrollY: 240, matchMedia: query => query.includes('851px') ? desktop : { matches: true }, scrollTo(x, y) { this.scrollX = x; this.scrollY = y; } };
  body.style = { position: '', top: '', left: '', width: '', overflow: '' };
  runInNewContext(source, { document, window });
  const key = (name, shiftKey = false) => { const event = { key: name, shiftKey, prevented: false, preventDefault() { this.prevented = true; } }; (document.events.keydown ?? []).forEach(callback => callback(event)); return event; };
  const resize = value => { desktop.matches = value; desktop.callbacks.forEach(callback => callback()); };
  return { nav, toggle, drawer, close, backdrop, body, hero, footer, previouslyInert, document, window, home, cta, details, summary, otherDetails, otherSummary, sublink, searchToggle, input, field, brand, key, resize };
}

test('mobile drawer starts closed and opens as an accessible modal', () => {
  const ui = setup(); assert.equal(ui.drawer.inert, true); assert.equal(ui.toggle.getAttribute('aria-expanded'), 'false');
  ui.toggle.click(); assert.equal(ui.drawer.inert, false); assert.equal(ui.drawer.getAttribute('role'), 'dialog');
  assert.equal(ui.drawer.getAttribute('aria-modal'), 'true'); assert.equal(ui.document.activeElement, ui.close);
  assert.equal(ui.nav.classList.contains('rp-inner-nav--open'), true);
});
test('opening locks scroll and isolates the background; closing restores it', () => {
  const ui = setup(); ui.body.style.overflow = 'clip'; ui.toggle.click();
  assert.equal(ui.body.style.position, 'fixed'); assert.equal(ui.body.style.top, '-240px');
  assert.equal(ui.hero.inert, true); assert.equal(ui.footer.inert, true); assert.equal(ui.toggle.inert, true);
  ui.close.click(); assert.equal(ui.body.style.position, ''); assert.equal(ui.body.style.overflow, 'clip');
  assert.equal(ui.window.scrollY, 240); assert.equal(ui.hero.inert, false); assert.equal(ui.footer.inert, false);
  assert.equal(ui.previouslyInert.inert, true); assert.equal(ui.document.activeElement, ui.toggle);
});
test('Escape and backdrop both close the drawer and return focus', () => {
  const ui = setup(); ui.toggle.click(); assert.equal(ui.key('Escape').prevented, true);
  assert.equal(ui.drawer.inert, true); assert.equal(ui.document.activeElement, ui.toggle);
  ui.toggle.click(); ui.backdrop.click(); assert.equal(ui.drawer.inert, true); assert.equal(ui.body.style.position, '');
});
test('Tab and Shift+Tab wrap inside the drawer and skip collapsed submenus', () => {
  const ui = setup(); ui.toggle.click(); assert.equal(ui.key('Tab', true).prevented, true); assert.equal(ui.document.activeElement, ui.cta);
  assert.equal(ui.key('Tab').prevented, true); assert.equal(ui.document.activeElement, ui.close);
  ui.hero.focus(); assert.equal(ui.document.activeElement, ui.close);
});
test('mobile dropdowns behave as an accordion and links dismiss the drawer', () => {
  const ui = setup(); ui.toggle.click(); ui.summary.click(); assert.equal(ui.details.open, true);
  ui.otherSummary.click(); assert.equal(ui.details.open, false); assert.equal(ui.otherDetails.open, true);
  ui.cta.click(); assert.equal(ui.drawer.inert, true); assert.equal(ui.otherDetails.open, false);
});
test('search and menu do not remain open together', () => {
  const ui = setup(); ui.searchToggle.click(); assert.equal(ui.field.hidden, false); assert.equal(ui.input.disabled, false);
  ui.toggle.click(); assert.equal(ui.field.hidden, true); assert.equal(ui.input.disabled, true);
  ui.close.click(); ui.searchToggle.click(); assert.equal(ui.drawer.inert, true); assert.equal(ui.document.activeElement, ui.input);
  ui.key('Escape'); assert.equal(ui.field.hidden, true); assert.equal(ui.document.activeElement, ui.searchToggle);
});
test('mobile search button closes the expanded panel and restores focus', () => {
  const ui = setup(); ui.searchToggle.click();
  assert.equal(ui.searchToggle.getAttribute('aria-label'), 'Close search');
  assert.equal(ui.searchToggle.click().prevented, true);
  assert.equal(ui.field.hidden, true); assert.equal(ui.input.disabled, true);
  assert.equal(ui.searchToggle.getAttribute('aria-expanded'), 'false');
  assert.equal(ui.document.activeElement, ui.searchToggle);
});
test('desktop resize releases the modal and keeps desktop navigation usable', () => {
  const ui = setup(); ui.toggle.click(); ui.resize(true);
  assert.equal(ui.drawer.inert, false); assert.equal(ui.drawer.getAttribute('role'), null); assert.equal(ui.body.style.position, '');
  assert.equal(ui.document.activeElement, ui.brand); ui.home.focus(); ui.resize(false);
  assert.equal(ui.drawer.inert, true); assert.equal(ui.document.activeElement, ui.toggle);
});
test('desktop dropdown hover and click-to-search still work', () => {
  const ui = setup(true); assert.equal(ui.drawer.inert, false); ui.toggle.click(); assert.equal(ui.drawer.getAttribute('role'), null);
  ui.details.fire('pointerenter', { pointerType: 'mouse' }); assert.equal(ui.details.open, true);
  ui.details.fire('pointerleave'); assert.equal(ui.details.open, false);
  ui.searchToggle.click(); assert.equal(ui.field.hidden, false); assert.equal(ui.document.activeElement, ui.input);
  assert.equal(ui.searchToggle.click().prevented, false);
});
