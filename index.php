<?php
$symbols = [
  'VFIDX' => 'Interm Term Investment Grade Bond Adm',
  'VTAPX' => 'Short Term Inflation Protection Securites Index Adml',
  'VFSUX' => 'Short Term Investment Grade Adml',
  'VBTLX' => 'Total Bond Market Index Adml',
  'VTABX' => 'Total International Bond Index Adml',
  'VFIAX' => '500 Index Adml',
  'VBIAX' => 'Balanced Index Adml',
  'VTMGX' => 'Developed Markets Index Adm',
  'VEMAX' => 'Emerging Markets Stock Index Adml',
  'VEXRX' => 'Explorer Adm',
  'VSCGX' => 'Lifestrategy Conservative Growth Inv',
  'VASGX' => 'Lifestrategy Growth Inv',
  'VASIX' => 'Lifestrategy Income Inv',
  'VSMGX' => 'Lifestrategy Moderate Growth Inv',
  'VIMAX' => 'Mid Cap Index Fund Adml',
  'VMRAX' => 'Morgan Growth Adm',
  'VSMAX' => 'Small Cap Index Adml',
  'VTIAX' => 'Total International Stock Index Adml',
  'VTSAX' => 'Total Stock Market Index Adml',
  'VWNFX' => 'Windsor Ii Inv'
];

if (array_key_exists($_SERVER['QUERY_STRING'], $symbols)) {
    require __DIR__ . '/vendor/autoload.php';

    $redis  = isset($_ENV['REDIS_URL']) ? new Predis\Client($_ENV['REDIS_URL']) : new Predis\Client;
    $symbol = $_SERVER['QUERY_STRING'];
    $data   = $redis->get($symbol);

    if (!$data) {
      $data = json_decode(json_decode(explode("\n", file_get_contents('http://www.google.com/async/finance_chart_data?async=x:MUTF,p:40Y,i:86400,q:' . $symbol))[1])->tnv->value);
      $data = gzencode(json_encode(array_map(null, array_map(function ($t) { return strtotime($t) * 1000; }, $data->t), $data->v[0])), 9);
      $redis->set($symbol, $data);
      $redis->expire($symbol, 86400);
    }

    header('Content-Encoding: gzip');
    die($data);
}
?><!doctype html>
<html>
  <head>
    <title>Fund Performance</title>
    <style>
      html, body, #chart { height: 100%; }
      body { margin: 0; }
    </style>
  </head>
  <body>
    <div id="chart"></div>
    <script src="//code.jquery.com/jquery-2.2.2.min.js"></script>
    <script src="//code.highcharts.com/4.2/highcharts.js"></script>
    <script src="//code.highcharts.com/4.2/themes/grid.js"></script>
    <script>
      $('#chart').highcharts({
        credits: { enabled: false },
        title: null,
        tooltip: { valueDecimals: 2 },
        xAxis: { type: 'datetime' },
        yAxis: { title: null },
        chart: {
          zoomType: 'xy',
          events: {
            load: function () {
              var chart = this;
              var symbols = <?php echo json_encode($symbols); ?>;
              Object.keys(symbols).forEach(function (symbol) {
                $.getJSON('?' + symbol, function (data) {
                  chart.addSeries({ name: symbols[symbol], data: data });
                });
              });
            }
          }
        }
      });
    </script>
  </body>
</html>
