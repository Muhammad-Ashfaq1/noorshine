(() => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-primary-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });
  }

  const header = document.querySelector('[data-site-header]');
  if (header) window.addEventListener('scroll', () => header.classList.toggle('is-scrolled', window.scrollY > 24), { passive: true });

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const reveals = document.querySelectorAll('.reveal');
  if (reducedMotion || !('IntersectionObserver' in window)) reveals.forEach(element => element.classList.add('is-visible'));
  else {
    document.documentElement.classList.add('has-reveal');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), { threshold: 0.12 });
    reveals.forEach(element => observer.observe(element));
  }
})();

