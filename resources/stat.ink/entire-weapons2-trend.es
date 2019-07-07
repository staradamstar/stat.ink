/*! Copyright (C) AIZAWA Hina | MIT License */
($ => {
  $(() => {
    $('.trend-graph').each(function () {
      const $graph = $(this);
      const json = JSON.parse($($graph.data('target')).text());
      const xaxisJson = JSON.parse($($graph.data('xaxis')).text());
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
        data: typeData.data.map(item => {
          return [
            item[0] * 1000,
            item[1] * 100,
          ];
        }),
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
          tickFormatter: v => Number(v).toFixed(1) + '%',
        },
        xaxis: {
          show: true,
          mode: 'time',
          // tickFormatter: val => {
          //   const d = new Date(val);
          //   const zero = v => {
          //     return v < 10 ? '0' + String(v) : String(v);
          //   };
          //   return d.getUTCFullYear() + '-' + zero(d.getUTCMonth() + 1);
          // },
        },
        legend: {
          container: $($graph.data('legend')),
        },
      });
    });
  });
})(jQuery);
