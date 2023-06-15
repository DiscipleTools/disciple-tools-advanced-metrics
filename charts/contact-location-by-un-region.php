<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


class DT_Advanced_Metrics_Chart_Contact_Location_By_Country extends DT_Metrics_Chart_Base {
    public $base_slug = 'disciple-tools-advanced-metrics'; // lowercase
    public $base_title = 'Advanced Metrics';
    public $namespace = 'dt/v1/advanced-metrics/';

    public $title = 'Contact Locations By UN Region';
    public $slug = 'contact-location-by-un-region'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = 'contact-location-by-un-region.js'; // should be full file name plus extension
    public $deep_link_hash = '#contact-location-by-un-region'; // should be the full hash name. #example_of_hash
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
        wp_register_script( 'amcharts-un-regions-low', 'https://cdn.amcharts.com/lib/5/geodata/unRegionsLow.js', false, '5' );
        wp_register_script( 'amcharts-animated', 'https://cdn.amcharts.com/lib/5/themes/Animated.js', false, '5' );

        wp_enqueue_script( 'dt_'.$this->slug.'_script', trailingslashit( plugin_dir_url( __FILE__ ) ) . $this->js_file_name, [
            'jquery',
            'jquery-ui-core',
            'amcharts-index',
            'amcharts-map',
            'amcharts-un-regions-low',
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
                    'title' => $this->title,
                    'regions' => [
                        'northAmerica' => esc_attr( __( 'North America', 'disciple_tools' ) ),
                        'centralAmerica' => esc_attr( __( 'Central America', 'disciple_tools' ) ),
                        'southAmerica' => esc_attr( __( 'South America', 'disciple_tools' ) ),
                        'polynesia' => esc_attr( __( 'Polynesia', 'disciple_tools' ) ),
                        'caribbean' => esc_attr( __( 'Caribbean', 'disciple_tools' ) ),
                        'northAfrica' => esc_attr( __( 'Northern Africa', 'disciple_tools' ) ),
                        'westAfrica' => esc_attr( __( 'Western Africa', 'disciple_tools' ) ),
                        'eastAfrica' => esc_attr( __( 'Eastern Africa', 'disciple_tools' ) ),
                        'middleAfrica' => esc_attr( __( 'Middle Africa', 'disciple_tools' ) ),
                        'southAfrica' => esc_attr( __( 'Southern Africa', 'disciple_tools' ) ),
                        'northEurope' => esc_attr( __( 'Northern Europe', 'disciple_tools' ) ),
                        'westEurope' => esc_attr( __( 'Western Europe', 'disciple_tools' ) ),
                        'eastEurope' => esc_attr( __( 'Eastern Europe', 'disciple_tools' ) ),
                        'southEurope' => esc_attr( __( 'Southern Europe', 'disciple_tools' ) ),
                        'westAsia' => esc_attr( __( 'Western Asia', 'disciple_tools' ) ),
                        'centralAsia' => esc_attr( __( 'Central Asia', 'disciple_tools' ) ),
                        'southAsia' => esc_attr( __( 'Southern Asia', 'disciple_tools' ) ),
                        'eastAsia' => esc_attr( __( 'Eastern Asia', 'disciple_tools' ) ),
                        'southeastAsia' => esc_attr( __( 'Southeastern Asia', 'disciple_tools' ) ),
                        'melanesia' => esc_attr( __( 'Melanesia', 'disciple_tools' ) ),
                        'australiaNZ' => esc_attr( __( 'Australia and New Zealand', 'disciple_tools' ) )
                    ]
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
            'stats' => [
                'countries' => $this->generate_contact_locations_by_country( $posts ),
                'regions' => $this->generate_contact_locations_by_un_regions( $posts )
            ]
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

        // Sort stats by count in descending order.
        usort( $stats, function ( $a, $b ){
            return $a['count'] < $b['count'];
        } );

        return $stats;
    }

    private function generate_contact_locations_by_un_regions( $posts ){
        $stats = [];
        foreach ( $posts as $post ){
            $already_assigned_region = [];
            foreach ( $post['locations'] as $location ){
                $region_name = !empty( $location['iso']['intermediate_region'] ) ? $location['iso']['intermediate_region'] : $location['iso']['sub_region'];
                $region = $this->determine_un_region_id( $region_name );
                if ( isset( $location['iso'] ) && !empty( $region ) && !in_array( $region, $already_assigned_region ) ){

                    // Keep a record, to avoid double counting for the same post on the same region!
                    $already_assigned_region[] = $region;

                    // Increment stat count accordingly.
                    if ( !isset( $stats[$region] ) ){
                        $stats[$region] = [
                            'region' => $region,
                            'name' => $region_name,
                            'count' => 0
                        ];
                    }
                    $stats[$region]['count']++;
                }
            }
        }

        // Sort stats by count in descending order.
        usort( $stats, function ( $a, $b ){
            return $a['count'] < $b['count'];
        } );

        return $stats;
    }

    private function determine_un_region_id( $region ){
        switch ( strtolower( trim( $region ) ) ){
            case 'northern america':
                return 'northAmerica';
            case 'central america':
                return 'centralAmerica';
            case 'south america':
                return 'southAmerica';
            case 'polynesia':
                return 'polynesia';
            case 'caribbean':
                return 'caribbean';
            case 'northern africa':
                return 'northAfrica';
            case 'western africa':
                return 'westAfrica';
            case 'eastern africa':
                return 'eastAfrica';
            case 'middle africa':
                return 'middleAfrica';
            case 'southern africa':
                return 'southAfrica';
            case 'northern europe':
                return 'northEurope';
            case 'western europe':
                return 'westEurope';
            case 'eastern europe':
                return 'eastEurope';
            case 'southern europe':
                return 'southEurope';
            case 'western asia':
                return 'westAsia';
            case 'central asia':
                return 'centralAsia';
            case 'southern asia':
                return 'southAsia';
            case 'eastern asia':
                return 'eastAsia';
            case 'south-eastern asia':
                return 'southeastAsia';
            case 'melanesia':
                return 'melanesia';
            case 'australia and new zealand':
                return 'australiaNZ';
            default:
                return '';
        }
    }

}
