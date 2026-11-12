/**
 * Filtre AJAX de la liste produits.
 * Le formulaire reste un GET classique sans JavaScript.
 * Avec JavaScript : le bouton Filtrer, le tri et la pagination
 * appellent /products/rows et remplacent le tableau.
 * Changer le select catégorie ne filtre pas tant que Filtrer n'est pas cliqué.
 */
(function () {
    var form = document.getElementById('product-filter');
    var tbody = document.getElementById('product-rows');
    var nav = document.getElementById('product-pagination');
    var count = document.getElementById('product-count');
    var sortLink = document.getElementById('product-sort');

    if (!form || !tbody || !window.fetch || !window.history) {
        return;
    }

    var rowsUrl = form.getAttribute('data-rows-url') || '';
    var listUrl = form.getAttribute('data-list-url') || '/products';
    var requestId = 0;

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function sortInput() {
        return form.querySelector('input[name="sort"]');
    }

    function setSort(on) {
        var input = sortInput();
        if (on) {
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sort';
                form.appendChild(input);
            }
            input.value = 'category';
        } else if (input) {
            input.remove();
        }
        if (sortLink) {
            sortLink.classList.toggle('th-sort--on', on);
        }
    }

    function paramsFromForm(page) {
        var params = new URLSearchParams();
        var search = (field('search').value || '').trim();
        var category = field('category').value || '';
        var sort = sortInput();

        if (search !== '') {
            params.set('search', search);
        }
        if (category !== '') {
            params.set('category', category);
        }
        if (sort && sort.value === 'category') {
            params.set('sort', 'category');
        }
        if (page > 1) {
            params.set('page', String(page));
        }
        return params;
    }

    function applyParamsToForm(params) {
        field('search').value = params.get('search') || '';
        field('category').value = params.get('category') || '';
        setSort(params.get('sort') === 'category');
    }

    function listHref(params) {
        var query = params.toString();
        return query === '' ? listUrl : listUrl + '?' + query;
    }

    function updateSortHref(params) {
        if (!sortLink) {
            return;
        }
        var next = new URLSearchParams(params.toString());
        next.delete('page');
        if (next.get('sort') === 'category') {
            next.delete('sort');
        } else {
            next.set('sort', 'category');
        }
        sortLink.setAttribute('href', listHref(next));
    }

    function load(page, push) {
        var id = ++requestId;
        var params = paramsFromForm(page);
        var href = listHref(params);

        tbody.setAttribute('aria-busy', 'true');

        return fetch(rowsUrl + (params.toString() ? '?' + params.toString() : ''), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('http');
            }
            return response.json();
        }).then(function (data) {
            if (id !== requestId) {
                return;
            }
            tbody.innerHTML = data.rows;
            if (nav) {
                nav.innerHTML = data.pagination;
            }
            if (count) {
                count.textContent = String(data.total) + ' produit(s) trouvé(s)';
            }
            setSort(data.sort === 'category');
            updateSortHref(params);
            if (push) {
                var current = window.location.pathname + window.location.search;
                if (href !== current) {
                    window.history.pushState({ productFilter: true }, '', href);
                }
            }
        }).catch(function () {
            if (id !== requestId) {
                return;
            }
            window.location.assign(href);
        }).finally(function () {
            if (id === requestId) {
                tbody.removeAttribute('aria-busy');
            }
        });
    }

    // Filtrer : pas de rechargement, on remplace le tableau et on met l'URL à jour.
    // Le select catégorie n'a pas d'écouteur : il ne part qu'avec ce bouton.
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        load(1, true);
    });

    if (sortLink) {
        sortLink.addEventListener('click', function (event) {
            event.preventDefault();
            setSort(!sortLink.classList.contains('th-sort--on'));
            load(1, true);
        });
    }

    if (nav) {
        nav.addEventListener('click', function (event) {
            var link = event.target.closest('a');
            if (!link) {
                return;
            }
            event.preventDefault();
            var url = new URL(link.href, window.location.href);
            applyParamsToForm(url.searchParams);
            var page = parseInt(url.searchParams.get('page') || '1', 10);
            if (!page || page < 1) {
                page = 1;
            }
            load(page, true);
        });
    }

    window.addEventListener('popstate', function () {
        var params = new URLSearchParams(window.location.search);
        applyParamsToForm(params);
        var page = parseInt(params.get('page') || '1', 10);
        if (!page || page < 1) {
            page = 1;
        }
        load(page, false);
    });
})();
