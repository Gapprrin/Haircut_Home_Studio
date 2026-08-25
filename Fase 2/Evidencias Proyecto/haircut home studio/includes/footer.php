</div>
</main>

<footer class="site">
<div class="container container-wide">
<div class="footer-social">
<a href="https://www.facebook.com/haircuthomestudio/" target="_blank" rel="noopener" aria-label="Facebook">
<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1z"/></svg>
</a>
<a href="https://www.instagram.com/haircut.homestudio/" target="_blank" rel="noopener" aria-label="Instagram">
<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm5 4.8A4.2 4.2 0 1 0 16.2 12 4.2 4.2 0 0 0 12 7.8zm6.4-.9a1 1 0 1 0 1 1 1 1 0 0 0-1-1zM12 9.5A2.5 2.5 0 1 1 9.5 12 2.5 2.5 0 0 1 12 9.5z"/></svg>
</a>
<a href="https://wa.me/56954182516" target="_blank" rel="noopener" aria-label="WhatsApp">
<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M12.04 2C6.58 2 2.15 6.4 2.15 11.83a9.78 9.78 0 0 0 1.32 4.92L2 22l5.4-1.41a10 10 0 0 0 4.64 1.18h.04c5.46 0 9.89-4.4 9.89-9.84C21.97 6.4 17.5 2 12.04 2zm5.76 13.91c-.24.68-1.4 1.3-1.94 1.38-.5.07-1.12.1-1.81-.11-.42-.13-.95-.31-1.64-.6-2.88-1.25-4.76-4.15-4.9-4.34-.14-.2-1.15-1.53-1.15-2.92s.73-2.07 1-2.36c.24-.27.52-.34.7-.34h.5c.16 0 .37 0 .57.44.24.52.8 2 .87 2.14.07.14.12.3 0 .48-.1.2-.16.3-.3.47-.15.16-.31.36-.44.49-.15.14-.3.3-.13.58.16.27.73 1.2 1.57 1.95 1.08.96 1.99 1.26 2.27 1.4.28.14.45.12.61-.07.17-.2.7-.81.88-1.09.19-.27.37-.23.62-.14.26.1 1.63.77 1.91.91.28.14.47.2.54.32.07.12.07.68-.17 1.36z"/></svg>
</a>
</div>
<p>Haircut Home Studio &mdash; Melipilla<?php echo !empty($es_admin_ui) ? ' · Panel de administración' : ''; ?></p>
<?php if (!empty($mostrar_acerca)): ?>
<p class="footer-links"><a href="acerca.php">Acerca de nosotros</a></p>
<?php endif; ?>
</div>
</footer>

<script>
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

  btn.addEventListener('click', function () {
    setOpen(!nav.classList.contains('is-open'));
  });
  if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
  if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
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

document.querySelectorAll('.carousel:not(.carousel-cover)').forEach(function (root) {
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
</script>

</body>
</html>
