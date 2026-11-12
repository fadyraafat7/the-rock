/* Shared manual product galleries; respects reduced-motion preferences. */
document.querySelectorAll('[data-product-carousel]').forEach(products => {
    const viewport = products.querySelector('.prod-viewport');
    const cards = [...products.querySelectorAll('.prod-card')];
    const buttons = [...products.querySelectorAll('.dot')];
    if (!viewport || !cards.length) return;
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const mobile = matchMedia('(max-width: 850px)');
    let selected = 0;
    const mark = index => buttons.forEach((button, i) => button.setAttribute('aria-pressed', String(i === Math.min(index, buttons.length - 1))));
    const offset = card => card.offsetLeft - cards[0].offsetLeft;
    const go = index => {
      selected = index;
      mark(index);
      viewport.scrollTo({left:offset(cards[index]), behavior:reducedMotion.matches ? 'instant' : 'smooth'});
    };
    buttons.forEach((button, index) => button.addEventListener('click', () => go(index)));
    let timer;
    viewport.addEventListener('scroll', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const max = viewport.scrollWidth - viewport.clientWidth;
        // Keep a clicked, visible card selected when the last scroll position is clamped.
        const target = Math.min(offset(cards[selected]), max);
        if (Math.abs(viewport.scrollLeft - target) < 4) return;
        const visibleCards = cards.filter(card => !mobile.matches || !card.classList.contains('product-gallery__desktop-repeat'));
        selected = viewport.scrollLeft >= max - 4 ? visibleCards.length - 1 : visibleCards.reduce((best, card, i) => Math.abs(offset(card) - viewport.scrollLeft) < Math.abs(offset(visibleCards[best]) - viewport.scrollLeft) ? i : best, 0);
        mark(selected);
      }, 120);
    }, {passive:true});
    mobile.addEventListener('change', () => {
      if (mobile.matches && cards[selected].classList.contains('product-gallery__desktop-repeat')) go(0);
    });
});
