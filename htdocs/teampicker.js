/*
 * Upgrades the team <select> into a listbox that can show club crests.
 *
 * A native select cannot render images in its options, and the crest is the
 * fastest way to recognise a team. So the select stays in the DOM as the form
 * control and the no-JS fallback, and this draws a listbox over it. If this
 * script never runs, picking still works with the native control.
 *
 * The select is delivered by htmx and replaced whenever the chosen username
 * changes, so enhancement re-runs after every swap.
 */
(function () {
    'use strict';

    function enhance(select) {
        if (!select || select.dataset.enhanced === 'true') {
            return;
        }
        select.dataset.enhanced = 'true';

        var wrapper = document.createElement('div');
        wrapper.className = 'teampicker';

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'teampicker-button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        button.id = select.id + '-button';

        var popup = document.createElement('div');
        popup.className = 'teampicker-popup';
        popup.hidden = true;

        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'teampicker-search';
        search.setAttribute('placeholder', 'Search teams');
        search.setAttribute('aria-label', 'Search teams');

        var list = document.createElement('ul');
        list.className = 'teampicker-list';
        list.setAttribute('role', 'listbox');
        list.id = select.id + '-listbox';
        button.setAttribute('aria-controls', list.id);

        popup.appendChild(search);
        popup.appendChild(list);

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(button);
        wrapper.appendChild(popup);
        wrapper.appendChild(select);
        select.hidden = true;

        var options = Array.prototype.slice.call(select.options).filter(function (option) {
            return option.value !== '' && option.textContent.trim() !== '';
        });
        var active = -1;

        function paintButton() {
            var chosen = select.options[select.selectedIndex];
            var logo = chosen && chosen.dataset ? chosen.dataset.logo : null;
            var label = chosen ? chosen.textContent.trim() : 'Choose a team';

            button.innerHTML = '';
            if (logo) {
                var crest = document.createElement('img');
                crest.src = logo;
                crest.alt = '';
                crest.width = 26;
                crest.height = 26;
                crest.className = 'team-crest';
                button.appendChild(crest);
            }
            var text = document.createElement('span');
            text.className = 'teampicker-value';
            text.textContent = label;
            button.appendChild(text);

            var chevron = document.createElement('span');
            chevron.className = 'teampicker-chevron';
            chevron.setAttribute('aria-hidden', 'true');
            chevron.textContent = '▾';
            button.appendChild(chevron);
        }

        function buildList(filter) {
            list.innerHTML = '';
            var needle = (filter || '').toLowerCase();

            options.forEach(function (option, index) {
                var name = option.textContent.trim();
                if (needle && name.toLowerCase().indexOf(needle) === -1) {
                    return;
                }

                var item = document.createElement('li');
                item.className = 'teampicker-option';
                item.setAttribute('role', 'option');
                item.dataset.index = String(index);
                item.setAttribute('aria-selected', String(option.selected));
                if (option.dataset.color) {
                    item.style.setProperty('--club', option.dataset.color);
                }

                if (option.dataset.logo) {
                    var crest = document.createElement('img');
                    crest.src = option.dataset.logo;
                    crest.alt = '';
                    crest.width = 24;
                    crest.height = 24;
                    crest.loading = 'lazy';
                    crest.className = 'team-crest';
                    item.appendChild(crest);
                }

                var text = document.createElement('span');
                text.textContent = name;
                item.appendChild(text);
                list.appendChild(item);
            });

            if (!list.children.length) {
                var empty = document.createElement('li');
                empty.className = 'teampicker-empty';
                empty.textContent = 'No teams match';
                list.appendChild(empty);
            }
        }

        function visibleItems() {
            return Array.prototype.slice.call(list.querySelectorAll('.teampicker-option'));
        }

        function highlight(position) {
            var items = visibleItems();
            items.forEach(function (item) {
                item.classList.remove('is-active');
            });
            if (!items.length) {
                active = -1;
                return;
            }
            active = Math.max(0, Math.min(position, items.length - 1));
            items[active].classList.add('is-active');
            items[active].scrollIntoView({ block: 'nearest' });
        }

        function open() {
            buildList('');
            popup.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            search.value = '';
            var chosen = visibleItems().findIndex(function (item) {
                return item.getAttribute('aria-selected') === 'true';
            });
            highlight(chosen === -1 ? 0 : chosen);
            search.focus();
        }

        function close(refocus) {
            popup.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            if (refocus) {
                button.focus();
            }
        }

        function choose(item) {
            if (!item) {
                return;
            }
            select.selectedIndex = options[Number(item.dataset.index)].index;
            /* Let htmx and anything else listening see a real change event. */
            select.dispatchEvent(new Event('change', { bubbles: true }));
            paintButton();
            close(true);
        }

        button.addEventListener('click', function () {
            if (popup.hidden) {
                open();
            } else {
                close(true);
            }
        });

        search.addEventListener('input', function () {
            buildList(search.value);
            highlight(0);
        });

        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                highlight(active + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                highlight(active - 1);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                choose(visibleItems()[active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
            } else if (event.key === 'Home') {
                event.preventDefault();
                highlight(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                highlight(visibleItems().length - 1);
            }
        });

        list.addEventListener('click', function (event) {
            var item = event.target.closest('.teampicker-option');
            choose(item);
        });

        document.addEventListener('click', function (event) {
            if (!popup.hidden && !wrapper.contains(event.target)) {
                close(false);
            }
        });

        paintButton();
    }

    function enhanceAll() {
        enhance(document.getElementById('teams'));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhanceAll);
    } else {
        enhanceAll();
    }

    /* The dropdown is swapped in by htmx on load and on username change. */
    document.body.addEventListener('htmx:afterSwap', enhanceAll);
})();
