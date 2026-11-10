const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#main-nav');
toggle?.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); nav.classList.toggle('is-open', open); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') { toggle.setAttribute('aria-expanded','false'); nav.classList.remove('is-open'); toggle.focus(); } });
document.querySelector('.error-summary')?.focus();

// Les liens et les destinations restent accessibles lorsque JavaScript est désactivé.
const filters = document.querySelector('.journey-filters');
if (filters) {
  filters.hidden = false;
  const cards = [...document.querySelectorAll('[data-destination-category]')];
  const count = document.querySelector('[data-destination-count]');
  filters.addEventListener('click', event => {
    const button = event.target.closest('[data-destination-filter]');
    if (!button) return;
    const category = button.dataset.destinationFilter;
    filters.querySelectorAll('button').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    let visible = 0;
    cards.forEach(card => { card.hidden = category !== 'all' && card.dataset.destinationCategory !== category; if (!card.hidden) visible++; });
    if (count) count.textContent = document.documentElement.lang === 'en' ? `${visible} destination${visible > 1 ? 's' : ''} to discover` : `${visible} destination${visible > 1 ? 's' : ''} à découvrir`;
  });
}
