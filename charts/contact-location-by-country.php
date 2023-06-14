<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


class DT_Advanced_Metrics_Chart_Contact_Location_By_Country extends DT_Metrics_Chart_Base {
    public $base_slug = 'disciple-tools-advanced-metrics'; // lowercase
    public $base_title = 'Advanced Metrics';
    public $namespace = 'dt/v1/advanced-metrics/';

    public $title = 'Contact Locations By Country';
    public $slug = 'contact-location-by-country'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'contact-location-by-country.js'; // should be full file name plus extension
    public $deep_link_hash = '#contact-location-by-country'; // should be the full hash name. #example_of_hash
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
        wp_register_script( 'amcharts-index', 'https://cdn.amcharts.com/lib/5/index.js', false, '5' );
        wp_register_script( 'amcharts-map', 'https://cdn.amcharts.com/lib/5/map.js', false, '5' );
        wp_register_script( 'amcharts-world-low', 'https://cdn.amcharts.com/lib/5/geodata/worldLow.js', false, '5' );
        wp_register_script( 'amcharts-animated', 'https://cdn.amcharts.com/lib/5/themes/Animated.js', false, '5' );

        wp_enqueue_script( 'dt_'.$this->slug.'_script', trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
            'jquery-ui-core',
            'amcharts-index',
            'amcharts-map',
            'amcharts-world-low',
            'amcharts-animated'
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
                    'title' => $this->title
                ]
            ]
        );
    }

    public function add_api_routes() {
        register_rest_route(
            $this->namespace . $this->slug, '/get-data', [
                'methods'  => 'POST',
                'callback' => [ $this, 'get_data' ],
                'permission_callback' => [ $this, 'has_permission' ],
            ]
        );
    }

    public function get_data( WP_REST_Request $request ){
        $params = $request->get_params();

        // List all contacts with current locations.
        $posts_list = DT_Posts::list_posts( 'contacts', [
            'limit' => 1000,
            'fields' => [
                [
                    'assigned_to' => [ 'me' ]
                ]
            ],
            'fields_to_return' => [ 'location_grid' ]
        ], false );

        // Load iso country codes.
        $country_codes_cache = [];
        $country_codes = $this->load_csv( '/csv/iso-3166-countries.csv' );

        // Extract valid locations.
        $posts = [];
        $geocoder = new Location_Grid_Geocoder();
        foreach ( $posts_list['posts'] ?? [] as $post ){

            $locations = [];

            foreach ( $post['location_grid'] ?? [] as $location ){
                if ( !empty( $location['id'] ) ){

                    // Decode and merge location ids with iso country codes.
                    $grid = $geocoder->query_by_grid_id( $location['id'] );
                    if ( isset( $grid, $grid['country_code'] ) ){
                        $code = $grid['country_code'];
                        if ( empty( $country_codes_cache[$code] ) ){
                            $iso_country_code = $this->extract_iso_country_code( $country_codes, $code );
                            if ( !empty( $iso_country_code ) ){
                                $country_codes_cache[$code] = $iso_country_code;
                            }
                        }

                        // Package location findings accordingly.
                        if ( !empty( $country_codes_cache[$code] ) ){
                            $iso_country_code = $country_codes_cache[$code];

                            $locations[] = [
                                'grid' => [
                                    'id' => $grid['grid_id'],
                                    'name' => $grid['name'],
                                    'longitude' => $grid['longitude'],
                                    'latitude' => $grid['latitude']
                                ],
                                'iso' => [
                                    'name' => $iso_country_code[0],
                                    'alpha_2' => $iso_country_code[1],
                                    'alpha_3' => $iso_country_code[2],
                                    'country_code' => $iso_country_code[3],
                                    'iso_3166_2' => $iso_country_code[4],
                                    'region' => $iso_country_code[5],
                                    'sub_region' => $iso_country_code[6],
                                    'intermediate_region' => $iso_country_code[7],
                                    'region_code' => $iso_country_code[8],
                                    'sub_region_code' => $iso_country_code[9],
                                    'intermediate_region_code' => $iso_country_code[10]
                                ]
                            ];
                        }
                    }
                }
            }

            $posts[] = [
                'id' => $post['ID'],
                'title' => $post['post_title'],
                'locations' => $locations
            ];
        }

        return [
            'posts' => $posts,
            'stats' => $this->generate_contact_locations_by_country( $posts )
        ];
    }

    private function load_csv( $csv_file ){
        $csv = [];
        $handle = fopen( __DIR__ . $csv_file, 'r' );
        if ( $handle !== false ){
            while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ){
                $csv[] = $data;
            }
            fclose( $handle );
        }

        return $csv;
    }

    private function extract_iso_country_code( $country_codes, $code ){
        foreach ( $country_codes ?? [] as $country_code ){

            /**
             * [0] => name
             * [1] => alpha-2
             * [2] => alpha-3
             * [3] => country-code
             * [4] => iso_3166-2
             * [5] => region
             * [6] => sub-region
             * [7] => intermediate-region
             * [8] => region-code
             * [9] => sub-region-code
             * [10] => intermediate-region-code
             */

            if ( $country_code[1] === $code ){
                return $country_code;
            }
        }

        return [];
    }

    private function generate_contact_locations_by_country( $posts ){
        $stats = [];
        foreach ( $posts as $post ){
            $already_assigned_country = [];
            foreach ( $post['locations'] as $location ){
                $code = $location['iso']['alpha_2'];
                if ( isset( $location['iso'] ) && !empty( $code ) && !in_array( $code, $already_assigned_country ) ){

                    // Keep a record, to avoid double counting for the same post on the same country!
                    $already_assigned_country[] = $code;

                    // Increment stat count accordingly.
                    if ( !isset( $stats[$code] ) ){
                        $stats[$code] = [
                            'code' => $code,
                            'name' => $location['iso']['name'] ?? '',
                            'count' => 0
                        ];
                    }
                    $stats[$code]['count']++;
                }
            }
        }

        return $stats;
    }

}
