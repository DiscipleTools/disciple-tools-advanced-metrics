jQuery(document).ready(function () {

  jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));

  let chartDiv = jQuery('#chart')

  chartDiv.empty().html(`
    <div class="section-header">${window.SHAREDFUNCTIONS.escapeHTML(window.wp_js_object.translations.title)}</div>
    <div style="display: inline-block" class="loading-spinner active"></div>
    <hr>

    <div id="chartdiv" style="width: 100%; height: 600px;"></div>
  `);

  let get_data = (callback) => {
    $(".loading-spinner").addClass("active");

    let params = {};

    jQuery.ajax({
      type: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: JSON.stringify(params),
      url: `${wp_js_object.rest_endpoints_base}get-data`,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', wpApiShare.nonce);
      },
    })
    .done(function (data) {
      callback(data);
    });
  }

  let normalise = (value, min, max) => {
    return (value - min) / (max - min);
  }

  am5.ready(function () {

    let root = am5.Root.new("chartdiv");
    root.setThemes([am5themes_Animated.new(root)]);

    let chart = root.container.children.push(am5map.MapChart.new(root, {
      projection: am5map.geoMercator()
    }));

    let polygonSeries = chart.series.push(
      am5map.MapPolygonSeries.new(root, {
        geoJSON: am5geodata_unRegionsLow,
        valueField: "value",
        calculateAggregates: true
      })
    );

    polygonSeries.set('heatRules', [{
      key: "fill",
      target: polygonSeries.mapPolygons.template,
      min: am5.color('#9BC8FE'),
      max: am5.color('#25529A'),
      dataField: 'value',
    }]);

    polygonSeries.mapPolygons.template.setAll({
      stroke: am5.color('#FFFFFF'),
      strokeWidth: 2,
      tooltipText: "{name}: 0",
      interactive: true,
      templateField: "polygonSettings",
      fill: am5.color('#eee'),
    });

    // Fetch latest snapshot data.
    get_data(function (response) {
      if (response && response['stats'] && response['stats']['regions']) {
        let regions = response['stats']['regions'];
        let data = [];
        regions.forEach((region) => {

          let name = `${window.SHAREDFUNCTIONS.escapeHTML(window.wp_js_object.translations.regions[region['region']])}`;

          // Capture data point updates.
          data.push({
            'id': region['region'],
            'name': name,
            'value': region['count'],
            'polygonSettings': {
              'tooltipText': `${name}: ${window.SHAREDFUNCTIONS.escapeHTML(region['count'])}`
            }
          });
        });

        if (data.length > 0) {
          polygonSeries.data.setAll(data);

          polygonSeries.mapPolygons.template.states.create("hover", {
            fillOpacity: .5,
          });
        }
      }
      // Remove spinner.
      $(".loading-spinner").removeClass("active");
    });
  });
});
