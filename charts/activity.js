jQuery(document).ready(function() {

  jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));


  let chartDiv = jQuery('#chart')
  let sourceData = wp_js_object.data

  $.urlParam = function (name) {
    let results = new RegExp('[\?&]' + name + '=([^&#]*)')
    .exec(window.location.search);
    return (results !== null) ? results[1] || 0 : false;
  }
  let step = 'month'
  let date_start = $.urlParam( 'date_start' )
  let step_param = $.urlParam( 'step' )
  let date_end = null;
  if ( date_start && step_param ){
    step = step_param
    date_end = moment(date_start).add( 1, step ).format("Y-MM-DD")
  }

  chartDiv.empty().html(`
    <div class="section-header">${ _.escape(window.wp_js_object.translations.title) }</div>
    <div style="display: inline-block" class="loading-spinner active"></div>
    <p>This page shows activity and actions that happened by year, month, week or day</p>
    <p>For example: how many contacts were created each year? </p>
    <p>Or: How many contacts have we assigned this week?</p>
    <hr>

    <button class="button group-by ${step==='all' ? '': 'hollow'}" data-value="all">All</button>
    <button class="button group-by ${step==='year' ? '': 'hollow'}" data-value="year">Year</button>
    <button class="button group-by ${step==='month' ? '': 'hollow'}" data-value="month">Month</button>
    <button class="button group-by ${step==='week' ? '': 'hollow'}" data-value="week">Week</button>
    <button class="button group-by ${step==='day' ? '': 'hollow'}" data-value="day">Day</button>
    <p id="date-range"></p>
    <div id="chartdiv" style="overflow-x:scroll;">
      <table style="width:auto">
        <thead>
          <tr id="table-header-row">

          </tr>
        </thead>
        <tbody id="table-body">
        </tbody>
      </table>
    </div>
  `)

  let display_data = (data)=>{
    let days = [];
    _.forOwn(data, (field_value, field_key)=>{
      days = _.union(days, field_value.counts.map(a=>a.day))
    })
    days.sort((a,b)=>{
      return moment(a).isBefore(moment(b)) ? 1 : -1;
    })
    let header = `<th style="min-width: 200px"></th>`
    let data_by_date = {};
    days.forEach(d=>{
      header += `<th>${_.escape(d)}</th>`
      data_by_date[d] = []
    })

    $('#table-header-row').html(header)
    let html = ``
    _.forOwn(data, (field_value, field_key)=>{
      html += `<tr><td>${_.escape(field_value.label)}</td>`
      days.forEach(d=>{
        let has = false
        field_value.counts.forEach(c=> {
          if (c.day===d) {
            has = true
            html += `<td>${_.escape(c.count)}</td>`
          }
        })
        if ( !has ){
          html += `<td></td>`
        }
      })
      html += `</tr>`

    })
    $('#table-body').html(html)

    if ( date_end && date_start ){
      $('#date-range').html(`
        Only showing Activity for contacts created between <strong>${_.escape(date_start)}</strong> and <strong>${_.escape(date_end)}</strong>
        <button class="button small hollow" id="clear-date-range">reset</button>
      `)
    }

  }



  $(document).on("click", '#clear-date-range', function () {
    window.location = `${window.wpApiShare.site_url}/metrics/advanced-metrics/activity`
  })



  let get_data = ( ) =>{
     $(".loading-spinner").addClass("active")
    jQuery.ajax({
        type: "GET",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: `${wp_js_object.rest_endpoints_base}get-data?step=${step}${date_start ? `&date_start=${date_start}` : ''}${date_end ? `&date_end=${date_end}` : ''}`,
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', wpApiShare.nonce);
        },
    })
    .done(function (data) {
      $(".loading-spinner").removeClass("active")
      display_data(data)
    })

  }

  get_data()

  $('.group-by').on("click", function () {
    step = $(this).data('value')
    date_start = null
    date_end = null
    get_data()
    $('.group-by').addClass('hollow')
    $(this).removeClass('hollow')
  })

})
