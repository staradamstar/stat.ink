/*! Copyright (C) AIZAWA Hina | MIT License */
($ => {
  const makeMonthMarkings = (json, color) => {
    const data = json.slice(1);
    const loopMax = Math.floor(data.length / 2);
    const results = [];
    for (let i = 0; i < loopMax; ++i) {
      results.push({
        xaxis: {
          from: data[i * 2 + 0][0] * 1000,
          to:   data[i * 2 + 1][0] * 1000 - 1,
        },
        color: color,
      });
    }
    return results;
  };

  $(() => {
    $('.trend-graph').each(function () {
      const $graph = $(this);
      const json = JSON.parse($($graph.data('target')).text());
      const xaxisJson = JSON.parse($($graph.data('xaxis')).text());
      const monthsJson = JSON.parse($($graph.data('months')).text());
      const data = json.map(typeData => ({
        label: typeData.name,
        lines: {
          show: true,
          fill: true,
          steps: true,
          zero: true,
        },
        points: {
          show: false,
        },
        shadowSize: 0,
        data: typeData.data.map(item => [item[0] * 1000, item[1] * 100]),
      }));
      $.plot($graph, data, {
        series: {
          stack: true,
        },
        yaxis: {
          show: true,
          min: 0,
          max: 100.1,
          minTickSize: 12.5,
          tickDecimals: 1,
          tickFormatter: v => `${v}%`,
        },
        xaxis: {
          show: true,
          mode: 'time',
          ticks: xaxisJson.map(v => [v[0] * 1000, v[1]]),
        },
        legend: {
          container: $($graph.data('legend')),
          sorted: 'reverse',
        },
        grid: {
          markings: makeMonthMarkings(monthsJson, 'rgba(0,0,0,.2)'),
        },
      });
    });
  });
})(jQuery);
