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

                /*
                 * Unavailable teams stay listed but cannot be chosen. Removing
                 * them entirely makes a team the player is hunting for simply
                 * absent, with no way to tell whether it is on a bye, playing
                 * before the deadline, or one they already used.
                 */
                if (option.disabled) {
                    item.classList.add('is-unavailable');
                    item.setAttribute('aria-disabled', 'true');
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
                text.className = 'teampicker-option-name';
                text.textContent = name;
                item.appendChild(text);

                if (option.disabled && option.dataset.reason) {
                    var reason = document.createElement('span');
                    reason.className = 'teampicker-reason';
                    reason.textContent = option.dataset.reason;
                    item.appendChild(reason);
                }

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

        /* Arrow keys move between teams that can actually be picked. */
        function selectableItems() {
            return visibleItems().filter(function (item) {
                return !item.classList.contains('is-unavailable');
            });
        }

        function moveHighlight(direction) {
            var items = selectableItems();
            if (!items.length) {
                return;
            }
            var current = items.findIndex(function (item) {
                return item.classList.contains('is-active');
            });
            var next = current === -1
                ? 0
                : (current + direction + items.length) % items.length;
            highlightItem(items[next]);
        }

        function highlightItem(item) {
            visibleItems().forEach(function (other) {
                other.classList.remove('is-active');
            });
            if (!item) {
                return;
            }
            item.classList.add('is-active');
            item.scrollIntoView({ block: 'nearest' });
            active = visibleItems().indexOf(item);
        }

        function highlightFirstSelectable() {
            highlightItem(selectableItems()[0]);
        }

        function open() {
            buildList('');
            popup.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            search.value = '';
            var chosen = selectableItems().find(function (item) {
                return item.getAttribute('aria-selected') === 'true';
            });
            if (chosen) {
                highlightItem(chosen);
            } else {
                highlightFirstSelectable();
            }
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
            if (!item || item.classList.contains('is-unavailable')) {
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
            highlightFirstSelectable();
        });

        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveHighlight(1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveHighlight(-1);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                choose(visibleItems()[active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
            } else if (event.key === 'Home') {
                event.preventDefault();
                highlightFirstSelectable();
            } else if (event.key === 'End') {
                event.preventDefault();
                var items = selectableItems();
                highlightItem(items[items.length - 1]);
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
