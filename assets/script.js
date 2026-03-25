/* This file handles scroll-triggered animations for poll/dashboard tiles
   using the IntersectionObserver API. Page-specific JS (timers, voting,
   modals, form validation) lives inline in each PHP file instead.
   IntersectionObserver watches for tiles entering the viewport.
   When a tile becomes visible, it fades in and slides up.
   Once animated, the tile is unobserved so the effect only plays once. */
var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(e) {
        if (e.isIntersecting) {
            e.target.style.opacity   = '1';
            e.target.style.transform = 'none';
            observer.unobserve(e.target); /* stop watching after first reveal */
        }
    });
}, { threshold: 0.08 }); /* trigger when 8% of the element is visible */

/* Apply the observer to all poll tiles and admin option tiles on the page */
document.querySelectorAll('.poll-tile, .dash-tile, .manage-option-tile').forEach(function(el) {
    observer.observe(el);
});
