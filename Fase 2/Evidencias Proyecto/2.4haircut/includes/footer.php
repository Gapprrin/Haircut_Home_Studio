</div>
</main>

<footer class="site">
<div class="container container-fluid">
<?php
$footer_cfg = configuracion();
$footer_ini = substr((string) ($footer_cfg['hora_inicio'] ?? '10:30:00'), 0, 5);
$footer_fin = substr((string) ($footer_cfg['hora_fin'] ?? '19:30:00'), 0, 5);
$footer_horario = hora_ampm($footer_ini) . " \u{2013} " . hora_ampm($footer_fin);
$footer_sabado = hora_ampm($footer_ini) . " \u{2013} " . hora_ampm(hora_cierre_sabado());
$footer_maps = 'https://www.google.com/maps/place/HairCut+Home+Studio/@-33.6828171,-71.1894721,17z/data=!3m1!4b1!4m6!3m5!1s0x9662ff607ff20d81:0xafcf5d95bed90e7c!8m2!3d-33.6828171!4d-71.1894721!16s%2Fg%2F11hcyscny1?hl=es';
?>
<div class="footer-top">
<div class="footer-map-wrap">
<iframe class="footer-map" title="Mapa de HairCut Home Studio en Melipilla"
 data-src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d500!2d-71.1894721!3d-33.6828171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662ff607ff20d81%3A0xafcf5d95bed90e7c!2sHairCut%20Home%20Studio!5e0!3m2!1ses!2scl!4v1"
 src="about:blank" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</div>
<div class="footer-info">
<div class="footer-hours">
<h3>Cuándo encontrarnos</h3>
<ul>
<li><span>Martes a viernes</span><span class="footer-time"><?php echo h($footer_horario); ?></span></li>
<li><span>Sábado</span><span class="footer-time"><?php echo h($footer_sabado); ?></span></li>
<li><span>Domingo y lunes</span><span class="footer-time">Cerrado</span></li>
</ul>
</div>
<div class="footer-contact">
<div>
<h3>Escríbenos</h3>
<div class="footer-write">
<a href="https://wa.me/56954182516" target="_blank" rel="noopener">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.15 6.4 2.15 11.83a9.78 9.78 0 0 0 1.32 4.92L2 22l5.4-1.41a10 10 0 0 0 4.64 1.18h.04c5.46 0 9.89-4.4 9.89-9.84C21.97 6.4 17.5 2 12.04 2zm5.76 13.91c-.24.68-1.4 1.3-1.94 1.38-.5.07-1.12.1-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.25-4.76-4.15-4.9-4.34-.14-.2-1.15-1.53-1.15-2.92s.73-2.07 1-2.36c.24-.27.52-.34.7-.34h.5c.16 0 .37 0 .57.44.24.52.8 2 .87 2.14.07.14.12.3 0 .48-.1.2-.16.3-.3.47-.15.16-.31.36-.44.49-.15.14-.3.3-.13.58.16.27.73 1.2 1.57 1.95 1.08.96 1.99 1.26 2.27 1.4.28.14.45.12.61-.07.17-.2.7-.81.88-1.09.19-.27.37-.23.62-.14.26.1 1.63.77 1.91.91.28.14.47.2.54.32.07.12.07.68-.17 1.36z"/></svg>
WhatsApp
</a>
<a href="https://www.instagram.com/haircut.homestudio/" target="_blank" rel="noopener">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm5 4.8A4.2 4.2 0 1 0 16.2 12 4.2 4.2 0 0 0 12 7.8zm6.4-.9a1 1 0 1 0 1 1 1 1 0 0 0-1-1zM12 9.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5z"/></svg>
Instagram
</a>
<a href="https://www.facebook.com/haircuthomestudio/" target="_blank" rel="noopener">
<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1z"/></svg>
Facebook
</a>
</div>
</div>
<div class="footer-place">
<h3>Ubicación</h3>
<p>Prof. Ricardo Luengo Mardones</p>
<a class="footer-pin" href="<?php echo h($footer_maps); ?>" target="_blank" rel="noopener">
<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 14.5 9 2.5 2.5 0 0 1 12 11.5z"/></svg>
Melipilla, Región Metropolitana
</a>
</div>
</div>
</div>
</div>
</div>
<div class="footer-bottom">
<div class="container container-fluid">
<p class="footer-copy">&copy; 2026 Haircut Home Studio</p>
</div>
</div>
</footer>

<script>
(function () {
  if (!document.body.classList.contains('home-page')) return;
  var els = document.querySelectorAll('.home-page-wrap > *, .home-mosaic-item, .home-products-section .product-card, .home-reviews-badge, footer.site .footer-top, footer.site .footer-bottom');
  if (!els.length) return;
  var show = function (el) { el.classList.add('is-in'); };
  if (!('IntersectionObserver' in window)) {
    els.forEach(show);
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, { threshold: 0.01, rootMargin: '0px 0px -4% 0px' });
  els.forEach(function (el, i) {
    el.style.transitionDelay = (Math.min(i % 8, 7) * 55) + 'ms';
    io.observe(el);
  });
})();
(function () {
  var btn = document.querySelector('.nav-toggle');
  var nav = document.getElementById('nav-acc');
  var overlay = document.querySelector('.side-nav-overlay');
  var closeBtn = document.querySelector('.side-nav-close');
  if (!btn || !nav) return;

  function setOpen(open) {
    nav.classList.toggle('is-open', open);
    document.body.classList.toggle('nav-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (overlay) overlay.hidden = !open;
  }

  window.setSideNavOpen = setOpen;
  setOpen(false);
  window.addEventListener('pagehide', function () { setOpen(false); });

  btn.addEventListener('click', function () {
    setOpen(!nav.classList.contains('is-open'));
  });
  if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
  if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });
})();

(function () {
  var loader = document.getElementById('app-loader');

  function closeNav() {
    if (typeof window.setSideNavOpen === 'function') {
      window.setSideNavOpen(false);
    } else {
      document.body.classList.remove('nav-open');
      var n = document.getElementById('nav-acc');
      var o = document.querySelector('.side-nav-overlay');
      if (n) n.classList.remove('is-open');
      if (o) o.hidden = true;
    }
  }

  function pintarLoader(on) {
    document.documentElement.classList.toggle('hhs-loading', on);
    document.body.classList.toggle('is-loading', on);
    if (loader) loader.hidden = !on;
  }

  function showLoader() {
    closeNav();
    pintarLoader(true);
  }

  function hideLoader() {
    closeNav();
    pintarLoader(false);
  }

  window.showAppLoader = showLoader;
  window.hideAppLoader = hideLoader;

  function irA(url, reemplazar, conLoader) {
    if (conLoader !== false) showLoader();
    if (reemplazar) {
      window.location.replace(url);
    } else {
      window.location.href = url;
    }
  }

  window.navigateApp = irA;
  hideLoader();

  window.addEventListener('pageshow', function () {
    closeNav();
    hideLoader();
  });

  var back = document.getElementById('header-back');
  if (back) {
    back.addEventListener('click', function (e) {
      e.preventDefault();
      var destino = back.getAttribute('href') || 'index.php';
      if (document.referrer) {
        try {
          var ref = new URL(document.referrer);
          if (ref.origin === window.location.origin && ref.pathname !== window.location.pathname) {
            window.history.back();
            return;
          }
        } catch (err) {}
      }
      irA(destino, false, false);
    });
  }

  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var form = e.target;
    if (!form || form.getAttribute('data-no-loader') != null) return;
    showLoader();
  });

  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var skip = e.target.closest('.nav-toggle, .side-nav-close, .carousel-dot, .carousel-btn, [data-edit-toggle], [data-edit-cancel], [data-no-loader], #header-back, #serv-add-toggle, #serv-cat-toggle, #serv-add-cancel, #serv-cat-cancel, #catalog-add-toggle, #catalog-add-cancel');
    if (skip) return;
    var a = e.target.closest('a[href]');
    if (!a || a.getAttribute('download') != null || a.target === '_blank') return;
    var href = a.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
    try {
      var url = new URL(a.href, window.location.href);
      if (url.origin !== window.location.origin) return;
      if (url.pathname === window.location.pathname) {
        e.preventDefault();
        irA(url.href, true, false);
        return;
      }
    } catch (err) {
      return;
    }
    showLoader();
  });
})();

document.querySelectorAll('.carousel-cover').forEach(function (root) {
  var track = root.querySelector('.carousel-track');
  var prev = root.querySelector('.carousel-btn-prev');
  var next = root.querySelector('.carousel-btn-next');
  if (!track) return;
  var cards = Array.prototype.slice.call(track.children);
  if (!cards.length) return;

  var index = cards.length >= 3 ? 1 : 0;

  function isDesktop() {
    return window.matchMedia('(min-width: 900px)').matches;
  }

  function render() {
    var desktop = isDesktop();
    cards.forEach(function (card, i) {
      card.classList.remove('is-center', 'is-side', 'is-left', 'is-right', 'is-hidden');
      if (i === index) {
        card.classList.add('is-center');
        return;
      }
      if (desktop && (i === index - 1 || i === index + 1)) {
        card.classList.add('is-side');
        card.classList.add(i < index ? 'is-left' : 'is-right');
        return;
      }
      card.classList.add('is-hidden');
    });
    root.classList.toggle('is-scrollable', cards.length > 1);
    if (prev) prev.disabled = index <= 0;
    if (next) next.disabled = index >= cards.length - 1;
  }

  if (prev) {
    prev.addEventListener('click', function () {
      if (index > 0) {
        index -= 1;
        render();
      }
    });
  }
  if (next) {
    next.addEventListener('click', function () {
      if (index < cards.length - 1) {
        index += 1;
        render();
      }
    });
  }

  var surface = root.querySelector('.carousel-viewport') || track;
  var startX = 0;
  var startY = 0;
  var tracking = false;

  function isField(el) {
    return el && el.closest && el.closest('input, select, textarea, button, a, label');
  }

  surface.addEventListener('touchstart', function (e) {
    if (!e.changedTouches || !e.changedTouches[0]) return;
    if (isField(e.target)) {
      tracking = false;
      return;
    }
    startX = e.changedTouches[0].clientX;
    startY = e.changedTouches[0].clientY;
    tracking = true;
  }, { passive: true });

  surface.addEventListener('touchend', function (e) {
    if (!tracking || !e.changedTouches || !e.changedTouches[0]) return;
    tracking = false;
    var dx = e.changedTouches[0].clientX - startX;
    var dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) < 36 || Math.abs(dx) < Math.abs(dy)) return;
    if (dx < 0 && index < cards.length - 1) {
      index += 1;
      render();
    } else if (dx > 0 && index > 0) {
      index -= 1;
      render();
    }
  }, { passive: true });

  render();
  window.addEventListener('resize', render);
});

document.querySelectorAll('.carousel-auto').forEach(function (root) {
  var track = root.querySelector('.carousel-track');
  var viewport = root.querySelector('.carousel-viewport');
  var dotsWrap = root.querySelector('.carousel-dots');
  var prev = root.querySelector('.carousel-btn-prev');
  var next = root.querySelector('.carousel-btn-next');
  if (!track || !viewport) return;

  var slides = Array.prototype.slice.call(track.querySelectorAll('.carousel-slide'));
  if (!slides.length) return;

  var page = 0;
  var dir = 1;
  var timer = null;
  var delay = parseInt(root.getAttribute('data-autoplay'), 10) || 5000;
  var perViewDesktop = parseInt(root.getAttribute('data-per-view'), 10) || 3;

  function perView() {
    if (window.matchMedia('(min-width: 900px)').matches) return Math.min(perViewDesktop, slides.length);
    return 1;
  }

  function maxStart() {
    return Math.max(0, slides.length - perView());
  }

  function pageCount() {
    var pv = perView();
    return Math.max(1, Math.ceil(slides.length / pv));
  }

  function startIndex() {
    return Math.min(page * perView(), maxStart());
  }

  function buildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    var total = pageCount();
    for (var i = 0; i < total; i++) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'carousel-dot' + (i === page ? ' is-active' : '');
      dot.setAttribute('aria-label', 'Ir a la diapositiva ' + (i + 1));
      (function (idx) {
        dot.addEventListener('click', function () {
          dir = idx > page ? 1 : -1;
          page = idx;
          render();
          restart();
        });
      })(i);
      dotsWrap.appendChild(dot);
    }
  }

  function render() {
    var maxPage = pageCount() - 1;
    if (page > maxPage) page = maxPage;
    if (page < 0) page = 0;

    var slide = slides[0];
    var gap = parseFloat(window.getComputedStyle(track).gap) || 16;
    var x = startIndex() * (slide.getBoundingClientRect().width + gap);
    track.style.transform = 'translateX(' + (-x) + 'px)';

    if (dotsWrap) {
      var dots = dotsWrap.querySelectorAll('.carousel-dot');
      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === page);
      });
    }

    var atStart = startIndex() <= 0;
    var atEnd = startIndex() >= maxStart();
    if (prev) prev.disabled = atStart;
    if (next) next.disabled = atEnd;
  }

  function goTo(nextPage) {
    var maxPage = pageCount() - 1;
    page = Math.max(0, Math.min(nextPage, maxPage));
    render();
  }

  function tick() {
    var maxPage = pageCount() - 1;
    if (maxPage <= 0) return;
    if (page >= maxPage) dir = -1;
    else if (page <= 0) dir = 1;
    goTo(page + dir);
  }

  function restart() {
    if (timer) clearInterval(timer);
    if (pageCount() > 1) {
      timer = setInterval(tick, delay);
    }
  }

  if (prev) {
    prev.addEventListener('click', function () {
      dir = -1;
      goTo(page - 1);
      restart();
    });
  }
  if (next) {
    next.addEventListener('click', function () {
      dir = 1;
      goTo(page + 1);
      restart();
    });
  }

  root.addEventListener('mouseenter', function () {
    if (timer) clearInterval(timer);
  });
  root.addEventListener('mouseleave', restart);

  var startX = 0;
  var startY = 0;
  var tracking = false;

  viewport.addEventListener('touchstart', function (e) {
    if (!e.changedTouches || !e.changedTouches[0]) return;
    startX = e.changedTouches[0].clientX;
    startY = e.changedTouches[0].clientY;
    tracking = true;
  }, { passive: true });

  viewport.addEventListener('touchend', function (e) {
    if (!tracking || !e.changedTouches || !e.changedTouches[0]) return;
    tracking = false;
    var dx = e.changedTouches[0].clientX - startX;
    var dy = e.changedTouches[0].clientY - startY;
    if (Math.abs(dx) < 36 || Math.abs(dx) < Math.abs(dy)) return;
    if (dx < 0) {
      dir = 1;
      goTo(page + 1);
    } else {
      dir = -1;
      goTo(page - 1);
    }
    restart();
  }, { passive: true });

  buildDots();
  render();
  restart();
  window.addEventListener('resize', function () {
    buildDots();
    render();
    restart();
  });
});

document.querySelectorAll('.carousel:not(.carousel-cover):not(.carousel-auto)').forEach(function (root) {
  var track = root.querySelector('.carousel-track');
  if (!track) return;

  function syncCarousel() {
    var overflow = track.scrollWidth > track.clientWidth + 8;
    track.classList.toggle('is-centered', !overflow);
    root.classList.toggle('is-scrollable', overflow);
  }

  root.querySelectorAll('.carousel-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var dir = parseInt(btn.getAttribute('data-dir'), 10);
      var card = track.querySelector('.product-card, .cat-card, .tipo-card, .catalog-card');
      var step = card ? card.getBoundingClientRect().width + 16 : 220;
      track.scrollBy({ left: dir * step, behavior: 'smooth' });
    });
  });

  syncCarousel();
  window.addEventListener('resize', syncCarousel);
});

(function () {
  if (!document.body.classList.contains('book-page')) return;
  var key = 'hhs-book-scroll';
  try { history.scrollRestoration = 'manual'; } catch (e) {}

  function restore() {
    try {
      var y = sessionStorage.getItem(key);
      if (y === null) return;
      window.scrollTo(0, parseInt(y, 10) || 0);
    } catch (e) {}
  }

  restore();
  window.addEventListener('DOMContentLoaded', restore);
  window.addEventListener('load', function () {
    restore();
    try { sessionStorage.removeItem(key); } catch (e) {}
  });

  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href*="nueva-reserva.php"]');
    if (!a) return;
    try { sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0)); } catch (err) {}
  });
})();

(function () {
  var map = document.querySelector('.footer-map');
  if (!map) return;
  var src = map.getAttribute('data-src');
  if (!src) return;
  var load = function () {
    if (map.getAttribute('src') === src) return;
    map.src = src;
  };
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(load, { timeout: 2500 });
  } else {
    window.setTimeout(load, 1200);
  }
})();

document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
  var ms = parseInt(el.getAttribute('data-autohide'), 10) || 2000;
  setTimeout(function () {
    el.classList.add('is-gone');
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 260);
  }, ms);
});
</script>

</body>
</html>
