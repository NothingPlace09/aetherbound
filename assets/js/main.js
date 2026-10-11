(function () {
  var cfg = window.AETHER || {};
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* Loading screen: shown once per browser session */
  var pre = document.getElementById("preloader");
  if (pre) {
    var t0 = Date.now(), closed = false;
    var closePre = function () {
      if (closed) return;
      closed = true;
      setTimeout(function () {
        pre.classList.add("done");
        try { sessionStorage.setItem("aeth_pre", "1"); } catch (e) {}
        setTimeout(function () { if (pre.parentNode) pre.parentNode.removeChild(pre); }, 800);
      }, Math.max(0, 1300 - (Date.now() - t0)));
    };
    if (document.readyState === "complete") closePre(); else window.addEventListener("load", closePre);
    setTimeout(closePre, 5000);
  }

  /* Toast + copy IP */
  var toastEl = document.getElementById("toast"), tt;
  function toast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(tt);
    tt = setTimeout(function () { toastEl.classList.remove("show"); }, 2400);
  }
  function copyIP() {
    var done = function () { toast(cfg.copied); };
    var fail = function () { toast(cfg.copyfail); };
    try { navigator.clipboard.writeText(cfg.ip).then(done, fail); } catch (e) { fail(); }
  }
  document.querySelectorAll("[data-copy]").forEach(function (b) { b.addEventListener("click", copyIP); });

  /* Buttons that are not active yet (Discord login) */
  document.querySelectorAll("[data-soon]").forEach(function (b) {
    b.addEventListener("click", function () { toast(cfg.soon); });
  });

  /* Live password requirements on the sign up form */
  var pwBox = document.querySelector("[data-pwreq]"), pwInput = document.getElementById("password");
  if (pwBox && pwInput) {
    var rules = {
      len: function (v) { return v.length >= 8; },
      upper: function (v) { return /[A-Z]/.test(v); },
      num: function (v) { return /[0-9]|[^A-Za-z0-9]/.test(v); }
    };
    var check = function () {
      pwBox.querySelectorAll("[data-rule]").forEach(function (li) {
        li.classList.toggle("ok", rules[li.getAttribute("data-rule")](pwInput.value));
      });
    };
    pwInput.addEventListener("input", check);
    check();
  }

  /* Spoilers in profile text: click (or Enter / Space) to reveal */
  function toggleSpoiler(el) { el.classList.toggle("shown"); }
  document.addEventListener("click", function (e) {
    var sp = e.target.closest && e.target.closest(".md-spoiler");
    if (sp) toggleSpoiler(sp);
  });
  document.addEventListener("keydown", function (e) {
    if ((e.key === "Enter" || e.key === " ") && e.target.classList && e.target.classList.contains("md-spoiler")) {
      e.preventDefault(); toggleSpoiler(e.target);
    }
  });

  /* Admin: ask before destructive actions, live preview of the role badge */
  document.querySelectorAll("form[data-confirm]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      if (!window.confirm(f.getAttribute("data-confirm"))) e.preventDefault();
    });
  });
  var rn = document.querySelector("[data-role-name]"), rc = document.querySelector("[data-role-color]"), rp = document.querySelector("[data-role-preview]");
  if (rn && rc && rp) {
    var upd = function () { rp.textContent = rn.value.trim() || "…"; rp.style.setProperty("--bc", rc.value); };
    rn.addEventListener("input", upd);
    rc.addEventListener("input", upd);
  }

  /* Platform tabs */
  var tabs = document.querySelectorAll("[data-tab]");
  var panels = document.querySelectorAll("[data-panel]");
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      var id = tab.getAttribute("data-tab");
      tabs.forEach(function (t) { t.setAttribute("aria-selected", t === tab ? "true" : "false"); });
      panels.forEach(function (p) { p.hidden = p.getAttribute("data-panel") !== id; });
    });
  });

  /* Mobile menu */
  var navEl = document.getElementById("nav"), burger = document.getElementById("burger");
  function closeMenu() { navEl.classList.remove("open"); burger.setAttribute("aria-expanded", "false"); }
  burger.addEventListener("click", function () {
    var open = navEl.classList.toggle("open");
    burger.setAttribute("aria-expanded", open ? "true" : "false");
  });
  document.querySelectorAll("#links a").forEach(function (a) { a.addEventListener("click", closeMenu); });

  /* Language dropdown (options are plain links to ?lang=xx) */
  var langMenu = document.getElementById("langmenu"), langBtn = document.getElementById("langbtn");
  var langList = document.getElementById("langlist"), navBox = document.getElementById("navbox");
  function setMenu(open) {
    langMenu.classList.toggle("open", open);
    navBox.classList.toggle("open-lang", open);
    langBtn.setAttribute("aria-expanded", open ? "true" : "false");
  }
  langBtn.addEventListener("click", function (e) { e.stopPropagation(); setMenu(!langMenu.classList.contains("open")); });
  document.addEventListener("click", function (e) {
    if (!langMenu.contains(e.target) && !langList.contains(e.target)) setMenu(false);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { setMenu(false); langBtn.focus(); }
  });

  /* Scroll effects: reveal, nav state, progress bar, background parallax */
  var vh = window.innerHeight;
  if (!reduce && "IntersectionObserver" in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.remove("pre"); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -6% 0px" });
    var groups = [".banner", "#play .sec-head", "#tabs", "#steps", "#community .sec-head", ".community", ".perk", ".divider", ".staff .sec-head", ".staff > .btn", ".foot"];
    groups.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el, i) {
        el.classList.add("rv");
        el.style.transitionDelay = (sel === ".perk" ? i * 120 : 0) + "ms";
        if (el.getBoundingClientRect().top > vh * 0.92) { el.classList.add("pre"); io.observe(el); }
      });
    });
  }
  var navWrap = document.getElementById("navwrap"), bar = document.getElementById("progress"), bgEl = document.getElementById("bgimg");
  var ticking = false;
  function onScroll() {
    var y = window.pageYOffset || document.documentElement.scrollTop || 0;
    var max = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    navWrap.classList.toggle("scrolled", y > 24);
    bar.style.transform = "scaleX(" + Math.min(1, y / max).toFixed(4) + ")";
    if (bgEl && !reduce) bgEl.style.transform = "translate3d(0," + Math.min(y * 0.25, 60).toFixed(1) + "px,0)";
    ticking = false;
  }
  window.addEventListener("scroll", function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  window.addEventListener("resize", function () { vh = window.innerHeight; onScroll(); });
  onScroll();

  /* Pink embers */
  var cv = document.getElementById("embers");
  if (cv && !reduce) {
    var ctx = cv.getContext("2d"), W = 0, H = 0, dots = [];
    var size = function () {
      var r = window.devicePixelRatio || 1;
      W = window.innerWidth; H = window.innerHeight;
      cv.width = W * r; cv.height = H * r;
      ctx.setTransform(r, 0, 0, r, 0, 0);
    };
    var make = function (initial) {
      return {
        x: Math.random() * W, y: initial ? Math.random() * H : H + 10,
        r: 0.8 + Math.random() * 2.2, v: 0.15 + Math.random() * 0.5,
        s: Math.random() * 6.28, w: Math.random() > 0.78
      };
    };
    size();
    var n = Math.min(46, Math.round(W / 34));
    for (var i = 0; i < n; i++) dots.push(make(true));
    window.addEventListener("resize", size);
    (function tick() {
      ctx.clearRect(0, 0, W, H);
      for (var i = 0; i < dots.length; i++) {
        var d = dots[i];
        d.y -= d.v; d.s += 0.012; d.x += Math.sin(d.s) * 0.3;
        if (d.y < -10) dots[i] = make(false);
        ctx.globalAlpha = 0.25 + 0.4 * Math.abs(Math.sin(d.s));
        ctx.fillStyle = d.w ? "#ffffff" : "#ff6aa8";
        ctx.fillRect(d.x, d.y, d.r * 1.6, d.r * 1.6);
      }
      requestAnimationFrame(tick);
    })();
  }
})();
