/**
 * Page behaviour that is not the chat: scroll-reveal, the project industry
 * filter, and highlighting the current section in the header nav.
 */
function initReveal() {
    const nodes = document.querySelectorAll('.reveal');
    if (!nodes.length) return;

    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        nodes.forEach((node) => node.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });

    nodes.forEach((node, index) => {
        node.style.setProperty('--reveal-delay', `${(index % 4) * 70}ms`);
        observer.observe(node);
    });
}

function initProjectFilter() {
    const filters = document.querySelector('[data-project-filters]');
    const grid = document.querySelector('[data-project-grid]');
    if (!filters || !grid) return;

    filters.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter]');
        if (!button) return;

        filters.querySelectorAll('[data-filter]').forEach((b) => b.classList.toggle('is-active', b === button));
        const wanted = button.dataset.filter;
        grid.querySelectorAll('[data-industry]').forEach((card) => {
            card.hidden = wanted !== '' && card.dataset.industry !== wanted;
        });
    });
}

function initScrollSpy() {
    const nav = document.querySelector('[data-scrollspy]');
    if (!nav || !('IntersectionObserver' in window)) return;

    const links = new Map([...nav.querySelectorAll('[data-nav]')].map((link) => [link.dataset.nav, link]));
    const sections = [...links.keys()].map((id) => document.getElementById(id)).filter(Boolean);

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            links.forEach((link, id) => link.classList.toggle('is-current', id === entry.target.id));
        });
    }, { rootMargin: '-40% 0px -55% 0px' });

    sections.forEach((section) => observer.observe(section));
}

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initProjectFilter();
    initScrollSpy();
});
