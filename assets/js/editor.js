(function () {
  var box = document.querySelector("[data-mdx]");
  if (!box) return;
  var ta = box.querySelector("[data-mdx-input]");
  var preview = box.querySelector("[data-mdx-preview]");
  var tools = box.querySelector(".mdx-tools");
  var picker = box.querySelector("[data-mdx-color]");
  var tabs = box.querySelectorAll("[data-mdx-tab]");
  var counter = document.querySelector("[data-mdx-count]");
  var max = parseInt(box.getAttribute("data-max"), 10) || 1500;
  var icons = box.querySelector("[data-mdx-icons]");
  var savedSel = null;

  function replace(start, end, text, selStart, selEnd) {
    ta.focus();
    ta.setSelectionRange(start, end);
    var ok = false;
    try { ok = document.execCommand("insertText", false, text); } catch (e) { ok = false; }
    if (!ok) ta.setRangeText(text, start, end, "end");
    ta.setSelectionRange(selStart, selEnd);
    count();
  }

  function wrap(before, after, placeholder, sel) {
    var s = sel ? sel[0] : ta.selectionStart, e = sel ? sel[1] : ta.selectionEnd;
    var chosen = ta.value.slice(s, e);
    var inner = chosen || placeholder;
    replace(s, e, before + inner + after, s + before.length, s + before.length + inner.length);
  }

  function lineRange() {
    var v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
    var a = v.lastIndexOf("\n", s - 1) + 1;
    var b = v.indexOf("\n", e);
    if (e > s && v.charAt(e - 1) === "\n") b = e - 1;
    if (b === -1) b = v.length;
    return [a, b];
  }

  function prefixLines(prefix, isHeading) {
    var r = lineRange(), text = ta.value.slice(r[0], r[1]);
    var lines = text.split("\n");
    var allHave = lines.every(function (l) { return l.indexOf(prefix) === 0; });
    var out = lines.map(function (l) {
      if (isHeading) l = l.replace(/^#{1,3}[ \t]+/, "");
      if (allHave) return isHeading ? l : l.slice(prefix.length);
      return prefix + l;
    }).join("\n");
    replace(r[0], r[1], out, r[0], r[0] + out.length);
  }

  var commands = {
    bold: function () { wrap("**", "**", "bold"); },
    italic: function () { wrap("*", "*", "italic"); },
    spoiler: function () { wrap("||", "||", "spoiler"); },
    code: function () {
      var chosen = ta.value.slice(ta.selectionStart, ta.selectionEnd);
      if (chosen.indexOf("\n") !== -1) wrap("```\n", "\n```", "code");
      else wrap("`", "`", "code");
    },
    link: function () {
      var s = ta.selectionStart, e = ta.selectionEnd, chosen = ta.value.slice(s, e) || "link";
      var url = "https://";
      var text = "[" + chosen + "](" + url + ")";
      var from = s + chosen.length + 3;
      replace(s, e, text, from, from + url.length);
    },
    quote: function () { prefixLines("> ", false); },
    list: function () { prefixLines("- ", false); },
    center: function () { wrap("[center]", "[/center]", "text"); },
    h1: function () { prefixLines("# ", true); },
    h2: function () { prefixLines("## ", true); },
    h3: function () { prefixLines("### ", true); },
    hr: function () {
      var s = ta.selectionEnd, v = ta.value;
      var pre = s > 0 && v.charAt(s - 1) !== "\n" ? "\n" : "";
      var ins = pre + "---\n";
      replace(s, s, ins, s + ins.length, s + ins.length);
    },
    icon: function () {
      if (icons) icons.hidden = !icons.hidden;
    },
    color: function () {
      savedSel = [ta.selectionStart, ta.selectionEnd];
      if (picker.showPicker) { try { picker.showPicker(); return; } catch (e) {} }
      picker.click();
    }
  };

  picker.addEventListener("input", function () {
    if (!savedSel) return;
    wrap("[color=" + picker.value + "]", "[/color]", "text", savedSel);
    savedSel = [ta.selectionStart, ta.selectionEnd];
  });

  tools.addEventListener("mousedown", function (e) {
    if (e.target.closest("button")) e.preventDefault();
  });
  tools.addEventListener("click", function (e) {
    var b = e.target.closest("button[data-cmd]");
    if (!b || box.classList.contains("previewing")) return;
    var fn = commands[b.getAttribute("data-cmd")];
    if (fn) fn();
  });

  var iconColor = null;
  if (icons) {
    var colorIn = icons.querySelector("[data-icon-color]"), colorOff = icons.querySelector("[data-icon-nocolor]");
    var paint = function () {
      icons.querySelectorAll("button[data-icon]").forEach(function (b) { b.style.color = iconColor || ""; });
      if (colorOff) colorOff.hidden = !iconColor;
    };
    if (colorIn) colorIn.addEventListener("input", function () { iconColor = colorIn.value; paint(); });
    if (colorOff) colorOff.addEventListener("click", function () { iconColor = null; paint(); });
    icons.addEventListener("mousedown", function (e) { if (e.target.closest("button")) e.preventDefault(); });
    icons.addEventListener("click", function (e) {
      var b = e.target.closest("button[data-icon]");
      if (!b || box.classList.contains("previewing")) return;
      var s = ta.selectionStart, ins = ":" + b.getAttribute("data-icon") + (iconColor ? iconColor : "") + ": ";
      replace(s, ta.selectionEnd, ins, s + ins.length, s + ins.length);
    });
  }

  ta.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey) {
      var k = e.key.toLowerCase();
      if (k === "b") { e.preventDefault(); commands.bold(); }
      else if (k === "i") { e.preventDefault(); commands.italic(); }
    }
  });

  function count() {
    if (counter) counter.textContent = ta.value.length + " / " + max;
  }
  ta.addEventListener("input", count);
  count();

  var req = 0;
  function show(which) {
    var prev = which === "preview";
    box.classList.toggle("previewing", prev);
    tabs.forEach(function (t) { t.setAttribute("aria-selected", t.getAttribute("data-mdx-tab") === which ? "true" : "false"); });
    tools.querySelectorAll("button").forEach(function (b) { b.disabled = prev; });
    if (icons && prev) icons.hidden = true;
    ta.hidden = prev;
    preview.hidden = !prev;
    if (!prev) { ta.focus(); return; }
    var mine = ++req;
    preview.innerHTML = '<p class="ptext empty"></p>';
    preview.firstChild.textContent = box.getAttribute("data-loading") || "…";
    var body = new URLSearchParams();
    body.set("csrf", box.getAttribute("data-csrf"));
    body.set("text", ta.value);
    body.set("kind", box.getAttribute("data-kind") || "");
    fetch(box.getAttribute("data-preview-url"), {
      method: "POST", credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString()
    }).then(function (r) { if (!r.ok) throw new Error("preview"); return r.text(); })
      .then(function (html) { if (mine === req) preview.innerHTML = html; })
      .catch(function () { if (mine === req) preview.textContent = "—"; });
  }
  tabs.forEach(function (t) {
    t.addEventListener("click", function () { show(t.getAttribute("data-mdx-tab")); });
  });
})();
