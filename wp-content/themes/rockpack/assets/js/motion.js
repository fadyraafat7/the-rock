(() => {
  const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (preference.matches || !('IntersectionObserver' in window) || !Element.prototype.animate) return;

  // Content stays visible without JavaScript and while waiting to enter the viewport.
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);
      if (preference.matches) return;
      entry.target.animate(
        [{ opacity: 0.75, transform: 'translateY(10px)' }, { opacity: 1, transform: 'translateY(0)' }],
        { duration: 500, easing: 'ease-out' }
      );
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('main section:not(.rp-hero):not(.rp-inner-hero)').forEach((section) => {
    // Avoid animating content already visible on initial load.
    if (section.getBoundingClientRect().top >= window.innerHeight) observer.observe(section);
  });
})();
