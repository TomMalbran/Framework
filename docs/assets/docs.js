// Framework Docs — small progressive-enhancement script (no dependencies)
(function () {
    "use strict";

    // A reader who arrived by the dev subdomain and is being served the release
    // copy is sent to the development one. The check is on which copy this is,
    // not on the path, so it stays right whichever way the subdomain is wired:
    // pointed at the site as it is, this redirects; pointed at it through a rule
    // that rewrites the path, the dev copy arrives already and nothing happens.
    if (location.hostname.indexOf("dev.") === 0 && window.DOCS_BRANCH !== "dev") {
        var canonical = location.hostname.slice(4);
        var here      = location.pathname + location.search + location.hash;
        location.replace(location.protocol + "//" + canonical + "/dev" + here);
        return;
    }

    // Where the site root sits, worked out from the stylesheet rather than the
    // url, so it is right at any depth and on the 404 page. It is kept as an
    // absolute url because navigating changes the depth the page sits at.
    var styleLink = document.querySelector("link[rel=stylesheet][href$=\"styles.css\"]");
    var assetPath = styleLink ? styleLink.getAttribute("href").replace("styles.css", "") : "assets/";
    var root      = assetPath.replace(/assets\/$/, "");
    // Resolved against baseURI rather than the url, since the 404 page is served
    // for any path and carries a <base href> to say where it really sits
    var baseUrl   = new URL(root || "./", document.baseURI).href;
    var assetUrl  = new URL(assetPath, document.baseURI).href;

    // The page being read, as a path from the site root
    function currentPage() {
        var path = location.href.split("#")[0].split("?")[0];
        return path.replace(baseUrl, "") || "index.html";
    }

    // The search dialog and the back-to-top button do nothing at all without this
    // script, so they are built here rather than copied into all 35 pages
    document.body.insertAdjacentHTML("beforeend", [
        "<div class=\"search-dialog\" id=\"searchDialog\" hidden>",
        "<div class=\"search-panel\" role=\"dialog\" aria-modal=\"true\" aria-label=\"Search\">",
        "<div class=\"search-field\">",
        "<input id=\"searchField\" type=\"search\" placeholder=\"Search the docs…\"",
        " autocomplete=\"off\" spellcheck=\"false\">",
        "<kbd>Esc</kbd>",
        "</div>",
        "<div class=\"search-results\" id=\"searchResults\"></div>",
        "<div class=\"search-foot\">",
        "<span><kbd>↑</kbd><kbd>↓</kbd> to navigate</span>",
        "<span><kbd>↵</kbd> to open</span>",
        "<span><kbd>Esc</kbd> to close</span>",
        "</div></div></div>",

        "<button class=\"to-top\" type=\"button\" aria-label=\"Back to top\">",
        "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\"",
        " stroke-width=\"2.4\" stroke-linecap=\"round\" stroke-linejoin=\"round\">",
        "<path d=\"M12 19V5M5 12l7-7 7 7\"/></svg>",
        "</button>",
    ].join(""));

    // Used by the search results and by the schema tables
    function escapeHtml(s) {
        return s.replace(/[&<>"]/g, function (ch) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[ch];
        });
    }

    var sidebar = document.getElementById("sidebar");

    // The mobile header sits flat until there is something scrolled under it,
    // far enough down that a nudge of the page does not set it off
    var topbar = document.querySelector(".topbar");
    if (topbar) {
        var markTopbar = function () {
            topbar.classList.toggle("is-stuck", window.scrollY > 40);
        };
        markTopbar();
        window.addEventListener("scroll", markTopbar, { passive: true });
    }

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

    // Build the menu from the single definition, so a page is added in one
    // place rather than in the sidebar of every page
    function renderNav() {
        var nav = document.getElementById("docsNav");
        if (!nav || !window.DOCS_NAV) { return; }

        var html = "";
        window.DOCS_NAV.forEach(function (group) {
            html += "<div class=\"nav-group\"><h4>" + group.title + "</h4>";
            group.items.forEach(function (item) {
                html += "<a href=\"" + baseUrl + item.url + "\" data-url=\"" + item.url + "\">" +
                    item.name + "</a>";
            });
            html += "</div>";
        });
        nav.innerHTML = html;
        markActive();
    }

    // Marks the page being read. Matched on the whole path, since two sections
    // can hold a page with the same file name
    function markActive() {
        var page = currentPage();
        document.querySelectorAll("#docsNav a").forEach(function (a) {
            a.classList.toggle("active", a.getAttribute("data-url") === page);
        });
    }

    // The badges in the sidebar and the mobile header, so a release only has to
    // touch version.js
    function renderVersion() {
        if (!window.DOCS_VERSION) { return; }

        var tag = "v" + window.DOCS_VERSION;
        var url = "https://github.com/FrameworkDevAR/Framework/releases/tag/" + tag;
        document.querySelectorAll(".version").forEach(function (badge) {
            badge.textContent = tag;
            badge.href = url;
        });
    }

    // Keep the current page, and its neighbours, in view after each navigation
    function centerActive() {
        var active = document.querySelector(".nav-group a.active");
        if (!sidebar || !active) { return; }
        var sRect = sidebar.getBoundingClientRect();
        var aRect = active.getBoundingClientRect();
        sidebar.scrollTop += (aRect.top - sRect.top) - (sidebar.clientHeight - aRect.height) / 2;
    }

    renderVersion();
    renderNav();
    centerActive();
    window.addEventListener("load", centerActive);
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(centerActive); }

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

    // "On this page" table of contents + scroll-spy. Rebuilt on every navigation,
    // so the headings it tracks are held here rather than in the builder
    var heads   = [];
    var linkFor = {};

    function slug(s) {
        return s.toLowerCase().trim().replace(/[^\w]+/g, "-").replace(/^-+|-+$/g, "");
    }

    function spy() {
        if (heads.length === 0) { return; }
        var current = heads[0];
        for (var i = 0; i < heads.length; i++) {
            if (heads[i].getBoundingClientRect().top <= 120) { current = heads[i]; }
            else { break; }
        }
        heads.forEach(function (h) { linkFor[h.id].classList.remove("active"); });
        if (current) { linkFor[current.id].classList.add("active"); }
    }
    window.addEventListener("scroll", spy, { passive: true });

    function buildToc() {
        var docMain = document.querySelector(".doc-main");
        var tocEl   = document.querySelector(".toc");
        var tocList = document.querySelector(".toc ul");
        heads   = [];
        linkFor = {};
        if (!docMain || !tocList) { return; }

        tocList.innerHTML = "";
        heads = Array.prototype.slice.call(docMain.querySelectorAll("h2, h3"));
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

        if (tocEl) { tocEl.style.display = heads.length === 0 ? "none" : ""; }
        spy();
    }

    // Copy-to-clipboard buttons on code blocks
    function addCopyButtons() {
        document.querySelectorAll("pre").forEach(function (pre) {
            if (pre.querySelector(".copy-btn")) { return; }
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
    }

    // The Schema JSON guide shows the framework's own tables by reading the very
    // file it documents, so the page cannot describe a shape it no longer writes
    var schemaCache = null;

    function renderSchema() {
        var host = document.querySelector("[data-schema]");
        if (!host) { return; }

        var draw = function (schema) {
            var tables  = Object.keys(schema);
            var columns = 0;
            var edges   = 0;
            tables.forEach(function (name) {
                columns += schema[name].fields.length;
                edges   += schema[name].foreigns.length;
            });

            var html = "<p>" + tables.length + " tables, " + columns + " columns and " +
                edges + " foreign keys, read from " +
                "<a class=\"src-link\" href=\"" + assetUrl + "schema.json\" target=\"_blank\"" +
                " rel=\"noopener noreferrer\"><code>schema.json</code></a>.</p>";

            tables.forEach(function (name) {
                var table = schema[name];
                html += "<details class=\"schema-table\"><summary><code>" + name + "</code>" +
                    "<span>" + escapeHtml(table.description) + "</span></summary>";

                html += "<table class=\"api-table\"><thead><tr><th>Column</th><th>Type</th>" +
                    "<th>Keys</th></tr></thead><tbody>";
                table.fields.forEach(function (field) {
                    var keys = [];
                    if (field.isPrimary) { keys.push("primary"); }
                    if (field.isKey) { keys.push("indexed"); }
                    html += "<tr><td><code>" + field.name + "</code></td>" +
                        "<td><code>" + field.type + "</code>" +
                        (field.length ? " " + field.length : "") + "</td>" +
                        "<td>" + (keys.length ? keys.join(", ") : "—") + "</td></tr>";
                });
                html += "</tbody></table>";

                if (table.foreigns.length) {
                    html += "<table class=\"api-table\"><thead><tr><th>Points at</th>" +
                        "<th>Table</th><th>Column</th></tr></thead><tbody>";
                    table.foreigns.forEach(function (foreign) {
                        html += "<tr><td><code>" + foreign.fromField + "</code></td>" +
                            "<td><code>" + foreign.toTable + "</code></td>" +
                            "<td><code>" + foreign.toField + "</code></td></tr>";
                    });
                    html += "</tbody></table>";
                }
                html += "</details>";
            });
            host.innerHTML = html;
        };

        if (schemaCache) { draw(schemaCache); return; }
        fetch(assetUrl + "schema.json")
            .then(function (response) {
                if (!response.ok) { throw new Error("missing"); }
                return response.json();
            })
            .then(function (schema) { schemaCache = schema; draw(schema); })
            .catch(function () {
                host.innerHTML = "<p class=\"schema-empty\">The schema could not be read. " +
                    "It is at <a class=\"src-link\" href=\"" + assetUrl + "schema.json\"" +
                    " target=\"_blank\" rel=\"noopener noreferrer\"><code>schema.json</code></a>.</p>";
            });
    }

    buildToc();
    addCopyButtons();
    renderSchema();


    // Navigating without reloading: fetch the target page and swap only the
    // content, so the sidebar keeps its scroll and the shell is not rebuilt.
    // Every page is still a whole document, so this only shortens what the
    // browser would have done, and anything unexpected hands back to it
    var pages    = {};
    var lastPath = location.href.split("#")[0];

    function isReadable(url) {
        return url.indexOf(baseUrl) === 0 && url.split("#")[0].slice(-5) === ".html";
    }

    // recenter is false when the menu itself was clicked — the item is already
    // under the pointer, so moving it away is the one thing nobody asked for
    function swap(url, push, recenter) {
        var cut  = url.indexOf("#");
        var hash = cut >= 0 ? url.slice(cut) : "";
        var path = cut >= 0 ? url.slice(0, cut) : url;

        function render(html) {
            var doc  = new DOMParser().parseFromString(html, "text/html");
            var next = doc.querySelector(".content");
            var here = document.querySelector(".content");
            if (!next || !here) { location.href = url; return; }

            // The url is moved first, so the relative links in what is inserted
            // resolve against the page they came from, not the one leaving
            if (push) { history.pushState({}, "", url); }
            lastPath = path;

            // Only the 404 page carries a <base href>, and it would keep every
            // relative link resolving against the site root once we leave it
            var base = document.querySelector("base");
            if (base) { base.remove(); }

            here.innerHTML = next.innerHTML;
            document.title = doc.title;

            markActive();
            if (recenter) { centerActive(); }
            buildToc();
            addCopyButtons();
            renderSchema();
            if (window.Prism) { window.Prism.highlightAll(); }

            var target = hash ? document.getElementById(hash.slice(1)) : null;
            if (target) { target.scrollIntoView(); } else { window.scrollTo(0, 0); }
        }

        if (pages[path]) { render(pages[path]); return; }
        fetch(path)
            .then(function (response) {
                if (!response.ok) { throw new Error("missing"); }
                return response.text();
            })
            .then(function (html) { pages[path] = html; render(html); })
            .catch(function () { location.href = url; });
    }

    document.addEventListener("click", function (e) {
        if (e.defaultPrevented || e.button !== 0) { return; }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }

        var link = e.target.closest("a[href]");
        if (!link || link.target === "_blank" || link.hasAttribute("download")) { return; }
        if (!isReadable(link.href)) { return; }

        // An anchor within the page being read is the browser's own job
        if (link.href.split("#")[0] === location.href.split("#")[0]) { return; }

        e.preventDefault();
        document.body.classList.remove("nav-open");

        var dialog = document.getElementById("searchDialog");
        if (dialog && !dialog.hidden) {
            dialog.hidden = true;
            document.body.classList.remove("search-open-body");
        }
        // Anywhere else — the content, the search, the 404 page — can lead to a
        // page far outside the menu's view, so there it is brought back into it
        swap(link.href, true, !link.closest("#docsNav"));
    });

    window.addEventListener("popstate", function () {
        // Moving between anchors of one page changes no content
        if (location.href.split("#")[0] === lastPath) { return; }
        // Back and forward can land anywhere, so the menu follows along
        swap(location.href, false, true);
    });


    // Search — a command palette: ⌘K or / to open, arrows to move, Enter to go
    var searchDialog = document.getElementById("searchDialog");
    var searchInput  = document.getElementById("searchField");
    var searchBox    = document.getElementById("searchResults");
    var searchOpen   = document.getElementById("searchOpen");
    if (searchDialog && searchInput && searchBox) {
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
            fetch(assetUrl + "search.json")
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    index = data;
                    loading = false;
                    if (pending) { var run = pending; pending = null; run(); }
                })
                .catch(function () { loading = false; pending = null; });
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
                html += "<a href=\"" + baseUrl + e.u + "\">" +
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
                html += "<a href=\"" + baseUrl + e.u + "\">" +
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
            // On a phone the box that opens this sits inside the menu, which would
            // otherwise stay open behind the dialog
            document.body.classList.remove("nav-open");
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
