(function () {
  var root = document.querySelector("[data-rules]");
  if (!root) return;
  var tabs = root.querySelectorAll("[data-rule-tab]");
  var panels = root.querySelectorAll("[data-rule-panel]");
  if (!tabs.length) return;

  function show(id, fromUser) {
    var found = false;
    panels.forEach(function (p) { if (p.getAttribute("data-rule-panel") === id) found = true; });
    if (!found) id = tabs[0].getAttribute("data-rule-tab");
    tabs.forEach(function (t) {
      var on = t.getAttribute("data-rule-tab") === id;
      if (on) t.setAttribute("aria-current", "true"); else t.removeAttribute("aria-current");
      if (on && fromUser && t.scrollIntoView && window.innerWidth <= 900) {
        try { t.scrollIntoView({ block: "nearest", inline: "center" }); } catch (e) {}
      }
    });
    panels.forEach(function (p) { p.hidden = p.getAttribute("data-rule-panel") !== id; });
    return id;
  }

  function fromHash() {
    var h = (location.hash || "").replace(/^#r-/, "");
    show(h, false);
  }

  tabs.forEach(function (t) {
    t.addEventListener("click", function (e) {
      e.preventDefault();
      var id = show(t.getAttribute("data-rule-tab"), true);
      if (history.replaceState) history.replaceState(null, "", "#r-" + id);
      if (window.innerWidth <= 900) {
        var top = root.querySelector(".rules-main").getBoundingClientRect().top + window.pageYOffset - 90;
        window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
      }
    });
  });
  window.addEventListener("hashchange", fromHash);
  fromHash();
})();
