function parseIsoDatePrix(val) {
  if (!val) return null;
  var parts = val.split('-');
  if (parts.length !== 3) return null;
  return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
}

function periodesPrixSeChevauchent(debutA, finA, debutB, finB) {
  var dA = debutA || new Date(1900, 0, 1);
  var fA = finA || new Date(2100, 11, 31);
  var dB = debutB || new Date(1900, 0, 1);
  var fB = finB || new Date(2100, 11, 31);
  return dA <= fB && dB <= fA;
}

function prixRowEstVisible(row, usineQuery, filterDebut, filterFin) {
  var usine = (row.getAttribute('data-usine') || '').toLowerCase();
  if (usineQuery && usine.indexOf(usineQuery) === -1) {
    return false;
  }

  if (filterDebut || filterFin) {
    var rowDebut = parseIsoDatePrix(row.getAttribute('data-date-debut'));
    var rowFin = parseIsoDatePrix(row.getAttribute('data-date-fin'));
    if (!periodesPrixSeChevauchent(rowDebut, rowFin, filterDebut, filterFin)) {
      return false;
    }
  }

  return true;
}

function rafraichirPaginationPrixTable(tableDiv) {
  var usineQuery = (tableDiv.querySelector('.prix-filtre-usine')?.value || '').trim().toLowerCase();
  var du = tableDiv.querySelector('.prix-filtre-debut')?.value || '';
  var au = tableDiv.querySelector('.prix-filtre-fin')?.value || '';
  var filterDebut = du ? parseIsoDatePrix(du) : null;
  var filterFin = au ? parseIsoDatePrix(au) : null;

  var rows = Array.from(tableDiv.querySelectorAll('.prix-row'));
  var visibleRows = [];

  rows.forEach(function(row) {
    var visible = prixRowEstVisible(row, usineQuery, filterDebut, filterFin);
    row.setAttribute('data-filter-visible', visible ? '1' : '0');
    if (visible) {
      visibleRows.push(row);
    }
  });

  var countEl = tableDiv.querySelector('.prix-count-visible');
  if (countEl) {
    countEl.textContent = visibleRows.length;
  }

  var emptyMsg = tableDiv.querySelector('.prix-filtre-empty');
  if (emptyMsg) {
    emptyMsg.classList.toggle('d-none', visibleRows.length > 0);
  }

  var perPage = 10;
  var totalPages = Math.max(1, Math.ceil(visibleRows.length / perPage));
  tableDiv.setAttribute('data-total-pages', totalPages);

  var currentPage = parseInt(tableDiv.getAttribute('data-current-page'), 10) || 1;
  if (currentPage > totalPages) {
    currentPage = totalPages;
    tableDiv.setAttribute('data-current-page', currentPage);
  }

  rows.forEach(function(row) {
    row.classList.add('d-none');
  });

  visibleRows.forEach(function(row, index) {
    var page = Math.floor(index / perPage) + 1;
    if (page === currentPage) {
      row.classList.remove('d-none');
    }
  });

  var pagination = tableDiv.querySelector('.pagination-controls');
  if (pagination) {
    pagination.style.display = totalPages > 1 ? 'flex' : 'none';
  }

  var pageInfo = tableDiv.querySelector('.page-info');
  if (pageInfo) {
    pageInfo.textContent = 'Page ' + currentPage + ' / ' + totalPages;
  }

  var btnPrev = tableDiv.querySelector('.btn-prev');
  var btnNext = tableDiv.querySelector('.btn-next');
  if (btnPrev) btnPrev.disabled = currentPage === 1;
  if (btnNext) btnNext.disabled = currentPage === totalPages;
}

function appliquerFiltresPrixTable(tableDiv) {
  tableDiv.setAttribute('data-current-page', '1');
  rafraichirPaginationPrixTable(tableDiv);
}

function changePage(tableId, direction) {
  var tableDiv = document.getElementById(tableId);
  if (!tableDiv) return;

  if (direction === 0) {
    rafraichirPaginationPrixTable(tableDiv);
    return;
  }

  var currentPage = parseInt(tableDiv.getAttribute('data-current-page'), 10) || 1;
  var totalPages = parseInt(tableDiv.getAttribute('data-total-pages'), 10) || 1;
  var newPage = currentPage + direction;

  if (newPage < 1 || newPage > totalPages) return;

  tableDiv.setAttribute('data-current-page', newPage);
  rafraichirPaginationPrixTable(tableDiv);
}

function initPrixTableFiltres() {
  document.querySelectorAll('.produit-table').forEach(function(tableDiv) {
    tableDiv.querySelectorAll('.prix-row').forEach(function(row) {
      row.setAttribute('data-filter-visible', '1');
    });

    var filtres = tableDiv.querySelector('.prix-filtres');
    if (filtres) {
      var inputs = filtres.querySelectorAll('.prix-filtre-usine, .prix-filtre-debut, .prix-filtre-fin');
      inputs.forEach(function(input) {
        input.addEventListener('input', function() {
          appliquerFiltresPrixTable(tableDiv);
        });
        input.addEventListener('change', function() {
          appliquerFiltresPrixTable(tableDiv);
        });
      });

      var resetBtn = filtres.querySelector('.prix-filtre-reset');
      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          filtres.querySelector('.prix-filtre-usine').value = '';
          filtres.querySelector('.prix-filtre-debut').value = '';
          filtres.querySelector('.prix-filtre-fin').value = '';
          appliquerFiltresPrixTable(tableDiv);
        });
      }
    }

    rafraichirPaginationPrixTable(tableDiv);
  });
}
