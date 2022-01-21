<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


class DT_Advanced_Metrics_Chart_Activity extends DT_Metrics_Chart_Base {
    public $base_slug = 'disciple-tools-advanced-metrics'; // lowercase
    public $base_title = "Advanced Metrics";
    public $namespace = 'dt/v1/advanced-metrics/';

    public $title = 'Activity';
    public $slug = 'activity'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'activity.js'; // should be full file name plus extension
    public $deep_link_hash = '#activity'; // should be the full hash name. #example_of_hash
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];


    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $url_path = dt_get_url_path();
        // only load scripts if exact url
        if ( strpos( $url_path, 'metrics/' . $this->base_slug . '/'. $this->slug ) === 0 ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        }
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }


    /**
     * Load scripts for the plugin
     */
    public function scripts() {
        wp_register_script( 'amcharts-core', 'https://www.amcharts.com/lib/4/core.js', false, '4' );
        wp_register_script( 'amcharts-charts', 'https://www.amcharts.com/lib/4/charts.js', false, '4' );
        wp_enqueue_script( 'dt_'.$this->slug.'_script', trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
            'jquery-ui-core',
            'amcharts-core',
            'amcharts-charts',
        ], filemtime( plugin_dir_path( __FILE__ ) .$this->js_file_name ), true );

        // Localize script with array data
        wp_localize_script(
            'dt_'.$this->slug.'_script', $this->js_object_name, [
                'base_slug' => $this->base_slug,
                'slug' => $this->slug,
                'name_key' => $this->slug,
                'plugin_uri' => plugin_dir_url( __DIR__ ),
                'stats' => [],
                'rest_endpoints_base' => esc_url_raw( rest_url() ) . $this->namespace . $this->slug . '/',
                'translations' => [
                    "title" => $this->title,
                    'filter_contacts_to_date_range' => __( "Filter contacts to date range:", 'disciple_tools' ),
                    'all_time' => __( "All time", 'disciple_tools' ),
                    'filter_to_date_range' => __( "Filter to date range", 'disciple_tools' ),
                ]
            ]
        );
    }

    public function add_api_routes() {
        register_rest_route(
            $this->namespace . $this->slug, '/get-data', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_data' ],
                'permission_callback' => [ $this, "has_permission" ],
            ]
        );
    }

    public function get_data( WP_REST_Request $request ){
        $params = $request->get_params();

        $step = isset( $params["step"] ) ? $params["step"] : "year";
        $date_start = isset( $params["date_start"] ) ? $params["date_start"] : "1970-01-01";
        $date_end = isset( $params["date_end"] ) ? $params["date_end"] : "2100-01-01";

        $options = [
            "all" => [
                "format" =>'',
                "start" => time() - 3600 * 24 * 365 * 40 // 40 years
            ],
            "year" => [
                "format" =>'%Y',
                "start" => time() - 3600 * 24 * 365 * 20 // 20 years
            ],
            "month" => [
                "format" =>'%Y-%m',
                "start" => time() - 3600 * 24 * 365 * 3 // 3 year
            ],
            "week" => [
                "format" =>'%YW%u',
                "start" => time() - 3600 * 24 * 7 * 30 // 30 weeks
            ],
            "day" => [
                "format" =>'%Y-%m-%d',
                "start" => time() - 3600 * 24 * 30 // 30 days
            ],
        ];
        $option = isset( $step, $options[$params["step"]] ) ? $options[$params["step"]] : $options["year"];

        $advanced_metrics_count = [
            "new_contacts" => [
                "label" => "New Contacts",
                "counts" => $this->format_date( $step, $this->new_contacts( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "activity" => [
                "label" => "Contacts with User Activity",
                "counts" => $this->format_date( $step, $this->activity( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "contacts_with_user_comments" => [
                "label" => "Contacts with User Comments",
                "counts" => $this->format_date( $step, $this->contacts_with_user_comments( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "assignments" => [
                "label" => "Contacts with User Assignment Change",
                "counts" => $this->format_date( $step, $this->user_assignment_change( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "active" => [
                "label" => "Contacts with Status as Active",
                "counts" => $this->format_date( $step, $this->became_active( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "assigned_dispatch" => [
                "label" => "Contacts Assigned for Dispatch",
                "counts" => $this->format_date( $step, $this->assigned_for_dispatch( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "assigned_follow_up" => [
                "label" => "Contacts Assigned for Follow-up",
                "counts" => $this->format_date( $step, $this->assigned_for_follow_up( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "contact_attempted" => [
                "label" => "Contacts with Contact Attempted",
                "counts" => $this->format_date( $step, $this->contact_attempted( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
            "first_meeting" => [
                "label" => "Contacts with 1st Meeting Complete",
                "counts" => $this->format_date( $step, $this->first_meeting( $option["format"], $option["start"], $date_start, $date_end ) ),
            ],
        ];

        $fields = DT_Posts::get_post_settings( 'contacts' )['fields'];

        foreach ( $fields as $field_key => $value ){
            if ( strpos( $field_key, "quick_button" ) !== false ) {
                $advanced_metrics_count[$field_key] = [
                    "label" => "Quick Action - " . $value['name'],
                    "counts" => $this->format_date( $step, $this->quick_action_count( $field_key, $option["format"], $option["start"], $date_start, $date_end ) ),
                ];
            }
        }
        return $advanced_metrics_count;
    }

    private function format_date( $step = 'year', $counts = [] ){
        if ( $step === 'week' ){
            foreach ( $counts as &$count ) {
                $count['day'] = dt_format_date( $count['day'] );
            }
        } elseif ( $step === 'month' ){
            foreach ( $counts as &$count ) {
                $count['day'] = dt_format_date( $count['day'], 'M Y' );
            }
        } elseif ( $step === 'day' ){
            foreach ( $counts as &$count ) {
                $count['day'] = dt_format_date( $count['day'] );
            }
        } elseif ( $step === 'all' ){
            foreach ( $counts as &$count ) {
                $count['day'] = "All";
            }
        }
        return $counts;
    }

    private function new_contacts( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $r = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(post_date, %s) day,
            COUNT(DISTINCT p.ID) count
            FROM $wpdb->posts as p
            WHERE post_type = 'contacts'
            AND post_date > FROM_UNIXTIME(%s)
            AND p.ID NOT IN (
                SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'type' AND meta_value = 'user' GROUP BY post_id
            )
            AND post_date >= %s
            AND post_date < %s
            GROUP by day
        ", $format, $activity_start, $date_start, $date_end ), ARRAY_A );

        return $r;
    }

    private function contacts_with_user_comments( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $r = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(comment_date, %s) day,
            COUNT(DISTINCT comment_post_id) count
            FROM $wpdb->comments
            INNER JOIN $wpdb->posts as p ON ( p.ID = comment_post_ID AND post_date >= %s AND post_date < %s )
            WHERE p.post_type = 'contacts'
            AND comment_type = 'comment'
            AND comment_date > FROM_UNIXTIME(%s)
            AND user_id != 0
            GROUP by day
        ", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $r;
    }

    private function activity( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE log.user_id != 0
            AND ( object_type = 'contacts' OR object_type = 'Comments' )
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }

    private function user_assignment_change( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE log.user_id != 0
            AND object_type = 'contacts'
            AND meta_key = 'assigned_to'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }

    private function became_active( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = 'overall_status'
            AND meta_value = 'active'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }

    private function assigned_for_follow_up( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = 'reason_assigned_to'
            AND meta_value = 'follow-up'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }
    private function assigned_for_dispatch( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = 'reason_assigned_to'
            AND meta_value = 'dispatch'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }

    private function first_meeting( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = 'seeker_path'
            AND meta_value = 'met'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }
    private function contact_attempted( $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = 'seeker_path'
            AND meta_value = 'attempted'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }


    private function quick_action_count( $quick_action_label, $format, $activity_start, $date_start, $date_end ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT FROM_UNIXTIME(`hist_time`, %s) as day,
            count(meta_key) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date >= %s AND post_date < %s )
            WHERE object_type = 'contacts'
            AND meta_key = '$quick_action_label'
            AND hist_time > %s
            group by day
            ORDER BY day ASC", $format, $date_start, $date_end, $activity_start ), ARRAY_A );

        return $days_active_results;
    }

}
