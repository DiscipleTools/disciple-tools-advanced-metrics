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

      <div id="sample_spinner" style="display: inline-block" class="loading-spinner"></div>
    `)

    // Create chart instance
    

    // Add data
    get_gender_ratio_chart()

    
  }

  window.get_gender_ratio_chart = function get_gender_ratio_chart( ) {


    let localizedObject = window.wp_js_object // change this object to the one named in ui-menu-and-enqueue.php

    let button = jQuery('#sample_button')

    $('#sample_spinner').addClass("active")

    return jQuery.ajax({
      type: "GET",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      url: `${localizedObject.rest_endpoints_base}/get_gender_ratio_chart`,
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', localizedObject.nonce);
      },
    })
    .done(function (data) {
      $('#sample_spinner').removeClass("active")
      var chart = am4core.create("chartdiv", am4charts.PieChart);
      chart.data = data
      console.log( data )

      // Add and configure Series
      var pieSeries = chart.series.push(new am4charts.PieSeries());
      pieSeries.dataFields.value = "count";
      pieSeries.dataFields.category = "gender";
      
    })
    .fail(function (err) {
      $('#sample_spinner').removeClass("active")
      console.log("error");
      console.log(err);
    })
  }
})();
