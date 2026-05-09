(function () {
  function createElement(tag, className, text) {
    var element = document.createElement(tag);

    if (className) {
      element.className = className;
    }

    if (typeof text === 'string') {
      element.textContent = text;
    }

    return element;
  }

  function initHostelTable(container) {
    var table = container.querySelector('.js-hostel-table');

    if (!table || !table.tBodies.length) {
      return;
    }

    container.classList.add('dataTables_wrapper', 'dt-bootstrap4', 'no-footer');

    var tbody = table.tBodies[0];
    var initialRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    var dataRows = initialRows.filter(function (row) {
      return !row.classList.contains('empty-row');
    });
    var pageSize = parseInt(container.getAttribute('data-page-size'), 10) || 5;
    var page = 1;
    var searchText = '';
    var renumber = container.getAttribute('data-renumber') === 'true';
    var emptyMessage = container.getAttribute('data-empty-message') || 'No entries found';

    var tableId = table.id || ('hostel-table-' + Math.random().toString(36).slice(2, 9));
    table.id = tableId;

    var toolbar = createElement('div', 'row align-items-center hostel-table-toolbar');
    var toolbarLeft = createElement('div', 'col-sm-12 col-md-6');
    var toolbarRight = createElement('div', 'col-sm-12 col-md-6');
    var leftControls = createElement('div', 'dataTables_length table-control-group table-control-start');
    leftControls.id = tableId + '_length';
    leftControls.appendChild(createElement('span', '', 'Show'));

    var pageSizeSelect = createElement('select', 'table-control-select');
    [5, 10, 25].forEach(function (size) {
      var option = createElement('option', '', String(size));
      option.value = String(size);

      if (size === pageSize) {
        option.selected = true;
      }

      pageSizeSelect.appendChild(option);
    });

    leftControls.appendChild(pageSizeSelect);
    leftControls.appendChild(createElement('span', '', 'entries'));
    toolbarLeft.appendChild(leftControls);
    toolbar.appendChild(toolbarLeft);

    var rightControls = createElement('div', 'dataTables_filter table-control-group table-control-end');
    rightControls.id = tableId + '_filter';
    var searchId = 'hostel-table-search-' + Math.random().toString(36).slice(2, 9);
    rightControls.appendChild(createElement('label', '', 'Search:'));

    var searchInput = createElement('input', 'table-search-input');
    searchInput.type = 'search';
    searchInput.id = searchId;
    rightControls.querySelector('label').setAttribute('for', searchId);
    rightControls.appendChild(searchInput);
    toolbarRight.appendChild(rightControls);
    toolbar.appendChild(toolbarRight);
    container.insertBefore(toolbar, table.parentNode);

    var footer = createElement('div', 'row align-items-center hostel-table-footer');
    var footerLeft = createElement('div', 'col-sm-12 col-md-6');
    var footerRight = createElement('div', 'col-sm-12 col-md-6');
    var infoLabel = createElement('div', 'dataTables_info table-info', 'Showing 0 to 0 of 0 entries');
    infoLabel.id = tableId + '_info';
    var pagination = createElement('div', 'dataTables_paginate paging_simple_numbers table-pagination table-pagination-wrap');
    pagination.id = tableId + '_paginate';
    var prevButton = createElement('button', 'paginate_button previous btn btn-sm btn-outline-secondary', 'Previous');
    var nextButton = createElement('button', 'paginate_button next btn btn-sm btn-primary', 'Next');
    prevButton.type = 'button';
    nextButton.type = 'button';
    pagination.appendChild(prevButton);
    pagination.appendChild(nextButton);
    footerLeft.appendChild(infoLabel);
    footerRight.appendChild(pagination);
    footer.appendChild(footerLeft);
    footer.appendChild(footerRight);
    container.appendChild(footer);

    function removeGeneratedEmptyRows() {
      Array.prototype.slice.call(tbody.querySelectorAll('.generated-empty-row')).forEach(function (row) {
        row.remove();
      });
    }

    function render() {
      removeGeneratedEmptyRows();

      var filteredRows = dataRows.filter(function (row) {
        return row.textContent.toLowerCase().indexOf(searchText) !== -1;
      });
      var total = filteredRows.length;
      var totalPages = Math.max(Math.ceil(total / pageSize), 1);

      if (page > totalPages) {
        page = totalPages;
      }

      var startIndex = total === 0 ? 0 : (page - 1) * pageSize;
      var endIndex = Math.min(startIndex + pageSize, total);

      dataRows.forEach(function (row) {
        row.style.display = 'none';
      });

      if (total === 0) {
        var row = createElement('tr', 'empty-row generated-empty-row');
        var cell = createElement('td', 'text-center py-4', emptyMessage);
        cell.colSpan = table.tHead && table.tHead.rows.length ? table.tHead.rows[0].cells.length : 1;
        row.appendChild(cell);
        tbody.appendChild(row);
      } else {
        filteredRows.slice(startIndex, endIndex).forEach(function (row, index) {
          row.style.display = '';

          if (renumber) {
            var firstCell = row.querySelector('td');

            if (firstCell) {
              firstCell.textContent = String(startIndex + index + 1);
            }
          }
        });
      }

      infoLabel.textContent = total === 0
        ? 'Showing 0 to 0 of 0 entries'
        : 'Showing ' + (startIndex + 1) + ' to ' + endIndex + ' of ' + total + ' entries';

      prevButton.disabled = page <= 1 || total === 0;
      nextButton.disabled = page >= totalPages || total === 0;
    }

    pageSizeSelect.addEventListener('change', function () {
      pageSize = parseInt(this.value, 10) || 5;
      page = 1;
      render();
    });

    searchInput.addEventListener('input', function () {
      searchText = this.value.trim().toLowerCase();
      page = 1;
      render();
    });

    prevButton.addEventListener('click', function () {
      if (page > 1) {
        page -= 1;
        render();
      }
    });

    nextButton.addEventListener('click', function () {
      var visibleCount = dataRows.filter(function (row) {
        return row.textContent.toLowerCase().indexOf(searchText) !== -1;
      }).length;

      if (page * pageSize < visibleCount) {
        page += 1;
        render();
      }
    });

    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    Array.prototype.slice.call(document.querySelectorAll('.hostel-datatable')).forEach(initHostelTable);

    Array.prototype.slice.call(document.querySelectorAll('.js-copy-room')).forEach(function (button) {
      button.addEventListener('click', function () {
        var roomNumber = this.getAttribute('data-room');

        if (navigator.clipboard && roomNumber) {
          navigator.clipboard.writeText(roomNumber);
        }
      });
    });
  });
})();
