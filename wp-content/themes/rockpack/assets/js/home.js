/* Home page galleries. */
(() => {
  'use strict';
  const hero = document.querySelector('[data-hero-gallery]');
  if (hero) {
    const slides = [...hero.querySelectorAll('.rp-hero-slide')];
    const buttons = [...hero.querySelectorAll('.rp-hero-dots button')];
    let index = 0;
    let timer;
    let transitionTimer;
    let started;
    let remaining = 3000;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const paused = () => document.hidden || hero.matches(':hover') || hero.contains(document.activeElement);
    const show = next => {
      const previous = index;
      window.clearTimeout(transitionTimer);
      index = next;
      slides.forEach((slide, i) => {
        slide.classList.remove('is-entering', 'is-leaving');
        slide.hidden = i !== index;
        slide.inert = i !== index;
      });
      if (previous !== index && !motion.matches) {
        const outgoing = slides[previous];
        outgoing.hidden = false;
        outgoing.classList.add('is-leaving');
        slides[index].classList.add('is-entering');
        transitionTimer = window.setTimeout(() => {
          outgoing.hidden = true;
          outgoing.classList.remove('is-leaving');
          slides[index].classList.remove('is-entering');
        }, 600);
      }
      buttons.forEach((item, i) => item.setAttribute('aria-pressed', String(i === index)));
    };
    const start = () => {
      if (timer !== undefined || slides.length < 2 || motion.matches || paused()) return;
      hero.classList.remove('is-paused');
      started = Date.now();
      timer = window.setTimeout(() => {
        timer = undefined;
        select((index + 1) % slides.length);
      }, remaining);
    };
    const pause = () => {
      if (timer !== undefined) {
        window.clearTimeout(timer);
        timer = undefined;
        remaining = Math.max(0, remaining - (Date.now() - started));
      }
      hero.classList.add('is-paused');
    };
    const select = next => {
      window.clearTimeout(timer);
      timer = undefined;
      hero.classList.remove('is-autoplay');
      show(next);
      remaining = 3000;
      if (slides.length > 1 && !motion.matches) {
        void hero.offsetWidth;
        hero.classList.add('is-autoplay');
        if (paused()) hero.classList.add('is-paused');
        else start();
      }
    };
    buttons.forEach((button, i) => button.addEventListener('click', () => select(i)));
    hero.addEventListener('mouseenter', pause);
    hero.addEventListener('mouseleave', () => { if (!paused()) start(); });
    hero.addEventListener('focusin', pause);
    hero.addEventListener('focusout', () => { window.setTimeout(() => { if (!paused()) start(); }, 0); });
    document.addEventListener('visibilitychange', () => { if (paused()) pause(); else start(); });
    motion.addEventListener('change', () => select(index));
    select(0);
  }
  const history = document.querySelector('[data-history-carousel]');
  const data = document.getElementById('home-history-data');
  if (history && data) {
    const slides = JSON.parse(data.textContent);
    let index = 0;
    let changing = false;
    const slide = history.querySelector('.history-slide');
    const change = direction => {
      if (slides.length < 2 || changing) return;
      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const suffix = direction > 0 ? 'next' : 'prev';
      index = (index + direction + slides.length) % slides.length;
      const update = () => {
        history.querySelector('.history-slide-title').textContent = slides[index].title;
        history.querySelector('.history-slide-text').textContent = slides[index].text;
      };
      if (reducedMotion) { update(); return; }
      changing = true;
      slide.classList.remove('is-entering-next', 'is-entering-prev');
      slide.classList.add('is-exiting-' + suffix);
      window.setTimeout(() => {
        update();
        slide.classList.remove('is-exiting-' + suffix);
        slide.classList.add('is-entering-' + suffix);
        window.setTimeout(() => {
          slide.classList.remove('is-entering-' + suffix);
          changing = false;
        }, 320);
      }, 220);
    };
    history.querySelector('.prev').addEventListener('click', () => change(-1));
    history.querySelector('.next').addEventListener('click', () => change(1));
    if (slides.length < 2) history.querySelectorAll('button').forEach(button => {button.disabled = true;});
  }
})();
