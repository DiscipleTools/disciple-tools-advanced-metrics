(function() {
  "use strict";
  jQuery(document).ready(function() {

    // expand the current selected menu
    jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));


    show_template_overview()

  })

  function show_template_overview(){

    let localizedObject = window.wp_js_object // change this object to the one named in ui-menu-and-enqueue.php
    let translations = localizedObject.translations

    let chartDiv = jQuery('#chart') // retrieves the chart div in the metrics page

    chartDiv.empty().html(`
      <span class="section-header">${localizedObject.translations.title}</span>

      <hr style="max-width:100%;">
      
      <div id="chartdiv"></div>
      
      <hr style="max-width:100%;">

      <button type="button" onclick="sample_api_call('Yeh successful response from API!')" class="button" id="sample_button">${translations["Sample API Call"]}</button>
      <div id="sample_spinner" style="display: inline-block" class="loading-spinner"></div>
    `)

    // Create chart instance
var chart = am4core.create("chartdiv", am4charts.XYChart);

    // Add data
    chart.data = [{
  "category": "",
  "from": 0,
  "to": 15,
  "name": "Stage #1",
  "fill": am4core.color("#0ca948")
}, {
  "category": "",
  "from": 15,
  "to": 75,
  "name": "Stage #2",
  "fill": am4core.color("#93da49")
}, {
  "category": "",
  "from": 75,
  "to": 90,
  "name": "Stage #3",
  "fill": am4core.color("#ffd100")
}, {
  "category": "",
  "from": 90,
  "to": 95,
  "name": "Stage #4",
  "fill": am4core.color("#cd213b")
}, {
  "category": "",
  "from": 95,
  "to": 100,
  "name": "Stage #5",
  "fill": am4core.color("#9e9e9e")
}];

    // Create axes
var yAxis = chart.yAxes.push(new am4charts.CategoryAxis());
yAxis.dataFields.category = "category";
yAxis.renderer.grid.template.disabled = true;
yAxis.renderer.labels.template.disabled = true;

var xAxis = chart.xAxes.push(new am4charts.ValueAxis());
xAxis.renderer.grid.template.disabled = true;
xAxis.renderer.grid.template.disabled = true;
xAxis.renderer.labels.template.disabled = true;
xAxis.min = 0;
xAxis.max = 100;

// Create series
var series = chart.series.push(new am4charts.ColumnSeries());
series.dataFields.valueX = "to";
series.dataFields.openValueX = "from";
series.dataFields.categoryY = "category";
series.columns.template.propertyFields.fill = "fill";
series.columns.template.strokeOpacity = 0;
series.columns.template.height = am4core.percent(100);

// Ranges/labels
chart.events.on("beforedatavalidated", function(ev) {
  var data = chart.data;
  for(var i = 0; i < data.length; i++) {
    var range = xAxis.axisRanges.create();
    range.value = data[i].to;
    range.label.text = data[i].to + "%";
    range.label.horizontalCenter = "right";
    range.label.paddingLeft = 5;
    range.label.paddingTop = 5;
    range.label.fontSize = 10;
    range.grid.strokeOpacity = 0.2;
    range.tick.length = 18;
    range.tick.strokeOpacity = 0.2;
  }
});

// Legend
var legend = new am4charts.Legend();
legend.parent = chart.chartContainer;
legend.itemContainers.template.clickable = false;
legend.itemContainers.template.focusable = false;
legend.itemContainers.template.cursorOverStyle = am4core.MouseCursorStyle.default;
legend.align = "right";
legend.data = chart.data;

}); // end am4core.ready()

  window.sample_api_call = function sample_api_call( button_data ) {


    let localizedObject = window.wp_js_object // change this object to the one named in ui-menu-and-enqueue.php

    let button = jQuery('#sample_button')

    $('#sample_spinner').addClass("active")

    let data = { "button_data": button_data };
    return jQuery.ajax({
      type: "POST",
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: `${localizedObject.rest_endpoints_base}/sample`,
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', localizedObject.nonce);
      },
    })
    .done(function (data) {
      $('#sample_spinner').removeClass("active")
      button.empty().append(data)
      console.log( 'success' )
      console.log( data )
    })
    .fail(function (err) {
      $('#sample_spinner').removeClass("active")
      button.empty().append("error. Something went wrong")
      console.log("error");
      console.log(err);
    })
  }
})();
