<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{project}} Icons</title>
    <style>
{{{style}}}
        :root {
            color-scheme: light;
            --text: rgb(28, 28, 30);
            --dim: rgb(107, 107, 112);
            --icon-color: rgb(105, 105, 105);
            --line: rgb(226, 226, 230);
            --card: rgb(255, 255, 255);
            --back: rgb(246, 246, 248);
        }
        :root[data-theme="dark"] {
            color-scheme: dark;
            --text: rgb(232, 232, 234);
            --dim: rgb(154, 154, 160);
            --icon-color: rgb(214, 214, 218);
            --line: rgb(48, 48, 52);
            --card: rgb(30, 30, 32);
            --back: rgb(19, 19, 21);
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }

        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            padding: 0 32px 32px;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            color: var(--text);
            background-color: var(--back);
        }

        header {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 -32px;
            padding: 12px 32px;
            border-bottom: 1px solid var(--line);
            background-color: var(--back);
        }
        header .title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }
        header .actions { display: flex; flex-wrap: wrap; gap: 12px; }

        h1 { margin: 0; font-size: 22px; }
        h2 { margin: 0 0 20px; font-size: 20px; text-transform: capitalize; }
        h2 span { margin-left: 8px; font-weight: normal; color: var(--dim); }

        input, button {
            padding: 8px 16px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--text);
            background-color: var(--card);
            font-family: inherit;
            font-size: 14px;
        }
        input { min-width: 220px; }
        input:focus { outline: none; border-color: var(--dim); }
        button { cursor: pointer; }
        button:hover { border-color: var(--dim); }

        nav { display: flex; flex-wrap: wrap; gap: 8px; }
        nav a {
            padding: 6px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--text);
            background-color: var(--card);
            font-size: 13px;
            text-decoration: none;
            text-transform: capitalize;
        }
        nav a:hover { border-color: var(--dim); }
        nav a b { color: var(--dim); font-weight: normal; }

        .filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 24px;
            margin: 32px 0 48px;
        }
        .filters h2 { margin: 0; }
        .filters .groups { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 24px; }
        .filters .group { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .filters h3 {
            margin: 0;
            font-size: 13px;
            font-weight: normal;
            color: var(--dim);
        }
        .filters button { padding: 6px 14px; font-size: 13px; }
        .filters button b { color: var(--dim); font-weight: normal; }
        .filters button.active {
            border-color: var(--text);
            color: var(--card);
            background-color: var(--text);
        }
        .filters button.active b { color: var(--card); opacity: .7; }

        section {
            margin: 32px 0;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 20px;
            background-color: var(--card);
            scroll-margin-top: calc(var(--header, 76px) + 16px);
        }

        ul {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        li {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 24px 12px;
            border-radius: 14px;
            text-align: center;
        }
        li:hover { background-color: var(--back); }
        li:hover em { border-color: var(--dim); }
        li span { font-size: 44px; color: var(--icon-color); }
        li p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
            color: var(--dim);
            word-break: break-word;
        }
        li .meta {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        li em {
            padding: 2px 9px;
            border: 1px solid var(--line);
            border-radius: 999px;
            font-size: 10px;
            font-style: normal;
            line-height: 1.5;
            color: var(--dim);
            background-color: var(--back);
        }
        li a {
            font-size: 11px;
            line-height: 1.3;
            color: var(--dim);
            text-decoration: none;
            opacity: .8;
            word-break: break-word;
        }
        li a:hover { color: var(--text); opacity: 1; text-decoration: underline; }

        #empty { margin: 32px 0; color: var(--dim); }
    </style>
    <script>
        const isDark = matchMedia("(prefers-color-scheme: dark)").matches;
        document.documentElement.dataset.theme =
            localStorage.getItem("icons-theme") || (isDark ? "dark" : "light");
    </script>
</head>
<body>
    <header>
        <div class="title">
            <h1>{{project}}</h1>
            <nav>
                {{#folders}}
                <a href="#{{name}}">{{name}} <b>{{amount}}</b></a>
                {{/folders}}
            </nav>
        </div>
        <div class="actions">
            <input id="search" type="search" placeholder="Search icons" autocomplete="off" spellcheck="false">
            <button id="theme" type="button"></button>
        </div>
    </header>

    <div class="filters">
        <h2>Filters</h2>
        <div class="groups">
            {{#filters}}
            <div class="group">
                <h3>{{title}}</h3>
                {{#items}}
                <button type="button" data-group="{{group}}" data-filter="{{name}}">{{title}} <b>{{amount}}</b></button>
                {{/items}}
            </div>
            {{/filters}}
        </div>
    </div>

    {{#folders}}
    <section class="folder" id="{{name}}">
        <h2>{{name}} <span>{{amount}} icons</span></h2>
        <ul>
            {{#icons}}
            <li data-name="{{name}} {{source}} {{matName}} {{modifiers}}" data-tags="{{tags}}">
                <span class="icon-{{name}}"></span>
                <div class="meta">
                    <p>{{name}}</p>
                    {{#source}}<em class="{{source}}">{{source}}</em>{{/source}}
                    {{#link}}<a href="{{link}}" target="_blank" rel="noopener">{{matName}}{{#modifiers}} {{modifiers}}{{/modifiers}}</a>{{/link}}
                </div>
            </li>
            {{/icons}}
        </ul>
    </section>

    {{/folders}}
    <p id="empty" hidden>No icons match the search.</p>

    <script>
        const root     = document.documentElement;
        const header   = document.querySelector("header");
        const button   = document.getElementById("theme");
        const search   = document.getElementById("search");
        const empty    = document.getElementById("empty");
        const sections = document.querySelectorAll("section.folder");
        const filters  = document.querySelectorAll(".filters button");
        const active   = new Map();

        function setHeaderHeight() {
            root.style.setProperty("--header", `${header.offsetHeight}px`);
        }

        function setTheme(theme) {
            root.dataset.theme = theme;
            button.textContent = theme === "dark" ? "Light mode" : "Dark mode";
            localStorage.setItem("icons-theme", theme);
        }

        function hasFilters(item) {
            const tags = item.dataset.tags.split(" ");
            for (const names of active.values()) {
                if (names.size && ![ ...names ].some((name) => tags.includes(name))) {
                    return false;
                }
            }
            return true;
        }

        function filterIcons() {
            const query = search.value.trim().toLowerCase();
            let   found = 0;

            for (const section of sections) {
                const selector = `nav a[href="#${section.id}"]`;
                const link     = document.querySelector(selector);
                let   total    = 0;

                for (const item of section.querySelectorAll("li")) {
                    const hasText = !query || item.dataset.name.toLowerCase().includes(query);
                    const isMatch = hasText && hasFilters(item);
                    item.hidden = !isMatch;
                    if (isMatch) {
                        total += 1;
                    }
                }

                section.hidden = total === 0;
                section.querySelector("h2 span").textContent = `${total} icons`;
                if (link) {
                    link.hidden = total === 0;
                    link.querySelector("b").textContent = total;
                }
                found += total;
            }

            empty.hidden = found > 0;
        }

        function setFilter(button) {
            const { group, filter } = button.dataset;
            const names = active.get(group) || new Set();

            if (names.has(filter)) {
                names.delete(filter);
            } else {
                names.add(filter);
            }

            active.set(group, names);
            button.classList.toggle("active", names.has(filter));
            filterIcons();
        }

        button.addEventListener("click", () => {
            setTheme(root.dataset.theme === "dark" ? "light" : "dark");
        });
        search.addEventListener("input", filterIcons);
        search.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                search.value = "";
                filterIcons();
            }
        });

        for (const item of filters) {
            item.addEventListener("click", () => setFilter(item));
        }

        new ResizeObserver(setHeaderHeight).observe(header);

        setHeaderHeight();
        setTheme(root.dataset.theme);
    </script>
</body>
</html>
