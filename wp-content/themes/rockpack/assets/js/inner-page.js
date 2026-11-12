/* Shared sliding mobile navigation, desktop dropdowns and header search. */
(function () {
  'use strict';
  var nav = document.querySelector('.rp-inner-nav');
  if (!nav) return;
  var toggle = nav.querySelector('.rp-inner-nav__toggle');
  var drawer = nav.querySelector('.rp-inner-nav__drawer');
  var closeButton = nav.querySelector('.rp-inner-nav__close');
  var backdrop = nav.querySelector('.rp-inner-nav__backdrop');
  var hasDrawer = !!(toggle && drawer && closeButton && backdrop);
  var dropdowns = Array.from(nav.querySelectorAll('.rp-inner-nav__dropdown'));
  var desktop = window.matchMedia('(min-width: 851px)');
  var hover = window.matchMedia('(hover: hover) and (pointer: fine)');
  var searchForm = nav.querySelector('.rp-header-search');
  var searchField = searchForm.querySelector('.rp-header-search__field');
  var searchInput = searchForm.querySelector('input[type="search"]');
  var searchToggle = nav.querySelector('.rp-inner-nav__search');
  var searchLabel = searchToggle.getAttribute('aria-label');
  var menuOpen = false;
  var inertTargets = [];
  var scrollState = null;

  function closeDropdowns(except) {
    dropdowns.forEach(function (item) { if (item !== except) item.open = false; });
  }

  function lockPage() {
    var style = document.body.style;
    scrollState = { x: window.scrollX, y: window.scrollY, styles: {} };
    ['position', 'top', 'left', 'width', 'overflow'].forEach(function (key) {
      scrollState.styles[key] = style[key];
    });
    style.position = 'fixed';
    style.top = -scrollState.y + 'px';
    style.left = -scrollState.x + 'px';
    style.width = '100%';
    style.overflow = 'hidden';
    // Hide everything outside the drawer from keyboard and assistive technology.
    var current = drawer;
    while (current && current !== document.body) {
      var parent = current.parentElement;
      if (!parent) break;
      Array.from(parent.children).forEach(function (sibling) {
        if (sibling === current || sibling === backdrop || sibling.hasAttribute('inert')) return;
        sibling.inert = true;
        inertTargets.push(sibling);
      });
      current = parent;
    }
  }

  function unlockPage() {
    inertTargets.forEach(function (element) { element.inert = false; });
    inertTargets = [];
    if (!scrollState) return;
    var saved = scrollState;
    Object.keys(saved.styles).forEach(function (key) { document.body.style[key] = saved.styles[key]; });
    scrollState = null;
    var behavior = document.documentElement.style.scrollBehavior;
    document.documentElement.style.scrollBehavior = 'auto';
    window.scrollTo(saved.x, saved.y);
    document.documentElement.style.scrollBehavior = behavior;
  }

  function setMobileMenu(expanded, restoreFocus) {
    if (!hasDrawer) return;
    expanded = !!expanded && !desktop.matches;
    var wasOpen = menuOpen;
    menuOpen = expanded;
    if (expanded && !wasOpen) {
      setSearch(false);
      closeDropdowns();
    }
    nav.classList.toggle('rp-inner-nav--open', expanded);
    toggle.setAttribute('aria-expanded', String(expanded));
    toggle.setAttribute('aria-label', expanded ? 'Close navigation' : 'Open navigation');
    drawer.inert = !desktop.matches && !expanded;
    if (expanded) {
      drawer.removeAttribute('aria-hidden');
      drawer.setAttribute('role', 'dialog');
      drawer.setAttribute('aria-modal', 'true');
      drawer.setAttribute('aria-labelledby', 'rp-mobile-menu-title');
      if (!wasOpen) {
        lockPage();
        closeButton.focus({ preventScroll: true });
      }
    } else {
      drawer.removeAttribute('role');
      drawer.removeAttribute('aria-modal');
      drawer.removeAttribute('aria-labelledby');
      if (desktop.matches) drawer.removeAttribute('aria-hidden');
      else drawer.setAttribute('aria-hidden', 'true');
      closeDropdowns();
      if (wasOpen) {
        unlockPage();
        if (restoreFocus !== false) toggle.focus({ preventScroll: true });
      }
    }
  }

  function setSearch(expanded) {
    if (expanded) {
      setMobileMenu(false, false);
      closeDropdowns();
    }
    nav.classList.toggle('rp-inner-nav--search-open', expanded);
    searchField.hidden = !expanded;
    searchInput.disabled = !expanded;
    searchToggle.setAttribute('aria-expanded', String(expanded));
    searchToggle.setAttribute('aria-label', expanded ? (desktop.matches ? searchToggle.dataset.submitLabel : searchToggle.dataset.closeLabel) : searchLabel);
    if (expanded) searchInput.focus();
  }

  if (hasDrawer) {
    nav.classList.add('rp-inner-nav--enhanced');
    setMobileMenu(false, false);
    toggle.addEventListener('click', function () { setMobileMenu(!menuOpen); });
    closeButton.addEventListener('click', function () { setMobileMenu(false); });
    backdrop.addEventListener('click', function () { setMobileMenu(false); });
  }
  dropdowns.forEach(function (item) {
    item.addEventListener('pointerenter', function (event) {
      if (event.pointerType !== 'mouse' || !desktop.matches || !hover.matches) return;
      closeDropdowns(item);
      item.open = true;
    });
    item.addEventListener('pointerleave', function () {
      if (desktop.matches && hover.matches && !item.contains(document.activeElement)) item.open = false;
    });
    item.addEventListener('toggle', function () { if (item.open) closeDropdowns(item); });
    item.addEventListener('focusout', function (event) {
      if (!item.contains(event.relatedTarget)) item.open = false;
    });
  });
  nav.addEventListener('click', function (event) {
    if (event.target.closest('a')) setMobileMenu(false);
  });
  document.addEventListener('click', function (event) {
    if (!event.target.closest('.rp-inner-nav__dropdown')) closeDropdowns();
    if (!nav.contains(event.target)) setMobileMenu(false);
    if (!searchForm.contains(event.target)) setSearch(false);
  });
  searchToggle.addEventListener('click', function (event) {
    if (nav.classList.contains('rp-inner-nav--search-open')) {
      if (!desktop.matches) { event.preventDefault(); setSearch(false); searchToggle.focus(); }
      return;
    }
    event.preventDefault();
    setSearch(true);
  });
  searchForm.addEventListener('focusout', function (event) {
    if (!searchForm.contains(event.relatedTarget)) setSearch(false);
  });
  desktop.addEventListener('change', function () {
    var drawerHadFocus = hasDrawer && drawer.contains(document.activeElement);
    var searchHadFocus = searchForm.contains(document.activeElement);
    setMobileMenu(false, false);
    setSearch(false);
    if (drawerHadFocus) (desktop.matches ? nav.querySelector('.rp-inner-nav__brand') : toggle).focus();
    else if (searchHadFocus) searchToggle.focus();
  });
  document.addEventListener('focusin', function (event) {
    if (menuOpen && !drawer.contains(event.target)) closeButton.focus({ preventScroll: true });
  });
  document.addEventListener('keydown', function (event) {
    if (menuOpen && event.key === 'Tab') {
      var focusable = Array.from(drawer.querySelectorAll('a[href], button:not([disabled]), summary, input:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(function (element) {
        return element.getClientRects().length && !element.closest('[inert]');
      });
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      var outside = !drawer.contains(document.activeElement);
      if (event.shiftKey && (document.activeElement === first || outside)) {
        event.preventDefault();
        if (last) last.focus();
      } else if (!event.shiftKey && (document.activeElement === last || outside)) {
        event.preventDefault();
        if (first) first.focus();
      }
      return;
    }
    if (event.key !== 'Escape') return;
    if (menuOpen) {
      event.preventDefault();
      setMobileMenu(false);
      return;
    }
    if (nav.classList.contains('rp-inner-nav--search-open')) {
      event.preventDefault();
      var searchHadFocus = searchForm.contains(document.activeElement);
      setSearch(false);
      if (searchHadFocus) searchToggle.focus();
    }
    var focusedDropdown = dropdowns.find(function (item) { return item.open && item.contains(document.activeElement); });
    closeDropdowns();
    if (focusedDropdown) focusedDropdown.querySelector('summary').focus();
  });
}());
