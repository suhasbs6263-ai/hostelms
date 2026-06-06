(function () {
  function readConfig(element) {
    try {
      return JSON.parse(element.getAttribute('data-hostel-chart') || '{}');
    } catch (error) {
      return {};
    }
  }

  function hasData(values) {
    return Array.isArray(values) && values.some(function (value) {
      return Number(value) > 0;
    });
  }

  function renderEmpty(element) {
    element.innerHTML = '<div class="empty-state py-4"><h4>No chart data yet</h4><p class="text-muted mb-0">Data will appear after records are added.</p></div>';
  }

  function createCanvas(element) {
    element.innerHTML = '';
    var canvas = document.createElement('canvas');
    canvas.setAttribute('aria-label', 'Dashboard chart');
    canvas.setAttribute('role', 'img');
    element.appendChild(canvas);
    return canvas;
  }

  function renderDonut(element, config) {
    var series = config.series || [];

    if (!hasData(series)) {
      renderEmpty(element);
      return;
    }

    new Chart(createCanvas(element), {
      type: 'doughnut',
      data: {
        labels: config.labels || [],
        datasets: [{
          data: series,
          backgroundColor: config.colors || ['#2563eb', '#14b8a6', '#f59e0b', '#ef4444'],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              boxHeight: 10,
              color: '#334155',
              font: { weight: '700' },
              padding: 16
            }
          },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            titleFont: { weight: '800' },
            bodyFont: { weight: '700' }
          }
        }
      }
    });
  }

  function renderBar(element, config) {
    var series = config.series || [];
    var firstSeries = series[0] || { data: [] };

    if (!hasData(firstSeries.data || [])) {
      renderEmpty(element);
      return;
    }

    new Chart(createCanvas(element), {
      type: 'bar',
      data: {
        labels: config.categories || [],
        datasets: series.map(function (item, index) {
          return {
            label: item.name || 'Total',
            data: item.data || [],
            backgroundColor: (config.colors || ['#2563eb', '#14b8a6'])[index] || '#2563eb',
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 44
          };
        })
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: { display: series.length > 1 },
          tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            titleFont: { weight: '800' },
            bodyFont: { weight: '700' }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              color: '#64748b',
              font: { weight: '700' }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: '#e2e8f0',
              drawBorder: false
            },
            ticks: {
              precision: 0,
              color: '#64748b',
              font: { weight: '700' }
            }
          }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
      return;
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-hostel-chart]')).forEach(function (element) {
      var config = readConfig(element);

      if (config.type === 'bar') {
        renderBar(element, config);
        return;
      }

      renderDonut(element, config);
    });
  });
})();
