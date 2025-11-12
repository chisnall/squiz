<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('squiz.title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    {{-- Roboto Mono font instead of JetBrains Monno --}}
    {{-- <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"> --}}
    <link rel="icon" type="image/png" sizes="32x32" href="https://laravel.com/img/favicon/favicon-32x32.png">
</head>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Add new entries to the DOM
        function addEntries(data) {
            const noEntries = entriesContainer.textContent.includes('No entries');

            let html = '';

            // Check for no entries
            if (noEntries) {
                entriesContainer.innerHTML = '';
            }

            if (data.length) {
                data.forEach((entry, index) => {
                    const isLast = index === data.length - 1;

                    html += `<div id="entry-${entry.id}" class="entry">`;

                    if (entry.terminated) {
                        html += '<div class="terminated">';
                    } else {
                        html += '<div class="line">';
                    }

                    html += `<div class="datetime"><pre class="datetime">${entry.datetime}</pre><div class="delete" data-id="${entry.id}">${deleteIcon}</div>`;

                    if (entry.terminated) {
                        html += '</div><pre>terminated</pre></div>';
                    } else {
                        html += '</div></div>';
                    }

                    html += `<div class="line"><pre>${entry.file}:${entry.line}</pre></div>`;
                    //html += `<div class="line"><pre>${entry.line}</pre></div>`;
                    if (entry.comment) html += `<div class="line"><pre class="italic">${entry.comment}</pre></div>`;
                    html += '<div class="vardump">' + atob(entry.entry) + '</div>';
                    html += `<hr>`;
                    html += `</div>`;
                });
            }

            showNotice();

            entriesContainer.insertAdjacentHTML('beforeend', html);

            runScriptsFrom(entriesContainer);
        }

        // Remove entries from the DOM
        function removeEntries(data) {
            if (data.length) {
                data.forEach((id) => {
                    const element = document.getElementById(`entry-${id}`);

                    if (element) {
                        element.remove();
                    }
                });
            }
        }

        // Apply theme
        function applyTheme(theme) {
            // Check for system theme
            if (theme === "system") {
                const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
                if (prefersDark) theme = 'dark';
                else theme = 'light';
            }

            if (theme === "dark") document.documentElement.setAttribute("data-theme", "dark");
            else if (theme === "light") document.documentElement.setAttribute("data-theme", "light");
        }

        // Run scripts in VarDumper output
        function runScriptsFrom(element) {
            element.querySelectorAll('script').forEach(script => {
                const s = document.createElement('script');
                if (script.src) s.src = script.src;
                else s.textContent = script.textContent;
                document.body.appendChild(s);
                script.remove();
            });
        }

        // Show notice to the side of the theme buttons
        function showNotice() {
            const notifyElements = [notify1, notify2];

            notifyElements.forEach(el => {
                // Make visible
                el.classList.add('on');
                el.style.opacity = '1';
                el.style.transition = 'opacity 1.0s ease';

                // Fade out after 2 seconds
                setTimeout(() => {
                    el.style.opacity = '0';

                    // Remove class after fade completes
                    setTimeout(() => {
                        el.classList.remove('on');
                    }, 1000); // matches transition duration
                }, 2000);
            });
        }

        // Style auto button
        function styleAutoButton(state) {
            if (state === 'running') {
                autoBtn.classList.add("running");
                autoBtn.classList.remove("paused");
            } else {
                autoBtn.classList.add("paused");
                autoBtn.classList.remove("running");
            }
        }

        // Style theme buttons
        function styleThemeButtons(button) {
            systemThemeButton.classList.remove("selected");
            lightThemeButton.classList.remove("selected");
            darkThemeButton.classList.remove("selected");
            document.getElementById(`icon-${button}`).classList.add("selected");
        }

        const squizToken = document.getElementById('squizToken').value;
        const squizPollingInterval = (+document.getElementById('squizPollingInterval').value) || 1000;
        const squizRoutePath = document.getElementById('squizRoutePath').value;

        const entriesContainer = document.getElementById("entries-container");
        const notify1 = document.getElementById("notify-1");
        const notify2 = document.getElementById("notify-2");
        const systemThemeButton = document.getElementById("icon-system");
        const lightThemeButton = document.getElementById("icon-light");
        const darkThemeButton = document.getElementById("icon-dark");

        const autoBtn = document.getElementById("auto-button");
        const clearBtn = document.getElementById("clear-button");

        let currentState = localStorage.getItem("currentState") ?? 'running';
        let currentTheme = localStorage.getItem("currentTheme") ?? 'system';

        let previousLogIdsValue = document.getElementById('logIds').value === '' ? null : document.getElementById('logIds').value;
        let previousLogIds = previousLogIdsValue === null ? [] : previousLogIdsValue.split(",").map(Number);

        const deleteIcon = '<svg fill="none" viewBox="4 2 16 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"></path></svg>';

        systemThemeButton.addEventListener("click", () => {
            localStorage.setItem("currentTheme", "system");
            styleThemeButtons('system');
            applyTheme('system');
        });

        lightThemeButton.addEventListener("click", () => {
            localStorage.setItem("currentTheme", "light");
            styleThemeButtons('light');
            applyTheme('light');
        });

        darkThemeButton.addEventListener("click", () => {
            localStorage.setItem("currentTheme", "dark");
            styleThemeButtons('dark');
            applyTheme('dark');
        });

        autoBtn.addEventListener("click", () => {
            currentState = currentState === 'running' ? 'paused' : 'running';
            localStorage.setItem("currentState", currentState);
            styleAutoButton(currentState);
        });

        clearBtn.addEventListener("click", () => {
            try {
                fetch(squizRoutePath + '/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-SQUIZ-TOKEN': squizToken
                    },
                });

                entriesContainer.innerHTML = '<pre>No entries.</pre>';
            } catch (error) {
                console.error("Request failed:", error);
            }
        });

        // Stops Chrome's triple-click selection on the delete icons
        document.addEventListener('pointerdown', function (e) {
            if (e.target.closest('div.delete')) {
                e.preventDefault();
            }
        });

        document.addEventListener('click', function(e) {
            const el = e.target.closest('div.delete');
            if (el) {
                const entryId = el.dataset.id;

                // Remove deleted log ID
                previousLogIds = previousLogIds.filter(x => x !== Number(entryId));

                const entryDiv = document.getElementById(`entry-${entryId}`)

                if (entryDiv) entryDiv.remove();

                let entriesCount = document.querySelectorAll('div.entry').length;

                if (entriesCount === 0) {
                    entriesContainer.innerHTML = '<pre>No entries.</pre>';
                }

                try {
                    fetch(squizRoutePath + '/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-SQUIZ-TOKEN': squizToken
                        },
                        body: JSON.stringify({
                            entryId: entryId
                        })
                    });
                } catch (error) {
                    console.error("Request failed:", error);
                }
            }
        });

        setInterval(async () => {
            if (currentState === 'paused') {
                return;
            }

            try {
                let response = await fetch(squizRoutePath + '/ids', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-SQUIZ-TOKEN': squizToken,
                    },
                });

                let data = await response.json();

                const logIdsDeleted = previousLogIds.filter(x => !data.includes(x));

                const logIdsNew = data.filter(x => !previousLogIds.includes(x));

                if (logIdsDeleted.length) {
                    // Remove deleted log IDs
                    previousLogIds = previousLogIds.filter(x => !logIdsDeleted.includes(x));

                    // Remove entries
                    removeEntries(logIdsDeleted);
                }

                if (logIdsNew.length) {
                    // Append new log IDs
                    previousLogIds.push(...logIdsNew);

                    let response = await fetch(squizRoutePath + '/entries', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-SQUIZ-TOKEN': squizToken
                        },
                        body: JSON.stringify({
                            logIds: logIdsNew
                        })
                    });

                    let data = await response.json();

                    await addEntries(data);
                }
            } catch (error) {
                console.error("Request failed:", error);
            }
        }, squizPollingInterval);

        // Style themed buttons and apply theme
        styleThemeButtons(currentTheme);
        styleAutoButton(currentState);
        applyTheme(currentTheme);
    });
</script>

<style>
    body { overflow-y: scroll; margin: 15px; padding-top: 75px; font-family: "Roboto", sans-serif; }
    pre { display: inline-block; margin: 0; font-family: "JetBrains Mono", monospace; font-size: 14px; font-weight: normal; }
    pre.datetime { font-weight: bold; }
    hr { border: none; height: 0; margin-top: 20px; margin-bottom: 14px; }
    h1 { font-weight: bold; font-size: 22px; }
    /*button { padding: 6px 20px; background-color: #6B7280; color: white; font-family: "Roboto", sans-serif; font-weight: bold; border-radius: 6px; cursor: pointer; }*/
    button { border-radius: 6px; cursor: pointer; }
    button.clear { padding: 6px 20px; font-family: "Roboto", sans-serif; font-weight: bold; }
    button.auto { display: flex; align-items: center; justify-content: center; padding: 0 10px; }
    button:focus { outline: none; }
    div.datetime { display: flex; align-items: center; }
    div.line { padding: 8px 0; }
    div.terminated { display: flex; justify-content: space-between; padding: 8px; }
    div.spacer { height: 10px; }
    div.vardump { padding: 5px 0; }
    div.heading { position: fixed; top: 0; left: 0; right: 0; padding: 10px 15px; z-index: 100000; display: flex; justify-content: space-between; align-items: center; cursor: default; }
    div.heading div.text { display: flex; align-items: center; gap: 10px; }
    div.heading div.text h1 { flex-shrink: 0; white-space: nowrap; }
    div.heading div.text svg { flex-shrink: 0; height: 25px; width: 25px; }
    div.heading div.centre { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    div.heading div.buttons { display: flex; gap: 10px; }
    div.icons { display: flex; height: 30px; padding: 4px; gap: 5px; border-radius: 19px; }
    div.icons div.icon { display: flex; padding: 0; border: 1px solid transparent; border-radius: 15px; }
    div.icons div.icon:hover { cursor: pointer; }
    div.notify { display: flex; align-items: center; color: transparent; font-size: 30px; }
    div.notify svg { height: 100%; width: auto; }
    div.delete { margin-top: -1px; }
    div.delete svg { display: block; margin-left: 10px; height: 15px; width: 15px; cursor: pointer; }
    div.warning { color: #ffffff; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 16px; }
    div.warning div.header { margin-bottom: 10px; font-weight: bold; }
    .flip { transform: scaleX(-1); }
    .italic { font-style: italic; }

    /* VarDumper */
    /*pre.sf-dump { background-color: #18181b; }*/
    /*pre.sf-dump { color: #cc7832; background-color: #fafafa; !*zinc-100*! }*/
    /*pre.sf-dump span.sf-dump-str { color: #2b9713; }*/
    /*pre.sf-dump span.sf-dump-private, pre.sf-dump span.sf-dump-protected, pre.sf-dump span.sf-dump-public { color: black; }*/
    /*pre.sf-dump span.sf-dump-note { color: #136eb8; }*/
    /*pre.sf-dump span.sf-dump-key { color: #2b9713; }*/
    /*pre.sf-dump a.sf-dump-ref { color: #595858; }*/
    /*pre.sf-dump span.sf-dump-ellipsis { color: #136eb8; }*/
    /*input.sf-dump-search-input { color: black !important; background-color: white; border-color: #d4d4d8; }*/
    /*span.sf-dump-search-count { color: black; background-color: white; border-color: #d4d4d8; }*/
    /*button.sf-dump-search-input-previous, button.sf-dump-search-input-next { border-color: #d4d4d8; fill: #71717a; background-color: #f4f4f5 !important; }*/

    /* Light theme */
    html[data-theme="light"] {
        body { background-color: #faf9f5; color: black; }
        hr { border-top: 1px solid #a1a1aa; }
        button { background-color: #d4d4d8; border: 1px solid #a1a1aa; }
        button:hover { background-color: #a1a1aa; }
        button.paused:hover { background-color: #d4d4d8; }
        button.running { background-color: #a3e635; }
        /*button.running:hover { background-color: #84cc16; }*/
        div.terminated { color: white; background-color: #dc2626; }
        div.heading { background-color: #fafafa; border-bottom: 1px solid #a1a1aa; }
        div.heading div.text { color: #1d4ed8; }
        div.heading div.centre div.on { color: red; }
        div.heading div.icons { background-color: #e4e4e7; }
        div.heading div.icons div.selected { background-color: #fafafa; border: 1px solid #a1a1aa; }
        div.warning { background-color: #dc2626; }

        /* VarDumper */
        /*pre.sf-dump { color: #cc7832; background-color: #fafafa; !*zinc-100*! }*/
        /*pre.sf-dump span.sf-dump-str { color: #2b9713; }*/
        /*pre.sf-dump span.sf-dump-private, pre.sf-dump span.sf-dump-protected, pre.sf-dump span.sf-dump-public { color: black; }*/
        /*pre.sf-dump span.sf-dump-note { color: #136eb8; }*/
        /*pre.sf-dump span.sf-dump-key { color: #2b9713; }*/
        /*pre.sf-dump a.sf-dump-ref { color: #595858; }*/
        /*pre.sf-dump span.sf-dump-ellipsis { color: #136eb8; }*/
        /*input.sf-dump-search-input { color: black !important; background-color: white; border-color: #d4d4d8; }*/
        /*span.sf-dump-search-count { color: black; background-color: white; border-color: #d4d4d8; }*/
        /*button.sf-dump-search-input-previous, button.sf-dump-search-input-next { border-color: #d4d4d8; fill: #71717a; background-color: #f4f4f5 !important; }*/
    }

    /* Dark theme */
    html[data-theme="dark"] {
        body { background-color: black; color: #d4d4d8; }
        hr { border-top: 1px solid #71717a; }
        button { color: #d4d4d8; background-color: #27272a; border: 1px solid #a1a1aa; }
        button:hover { background-color: #3f3f46; }
        button.paused:hover { background-color: #27272a; }
        button.running { background-color: #4d7c0f; }
        /*button.running:hover { background-color: #65a30d; }*/
        div.terminated { color: white; background-color: red; }
        div.heading { background-color: black; border-bottom: 1px solid #71717a; }
        div.heading div.text { color: #0284c7; }
        div.heading div.centre div.on { color: red; }
        div.heading div.icons { background-color: #27272a; }
        div.heading div.icons div.selected { color: black; background-color: #fafafa; border: 1px solid #a1a1aa; }
        div.warning { background-color: red; }

        /* VarDumper */
        /*pre.sf-dump { background-color: #18181b; !* zinc-800 *! }*/
        /*input.sf-dump-search-input { color: white !important; background-color: black; border-color: #494950; }*/
        /*span.sf-dump-search-count { color: white; background-color: black; border-color: #494950; }*/
        /*button.sf-dump-search-input-previous, button.sf-dump-search-input-next { border-color: #494950; fill: #d4d4d8; background-color: #27272a !important; }*/
    }
</style>

<body>

<div class="heading">
    <div class="text">
        <svg fill="currentColor" viewBox="2 5 20 14">
            <path fill-rule="evenodd" d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd"></path>
        </svg>
        <h1>{{ config('squiz.heading') }}</h1>
    </div>

    <div class="centre">
        <div id="notify-1" class="notify flip">
            {{--<svg fill="none" viewBox="4 7 16 10">--}}
            {{--    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4 4 4"></path>--}}
            {{--</svg>--}}
            {{--<svg fill="currentColor" viewBox="4 3 16 19">--}}
            {{--    <path d="M8.597 3.2A1 1 0 0 0 7.04 4.289a3.49 3.49 0 0 1 .057 1.795 3.448 3.448 0 0 1-.84 1.575.999.999 0 0 0-.077.094c-.596.817-3.96 5.6-.941 10.762l.03.049a7.73 7.73 0 0 0 2.917 2.602 7.617 7.617 0 0 0 3.772.829 8.06 8.06 0 0 0 3.986-.975 8.185 8.185 0 0 0 3.04-2.864c1.301-2.2 1.184-4.556.588-6.441-.583-1.848-1.68-3.414-2.607-4.102a1 1 0 0 0-1.594.757c-.067 1.431-.363 2.551-.794 3.431-.222-2.407-1.127-4.196-2.224-5.524-1.147-1.39-2.564-2.3-3.323-2.788a8.487 8.487 0 0 1-.432-.287Z"></path>--}}
            {{--</svg>--}}
            🔥
        </div>

        <div class="icons">
            <div id="icon-system" class="icon">
                <svg viewBox="0 0 28 28" fill="none">
                    <path d="M7.5 8.5C7.5 7.94772 7.94772 7.5 8.5 7.5H19.5C20.0523 7.5 20.5 7.94772 20.5 8.5V16.5C20.5 17.0523 20.0523 17.5 19.5 17.5H8.5C7.94772 17.5 7.5 17.0523 7.5 16.5V8.5Z" stroke="currentColor"></path>
                    <path d="M7.5 8.5C7.5 7.94772 7.94772 7.5 8.5 7.5H19.5C20.0523 7.5 20.5 7.94772 20.5 8.5V14.5C20.5 15.0523 20.0523 15.5 19.5 15.5H8.5C7.94772 15.5 7.5 15.0523 7.5 14.5V8.5Z" stroke="currentColor"></path>
                    <path d="M16.5 20.5V17.5H11.5V20.5M16.5 20.5H11.5M16.5 20.5H17.5M11.5 20.5H10.5" stroke="currentColor" stroke-linecap="round"></path>
                </svg>
            </div>

            <div id="icon-light" class="icon">
                <svg viewBox="0 0 28 28" fill="none">
                    <circle cx="14" cy="14" r="3.5" stroke="currentColor"></circle>
                    <path d="M14 8.5V6.5" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M17.889 10.1115L19.3032 8.69727" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M19.5 14L21.5 14" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M17.889 17.8885L19.3032 19.3027" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M14 21.5V19.5" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M8.69663 19.3029L10.1108 17.8887" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M6.5 14L8.5 14" stroke="currentColor" stroke-linecap="round"></path>
                    <path d="M8.69663 8.69711L10.1108 10.1113" stroke="currentColor" stroke-linecap="round"></path>
                </svg>
            </div>

            <div id="icon-dark" class="icon">
                <svg viewBox="0 0 28 28" fill="none">
                    <path d="M10.5 9.99914C10.5 14.1413 13.8579 17.4991 18 17.4991C19.0332 17.4991 20.0176 17.2902 20.9132 16.9123C19.7761 19.6075 17.109 21.4991 14 21.4991C9.85786 21.4991 6.5 18.1413 6.5 13.9991C6.5 10.8902 8.39167 8.22304 11.0868 7.08594C10.7089 7.98159 10.5 8.96597 10.5 9.99914Z" stroke="currentColor" stroke-linejoin="round"></path>
                    <path d="M16.3561 6.50754L16.5 5.5L16.6439 6.50754C16.7068 6.94752 17.0525 7.29321 17.4925 7.35607L18.5 7.5L17.4925 7.64393C17.0525 7.70679 16.7068 8.05248 16.6439 8.49246L16.5 9.5L16.3561 8.49246C16.2932 8.05248 15.9475 7.70679 15.5075 7.64393L14.5 7.5L15.5075 7.35607C15.9475 7.29321 16.2932 6.94752 16.3561 6.50754Z" fill="currentColor" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M20.3561 11.5075L20.5 10.5L20.6439 11.5075C20.7068 11.9475 21.0525 12.2932 21.4925 12.3561L22.5 12.5L21.4925 12.6439C21.0525 12.7068 20.7068 13.0525 20.6439 13.4925L20.5 14.5L20.3561 13.4925C20.2932 13.0525 19.9475 12.7068 19.5075 12.6439L18.5 12.5L19.5075 12.3561C19.9475 12.2932 20.2932 11.9475 20.3561 11.5075Z" fill="currentColor" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>

        <div id="notify-2" class="notify">
            {{--<svg fill="none" viewBox="4 7 16 10">--}}
            {{--    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path>--}}
            {{--</svg>--}}
            {{--<svg fill="currentColor" viewBox="4 3 16 19" transform="matrix(-1,0,0,1,0,0)">--}}
            {{--    <path d="M8.597 3.2A1 1 0 0 0 7.04 4.289a3.49 3.49 0 0 1 .057 1.795 3.448 3.448 0 0 1-.84 1.575.999.999 0 0 0-.077.094c-.596.817-3.96 5.6-.941 10.762l.03.049a7.73 7.73 0 0 0 2.917 2.602 7.617 7.617 0 0 0 3.772.829 8.06 8.06 0 0 0 3.986-.975 8.185 8.185 0 0 0 3.04-2.864c1.301-2.2 1.184-4.556.588-6.441-.583-1.848-1.68-3.414-2.607-4.102a1 1 0 0 0-1.594.757c-.067 1.431-.363 2.551-.794 3.431-.222-2.407-1.127-4.196-2.224-5.524-1.147-1.39-2.564-2.3-3.323-2.788a8.487 8.487 0 0 1-.432-.287Z"></path>--}}
            {{--</svg>--}}
            🔥
        </div>
    </div>

    <div class="buttons">
        <button class="auto" type="button" id="auto-button" name="auto-button" title="Auto refresh">
            <svg width="14" height="14" fill="none" viewBox="3.97 3 16.06 18">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.651 7.65a7.131 7.131 0 0 0-12.68 3.15M18.001 4v4h-4m-7.652 8.35a7.13 7.13 0 0 0 12.68-3.15M6 20v-4h4"></path>
            </svg>
        </button>

        <button class="clear" type="button" id="clear-button" name="clear-button" title="Clear entries">Clear</button>
    </div>
</div>

@foreach (['polling_interval', 'title', 'heading'] as $config)
    @if(!config("squiz.$config"))
        <div class="warning">
            <div class="header">WARNING</div>
            <div>This value is set to null in your .env file:</div>
            <div><pre>{{ $config }}</pre></div>
        </div>
    @endif
@endforeach

<div id="entries-container">
    @if(count($logEntries) === 0)
        <pre>No entries.</pre>
    @endif

    @foreach($logEntries as $logEntry)
        <div id="entry-{{ $logEntry['id'] }}" class="entry">
            @if($logEntry['terminated'])
                <div class="terminated">
                    <div class="datetime">
                        <pre class="datetime">{{ $logEntry['datetime'] }}</pre>

                        <div class="delete" data-id="{{ $logEntry['id'] }}">
                            <svg fill="none" viewBox="4 2 16 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"></path>
                            </svg>
                        </div>
                    </div>

                    <pre>terminated</pre>
                </div>
            @else
                <div class="line">
                    <div class="datetime">
                        <pre class="datetime">{{ $logEntry['datetime'] }}</pre>

                        <div class="delete" data-id="{{ $logEntry['id'] }}">
                            <svg fill="none" viewBox="4 2 16 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif

            <div class="line">
                <pre>{{ $logEntry['file'] . ':' . $logEntry['line'] }}</pre>
            </div>

            {{--<div class="line">--}}
            {{--    <pre>{{ $logEntry['line'] }}</pre>--}}
            {{--</div>--}}

            @if($logEntry['comment'])
                <div class="line">
                    <pre class="italic">{{ $logEntry['comment'] }}</pre>
                </div>
            @endif

            <div class="vardump">
                {!! base64_decode($logEntry['entry']) !!}
            </div>

            {{-- @if(!$loop->last) --}}
            <hr>
            {{-- @endif --}}
        </div>
    @endforeach
</div>

<form>
    <input type="hidden" id="logIds" name="logIds" value="{{ implode(',', $logIds) }}">
    <input type="hidden" id="squizToken" name="squizToken" value="{{ config('squiz.token') }}">
    <input type="hidden" id="squizPollingInterval" name="squizPollingInterval" value="{{ config('squiz.polling_interval') }}">
    <input type="hidden" id="squizRoutePath" name="squizRoutePath" value="{{ config('squiz.route_path') }}">
</form>

</body>
</html>
