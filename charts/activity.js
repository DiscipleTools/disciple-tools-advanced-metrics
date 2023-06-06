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

  let build_modal_html = () => {
    return `
        <div class="reveal medium" id="advanced_metrics_modal" data-reveal data-reset-on-close>
            <h3 id="advanced_metrics_modal_title">${ _.escape(window.wp_js_object.translations.modal.title) }</h3>

            <br>
            <div style="overflow: auto; max-height: 400px;">
              <table>
                  <thead>
                      <th>${_.escape(window.wp_js_object.translations.modal.table_head_title)}</th>
                  </thead>
                  <tbody id="advanced_metrics_modal_table_body"></tbody>
              </table>
            </div>

            <br>
            <button class="button loader" data-close aria-label="Close reveal" type="button">
                ${ _.escape(window.wp_js_object.translations.modal.cancel_button) }
            </button>

            <button class="close-button" data-close aria-label="Close" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
  };

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
    ${ build_modal_html() }
  `)

  // Instantiate main advanced metrics modal.
  new Foundation.Reveal($('#advanced_metrics_modal'));

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
            html += `<td><a href="#" class="metric-count-button" data-step="${step}" data-metric_day="${_.escape(c.day_raw)}" data-metric_key="${_.escape(field_key)}" data-metric_label="${_.escape(field_value.label)}">${_.escape(c.count)}</a></td>`
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

  $(document).on("click", '.metric-count-button', function () {
    let modal = $('#advanced_metrics_modal');
    $(modal).find('#advanced_metrics_modal_title').html($(this).data('metric_label'));
    $(modal).find('#advanced_metrics_modal_table_body').empty();
    $(modal).data('step', $(this).data('step'));
    $(modal).data('metric_day', $(this).data('metric_day'));
    $(modal).data('metric_key', $(this).data('metric_key'));
    modal.foundation('open');
  });

  $(document).on('open.zf.reveal', '[data-reveal]', function (evt) {
    let modal = $(evt.currentTarget);
    let params = {
      'step': $(modal).data('step'),
      'metric_day': $(modal).data('metric_day'),
      'metric_key': $(modal).data('metric_key'),
      'date_start': date_start,
      'date_end': date_end
    };

    jQuery.ajax({
      type: "POST",
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: JSON.stringify(params),
      url: `${wp_js_object.rest_endpoints_base}get-count-data`,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', wpApiShare.nonce);
      },
    })
    .done(function (data) {
      let modal = $('#advanced_metrics_modal');
      let modal_tbody = $(modal).find('#advanced_metrics_modal_table_body');

      // Display count contact details.
      modal_tbody.fadeOut('fast', function () {
        modal_tbody.empty();
        data.forEach(function (post) {
          if (post['id'] && post['title']) {
            let post_url = wpApiShare.site_url + '/contacts/' + post['id'];
            modal_tbody.append(`
                <tr>
                    <td><a href="${post_url}" target="_blank">${window.lodash.escape(post['title'])}</a></td>
                </tr>
              `);
          }
        });

        modal_tbody.fadeIn('fast');
      });
    });
  });

  $(document).on('closed.zf.reveal', '[data-reveal]', function (evt) {
  });

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
