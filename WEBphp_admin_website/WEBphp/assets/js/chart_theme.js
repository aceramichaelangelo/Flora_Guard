/* Shared Chart.js defaults so dashboard/report charts match the app's type & palette. */
(function () {
  if (typeof Chart === 'undefined') return;

  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#64748b';
  Chart.defaults.borderColor = '#eef2f0';

  Chart.defaults.plugins.tooltip.backgroundColor = '#14532d';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };
  Chart.defaults.plugins.tooltip.displayColors = true;
  Chart.defaults.plugins.tooltip.boxPadding = 4;

  Chart.defaults.plugins.legend.labels.font = { weight: '600' };
  Chart.defaults.scale.grid.color = '#f1f5f4';
})();
