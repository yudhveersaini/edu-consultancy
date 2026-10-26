/* ==========================================================================
 * EduVista Admissions — site interactions
 * ========================================================================== */
(function () {
  var body = document.body;

  /* ------------------------------------------------ sticky header + top */
  var header = document.querySelector('.header');
  var toTop = document.querySelector('.to-top');
  function onScroll() {
    var y = window.scrollY;
    if (header) header.classList.toggle('is-scrolled', y > 10);
    if (toTop) toTop.classList.toggle('show', y > 600);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

  /* ------------------------------------------------------ mobile nav */
  var toggle = document.querySelector('.nav-toggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = body.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open);
      body.style.overflow = open ? 'hidden' : '';
    });
    document.querySelectorAll('.nav a').forEach(function (a) {
      a.addEventListener('click', function () {
        body.classList.remove('nav-open');
        body.style.overflow = '';
      });
    });
  }

  /* ------------------------------------------------ highlight current page */
  var here = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav a').forEach(function (a) {
    if (a.getAttribute('href') === here) a.classList.add('active');
  });

  /* ------------------------------------------------- reveal on scroll */
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ------------------------------------------------------ count up */
  var counters = document.querySelectorAll('[data-count]');
  function run(el) {
    var end = parseFloat(el.dataset.count), start = null, dur = 1800;
    function tick(t) {
      if (!start) start = t;
      var p = Math.min((t - start) / dur, 1);
      el.textContent = Math.floor(end * (1 - Math.pow(1 - p, 3))).toLocaleString('en-IN');
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  if ('IntersectionObserver' in window) {
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { run(e.target); co.unobserve(e.target); }
      });
    }, { threshold: 0.5 });
    counters.forEach(function (el) { co.observe(el); });
  }

  /* ------------------------------------------------------------ faq */
  document.querySelectorAll('.faq__q').forEach(function (q) {
    q.addEventListener('click', function () {
      var item = q.parentElement;
      var open = item.classList.contains('open');
      item.parentElement.querySelectorAll('.faq__item.open').forEach(function (i) {
        i.classList.remove('open');
        i.querySelector('.faq__q').setAttribute('aria-expanded', 'false');
      });
      if (!open) { item.classList.add('open'); q.setAttribute('aria-expanded', 'true'); }
    });
  });

  /* -------------------------------------------------- course filter */
  var tabs = document.querySelectorAll('.tab[data-filter]');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      var f = tab.dataset.filter;
      document.querySelectorAll('.course[data-cat]').forEach(function (c) {
        c.classList.toggle('is-hidden', f !== 'all' && c.dataset.cat !== f);
      });
    });
  });

  /* ---------------------------------------------- enquiry forms (AJAX) */
  document.querySelectorAll('form[data-enquiry]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var status = form.querySelector('.form-status');
      var btn = form.querySelector('button[type="submit"]');
      var label = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Bhej rahe hain…';
      if (status) status.className = 'form-status';

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.ok) throw new Error(res.error || 'error');
          form.reset();
          if (status) { status.className = 'form-status ok'; status.textContent = 'Shukriya! Hamare counsellor 24 ghante ke andar aapse sampark karenge.'; }
        })
        .catch(function (err) {
          if (status) {
            status.className = 'form-status err';
            status.textContent = (err && err.message && err.message !== 'error' && err.message.indexOf('JSON') === -1)
              ? err.message
              : 'Abhi enquiry nahi bheji ja saki. Kripya call ya WhatsApp karein.';
          }
        })
        .then(function () { btn.disabled = false; btn.innerHTML = label; });
    });
  });

  /* ------------------------------------------------------ footer year */
  document.querySelectorAll('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
