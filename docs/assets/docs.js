// Framework Docs — small progressive-enhancement script (no dependencies)
//
// It reads from the top: main() says what the site does, in order, and every
// function it names is defined below it. Nothing runs until the last line.
(function () {
    "use strict";

    // Where the site root and its assets sit. Worked out once by readSite(), and
    // kept as absolute urls because navigating changes the depth of the page
    let baseUrl  = "";
    let assetUrl = "";

    // The parts of the chrome that are driven from here
    let sidebar     = null;
    let sidebarHead = null;
    let topbar      = null;
    let toTop       = null;
    let menuToggle  = null;

    // The headings of the page being read, and the link that points at each
    let heads   = [];
    let linkFor = {};

    // The pages already fetched, and the one being read, for navigating in place
    const pages = {};
    let lastPath = "";

    // Read once, and drawn again on every visit to the page that shows it
    let schemaCache = null;

    // The search dialog, and the state of the index behind it
    let searchDialog  = null;
    let searchInput   = null;
    let searchBox     = null;
    let searchButton  = null;
    let searchIndex   = null;
    let searchLoading = false;
    let searchPending = null;
    let searchAt      = -1;
    let lastX         = -1;
    let lastY         = -1;

    // Shown before anything is typed — the pages most people open first
    const FEATURED = [
        "introduction/getting-started.html",
        "core/discovery.html",
        "core/build.html",
        "core/routing.html",
        "database/models.html",
        "database/query.html",
        "database/migrations.html",
        "introduction/cli.html"
    ];


    /**
     * Runs the documentation site
     * @return {void}
     */
    function main() {
        if (goToDevCopy()) {
            return;
        }

        readSite();
        buildChrome();
        findElements();

        renderVersion();
        renderNav();
        centerActive();
        renderContent();

        watchTopbar();
        watchMenu();
        watchBackToTop();
        watchSidebarHead();
        watchHeadings();
        watchNavigation();
        watchSearch();

        window.addEventListener("load", centerActive);
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(centerActive);
        }
    }



    /**
     * Sends a reader who arrived by the dev subdomain to the development copy
     * @return {boolean} True when the page is leaving, so nothing else should run
     */
    function goToDevCopy() {
        // Keyed on which copy this is, not on the path, so it stays right
        // whichever way the subdomain is wired
        if (location.hostname.indexOf("dev.") !== 0 || window.DOCS_BRANCH === "dev") {
            return false;
        }

        const canonical = location.hostname.slice(4);
        const here      = `${location.pathname}${location.search}${location.hash}`;

        location.replace(`${location.protocol}//${canonical}/dev${here}`);
        return true;
    }

    /**
     * Works out where the site root and its assets sit
     * @return {void}
     */
    function readSite() {
        // From the stylesheet rather than the url, so it is right at any depth,
        // and against baseURI, since the 404 page carries a base href
        const link = document.querySelector(`link[rel=stylesheet][href$="styles.css"]`);
        const path = link ? link.getAttribute("href").replace("styles.css", "") : "assets/";
        const root = path.replace(/assets\/$/, "");

        baseUrl  = new URL(root || "./", document.baseURI).href;
        assetUrl = new URL(path, document.baseURI).href;
        lastPath = location.href.split("#")[0];
    }

    /**
     * Builds the search dialog and the back-to-top button
     * @return {void}
     */
    function buildChrome() {
        // Both do nothing without this script, so they are built here rather
        // than copied into all 35 pages
        document.body.insertAdjacentHTML("beforeend", `
            <div class="search-dialog" id="searchDialog" hidden>
                <div class="search-panel" role="dialog" aria-modal="true" aria-label="Search">
                    <div class="search-field">
                        <input id="searchField" type="search" placeholder="Search the docs…"
                            autocomplete="off" spellcheck="false">
                        <kbd>Esc</kbd>
                    </div>
                    <div class="search-results" id="searchResults"></div>
                    <div class="search-foot">
                        <span><kbd>↑</kbd><kbd>↓</kbd> to navigate</span>
                        <span><kbd>↵</kbd> to open</span>
                        <span><kbd>Esc</kbd> to close</span>
                    </div>
                </div>
            </div>

            <button class="to-top" type="button" aria-label="Back to top">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 19V5M5 12l7-7 7 7"/>
                </svg>
            </button>
        `);
    }

    /**
     * Finds the parts of the page the rest of this works on
     * @return {void}
     */
    function findElements() {
        // After the chrome is built, so the search dialog is among them
        sidebar      = document.getElementById("sidebar");
        sidebarHead  = document.querySelector(".sidebar-head");
        topbar       = document.querySelector(".topbar");
        toTop        = document.querySelector(".to-top");
        menuToggle   = document.querySelector(".menu-toggle");
        searchDialog = document.getElementById("searchDialog");
        searchInput  = document.getElementById("searchField");
        searchBox    = document.getElementById("searchResults");
        searchButton = document.getElementById("searchOpen");
    }



    /**
     * Fills the version badges, so a release only has to touch version.js
     * @return {void}
     */
    function renderVersion() {
        // Anywhere that is not the released site says so rather than naming a
        // version, which would be the last released one and so a lie
        const badges = document.querySelectorAll(".version");
        const local  = isLocal();
        const isDev  = local || (window.DOCS_BRANCH && window.DOCS_BRANCH !== "main");

        if (isDev) {
            for (const badge of badges) {
                markAsDev(badge, local);
            }
            return;
        }
        if (!window.DOCS_VERSION) {
            return;
        }

        for (const badge of badges) {
            markAsRelease(badge, window.DOCS_VERSION);
        }
    }

    /**
     * Marks the given badge as the development documentation
     * @param {HTMLAnchorElement} badge
     * @param {boolean}           local Whether it is read from a working copy, which leads nowhere
     * @return {void}
     */
    function markAsDev(badge, local) {
        badge.textContent = "dev";
        badge.classList.add("is-dev");
        badge.removeAttribute("target");

        if (local) {
            badge.removeAttribute("href");
            badge.title = "The documentation in your working copy";
            return;
        }

        badge.href  = releaseUrl();
        badge.title = "You are reading the development documentation. Go to the released one";
    }

    /**
     * Marks the given badge as the released documentation, at the given version
     * @param {HTMLAnchorElement} badge
     * @param {string}            version
     * @return {void}
     */
    function markAsRelease(badge, version) {
        const tag = `v${version}`;

        badge.textContent = tag;
        badge.href = `https://github.com/FrameworkDevAR/Framework/releases/tag/${tag}`;
    }

    /**
     * Returns true if this is served from a working copy rather than the published site
     * @return {boolean}
     */
    function isLocal() {
        // The stamp telling the published copies apart only happens at deploy
        return location.protocol === "file:" ||
            location.hostname === "localhost" ||
            location.hostname === "127.0.0.1" ||
            location.hostname === "[::1]";
    }

    /**
     * Returns where the released documentation sits, seen from the development
     * copy. It is a directory under the same site, unless the reader came by the
     * dev subdomain, in which case it is the site the subdomain hangs off
     * @return {string}
     */
    function releaseUrl() {
        if (location.hostname.indexOf("dev.") === 0) {
            return `${location.protocol}//${location.hostname.slice(4)}/`;
        }
        return baseUrl.replace(/dev\/$/, "");
    }



    /**
     * Builds the menu from the single definition, so a page is added in one
     * place rather than in the sidebar of every page
     * @return {void}
     */
    function renderNav() {
        const nav = document.getElementById("docsNav");
        if (!nav || !window.DOCS_NAV) {
            return;
        }

        let html = "";
        for (const group of window.DOCS_NAV) {
            html += navGroupHtml(group);
        }

        nav.innerHTML = html;
        markActive();
    }

    /**
     * Returns the markup of one group of the menu
     * @param {Object} group
     * @return {string}
     */
    function navGroupHtml(group) {
        let html = `<div class="nav-group"><h4>${group.title}</h4>`;

        for (const item of group.items) {
            html += `<a href="${baseUrl}${item.url}" data-url="${item.url}">${item.name}</a>`;
        }
        return `${html}</div>`;
    }

    /**
     * Marks the page being read. Matched on the whole path, since two sections
     * can hold a page with the same file name
     * @return {void}
     */
    function markActive() {
        const links = document.querySelectorAll("#docsNav a");
        const page  = currentPage();

        for (const link of links) {
            link.classList.toggle("active", link.getAttribute("data-url") === page);
        }
    }

    /**
     * Keeps the current page, and its neighbors, in view after each navigation
     * @return {void}
     */
    function centerActive() {
        const active = document.querySelector(".nav-group a.active");
        if (!sidebar || !active) {
            return;
        }

        const sRect = sidebar.getBoundingClientRect();
        const aRect = active.getBoundingClientRect();

        sidebar.scrollTop += (aRect.top - sRect.top) - (sidebar.clientHeight - aRect.height) / 2;
    }

    /**
     * Returns the page being read, as a path from the site root
     * @return {string}
     */
    function currentPage() {
        const path = location.href.split("#")[0].split("?")[0];
        return path.replace(baseUrl, "") || "index.html";
    }



    /**
     * Draws everything belonging to the content rather than to the chrome around
     * it, so a page swapped in place is finished the same way as one loaded
     * @return {void}
     */
    function renderContent() {
        buildToc();
        addCopyButtons();
        renderSchema();
    }

    /**
     * Builds the table of contents from the headings of the page being read
     * @return {void}
     */
    function buildToc() {
        const docMain = document.querySelector(".doc-main");
        const tocEl   = document.querySelector(".toc");
        const tocList = document.querySelector(".toc ul");

        heads   = [];
        linkFor = {};
        if (!docMain || !tocList) {
            return;
        }

        tocList.innerHTML = "";
        heads = [ ...docMain.querySelectorAll("h2, h3") ];
        for (const heading of heads) {
            tocList.appendChild(tocItem(heading));
        }

        if (tocEl) {
            tocEl.style.display = heads.length === 0 ? "none" : "";
        }
        spy();
    }

    /**
     * Returns the entry of the table of contents for the given heading
     * @param {HTMLElement} heading
     * @return {HTMLElement}
     */
    function tocItem(heading) {
        if (!heading.id) {
            heading.id = slug(heading.textContent);
        }

        const a = document.createElement("a");
        a.href = `#${heading.id}`;
        a.textContent = heading.textContent;
        a.className = heading.tagName === "H3" ? "lvl-3" : "lvl-2";

        const li = document.createElement("li");
        li.appendChild(a);
        linkFor[heading.id] = a;
        return li;
    }

    /**
     * Returns the given heading as an id, for a heading that carries none
     * @param {string} text
     * @return {string}
     */
    function slug(text) {
        return text.toLowerCase().trim().replace(/[^\w]+/g, "-").replace(/^-+|-+$/g, "");
    }

    /**
     * Marks the heading being read in the table of contents
     * @return {void}
     */
    function spy() {
        if (heads.length === 0) {
            return;
        }

        let current = heads[0];
        for (const heading of heads) {
            if (heading.getBoundingClientRect().top > 120) {
                break;
            }
            current = heading;
        }

        for (const heading of heads) {
            linkFor[heading.id].classList.remove("active");
        }
        if (current) {
            linkFor[current.id].classList.add("active");
        }
    }

    /**
     * Adds the copy to clipboard button to every code block
     * @return {void}
     */
    function addCopyButtons() {
        const blocks = document.querySelectorAll("pre");

        for (const block of blocks) {
            addCopyButton(block);
        }
    }

    /**
     * Adds the copy to clipboard button to the given code block
     * @param {HTMLElement} pre
     * @return {void}
     */
    function addCopyButton(pre) {
        if (pre.querySelector(".copy-btn")) {
            return;
        }

        const btn = document.createElement("button");
        btn.className = "copy-btn";
        btn.type = "button";
        btn.textContent = "Copy";
        btn.addEventListener("click", function () {
            copyBlock(pre, btn);
        });
        pre.appendChild(btn);
    }

    /**
     * Copies the given code block, and says so on its button for a moment
     * @param {HTMLElement} pre
     * @param {HTMLElement} btn
     * @return {void}
     */
    function copyBlock(pre, btn) {
        const code = pre.querySelector("code");
        const text = code ? code.innerText : pre.innerText;

        navigator.clipboard.writeText(text).then(function () {
            btn.textContent = "Copied";
            setTimeout(function () {
                btn.textContent = "Copy";
            }, 1400);
        });
    }



    /**
     * Draws the Framework's own tables into the Schema JSON guide
     * @return {void}
     */
    function renderSchema() {
        // Read from the very file the page documents, so it cannot describe a
        // shape the build no longer writes
        const host = document.querySelector("[data-schema]");
        if (!host) {
            return;
        }
        if (schemaCache) {
            drawSchema(host, schemaCache);
            return;
        }

        fetch(`${assetUrl}schema.json`)
            .then(readJson)
            .then(function (schema) {
                schemaCache = schema;
                drawSchema(host, schema);
            })
            .catch(function () {
                host.innerHTML = schemaMissingHtml();
            });
    }

    /**
     * Writes the given schema into the page
     * @param {HTMLElement} host
     * @param {Object}      schema
     * @return {void}
     */
    function drawSchema(host, schema) {
        const tables = Object.keys(schema);
        let columns  = 0;
        let edges    = 0;

        for (const name of tables) {
            columns += schema[name].fields.length;
            edges   += schema[name].foreigns.length;
        }

        let html = `<p>${tables.length} tables, ${columns} columns and ${edges} ` +
            `foreign keys, read from ${schemaLink()}.</p>`;

        for (const name of tables) {
            html += schemaTableHtml(name, schema[name]);
        }
        host.innerHTML = html;
    }

    /**
     * Returns the markup of one table of the schema
     * @param {string} name
     * @param {Object} table
     * @return {string}
     */
    function schemaTableHtml(name, table) {
        let html = `<details class="schema-table"><summary><code>${name}</code>` +
            `<span>${escapeHtml(table.description)}</span></summary>`;

        html += `<table class="api-table"><thead><tr><th>Column</th><th>Type</th>` +
            `<th>Keys</th></tr></thead><tbody>`;
        for (const field of table.fields) {
            html += schemaFieldHtml(field);
        }
        html += `</tbody></table>`;

        if (table.foreigns.length) {
            html += `<table class="api-table"><thead><tr><th>Points at</th>` +
                `<th>Table</th><th>Column</th></tr></thead><tbody>`;
            for (const foreign of table.foreigns) {
                html += schemaForeignHtml(foreign);
            }
            html += `</tbody></table>`;
        }
        return `${html}</details>`;
    }

    /**
     * Returns the row of one column of a table
     * @param {Object} field
     * @return {string}
     */
    function schemaFieldHtml(field) {
        const keys = [];

        if (field.isPrimary) {
            keys.push("primary");
        }
        if (field.isKey) {
            keys.push("indexed");
        }

        const length = field.length ? ` ${field.length}` : "";
        const shown  = keys.length ? keys.join(", ") : "—";

        return `<tr><td><code>${field.name}</code></td>` +
            `<td><code>${field.type}</code>${length}</td>` +
            `<td>${shown}</td></tr>`;
    }

    /**
     * Returns the row of one foreign key of a table
     * @param {Object} foreign
     * @return {string}
     */
    function schemaForeignHtml(foreign) {
        return `<tr><td><code>${foreign.fromField}</code></td>` +
            `<td><code>${foreign.toTable}</code></td>` +
            `<td><code>${foreign.toField}</code></td></tr>`;
    }

    /**
     * Returns a link to the schema file the page is drawn from
     * @return {string}
     */
    function schemaLink() {
        return `<a class="src-link" href="${assetUrl}schema.json" target="_blank"` +
            ` rel="noopener noreferrer"><code>schema.json</code></a>`;
    }

    /**
     * Returns what the page says when the schema could not be read
     * @return {string}
     */
    function schemaMissingHtml() {
        return `<p class="schema-empty">The schema could not be read. It is at ` +
            `${schemaLink()}.</p>`;
    }



    /**
     * Follows the links between the pages without reloading
     * @return {void}
     */
    function watchNavigation() {
        // Only the content is swapped, so the sidebar keeps its scroll. Every
        // page is still a whole document, so anything odd hands back to the browser
        document.addEventListener("click", onDocumentClick);
        window.addEventListener("popstate", onPopState);
    }

    /**
     * Takes over a click on a link of this site
     * @param {MouseEvent} e
     * @return {void}
     */
    function onDocumentClick(e) {
        if (e.defaultPrevented || e.button !== 0) {
            return;
        }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        const link = e.target.closest("a[href]");
        if (!link || link.target === "_blank" || link.hasAttribute("download")) {
            return;
        }
        if (!isReadable(link.href)) {
            return;
        }

        // An anchor within the page being read is the browser's own job
        if (link.href.split("#")[0] === location.href.split("#")[0]) {
            return;
        }

        e.preventDefault();
        document.body.classList.remove("nav-open");
        if (searchDialog && !searchDialog.hidden) {
            closeSearch();
        }

        // Anywhere else — the content, the search, the 404 page — can lead to a
        // page far outside the menu's view, so there it is brought back into it
        swap(link.href, true, !link.closest("#docsNav"));
    }

    /**
     * Follows the back and forward buttons
     * @return {void}
     */
    function onPopState() {
        // Moving between anchors of one page changes no content
        if (location.href.split("#")[0] === lastPath) {
            return;
        }
        // Back and forward can land anywhere, so the menu follows along
        swap(location.href, false, true);
    }

    /**
     * Returns true if the given url is a page of this site, and so ours to swap
     * @param {string} url
     * @return {boolean}
     */
    function isReadable(url) {
        return url.indexOf(baseUrl) === 0 && url.split("#")[0].slice(-5) === ".html";
    }

    /**
     * Swaps the content for the given page
     * @param {string}  url
     * @param {boolean} push     Whether to add the page to the history
     * @param {boolean} recenter False when the menu itself was clicked, since the
     *                           item is already under the pointer and moving it
     *                           away is the one thing nobody asked for
     * @return {void}
     */
    function swap(url, push, recenter) {
        const path = pathOf(url);

        if (pages[path]) {
            showPage(pages[path], url, push, recenter);
            return;
        }

        fetch(path)
            .then(readText)
            .then(function (html) {
                pages[path] = html;
                showPage(html, url, push, recenter);
            })
            .catch(function () {
                location.href = url;
            });
    }

    /**
     * Puts the fetched page in place of the one being left
     * @param {string}  html
     * @param {string}  url
     * @param {boolean} push
     * @param {boolean} recenter
     * @return {void}
     */
    function showPage(html, url, push, recenter) {
        const doc  = new DOMParser().parseFromString(html, "text/html");
        const next = doc.querySelector(".content");
        const here = document.querySelector(".content");
        if (!next || !here) {
            location.href = url;
            return;
        }

        // The url is moved first, so the relative links in what is inserted
        // resolve against the page they came from, not the one leaving
        if (push) {
            history.pushState({}, "", url);
        }
        lastPath = pathOf(url);

        // Only the 404 page carries a base href, and it would keep every
        // relative link resolving against the site root once we leave it
        const base = document.querySelector("base");
        if (base) {
            base.remove();
        }

        here.innerHTML = next.innerHTML;
        document.title = doc.title;

        markActive();
        if (recenter) {
            centerActive();
        }
        renderContent();
        if (window.Prism) {
            window.Prism.highlightAll();
        }

        goToHash(hashOf(url));
    }

    /**
     * Moves to the given anchor of the page just swapped in, or to the top of it
     * @param {string} hash
     * @return {void}
     */
    function goToHash(hash) {
        const target = hash ? document.getElementById(hash.slice(1)) : null;

        if (target) {
            target.scrollIntoView();
            return;
        }
        window.scrollTo(0, 0);
    }

    /**
     * Returns the given url without its anchor
     * @param {string} url
     * @return {string}
     */
    function pathOf(url) {
        const cut = url.indexOf("#");
        return cut >= 0 ? url.slice(0, cut) : url;
    }

    /**
     * Returns the anchor of the given url, empty when it carries none
     * @param {string} url
     * @return {string}
     */
    function hashOf(url) {
        const cut = url.indexOf("#");
        return cut >= 0 ? url.slice(cut) : "";
    }



    /**
     * Wires the search: a command palette opened with ⌘K, Ctrl+K, or "/"
     * @return {void}
     */
    function watchSearch() {
        if (!searchDialog || !searchInput || !searchBox) {
            return;
        }

        // Anything marked data-search opens it — the sidebar box, and the 404 button
        const openers = document.querySelectorAll("[data-search]");
        for (const opener of openers) {
            opener.addEventListener("click", openSearch);
        }
        if (searchButton && !/Mac|iP(hone|ad)/.test(navigator.platform)) {
            searchButton.querySelector("kbd").textContent = "Ctrl K";
        }

        searchInput.addEventListener("input", onSearchInput);
        searchInput.addEventListener("keydown", onSearchKeydown);
        searchBox.addEventListener("mousemove", onResultsMouseMove);
        searchDialog.addEventListener("click", onDialogClick);
        document.addEventListener("keydown", onGlobalKeydown);
    }

    /**
     * Opens the search dialog
     * @return {void}
     */
    function openSearch() {
        // On a phone the box that opens this sits inside the menu, which would
        // otherwise stay open behind the dialog
        document.body.classList.remove("nav-open");
        searchDialog.hidden = false;
        document.body.classList.add("search-open-body");
        searchInput.focus();
        searchInput.select();
        loadIndex(updateResults);
    }

    /**
     * Closes the search dialog
     * @return {void}
     */
    function closeSearch() {
        searchDialog.hidden = true;
        document.body.classList.remove("search-open-body");
        searchAt = -1;
    }

    /**
     * Draws the results again for whatever is typed now
     * @return {void}
     */
    function onSearchInput() {
        loadIndex(updateResults);
    }

    /**
     * Moves through the results, opens one, or closes the dialog
     * @param {KeyboardEvent} e
     * @return {void}
     */
    function onSearchKeydown(e) {
        const items = searchBox.querySelectorAll("a");

        if (e.key === "Escape") {
            closeSearch();
            return;
        }
        if (!items.length) {
            return;
        }

        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectResult(searchAt + 1, true);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectResult(searchAt - 1, true);
        } else if (e.key === "Enter" && searchAt >= 0) {
            e.preventDefault();
            items[searchAt].click();
        }
    }

    /**
     * Moves the selection to the result under the pointer
     * @param {MouseEvent} e
     * @return {void}
     */
    function onResultsMouseMove(e) {
        // Tracks mousemove and ignores a repeat of the same position, so
        // scrolling by keyboard cannot lose the selection to a still pointer
        if (e.clientX === lastX && e.clientY === lastY) {
            return;
        }
        lastX = e.clientX;
        lastY = e.clientY;

        const link = e.target.closest("a");
        if (!link) {
            return;
        }

        const at = [ ...searchBox.querySelectorAll("a") ].indexOf(link);
        if (at >= 0 && at !== searchAt) {
            selectResult(at, false);
        }
    }

    /**
     * Closes the dialog when the backdrop is clicked, but not the panel
     * @param {MouseEvent} e
     * @return {void}
     */
    function onDialogClick(e) {
        if (!e.target.closest(".search-panel")) {
            closeSearch();
        }
    }

    /**
     * Opens and closes the search from anywhere on the page
     * @param {KeyboardEvent} e
     * @return {void}
     */
    function onGlobalKeydown(e) {
        const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);

        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
            e.preventDefault();
            if (searchDialog.hidden) {
                openSearch();
            } else {
                closeSearch();
            }
        } else if (e.key === "/" && !typing && searchDialog.hidden) {
            e.preventDefault();
            openSearch();
        }
    }

    /**
     * Reads the search index, and runs the given callback once it is there
     * @param {Function} then
     * @return {void}
     */
    function loadIndex(then) {
        // The callback is kept while the index is in flight, so typing before it
        // arrives still renders once it does
        if (searchIndex) {
            then();
            return;
        }

        searchPending = then;
        if (searchLoading) {
            return;
        }
        searchLoading = true;

        fetch(`${assetUrl}search.json`)
            .then(readJson)
            .then(onIndexRead)
            .catch(function () {
                searchLoading = false;
                searchPending = null;
            });
    }

    /**
     * Keeps the index that was read, and runs whatever was waiting for it
     * @param {Array} data
     * @return {void}
     */
    function onIndexRead(data) {
        searchIndex   = data;
        searchLoading = false;

        if (searchPending) {
            const run = searchPending;
            searchPending = null;
            run();
        }
    }

    /**
     * Draws either the featured list or the matches, depending on what is typed
     * @return {void}
     */
    function updateResults() {
        const query = searchInput.value.trim().toLowerCase();

        if (query.length < 2) {
            showFeaturedResults();
            return;
        }
        renderResults(query);
    }

    /**
     * Draws the starting point: the featured pages, looked up in the index so
     * their titles and sections stay right even as the docs change
     * @return {void}
     */
    function showFeaturedResults() {
        let html = `<p class="sr-group">Start here</p>`;

        for (const url of FEATURED) {
            const entry = entryFor(url);
            if (entry) {
                html += resultHtml(entry, "", -1);
            }
        }

        searchBox.innerHTML = html;
        searchBox.scrollTop = 0;
        selectResult(0, false);
    }

    /**
     * Returns the entry of the index for the given page, or null
     * @param {string} url
     * @return {Object|null}
     */
    function entryFor(url) {
        for (const entry of searchIndex) {
            if (entry.u === url) {
                return entry;
            }
        }
        return null;
    }

    /**
     * Draws the results for the given query
     * @param {string} query
     * @return {void}
     */
    function renderResults(query) {
        const found = matchesFor(query);

        if (!found.length) {
            searchBox.innerHTML = `<p class="search-empty">Nothing found for “${escapeHtml(query)}”</p>`;
            searchBox.scrollTop = 0;
            searchAt = -1;
            return;
        }

        let html = "";
        for (const match of found) {
            html += resultHtml(match.e, query, match.s);
        }

        searchBox.innerHTML = html;
        searchBox.scrollTop = 0;
        selectResult(0, false);
    }

    /**
     * Returns the entries that answer the given query, best first
     * @param {string} query
     * @return {Array}
     */
    function matchesFor(query) {
        const found = [];

        for (const entry of searchIndex) {
            const rank = score(entry, query);
            if (rank >= 0) {
                found.push({ e: entry, s: rank });
            }
        }

        found.sort(compareMatches);
        return found.slice(0, 20);
    }

    /**
     * Orders two matches: by relevance, then a whole page before a section of
     * one, then the shorter title
     * @param {Object} a
     * @param {Object} b
     * @return {number}
     */
    function compareMatches(a, b) {
        if (a.s !== b.s) {
            return a.s - b.s;
        }

        const pageA = a.e.u.indexOf("#") < 0;
        const pageB = b.e.u.indexOf("#") < 0;
        if (pageA !== pageB) {
            return pageA ? -1 : 1;
        }
        return a.e.t.length - b.e.t.length;
    }

    /**
     * Returns how well the given entry answers the query, lower being better,
     * and -1 when it does not answer it at all
     * @param {Object} entry
     * @param {string} query
     * @return {number}
     */
    function score(entry, query) {
        const title = entry.t.toLowerCase();

        if (title === query) {
            return 0;
        }
        if (title.indexOf(query) === 0) {
            return 1;
        }
        if (title.indexOf(query) > 0) {
            return 2;
        }
        if (entry.s.toLowerCase().indexOf(query) >= 0) {
            return 3;
        }
        if (entry.d.toLowerCase().indexOf(query) >= 0) {
            return 4;
        }
        return -1;
    }

    /**
     * Returns the markup of one result
     * @param {Object} entry
     * @param {string} query The text to mark, empty for the featured list
     * @param {number} rank  The score it matched with, 4 being a body hit
     * @return {string}
     */
    function resultHtml(entry, query, rank) {
        const title = query ? highlight(entry.t, query) : escapeHtml(entry.t);
        let text = "";

        if (rank === 4) {
            text = `<span class="sr-text">${snippet(entry.d, query)}</span>`;
        }
        return `<a href="${baseUrl}${entry.u}">` +
            `<span class="sr-title">${title}</span>` +
            `<span class="sr-where">${escapeHtml(entry.s)}</span>` +
            `${text}</a>`;
    }

    /**
     * Returns the given text with the match in it marked
     * @param {string} text
     * @param {string} query
     * @return {string}
     */
    function highlight(text, query) {
        const at = text.toLowerCase().indexOf(query);
        if (at < 0) {
            return escapeHtml(text);
        }

        return `${escapeHtml(text.slice(0, at))}` +
            `<mark>${escapeHtml(text.slice(at, at + query.length))}</mark>` +
            `${escapeHtml(text.slice(at + query.length))}`;
    }

    /**
     * Returns a piece of the body around the match, so a body hit shows why it matched
     * @param {string} text
     * @param {string} query
     * @return {string}
     */
    function snippet(text, query) {
        const at = text.toLowerCase().indexOf(query);
        if (at < 0) {
            return escapeHtml(text.slice(0, 110)) + (text.length > 110 ? "…" : "");
        }

        const from = Math.max(0, at - 40);
        const cut  = text.slice(from, from + 130);
        return `${from > 0 ? "…" : ""}${highlight(cut, query)}…`;
    }

    /**
     * Moves the selection to the given result
     * @param {number}  at
     * @param {boolean} scroll Whether to bring it into view
     * @return {void}
     */
    function selectResult(at, scroll) {
        const items = searchBox.querySelectorAll("a");
        if (!items.length) {
            searchAt = -1;
            return;
        }

        if (searchAt >= 0 && items[searchAt]) {
            items[searchAt].classList.remove("active");
        }
        searchAt = (at + items.length) % items.length;
        items[searchAt].classList.add("active");

        if (scroll) {
            items[searchAt].scrollIntoView({ block: "nearest" });
        }
    }



    /**
     * Marks the mobile header once there is something scrolled under it, far
     * enough down that a nudge of the page does not set it off
     * @return {void}
     */
    function watchTopbar() {
        if (!topbar) {
            return;
        }
        markTopbar();
        window.addEventListener("scroll", markTopbar, { passive: true });
    }

    /**
     * Marks the mobile header as scrolled under, or not
     * @return {void}
     */
    function markTopbar() {
        topbar.classList.toggle("is-stuck", window.scrollY > 40);
    }

    /**
     * Opens and closes the menu on a phone
     * @return {void}
     */
    function watchMenu() {
        if (!menuToggle) {
            return;
        }
        menuToggle.addEventListener("click", toggleMenu);
        document.addEventListener("click", closeMenuOnOutsideClick);
    }

    /**
     * Opens the menu, or closes it
     * @return {void}
     */
    function toggleMenu() {
        document.body.classList.toggle("nav-open");
    }

    /**
     * Closes the menu when anything outside it is clicked
     * @param {MouseEvent} e
     * @return {void}
     */
    function closeMenuOnOutsideClick(e) {
        if (document.body.classList.contains("nav-open") &&
            !e.target.closest(".sidebar") && !e.target.closest(".menu-toggle")) {
            document.body.classList.remove("nav-open");
        }
    }

    /**
     * Reveals the back-to-top button after scrolling, and returns to the top on click
     * @return {void}
     */
    function watchBackToTop() {
        if (!toTop) {
            return;
        }
        markBackToTop();
        window.addEventListener("scroll", markBackToTop, { passive: true });
        toTop.addEventListener("click", scrollToTop);
    }

    /**
     * Shows the back-to-top button, or hides it
     * @return {void}
     */
    function markBackToTop() {
        toTop.classList.toggle("is-visible", window.scrollY > 400);
    }

    /**
     * Returns to the top of the page
     * @return {void}
     */
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    /**
     * Shows a divider under the sidebar header once the nav scrolls beneath it
     * @return {void}
     */
    function watchSidebarHead() {
        if (!sidebar || !sidebarHead) {
            return;
        }
        markSidebarHead();
        sidebar.addEventListener("scroll", markSidebarHead, { passive: true });
    }

    /**
     * Marks the sidebar header as scrolled under, or not
     * @return {void}
     */
    function markSidebarHead() {
        sidebarHead.classList.toggle("is-stuck", sidebar.scrollTop > 4);
    }

    /**
     * Keeps the table of contents following the page as it is scrolled
     * @return {void}
     */
    function watchHeadings() {
        window.addEventListener("scroll", spy, { passive: true });
    }



    /**
     * Returns the body of the given response as JSON, and fails when it is missing
     * @param {Response} response
     * @return {Promise}
     */
    function readJson(response) {
        if (!response.ok) {
            throw new Error("missing");
        }
        return response.json();
    }

    /**
     * Returns the body of the given response as text, and fails when it is missing
     * @param {Response} response
     * @return {Promise}
     */
    function readText(response) {
        if (!response.ok) {
            throw new Error("missing");
        }
        return response.text();
    }

    /**
     * Escapes the given text for html, for the search results and the schema tables
     * @param {string} text
     * @return {string}
     */
    function escapeHtml(text) {
        const named = { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" };

        return text.replace(/[&<>"]/g, function (ch) {
            return named[ch];
        });
    }


    main();
})();
