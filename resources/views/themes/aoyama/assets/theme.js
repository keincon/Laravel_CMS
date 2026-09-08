/* Aoyama theme — mobile nav, hero carousel, back-to-top */
(function () {
  'use strict';

  document.documentElement.classList.add('theme-aoyama-ready');

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    initMobileNav();
    initHeroCarousel();
    initBackToTop();
    initCouponFilter();
  });

  function initMobileNav() {
    var toggle = document.querySelector('[data-ao-menu-toggle]');
    var body = document.body;
    var backdrop = document.querySelector('[data-ao-drawer-backdrop]');
    if (!toggle || !body.classList.contains('theme-aoyama')) {
      return;
    }

    function close() {
      body.classList.remove('ao-nav-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.querySelectorAll('.ao-nav > li.is-open').forEach(function (li) {
        li.classList.remove('is-open');
      });
    }

    function open() {
      body.classList.add('ao-nav-open');
      toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function () {
      if (body.classList.contains('ao-nav-open')) {
        close();
      } else {
        open();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', close);
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        close();
      }
    });

    document.querySelectorAll('.ao-nav > li').forEach(function (li) {
      var link = li.querySelector(':scope > a');
      var dropdown = li.querySelector(':scope > .ao-dropdown');
      if (!link || !dropdown) {
        return;
      }
      link.addEventListener('click', function (e) {
        if (window.matchMedia('(max-width: 1100px)').matches) {
          e.preventDefault();
          li.classList.toggle('is-open');
        }
      });
    });
  }

  function initHeroCarousel() {
    var root = document.querySelector('[data-ao-hero]');
    if (!root) {
      return;
    }

    var slides = Array.prototype.slice.call(root.querySelectorAll('[data-ao-slide]'));
    var dotsWrap = root.querySelector('[data-ao-dots]');
    if (slides.length < 2) {
      return;
    }

    var index = 0;
    var timer = null;
    var interval = 5500;

    function go(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (slide, n) {
        slide.classList.toggle('is-active', n === index);
      });
      if (dotsWrap) {
        dotsWrap.querySelectorAll('button').forEach(function (btn, n) {
          btn.classList.toggle('is-active', n === index);
          btn.setAttribute('aria-selected', n === index ? 'true' : 'false');
        });
      }
    }

    if (dotsWrap) {
      dotsWrap.innerHTML = '';
      slides.forEach(function (_, n) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'スライド ' + (n + 1));
        btn.setAttribute('aria-selected', n === 0 ? 'true' : 'false');
        if (n === 0) {
          btn.classList.add('is-active');
        }
        btn.addEventListener('click', function () {
          go(n);
          restart();
        });
        dotsWrap.appendChild(btn);
      });
    }

    function tick() {
      go(index + 1);
    }

    function restart() {
      if (timer) {
        clearInterval(timer);
      }
      timer = setInterval(tick, interval);
    }

    root.addEventListener('mouseenter', function () {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    });
    root.addEventListener('mouseleave', restart);

    go(0);
    restart();
  }

  function initBackToTop() {
    var btn = document.querySelector('[data-ao-back-top]');
    if (!btn) {
      return;
    }

    function onScroll() {
      if (window.scrollY > 360) {
        btn.classList.add('is-visible');
      } else {
        btn.classList.remove('is-visible');
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initCouponFilter() {
    var root = document.querySelector('[data-ao-filter]');
    if (!root) {
      return;
    }
    var chips = Array.prototype.slice.call(root.querySelectorAll('[data-filter]'));
    var cards = Array.prototype.slice.call(document.querySelectorAll('.ao-coupon[data-cats]'));
    var empty = document.querySelector('.ao-filter-empty');

    function apply(filter) {
      var visible = 0;
      cards.forEach(function (card) {
        var cats = (card.getAttribute('data-cats') || '').split(/\s+/);
        var show = filter === 'all' || cats.indexOf(filter) !== -1;
        card.classList.toggle('is-hidden', !show);
        if (show) {
          visible += 1;
        }
      });
      if (empty) {
        empty.hidden = visible > 0;
      }
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        chips.forEach(function (c) {
          c.classList.toggle('is-active', c === chip);
        });
        apply(chip.getAttribute('data-filter') || 'all');
      });
    });
  }
})();
