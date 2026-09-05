/* tienda.js — snippet 14 (filtros y orden de la pagina Tours en Mendoza; incluye el filtro desde la URL, que el snippet 33 duplicaba) */
(function(){
      function init() {
        var grid = document.getElementById('vempra-shop-grid');
        var holder = document.getElementById('vempra-shop-sort-holder');
        if (holder && !holder.querySelector('select')) {
          holder.innerHTML = '<label for="vempra-sort">Ordenar por:</label>' +
            '<select id="vempra-sort" class="vempra-shop-sort-select">' +
            '<option value="featured">Recomendados</option>' +
            '<option value="price-asc">Precio: menor a mayor</option>' +
            '<option value="price-desc">Precio: mayor a menor</option>' +
            '<option value="name">Nombre A-Z</option>' +
            '</select>';
        }
        var noResults = document.getElementById('vempra-shop-noresults');
        var filters = document.querySelectorAll('.vempra-shop-filter');
        var sortSelect = document.getElementById('vempra-sort');
        var cards = Array.prototype.slice.call(grid.children).filter(function(el){
          return el.classList && el.classList.contains('vempra-shop-card');
        });
        var originalOrder = cards.slice();
        var state = { filter: 'all', sort: 'featured' };
        function render() {
          // 1. Ordenar
          var sorted = cards.slice();
          if (state.sort === 'price-asc') {
            sorted.sort(function(a, b) { return parseInt(a.dataset.price, 10) - parseInt(b.dataset.price, 10); });
          } else if (state.sort === 'price-desc') {
            sorted.sort(function(a, b) { return parseInt(b.dataset.price, 10) - parseInt(a.dataset.price, 10); });
          } else if (state.sort === 'name') {
            sorted.sort(function(a, b) { return a.dataset.name.localeCompare(b.dataset.name); });
          } else {
            sorted = originalOrder.slice();
          }
          sorted.forEach(function(card) { grid.appendChild(card); });
          // 2. Filtrar (con clase + display inline)
          var visible = 0;
          cards.forEach(function(card) {
            var cats = (card.getAttribute('data-category') || '').split(' ');
            var match = state.filter === 'all' || cats.indexOf(state.filter) !== -1;
            if (match) {
              card.classList.remove('vempra-hidden');
              card.style.display = '';
              visible++;
            } else {
              card.classList.add('vempra-hidden');
              card.style.display = 'none';
            }
          });
          if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
        }
        filters.forEach(function(btn) {
          btn.addEventListener('click', function() {
            filters.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            state.filter = this.getAttribute('data-filter');
            render();
          });
        });
        if (sortSelect) {
          sortSelect.addEventListener('change', function() {
            state.sort = this.value;
            render();
          });
        }

        // === FILTRO DESDE URL ===
        // Lee ?filter=X (o ?categoria=X / ?cat=X) y activa el botón al cargar
        var params = new URLSearchParams(window.location.search);
        var urlFilter = params.get('filter') || params.get('categoria') || params.get('cat');
        if (urlFilter) {
          var targetBtn = document.querySelector('.vempra-shop-filter[data-filter="' + urlFilter + '"]');
          if (targetBtn) {
            targetBtn.click();
            // Scroll suave a la zona de filtros
            setTimeout(function() {
              var section = document.querySelector('.vempra-shop-controls');
              if (section) {
                var offset = section.getBoundingClientRect().top + window.pageYOffset - 100;
                window.scrollTo({ top: offset, behavior: 'smooth' });
              }
            }, 150);
          } else {
          }
        }
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
    })();
