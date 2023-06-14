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

  am5.ready(function () {

    let root = am5.Root.new("chartdiv");
    root.setThemes([am5themes_Animated.new(root)]);

    let chart = root.container.children.push(am5map.MapChart.new(root, {}));

    let polygonSeries = chart.series.push(
      am5map.MapPolygonSeries.new(root, {
        geoJSON: am5geodata_worldLow,
        exclude: ["AQ"]
      })
    );

    let bubbleSeries = chart.series.push(
      am5map.MapPointSeries.new(root, {
        valueField: "count",
        calculateAggregates: true,
        polygonIdField: "code"
      })
    );

    let circleTemplate = am5.Template.new({});

    bubbleSeries.bullets.push(function (root, series, dataItem) {
      let count = dataItem.dataContext.count;
      let radius = count ? ( ( ( count / 2 ) > 10 ) ? ( count / 2 ) : 10 ) : 10;

      let container = am5.Container.new(root, {});
      let circle = container.children.push(
        am5.Circle.new(root, {
          radius: radius,
          fillOpacity: 0.7,
          fill: am5.color(0xff0000),
          cursorOverStyle: "pointer",
          tooltipText: `{name}: [bold]{count}[/]`
        }, circleTemplate)
      );

      let countryLabel = container.children.push(
        am5.Label.new(root, {
          text: "{name}",
          paddingLeft: 5,
          populateText: true,
          fontWeight: "bold",
          fontSize: 13,
          centerY: am5.p50
        })
      );

      circle.on("radius", function (radius) {
        countryLabel.set("x", radius);
      })

      return am5.Bullet.new(root, {
        sprite: container,
        dynamic: true
      });
    });

    bubbleSeries.bullets.push(function (root, series, dataItem) {
      return am5.Bullet.new(root, {
        sprite: am5.Label.new(root, {
          text: "{count.formatNumber('#.')}",
          fill: am5.color(0xffffff),
          populateText: true,
          centerX: am5.p50,
          centerY: am5.p50,
          textAlign: "center"
        }),
        dynamic: true
      });
    });

    // minValue and maxValue must be set for the animations to work
    bubbleSeries.set("heatRules", [
      {
        target: circleTemplate,
        dataField: "count",
        min: 10,
        max: 50,
        minValue: 0,
        maxValue: 100,
        key: "radius"
      }
    ]);

    // Fetch latest snapshot data.
    get_data(function (response) {

      // Re-package stats into required data shape.
      let data = [];
      if (response['stats']) {
        $.each(response['stats'], function (idx, stat) {
          data.push(stat);
        });
      }
      bubbleSeries.data.setAll(data);

      $(".loading-spinner").removeClass("active");
    });
  });
});
