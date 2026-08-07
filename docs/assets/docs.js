// Framework Docs — small progressive-enhancement script (no dependencies)
(function () {
    "use strict";

    // Mobile navigation toggle
    var toggle = document.querySelector(".menu-toggle");
    if (toggle) {
        toggle.addEventListener("click", function () {
            document.body.classList.toggle("nav-open");
        });
        document.addEventListener("click", function (e) {
            if (document.body.classList.contains("nav-open") &&
                !e.target.closest(".sidebar") && !e.target.closest(".menu-toggle")) {
                document.body.classList.remove("nav-open");
            }
        });
    }

    // Highlight the current page in the sidebar
    var here = location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".nav-group a").forEach(function (a) {
        var href = (a.getAttribute("href") || "").split("/").pop();
        if (href === here) { a.classList.add("active"); }
    });

    // Scroll the active nav item to the middle of the sidebar, so the current
    // page (and its neighbours) stay visible after each navigation.
    var sidebar = document.getElementById("sidebar");
    var activeLink = document.querySelector(".nav-group a.active");
    if (sidebar && activeLink) {
        var centerActive = function () {
            var sRect = sidebar.getBoundingClientRect();
            var aRect = activeLink.getBoundingClientRect();
            var delta = (aRect.top - sRect.top) - (sidebar.clientHeight - aRect.height) / 2;
            sidebar.scrollTop += delta;
        };
        // Run now, and again once web fonts settle (they change the sidebar height).
        centerActive();
        window.addEventListener("load", centerActive);
        if (document.fonts && document.fonts.ready) { document.fonts.ready.then(centerActive); }
    }

    // Back-to-top button: reveal after scrolling, smooth-scroll to top on click
    var toTop = document.querySelector(".to-top");
    if (toTop) {
        var toggleTop = function () {
            toTop.classList.toggle("is-visible", window.scrollY > 400);
        };
        toggleTop();
        window.addEventListener("scroll", toggleTop, { passive: true });
        toTop.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    // "On this page" table of contents + scroll-spy
    var docMain = document.querySelector(".doc-main");
    var tocList = document.querySelector(".toc ul");
    if (docMain && tocList) {
        var slug = function (s) {
            return s.toLowerCase().trim().replace(/[^\w]+/g, "-").replace(/^-+|-+$/g, "");
        };
        var heads = Array.prototype.slice.call(docMain.querySelectorAll("h2, h3"));
        var linkFor = {};

        heads.forEach(function (h) {
            if (!h.id) { h.id = slug(h.textContent); }
            var a = document.createElement("a");
            a.href = "#" + h.id;
            a.textContent = h.textContent;
            a.className = h.tagName === "H3" ? "lvl-3" : "lvl-2";
            var li = document.createElement("li");
            li.appendChild(a);
            tocList.appendChild(li);
            linkFor[h.id] = a;
        });

        if (heads.length === 0) {
            var tocEl = document.querySelector(".toc");
            if (tocEl) { tocEl.style.display = "none"; }
        } else {
            var spy = function () {
                var current = heads[0];
                for (var i = 0; i < heads.length; i++) {
                    if (heads[i].getBoundingClientRect().top <= 120) { current = heads[i]; }
                    else { break; }
                }
                heads.forEach(function (h) { linkFor[h.id].classList.remove("active"); });
                if (current) { linkFor[current.id].classList.add("active"); }
            };
            spy();
            window.addEventListener("scroll", spy, { passive: true });
        }
    }

    // Copy-to-clipboard buttons on code blocks
    document.querySelectorAll("pre").forEach(function (pre) {
        var btn = document.createElement("button");
        btn.className = "copy-btn";
        btn.type = "button";
        btn.textContent = "Copy";
        btn.addEventListener("click", function () {
            var code = pre.querySelector("code");
            var text = code ? code.innerText : pre.innerText;
            navigator.clipboard.writeText(text).then(function () {
                btn.textContent = "Copied";
                setTimeout(function () { btn.textContent = "Copy"; }, 1400);
            });
        });
        pre.appendChild(btn);
    });


    // Search — a command palette: ⌘K or / to open, arrows to move, Enter to go
    var searchDialog = document.getElementById("searchDialog");
    var searchInput  = document.getElementById("searchField");
    var searchBox    = document.getElementById("searchResults");
    var searchOpen   = document.getElementById("searchOpen");
    if (searchDialog && searchInput && searchBox) {
        var base    = document.querySelector("link[rel=stylesheet][href$=\"styles.css\"]");
        var prefix  = base ? base.getAttribute("href").replace("styles.css", "") : "assets/";
        var root    = prefix.replace(/assets\/$/, "");
        var index   = null;
        var loading = false;
        var current = -1;

        // Shown before anything is typed — the pages most people open first
        var FEATURED = [
            "introduction/getting-started.html",
            "core/discovery.html",
            "core/build.html",
            "core/routing.html",
            "database/models.html",
            "database/query.html",
            "database/migrations.html",
            "introduction/cli.html"
        ];

        // Keeps the last callback while the index is in flight, so typing before it
        // arrives still renders once it does
        var pending = null;
        function loadIndex(then) {
            if (index) { then(); return; }
            pending = then;
            if (loading) { return; }
            loading = true;
            fetch(prefix + "search.json")
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    index = data;
                    loading = false;
                    if (pending) { var run = pending; pending = null; run(); }
                })
                .catch(function () { loading = false; pending = null; });
        }

        function escapeHtml(s) {
            return s.replace(/[&<>"]/g, function (ch) {
                return { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[ch];
            });
        }

        function highlight(text, query) {
            var at = text.toLowerCase().indexOf(query);
            if (at < 0) { return escapeHtml(text); }
            return escapeHtml(text.slice(0, at)) +
                "<mark>" + escapeHtml(text.slice(at, at + query.length)) + "</mark>" +
                escapeHtml(text.slice(at + query.length));
        }

        // A snippet of the body around the match, so a body hit shows why it matched
        function snippet(text, query) {
            var at = text.toLowerCase().indexOf(query);
            if (at < 0) { return escapeHtml(text.slice(0, 110)) + (text.length > 110 ? "…" : ""); }
            var from = Math.max(0, at - 40);
            var cut  = text.slice(from, from + 130);
            return (from > 0 ? "…" : "") + highlight(cut, query) + "…";
        }

        function score(entry, query) {
            var title = entry.t.toLowerCase();
            if (title === query)            { return 0; }
            if (title.indexOf(query) === 0) { return 1; }
            if (title.indexOf(query) > 0)   { return 2; }
            if (entry.s.toLowerCase().indexOf(query) >= 0) { return 3; }
            if (entry.d.toLowerCase().indexOf(query) >= 0) { return 4; }
            return -1;
        }

        function render(query) {
            var found = [];
            for (var i = 0; i < index.length; i++) {
                var s = score(index[i], query);
                if (s >= 0) { found.push({ e: index[i], s: s }); }
            }
            found.sort(function (a, b) {
                if (a.s !== b.s) { return a.s - b.s; }
                // Same relevance: a whole page beats a section of one, then the shorter title
                var pa = a.e.u.indexOf("#") < 0, pb = b.e.u.indexOf("#") < 0;
                if (pa !== pb) { return pa ? -1 : 1; }
                return a.e.t.length - b.e.t.length;
            });
            found = found.slice(0, 20);

            if (!found.length) {
                searchBox.innerHTML = "<p class=\"search-empty\">Nothing found for “" + escapeHtml(query) + "”</p>";
                searchBox.scrollTop = 0;
                current = -1;
                return;
            }

            var html = "";
            for (var j = 0; j < found.length; j++) {
                var e = found[j].e;
                html += "<a href=\"" + root + e.u + "\">" +
                    "<span class=\"sr-title\">" + highlight(e.t, query) + "</span>" +
                    "<span class=\"sr-where\">" + escapeHtml(e.s) + "</span>" +
                    (found[j].s === 4 ? "<span class=\"sr-text\">" + snippet(e.d, query) + "</span>" : "") +
                    "</a>";
            }
            searchBox.innerHTML = html;
            searchBox.scrollTop = 0;
            select(0, false);
        }

        // The starting point: the featured pages, looked up in the index so their
        // titles and sections stay right even as the docs change
        function showFeatured() {
            var html = "<p class=\"sr-group\">Start here</p>";
            for (var i = 0; i < FEATURED.length; i++) {
                var e = null;
                for (var j = 0; j < index.length; j++) {
                    if (index[j].u === FEATURED[i]) { e = index[j]; break; }
                }
                if (!e) { continue; }
                html += "<a href=\"" + root + e.u + "\">" +
                    "<span class=\"sr-title\">" + escapeHtml(e.t) + "</span>" +
                    "<span class=\"sr-where\">" + escapeHtml(e.s) + "</span></a>";
            }
            searchBox.innerHTML = html;
            searchBox.scrollTop = 0;
            select(0, false);
        }

        // Either the featured list or the matches, depending on what is typed
        function update() {
            var query = searchInput.value.trim().toLowerCase();
            if (query.length < 2) { showFeatured(); } else { render(query); }
        }

        function select(at, scroll) {
            var items = searchBox.querySelectorAll("a");
            if (!items.length) { current = -1; return; }
            if (current >= 0 && items[current]) { items[current].classList.remove("active"); }
            current = (at + items.length) % items.length;
            items[current].classList.add("active");
            if (scroll) { items[current].scrollIntoView({ block: "nearest" }); }
        }

        // Hovering moves the selection, so the arrows carry on from the row under the
        // pointer. This tracks mousemove rather than mouseover, and ignores a repeat of
        // the same position, so scrolling the list by keyboard cannot steal the
        // selection back to whatever happens to slide under a still pointer.
        var lastX = -1, lastY = -1;
        searchBox.addEventListener("mousemove", function (e) {
            if (e.clientX === lastX && e.clientY === lastY) { return; }
            lastX = e.clientX;
            lastY = e.clientY;

            var link = e.target.closest("a");
            if (!link) { return; }
            var at = Array.prototype.indexOf.call(searchBox.querySelectorAll("a"), link);
            if (at >= 0 && at !== current) { select(at, false); }
        });

        function openSearch() {
            searchDialog.hidden = false;
            document.body.classList.add("search-open-body");
            searchInput.focus();
            searchInput.select();
            loadIndex(update);
        }

        function closeSearch() {
            searchDialog.hidden = true;
            document.body.classList.remove("search-open-body");
            current = -1;
        }

        // Anything marked data-search opens it — the sidebar box, and the 404 button
        document.querySelectorAll("[data-search]").forEach(function (el) {
            el.addEventListener("click", openSearch);
        });
        if (searchOpen && !/Mac|iP(hone|ad)/.test(navigator.platform)) {
            searchOpen.querySelector("kbd").textContent = "Ctrl K";
        }

        searchInput.addEventListener("input", function () {
            loadIndex(update);
        });

        searchInput.addEventListener("keydown", function (e) {
            var items = searchBox.querySelectorAll("a");
            if (e.key === "Escape") { closeSearch(); return; }
            if (!items.length) { return; }
            if (e.key === "ArrowDown")    { e.preventDefault(); select(current + 1, true); }
            else if (e.key === "ArrowUp") { e.preventDefault(); select(current - 1, true); }
            else if (e.key === "Enter" && current >= 0) { e.preventDefault(); items[current].click(); }
        });

        // Clicking the backdrop closes it; clicking inside the panel does not
        searchDialog.addEventListener("click", function (e) {
            if (!e.target.closest(".search-panel")) { closeSearch(); }
        });

        // ⌘K / Ctrl+K anywhere, and "/" when not already typing
        document.addEventListener("keydown", function (e) {
            var typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
                e.preventDefault();
                searchDialog.hidden ? openSearch() : closeSearch();
            } else if (e.key === "/" && !typing && searchDialog.hidden) {
                e.preventDefault();
                openSearch();
            }
        });
    }
    // Show a divider under the sidebar header once the nav scrolls beneath it
    var sidebarHead = document.querySelector(".sidebar-head");
    if (sidebar && sidebarHead) {
        var markStuck = function () {
            sidebarHead.classList.toggle("is-stuck", sidebar.scrollTop > 4);
        };
        markStuck();
        sidebar.addEventListener("scroll", markStuck, { passive: true });
    }

})();
