(function () {
  var form = document.querySelector("[data-apply]");
  if (!form) return;
  var steps = Array.prototype.slice.call(form.querySelectorAll("[data-step]"));
  var bars = form.querySelectorAll(".ap-prog i");
  var btnPrev = form.querySelector("[data-prev]");
  var btnNext = form.querySelector("[data-next]");
  var btnSubmit = form.querySelector("[data-submit]");
  var review = form.querySelector("[data-review]");
  var current = 0;
  form.classList.add("js");

  function clearErr(box) {
    box.querySelectorAll("[data-err]").forEach(function (n) { n.remove(); });
  }
  function addErr(anchor, msg) {
    var p = document.createElement("p");
    p.className = "ap-err";
    p.setAttribute("data-err", "");
    p.textContent = msg;
    anchor.appendChild(p);
  }

  function show(i, scroll) {
    current = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach(function (s, n) { s.classList.toggle("active", n === current); });
    bars.forEach(function (b, n) { b.classList.toggle("on", n <= current); });
    btnPrev.hidden = current === 0;
    btnNext.hidden = current === steps.length - 1;
    btnSubmit.hidden = current !== steps.length - 1;
    if (current === steps.length - 1) buildReview();
    if (scroll) {
      var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
      window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
    }
  }

  form.querySelectorAll(".ap-day").forEach(function (day) {
    var boxes = day.querySelectorAll("input[type=checkbox]");
    boxes.forEach(function (b) {
      b.addEventListener("change", function () {
        if (!b.checked) return;
        boxes.forEach(function (o) {
          if (o === b) return;
          if (b.value === "never" || o.value === "never") o.checked = false;
        });
      });
    });
  });

  function validate(step) {
    clearErr(step);
    var ok = true;
    var fields = step.querySelectorAll("input, textarea, select");
    for (var i = 0; i < fields.length; i++) {
      var f = fields[i];
      if (f.type === "checkbox" && f.name.indexOf("avail") === 0) continue;
      if (!f.checkValidity()) {
        ok = false;
        f.reportValidity();
        f.focus();
        break;
      }
    }
    var avail = step.querySelector("[data-avail]");
    if (ok && avail) {
      var missing = false;
      avail.querySelectorAll(".ap-day").forEach(function (day) {
        if (!day.querySelector("input:checked")) missing = true;
      });
      if (missing) {
        ok = false;
        addErr(avail, form.getAttribute("data-avail-error") || "Pick at least one option for every day.");
      }
    }
    return ok;
  }

  function addRow(label, value) {
    var dt = document.createElement("dt");
    dt.textContent = label;
    var dd = document.createElement("dd");
    dd.textContent = value;
    review.appendChild(dt);
    review.appendChild(dd);
  }

  function labelOf(field) {
    var l = form.querySelector('label[for="' + field.id + '"]');
    return l ? l.textContent.replace(/\s*\*\s*$/, "").trim() : field.name;
  }

  function buildReview() {
    review.textContent = "";
    var radio = form.querySelector('input[name="premium"]:checked');
    form.querySelectorAll("[data-step='1'] input[type=text], [data-step='1'] input[type=number]").forEach(function (f) {
      addRow(labelOf(f), f.value);
    });
    var prem = form.querySelector("[data-step='1'] fieldset legend");
    if (prem && radio) addRow(prem.textContent.replace(/\s*\*\s*$/, "").trim(), radio.parentNode.textContent.trim());
    var avail = [];
    form.querySelectorAll(".ap-day").forEach(function (day) {
      var picked = [];
      day.querySelectorAll("input:checked").forEach(function (c) { picked.push(c.parentNode.textContent.trim()); });
      avail.push(day.querySelector("legend").textContent.trim() + ": " + picked.join(", "));
    });
    addRow(form.querySelector("[data-step='2'] h3").textContent, avail.join("\n"));
    form.querySelectorAll("[data-step='3'] textarea, [data-step='3'] input[type=text]").forEach(function (f) {
      addRow(labelOf(f), f.value || "—");
    });
  }

  btnNext.addEventListener("click", function () {
    if (validate(steps[current])) show(current + 1, true);
  });
  btnPrev.addEventListener("click", function () { show(current - 1, true); });

  form.addEventListener("submit", function (e) {
    for (var i = 0; i < steps.length - 1; i++) {
      if (!validate(steps[i])) { e.preventDefault(); show(i, true); return; }
    }
    if (!form.querySelector("input[name=confirm]").checkValidity()) {
      e.preventDefault();
      form.querySelector("input[name=confirm]").reportValidity();
      return;
    }
    btnSubmit.disabled = true;
    btnSubmit.textContent = btnSubmit.getAttribute("data-sending") || "…";
  });

  var first = form.querySelector("[data-err]");
  var start = 0;
  if (first) {
    var s = first.closest("[data-step]");
    if (s) start = steps.indexOf(s);
  }
  show(start, false);
})();
