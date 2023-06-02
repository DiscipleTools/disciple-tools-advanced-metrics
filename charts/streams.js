jQuery(document).ready(function() {
  jQuery('#metrics-sidemenu').foundation('down', jQuery(`#${window.wp_js_object.base_slug}-menu`));

  let chartDiv = jQuery('#chart')
  let sourceData = wp_js_object.data

  chartDiv.empty().html(`
    <div class="section-header">${ _.escape(window.wp_js_object.translations.title) }</div>
    <div style="display: inline-block" class="loading-spinner active"></div>
    <p>This page shows activity and actions based on when contacts were created in a year, month, week or day</p>
    <p>For example: Of contacts created in may, how many are now active? </p>
    <p>Note: this does not show how many 1st meetings happened in May. It shows how many 1st meeting happened for contacts that were created in May</p>
    <hr>

    <button class="button group-by hollow" data-value="all">All</button>
    <button class="button group-by hollow" data-value="year">Year</button>
    <button class="button group-by" data-value="month">Month</button>
    <button class="button group-by hollow" data-value="week">Week</button>
    <button class="button group-by hollow" data-value="day">Day</button>
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
    console.log( data );
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
      header += `<th><a href="${window.wpApiShare.site_url}/metrics/advanced-metrics/activity?date_start=${_.escape(moment(d).format('Y-MM-DD'))}&step=${step}" target="_blank">${_.escape(d)}</a></th>`
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
            html += `<td><a href="#">${_.escape(c.count)}</a></td>`
          }
        })
        if ( !has ){
          html += `<td></td>`
        }
      })
      html += `</tr>`

    })
    $('#table-body').html(html)

  }



  let step = 'month'
  let get_data = ( ) =>{
     $(".loading-spinner").addClass("active")
    jQuery.ajax({
        type: "GET",
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: `${wp_js_object.rest_endpoints_base}get-data?step=${step}`,
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
    let val = $(this).data('value')
    step = val
    get_data( )
    $('.group-by').addClass('hollow')
    $(this).removeClass('hollow')
  })

})
