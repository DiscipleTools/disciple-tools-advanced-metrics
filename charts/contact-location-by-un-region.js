jQuery(document).ready(function () {

  jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));

  let chartDiv = jQuery('#chart')

  chartDiv.empty().html(`
    <div class="section-header">${_.escape(window.wp_js_object.translations.title)}</div>
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
        geoJSON: am5geodata_unRegionsLow
      })
    );

    polygonSeries.mapPolygons.template.setAll({
      stroke: am5.color(0xffffff),
      strokeWidth: 2,
      fillOpacity: 0.2,
      tooltipText: "{name}: {value}",
      interactive: true,
      templateField: "polygonSettings"
    });

    polygonSeries.mapPolygons.template.states.create("hover", {
      fill: am5.color(0x677935)
    });

    // Fetch latest snapshot data.
    get_data(function (response) {

      if (response && response['stats'] && response['stats']['regions']) {
        let regions = response['stats']['regions'];
        let regionWithMinCount = _.minBy(regions, 'count');
        let regionWithMaxCount = _.maxBy(regions, 'count');

        // Assuming valid region range has been identified, proceed with map refresh.
        if (regionWithMinCount && regionWithMaxCount) {
          let data = [];
          $.each(regions, function (idx, region) {
            let opacity = normalise(region['count'], regionWithMinCount['count'], regionWithMaxCount['count']);
            data.push({
              'id': region['region'],
              'name': `${_.escape(window.wp_js_object.translations.regions[region['region']])}`,
              'value': region['count'],
              'polygonSettings': {
                'fillOpacity': (opacity > 0.3) ? opacity : (0.3 + opacity)
              }
            });
          });

          if (data.length > 0) {
            polygonSeries.data.setAll(data);
          }
        }
      }

      // Remove spinner.
      $(".loading-spinner").removeClass("active");
    });
  });
});
