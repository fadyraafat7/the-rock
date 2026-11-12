/* Accessible, keyboard-operable About Us capability tabs. */
document.querySelectorAll('[data-about-tabs]').forEach(group => {
  const tabs = [...group.querySelectorAll('[role="tab"]')];
  const select = tab => {
    tabs.forEach(item => {
      const selected = item === tab;
      item.setAttribute('aria-selected', String(selected));
      item.tabIndex = selected ? 0 : -1;
      document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
    });
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => select(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      if (next === undefined) return;
      event.preventDefault();
      select(tabs[next]);
      tabs[next].focus();
    });
  });
});

/* Regional growth carousel controls. */
document.querySelectorAll('.about-growth__carousel').forEach(carousel => {
  const viewport = carousel.querySelector('.about-growth__cards');
  const controls = carousel.querySelector('.about-growth__dots');
  const cards = [...viewport.querySelectorAll('li')];
  if (cards.length < 2) return;
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  let selected = 0;
  const offset = card => card.getBoundingClientRect().left - viewport.getBoundingClientRect().left + viewport.scrollLeft;
  const buttons = cards.map((card, index) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.setAttribute('aria-label', `Show regional growth card ${index + 1}: ${card.querySelector('p').textContent.trim()}`);
    button.addEventListener('click', () => {
      selected = index;
      mark();
      viewport.scrollTo({ left: offset(card), behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    });
    controls.appendChild(button);
    return button;
  });
  function mark() { buttons.forEach((button, index) => button.setAttribute('aria-pressed', String(index === selected))); }
  mark();
  carousel.classList.add('has-controls');
  let timer;
  viewport.addEventListener('scroll', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const max = viewport.scrollWidth - viewport.clientWidth;
      if (Math.abs(viewport.scrollLeft - Math.min(offset(cards[selected]), max)) < 4) return;
      selected = viewport.scrollLeft >= max - 4 ? cards.length - 1 : cards.reduce((best, card, index) => Math.abs(offset(card) - viewport.scrollLeft) < Math.abs(offset(cards[best]) - viewport.scrollLeft) ? index : best, 0);
      mark();
    }, 120);
  }, { passive: true });
});
