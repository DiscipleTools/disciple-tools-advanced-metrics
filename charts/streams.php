<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


class DT_Advanced_Metrics_Chart_Streams extends DT_Metrics_Chart_Base {

    public $base_slug = 'disciple-tools-advanced-metrics'; // lowercase
    public $base_title = "Advanced Metrics";
    public $namespace = 'dt/v1/advanced-metrics/';

    public $title = 'Streams';
    public $slug = 'streams'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'streams.js'; // should be full file name plus extension
    public $deep_link_hash = '#streams'; // should be the full hash name. #example_of_hash
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
                "counts" => $this->format_date( $step, $this->new_contacts( $option["format"], $option["start"] ) ),
            ],
            "activity" => [
                "label" => "Contacts with User Activity",
                "counts" => $this->format_date( $step, $this->activity( $option["format"], $option["start"] ) ),
            ],
            "contacts_with_user_comments" => [
                "label" => "Contacts with User Comments",
                "counts" => $this->format_date( $step, $this->contacts_with_user_comments( $option["format"], $option["start"] ) ),
            ],
            "assignments" => [
                "label" => "Contacts with User Assignment Change",
                "counts" => $this->format_date( $step, $this->user_assignment_change( $option["format"], $option["start"] ) ),
            ],
            "active" => [
                "label" => "Contacts with Status as Active",
                "counts" => $this->format_date( $step, $this->became_active( $option["format"], $option["start"] ) ),
            ],
            "assigned_dispatch" => [
                "label" => "Contacts Assigned for Dispatch",
                "counts" => $this->format_date( $step, $this->assigned_for_dispatch( $option["format"], $option["start"] ) ),
            ],
            "assigned_follow_up" => [
                "label" => "Contacts Assigned for Follow-up",
                "counts" => $this->format_date( $step, $this->assigned_for_follow_up( $option["format"], $option["start"] ) ),
            ],
            "contact_attempted" => [
                "label" => "Contacts with Contact Attempted",
                "counts" => $this->format_date( $step, $this->contact_attempted( $option["format"], $option["start"] ) ),
            ],
            "first_meeting" => [
                "label" => "Contacts with 1st Meeting Complete",
                "counts" => $this->format_date( $step, $this->first_meeting( $option["format"], $option["start"] ) ),
            ],
        ];

        $fields = DT_Posts::get_post_settings( 'contacts' )['fields'];

        foreach ( $fields as $field_key => $value ){
            if ( strpos( $field_key, "quick_button" ) !== false ) {
                $advanced_metrics_count[$field_key] = [
                    "label" => "Quick Action - " . $value['name'],
                    "counts" => $this->format_date( $step, $this->quick_action_count( $field_key, $option["format"], $option["start"] ) ),
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

    private function new_contacts( $format, $start ){
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
            GROUP by day
        ", $format, $start ), ARRAY_A );

        return $r;
    }

    private function contacts_with_user_comments( $format, $start ){
        global $wpdb;
        $r = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) day,
            COUNT(DISTINCT comment_post_id) count
            FROM $wpdb->comments
            INNER JOIN $wpdb->posts as p ON ( p.ID = comment_post_ID AND post_date > FROM_UNIXTIME(%s) )
            WHERE p.post_type = 'contacts'
            AND comment_type = 'comment'
            AND user_id != 0
            GROUP by day
        ", $format, $start ), ARRAY_A );

        return $r;
    }

    private function activity( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE log.user_id != 0
            AND ( object_type = 'contacts' OR object_type = 'Comments' )
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function user_assignment_change( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE log.user_id != 0
            AND object_type = 'contacts'
            AND meta_key = 'assigned_to'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function became_active( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'overall_status'
            AND meta_value = 'active'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function assigned_for_follow_up( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'reason_assigned_to'
            AND meta_value = 'follow-up'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function assigned_for_dispatch( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'reason_assigned_to'
            AND meta_value = 'dispatch'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function first_meeting( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'seeker_path'
            AND meta_value = 'met'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function contact_attempted( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(distinct(object_id)) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'seeker_path'
            AND meta_value = 'attempted'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }


    private function meeting_quick_action( $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(meta_key) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = 'quick_button_meeting_complete'
            group by day
            ORDER BY day ASC", $format, $start ), ARRAY_A );

        return $days_active_results;
    }

    private function quick_action_count( $quick_action_label, $format, $start ){
        global $wpdb;
        $days_active_results = $wpdb->get_results( $wpdb->prepare( "
            SELECT DATE_FORMAT(p.post_date, %s) as day,
            count(meta_key) as count
            FROM $wpdb->dt_activity_log as log
            INNER JOIN $wpdb->posts as p ON ( p.ID = object_id AND post_date > FROM_UNIXTIME(%s) )
            WHERE object_type = 'contacts'
            AND meta_key = %s
            group by day
            ORDER BY day ASC", $format, $start, $quick_action_label ), ARRAY_A );

        return $days_active_results;
    }


}
