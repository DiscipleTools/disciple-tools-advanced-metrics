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
        global $wpdb;

        // List all grouped location grids and associated counts.
        // phpcs:disable
        $location_grid_counts = $wpdb->get_results( $wpdb->prepare( "
            SELECT lg.admin0_grid_id location_grid, COUNT(lg.admin0_grid_id) count
              FROM $wpdb->posts as p
              JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'location_grid'
              LEFT JOIN $wpdb->dt_location_grid lg ON pm.meta_value = lg.grid_id
              WHERE p.post_type = 'contacts'
              AND p.post_status = 'publish'
              AND lg.admin0_grid_id IS NOT NULL
              GROUP BY lg.admin0_grid_id
              ORDER BY count DESC
        " ), ARRAY_A );
        // phpcs:enable

        // Fetch iso countries and codes.
        $countries = $this->get_countries();

        // Extract valid locations.
        $stat_regions = [];
        $already_assigned_grid = [];
        foreach ( $location_grid_counts ?? [] as $location_grid ){
            if ( !empty( $location_grid ) ){
                $location_grid_id = $location_grid['location_grid'];
                $location_grid_count = $location_grid['count'];

               // Decode and merge location ids with iso country region codes.
                if ( isset( $countries[$location_grid_id] ) ){
                    $country = $countries[$location_grid_id];
                    $region_name = $country['un_region'];
                    $region = $this->determine_un_region_id( $region_name );
                    if ( !empty( $region ) && !in_array( $location_grid_id, $already_assigned_grid ) ){

                        // Keep a record, to avoid double counting for the same location grid on the same region!
                        $already_assigned_grid[] = $location_grid_id;

                        // Increment stat count accordingly.
                        if ( !isset( $stat_regions[$region] ) ){
                            $stat_regions[$region] = [
                                'region' => $region,
                                'name' => $region_name,
                                'count' => 0
                            ];
                        }
                        $stat_regions[$region]['count'] += $location_grid_count;
                    }
                }
            }
        }

        // Sort stats by count in descending order.
        usort( $stat_regions, function ( $a, $b ){
            return $a['count'] < $b['count'];
        } );

        return [
            'stats' => [
                'regions' => $stat_regions
            ]
        ];
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

    private function get_countries(){
        return [
            '100000001' => [
                'country' => 'Afghanistan',
                'country_code' => 'AF',
                'un_region' => 'Southern Asia'
            ],
            '100001074' => [
                'country' => 'Åland Islands',
                'country_code' => 'AX',
                'un_region' => 'Northern Europe'
            ],
            '100001091' => [
                'country' => 'Albania',
                'country_code' => 'AL',
                'un_region' => 'Southern Europe'
            ],
            '100385185' => [
                'country' => 'Algeria',
                'country_code' => 'DZ',
                'un_region' => 'Northern Africa'
            ],
            '100002800' => [
                'country' => 'American Samoa',
                'country_code' => 'AS',
                'un_region' => 'Polynesia'
            ],
            '100001519' => [
                'country' => 'Andorra',
                'country_code' => 'AD',
                'un_region' => 'Southern Europe'
            ],
            '100000364' => [
                'country' => 'Angola',
                'country_code' => 'AO',
                'un_region' => 'Middle Africa'
            ],
            '100001073' => [
                'country' => 'Anguilla',
                'country_code' => 'AI',
                'un_region' => 'Caribbean'
            ],
            '100002895' => [
                'country' => 'Antarctica',
                'country_code' => 'AQ',
                'un_region' => ''
            ],
            '100002901' => [
                'country' => 'Antigua and Barbuda',
                'country_code' => 'AG',
                'un_region' => 'Caribbean'
            ],
            '100002260' => [
                'country' => 'Argentina',
                'country_code' => 'AR',
                'un_region' => 'South America'
            ],
            '100002788' => [
                'country' => 'Armenia',
                'country_code' => 'AM',
                'un_region' => 'Western Asia'
            ],
            '100000000' => [
                'country' => 'Aruba',
                'country_code' => 'AW',
                'un_region' => 'Caribbean'
            ],
            '100002910' => [
                'country' => 'Australia',
                'country_code' => 'AU',
                'un_region' => 'Australia and New Zealand'
            ],
            '100003491' => [
                'country' => 'Austria',
                'country_code' => 'AT',
                'un_region' => 'Western Europe'
            ],
            '100005723' => [
                'country' => 'Azerbaijan',
                'country_code' => 'AZ',
                'un_region' => 'Western Asia'
            ],
            '100024587' => [
                'country' => 'Bahamas',
                'country_code' => 'BS',
                'un_region' => 'Caribbean'
            ],
            '100024581' => [
                'country' => 'Bahrain',
                'country_code' => 'BH',
                'un_region' => 'Western Asia'
            ],
            '100018514' => [
                'country' => 'Bangladesh',
                'country_code' => 'BD',
                'un_region' => 'Southern Asia'
            ],
            '100041079' => [
                'country' => 'Barbados',
                'country_code' => 'BB',
                'un_region' => 'Caribbean'
            ],
            '100024784' => [
                'country' => 'Belarus',
                'country_code' => 'BY',
                'un_region' => 'Eastern Europe'
            ],
            '100017364' => [
                'country' => 'Belgium',
                'country_code' => 'BE',
                'un_region' => 'Western Europe'
            ],
            '100024909' => [
                'country' => 'Belize',
                'country_code' => 'BZ',
                'un_region' => 'Central America'
            ],
            '100018011' => [
                'country' => 'Benin',
                'country_code' => 'BJ',
                'un_region' => 'Western Africa'
            ],
            '100024916' => [
                'country' => 'Bermuda',
                'country_code' => 'BM',
                'un_region' => 'Northern America'
            ],
            '100041128' => [
                'country' => 'Bhutan',
                'country_code' => 'BT',
                'un_region' => 'Southern Asia'
            ],
            '100024928' => [
                'country' => 'Bolivia [Plurinational State of)',
                'country_code' => 'BO',
                'un_region' => 'South America'
            ],
            '100018100' => [
                'country' => 'Bonaire, Sint Eustatius and Saba',
                'country_code' => 'BQ',
                'un_region' => 'Caribbean'
            ],
            '100024620' => [
                'country' => 'Bosnia and Herzegovina',
                'country_code' => 'BA',
                'un_region' => 'Southern Europe'
            ],
            '100041355' => [
                'country' => 'Botswana',
                'country_code' => 'BW',
                'un_region' => 'Southern Africa'
            ],
            '100041354' => [
                'country' => 'Bouvet Island',
                'country_code' => 'BV',
                'un_region' => 'South America'
            ],
            '100025352' => [
                'country' => 'Brazil',
                'country_code' => 'BR',
                'un_region' => 'South America'
            ],
            '100222390' => [
                'country' => 'British Indian Ocean Territory',
                'country_code' => 'IO',
                'un_region' => 'Eastern Africa'
            ],
            '100041091' => [
                'country' => 'Brunei Darussalam',
                'country_code' => 'BN',
                'sub_region' => 'South-eastern Asia'
            ],
            '100024289' => [
                'country' => 'Bulgaria',
                'country_code' => 'BG',
                'un_region' => 'Eastern Europe'
            ],
            '100018104' => [
                'country' => 'Burkina Faso',
                'country_code' => 'BF',
                'un_region' => 'Western Africa'
            ],
            '100005813' => [
                'country' => 'Burundi',
                'country_code' => 'BI',
                'un_region' => 'Eastern Africa'
            ],
            '100055707' => [
                'country' => 'Cabo Verde',
                'country_code' => 'CV',
                'un_region' => 'Western Africa'
            ],
            '100235196' => [
                'country' => 'Cambodia',
                'country_code' => 'KH',
                'un_region' => 'South-eastern Asia'
            ],
            '100053847' => [
                'country' => 'Cameroon',
                'country_code' => 'CM',
                'un_region' => 'Middle Africa'
            ],
            '100041471' => [
                'country' => 'Canada',
                'country_code' => 'CA',
                'un_region' => 'Northern America'
            ],
            '100056006' => [
                'country' => 'Cayman Islands',
                'country_code' => 'KY',
                'un_region' => 'Caribbean'
            ],
            '100041402' => [
                'country' => 'Central African Republic',
                'country_code' => 'CF',
                'un_region' => 'Middle Africa'
            ],
            '100343145' => [
                'country' => 'Chad',
                'country_code' => 'TD',
                'un_region' => 'Middle Africa'
            ],
            '100050338' => [
                'country' => 'Chile',
                'country_code' => 'CL',
                'un_region' => 'South America'
            ],
            '100050711' => [
                'country' => 'China',
                'country_code' => 'CN',
                'un_region' => 'Eastern Asia'
            ],
            '100056005' => [
                'country' => 'Christmas Island',
                'country_code' => 'CX',
                'un_region' => 'Australia and New Zealand'
            ],
            '100047360' => [
                'country' => 'Cocos (Keeling) Islands',
                'country_code' => 'CC',
                'un_region' => 'Australia and New Zealand'
            ],
            '100054605' => [
                'country' => 'Colombia',
                'country_code' => 'CO',
                'un_region' => 'South America'
            ],
            '100055703' => [
                'country' => 'Comoros',
                'country_code' => 'KM',
                'un_region' => 'Eastern Africa'
            ],
            '100054543' => [
                'country' => 'Congo',
                'country_code' => 'CG',
                'un_region' => 'Middle Africa'
            ],
            '100054276' => [
                'country' => 'Congo, Democratic Republic of the',
                'country_code' => 'CD',
                'un_region' => 'Middle Africa'
            ],
            '100054604' => [
                'country' => 'Cook Islands',
                'country_code' => 'CK',
                'un_region' => 'Polynesia'
            ],
            '100055730' => [
                'country' => 'Costa Rica',
                'country_code' => 'CR',
                'un_region' => 'Central America'
            ],
            '100053495' => [
                'country' => 'Côte d\'Ivoire',
                'country_code' => 'CI',
                'un_region' => 'Western Africa'
            ],
            '100133112' => [
                'country' => 'Croatia',
                'country_code' => 'HR',
                'un_region' => 'Southern Europe'
            ],
            '100055819' => [
                'country' => 'Cuba',
                'country_code' => 'CU',
                'un_region' => 'Caribbean'
            ],
            '100056004' => [
                'country' => 'Curaçao',
                'country_code' => 'CW',
                'un_region' => 'Caribbean'
            ],
            '100056014' => [
                'country' => 'Cyprus',
                'country_code' => 'CY',
                'un_region' => 'Western Asia'
            ],
            '100056020' => [
                'country' => 'Czechia',
                'country_code' => 'CZ',
                'un_region' => 'Eastern Europe'
            ],
            '100072563' => [
                'country' => 'Denmark',
                'country_code' => 'DK',
                'un_region' => 'Northern Europe'
            ],
            '100072535' => [
                'country' => 'Djibouti',
                'country_code' => 'DJ',
                'un_region' => 'Eastern Africa'
            ],
            '100072552' => [
                'country' => 'Dominica',
                'country_code' => 'DM',
                'un_region' => 'Caribbean'
            ],
            '100072668' => [
                'country' => 'Dominican Republic',
                'country_code' => 'DO',
                'un_region' => 'Caribbean'
            ],
            '100072856' => [
                'country' => 'Ecuador',
                'country_code' => 'EC',
                'un_region' => 'South America'
            ],
            '100074143' => [
                'country' => 'Egypt',
                'country_code' => 'EG',
                'un_region' => 'Northern Africa'
            ],
            '100341608' => [
                'country' => 'El Salvador',
                'country_code' => 'SV',
                'un_region' => 'Central America'
            ],
            '100131824' => [
                'country' => 'Equatorial Guinea',
                'country_code' => 'GQ',
                'un_region' => 'Middle Africa'
            ],
            '100074514' => [
                'country' => 'Eritrea',
                'country_code' => 'ER',
                'un_region' => 'Eastern Africa'
            ],
            '100083318' => [
                'country' => 'Estonia',
                'country_code' => 'EE',
                'un_region' => 'Northern Europe'
            ],
            '100342975' => [
                'country' => 'Eswatini (Swaziland)',
                'country_code' => 'SZ',
                'un_region' => 'Southern Africa'
            ],
            '100088242' => [
                'country' => 'Ethiopia',
                'country_code' => 'ET',
                'un_region' => 'Eastern Africa'
            ],
            '100089588' => [
                'country' => 'Falkland Islands (Malvinas)',
                'country_code' => 'FK',
                'un_region' => 'South America'
            ],
            '100130389' => [
                'country' => 'Faroe Islands',
                'country_code' => 'FO',
                'un_region' => 'Northern Europe'
            ],
            '100089567' => [
                'country' => 'Fiji',
                'country_code' => 'FJ',
                'un_region' => 'Melanesia'
            ],
            '100089023' => [
                'country' => 'Finland',
                'country_code' => 'FI',
                'un_region' => 'Northern Europe'
            ],
            '100089589' => [
                'country' => 'France',
                'country_code' => 'FR',
                'un_region' => 'Western Europe'
            ],
            '100132604' => [
                'country' => 'French Guiana',
                'country_code' => 'GF',
                'un_region' => 'South America'
            ],
            '100314694' => [
                'country' => 'French Polynesia',
                'country_code' => 'PF',
                'un_region' => 'Polynesia'
            ],
            '100002896' => [
                'country' => 'French Southern Territories',
                'country_code' => 'TF',
                'un_region' => 'Eastern Africa'
            ],
            '100130431' => [
                'country' => 'Gabon',
                'country_code' => 'GA',
                'un_region' => 'Middle Africa'
            ],
            '100131733' => [
                'country' => 'Gambia',
                'country_code' => 'GM',
                'un_region' => 'Western Africa'
            ],
            '100131072' => [
                'country' => 'Georgia',
                'country_code' => 'GE',
                'un_region' => 'Western Asia'
            ],
            '100056133' => [
                'country' => 'Germany',
                'country_code' => 'DE',
                'un_region' => 'Western Europe'
            ],
            '100131170' => [
                'country' => 'Ghana',
                'country_code' => 'GH',
                'un_region' => 'Western Africa'
            ],
            '100131318' => [
                'country' => 'Gibraltar',
                'country_code' => 'GI',
                'un_region' => 'Southern Europe'
            ],
            '100131864' => [
                'country' => 'Greece',
                'country_code' => 'GR',
                'un_region' => 'Southern Europe'
            ],
            '100132221' => [
                'country' => 'Greenland',
                'country_code' => 'GL',
                'un_region' => 'Northern America'
            ],
            '100132213' => [
                'country' => 'Grenada',
                'country_code' => 'GD',
                'un_region' => 'Caribbean'
            ],
            '100131698' => [
                'country' => 'Guadeloupe',
                'country_code' => 'GP',
                'un_region' => 'Caribbean'
            ],
            '100132628' => [
                'country' => 'Guam',
                'country_code' => 'GU',
                'un_region' => 'Micronesia'
            ],
            '100132227' => [
                'country' => 'Guatemala',
                'country_code' => 'GT',
                'un_region' => 'Central America'
            ],
            '100131154' => [
                'country' => 'Guernsey',
                'country_code' => 'GG',
                'un_region' => 'Northern Europe'
            ],
            '100131319' => [
                'country' => 'Guinea',
                'country_code' => 'GN',
                'un_region' => 'Western Africa'
            ],
            '100131777' => [
                'country' => 'Guinea-Bissau',
                'country_code' => 'GW',
                'un_region' => 'Western Africa'
            ],
            '100132648' => [
                'country' => 'Guyana',
                'country_code' => 'GY',
                'un_region' => 'South America'
            ],
            '100133694' => [
                'country' => 'Haiti',
                'country_code' => 'HT',
                'un_region' => 'Caribbean'
            ],
            '100132794' => [
                'country' => 'Heard Island and McDonald Islands',
                'country_code' => 'HM',
                'un_region' => 'Australia and New Zealand'
            ],
            '100367575' => [
                'country' => 'Vatican City',
                'country_code' => 'VA',
                'un_region' => 'Southern Europe'
            ],
            '100132795' => [
                'country' => 'Honduras',
                'country_code' => 'HN',
                'un_region' => 'Central America'
            ],
            '100132775' => [
                'country' => 'Hong Kong',
                'country_code' => 'HK',
                'un_region' => 'Eastern Asia'
            ],
            '100134422' => [
                'country' => 'Hungary',
                'country_code' => 'HU',
                'un_region' => 'Eastern Europe'
            ],
            '100222839' => [
                'country' => 'Iceland',
                'country_code' => 'IS',
                'un_region' => 'Northern Europe'
            ],
            '100219347' => [
                'country' => 'India',
                'country_code' => 'IN',
                'un_region' => 'Southern Asia'
            ],
            '100385182' => [
                'country' => 'Indonesia',
                'country_code' => 'ID',
                'un_region' => 'South-eastern Asia'
            ],
            '100222418' => [
                'country' => 'Iran (Islamic Republic of)',
                'country_code' => 'IR',
                'un_region' => 'Southern Asia'
            ],
            '100222718' => [
                'country' => 'Iraq',
                'country_code' => 'IQ',
                'un_region' => 'Western Asia'
            ],
            '100222391' => [
                'country' => 'Ireland',
                'country_code' => 'IE',
                'un_region' => 'Northern Europe'
            ],
            '100219316' => [
                'country' => 'Isle of Man',
                'country_code' => 'IM',
                'un_region' => 'Northern Europe'
            ],
            '100222967' => [
                'country' => 'Israel',
                'country_code' => 'IL',
                'un_region' => 'Western Asia'
            ],
            '100222975' => [
                'country' => 'Italy',
                'country_code' => 'IT',
                'un_region' => 'Southern Europe'
            ],
            '100231206' => [
                'country' => 'Jamaica',
                'country_code' => 'JM',
                'un_region' => 'Caribbean'
            ],
            '100231299' => [
                'country' => 'Japan',
                'country_code' => 'JP',
                'un_region' => 'Eastern Asia'
            ],
            '100231221' => [
                'country' => 'Jersey',
                'country_code' => 'JE',
                'un_region' => 'Northern Europe'
            ],
            '100231234' => [
                'country' => 'Jordan',
                'country_code' => 'JO',
                'un_region' => 'Western Asia'
            ],
            '100233158' => [
                'country' => 'Kazakhstan',
                'country_code' => 'KZ',
                'un_region' => 'Central Asia'
            ],
            '100233347' => [
                'country' => 'Kenya',
                'country_code' => 'KE',
                'un_region' => 'Eastern Africa'
            ],
            '100238556' => [
                'country' => 'Kiribati',
                'country_code' => 'KI',
                'un_region' => 'Micronesia'
            ],
            '100238572' => [
                'country' => 'Korea (Democratic People\'s Republic of)',
                'country_code' => 'KP',
                'un_region' => 'Eastern Asia'
            ],
            '100309648' => [
                'country' => 'Korea, Republic of',
                'country_code' => 'KR',
                'un_region' => 'Eastern Asia'
            ],
            '100238819' => [
                'country' => 'Kuwait',
                'country_code' => 'KW',
                'un_region' => 'Western Asia'
            ],
            '100235142' => [
                'country' => 'Kyrgyzstan',
                'country_code' => 'KG',
                'un_region' => 'Central Asia'
            ],
            '100238826' => [
                'country' => 'Lao People\'s Democratic Republic',
                'country_code' => 'LA',
                'un_region' => 'South-eastern Asia'
            ],
            '100241717' => [
                'country' => 'Latvia',
                'country_code' => 'LV',
                'un_region' => 'Northern Europe'
            ],
            '100238987' => [
                'country' => 'Lebanon',
                'country_code' => 'LB',
                'un_region' => 'Western Asia'
            ],
            '100241376' => [
                'country' => 'Lesotho',
                'country_code' => 'LS',
                'un_region' => 'Southern Africa'
            ],
            '100240594' => [
                'country' => 'Liberia',
                'country_code' => 'LR',
                'un_region' => 'Western Africa'
            ],
            '100240981' => [
                'country' => 'Libya',
                'country_code' => 'LY',
                'un_region' => 'Northern Africa'
            ],
            '100241015' => [
                'country' => 'Liechtenstein',
                'country_code' => 'LI',
                'un_region' => 'Western Europe'
            ],
            '100241387' => [
                'country' => 'Lithuania',
                'country_code' => 'LT',
                'un_region' => 'Northern Europe'
            ],
            '100241446' => [
                'country' => 'Luxembourg',
                'country_code' => 'LU',
                'un_region' => 'Western Europe'
            ],
            '100241749' => [
                'country' => 'Macao',
                'country_code' => 'MO',
                'un_region' => 'Eastern Asia'
            ],
            '100243784' => [
                'country' => 'Madagascar',
                'country_code' => 'MG',
                'un_region' => 'Eastern Africa'
            ],
            '100249866' => [
                'country' => 'Malawi',
                'country_code' => 'MW',
                'un_region' => 'Eastern Africa'
            ],
            '100253277' => [
                'country' => 'Malaysia',
                'country_code' => 'MY',
                'un_region' => 'South-eastern Asia'
            ],
            '100245356' => [
                'country' => 'Maldives',
                'country_code' => 'MV',
                'un_region' => 'Southern Asia'
            ],
            '100247331' => [
                'country' => 'Mali',
                'country_code' => 'ML',
                'un_region' => 'Western Africa'
            ],
            '100248384' => [
                'country' => 'Malta',
                'country_code' => 'MT',
                'un_region' => 'Southern Europe'
            ],
            '100247244' => [
                'country' => 'Marshall Islands',
                'country_code' => 'MH',
                'un_region' => 'Micronesia'
            ],
            '100249816' => [
                'country' => 'Martinique',
                'country_code' => 'MQ',
                'un_region' => 'Caribbean'
            ],
            '100249754' => [
                'country' => 'Mauritania',
                'country_code' => 'MR',
                'un_region' => 'Western Africa'
            ],
            '100249853' => [
                'country' => 'Mauritius',
                'country_code' => 'MU',
                'un_region' => 'Eastern Africa'
            ],
            '100253438' => [
                'country' => 'Mayotte',
                'country_code' => 'YT',
                'un_region' => 'Eastern Africa'
            ],
            '100245357' => [
                'country' => 'Mexico',
                'country_code' => 'MX',
                'un_region' => 'Central America'
            ],
            '100130426' => [
                'country' => 'Micronesia (Federated States of)',
                'country_code' => 'FM',
                'un_region' => 'Micronesia'
            ],
            '100243746' => [
                'country' => 'Moldova, Republic of',
                'country_code' => 'MD',
                'un_region' => 'Eastern Europe'
            ],
            '100243745' => [
                'country' => 'Monaco',
                'country_code' => 'MC',
                'un_region' => 'Western Europe'
            ],
            '100248845' => [
                'country' => 'Mongolia',
                'country_code' => 'MN',
                'un_region' => 'Eastern Asia'
            ],
            '100248823' => [
                'country' => 'Montenegro',
                'country_code' => 'ME',
                'un_region' => 'Southern Europe'
            ],
            '100249812' => [
                'country' => 'Montserrat',
                'country_code' => 'MS',
                'un_region' => 'Caribbean'
            ],
            '100241761' => [
                'country' => 'Morocco',
                'country_code' => 'MA',
                'un_region' => 'Northern Africa'
            ],
            '100249200' => [
                'country' => 'Mozambique',
                'country_code' => 'MZ',
                'un_region' => 'Eastern Africa'
            ],
            '100248458' => [
                'country' => 'Myanmar',
                'country_code' => 'MM',
                'un_region' => 'South-eastern Asia'
            ],
            '100253456' => [
                'country' => 'Namibia',
                'country_code' => 'NA',
                'un_region' => 'Southern Africa'
            ],
            '100259807' => [
                'country' => 'Nauru',
                'country_code' => 'NR',
                'un_region' => 'Micronesia'
            ],
            '100255729' => [
                'country' => 'Nepal',
                'country_code' => 'NP',
                'un_region' => 'Southern Asia'
            ],
            '100254765' => [
                'country' => 'Netherlands',
                'country_code' => 'NL',
                'un_region' => 'Western Europe'
            ],
            '100253577' => [
                'country' => 'New Caledonia',
                'country_code' => 'NC',
                'un_region' => 'Melanesia'
            ],
            '100259822' => [
                'country' => 'New Zealand',
                'country_code' => 'NZ',
                'un_region' => 'Australia and New Zealand'
            ],
            '100254606' => [
                'country' => 'Nicaragua',
                'country_code' => 'NI',
                'un_region' => 'Central America'
            ],
            '100253616' => [
                'country' => 'Niger',
                'country_code' => 'NE',
                'un_region' => 'Western Africa'
            ],
            '100253793' => [
                'country' => 'Nigeria',
                'country_code' => 'NG',
                'un_region' => 'Western Africa'
            ],
            '100254764' => [
                'country' => 'Niue',
                'country_code' => 'NU',
                'un_region' => 'Polynesia'
            ],
            '100253792' => [
                'country' => 'Norfolk Island',
                'country_code' => 'NF',
                'un_region' => 'Australia and New Zealand'
            ],
            '100247245' => [
                'country' => 'North Macedonia',
                'country_code' => 'MK',
                'un_region' => 'Southern Europe'
            ],
            '100249195' => [
                'country' => 'Northern Mariana Islands',
                'country_code' => 'MP',
                'un_region' => 'Micronesia'
            ],
            '100255271' => [
                'country' => 'Norway',
                'country_code' => 'NO',
                'un_region' => 'Northern Europe'
            ],
            '100259917' => [
                'country' => 'Oman',
                'country_code' => 'OM',
                'un_region' => 'Western Asia'
            ],
            '100259978' => [
                'country' => 'Pakistan',
                'country_code' => 'PK',
                'un_region' => 'Southern Asia'
            ],
            '100306566' => [
                'country' => 'Palau',
                'country_code' => 'PW',
                'un_region' => 'Micronesia'
            ],
            '100314675' => [
                'country' => 'Palestine, State of',
                'country_code' => 'PS',
                'un_region' => 'Western Asia'
            ],
            '100260160' => [
                'country' => 'Panama',
                'country_code' => 'PA',
                'un_region' => 'Central America'
            ],
            '100306583' => [
                'country' => 'Papua New Guinea',
                'country_code' => 'PG',
                'un_region' => 'Melanesia'
            ],
            '100314438' => [
                'country' => 'Paraguay',
                'country_code' => 'PY',
                'un_region' => 'South America'
            ],
            '100260852' => [
                'country' => 'Peru',
                'country_code' => 'PE',
                'un_region' => 'South America'
            ],
            '100262889' => [
                'country' => 'Philippines',
                'country_code' => 'PH',
                'un_region' => 'South-eastern Asia'
            ],
            '100260851' => [
                'country' => 'Pitcairn',
                'country_code' => 'PN',
                'un_region' => 'Polynesia'
            ],
            '100306693' => [
                'country' => 'Poland',
                'country_code' => 'PL',
                'un_region' => 'Eastern Europe'
            ],
            '100309849' => [
                'country' => 'Portugal',
                'country_code' => 'PT',
                'un_region' => 'Southern Europe'
            ],
            '100309569' => [
                'country' => 'Puerto Rico',
                'country_code' => 'PR',
                'un_region' => 'Caribbean'
            ],
            '100314700' => [
                'country' => 'Qatar',
                'country_code' => 'QA',
                'un_region' => 'Western Asia'
            ],
            '100314708' => [
                'country' => 'Réunion',
                'country_code' => 'RE',
                'un_region' => 'Eastern Africa'
            ],
            '100314737' => [
                'country' => 'Romania',
                'country_code' => 'RO',
                'un_region' => 'Eastern Europe'
            ],
            '100317719' => [
                'country' => 'Russian Federation',
                'country_code' => 'RU',
                'un_region' => 'Eastern Europe'
            ],
            '100322810' => [
                'country' => 'Rwanda',
                'country_code' => 'RW',
                'un_region' => 'Eastern Africa'
            ],
            '100024783' => [
                'country' => 'Saint Barthélemy',
                'country_code' => 'BL',
                'un_region' => 'Caribbean'
            ],
            '100341225' => [
                'country' => 'Saint Helena, Ascension and Tristan da Cunha',
                'country_code' => 'SH',
                'un_region' => 'Western Africa'
            ],
            '100238557' => [
                'country' => 'Saint Kitts and Nevis',
                'country_code' => 'KN',
                'un_region' => 'Caribbean'
            ],
            '100241004' => [
                'country' => 'Saint Lucia',
                'country_code' => 'LC',
                'un_region' => 'Caribbean'
            ],
            '100241760' => [
                'country' => 'Saint Martin (French part)',
                'country_code' => 'MF',
                'un_region' => 'Caribbean'
            ],
            '100341992' => [
                'country' => 'Saint Pierre and Miquelon',
                'country_code' => 'PM',
                'un_region' => 'Northern America'
            ],
            '100367576' => [
                'country' => 'Saint Vincent and the Grenadines',
                'country_code' => 'VC',
                'un_region' => 'Caribbean'
            ],
            '100379993' => [
                'country' => 'Samoa',
                'country_code' => 'WS',
                'un_region' => 'Polynesia'
            ],
            '100341889' => [
                'country' => 'San Marino',
                'country_code' => 'SM',
                'un_region' => 'Southern Europe'
            ],
            '100342287' => [
                'country' => 'Sao Tome and Principe',
                'country_code' => 'ST',
                'un_region' => 'Middle Africa'
            ],
            '100340252' => [
                'country' => 'Saudi Arabia',
                'country_code' => 'SA',
                'un_region' => 'Western Asia'
            ],
            '100340602' => [
                'country' => 'Senegal',
                'country_code' => 'SN',
                'un_region' => 'Western Africa'
            ],
            '100341995' => [
                'country' => 'Serbia',
                'country_code' => 'RS',
                'un_region' => 'Southern Europe'
            ],
            '100343036' => [
                'country' => 'Seychelles',
                'country_code' => 'SC',
                'un_region' => 'Eastern Africa'
            ],
            '100341436' => [
                'country' => 'Sierra Leone',
                'country_code' => 'SL',
                'un_region' => 'Western Africa'
            ],
            '100341218' => [
                'country' => 'Singapore',
                'country_code' => 'SG',
                'un_region' => 'South-eastern Asia'
            ],
            '100343035' => [
                'country' => 'Sint Maarten (Dutch part)',
                'country_code' => 'SX',
                'un_region' => 'Caribbean'
            ],
            '100342370' => [
                'country' => 'Slovakia',
                'country_code' => 'SK',
                'un_region' => 'Eastern Europe'
            ],
            '100342458' => [
                'country' => 'Slovenia',
                'country_code' => 'SI',
                'un_region' => 'Southern Europe'
            ],
            '100341242' => [
                'country' => 'Solomon Islands',
                'country_code' => 'SB',
                'un_region' => 'Melanesia'
            ],
            '100341899' => [
                'country' => 'Somalia',
                'country_code' => 'SO',
                'un_region' => 'Eastern Africa'
            ],
            '100380454' => [
                'country' => 'South Africa',
                'country_code' => 'ZA',
                'un_region' => 'Southern Africa'
            ],
            '100341224' => [
                'country' => 'South Georgia and the South Sandwich Islands',
                'country_code' => 'GS',
                'un_region' => 'South America'
            ],
            '100342182' => [
                'country' => 'South Sudan',
                'country_code' => 'SS',
                'un_region' => 'Eastern Africa'
            ],
            '100074576' => [
                'country' => 'Spain',
                'country_code' => 'ES',
                'un_region' => 'Southern Europe'
            ],
            '100241027' => [
                'country' => 'Sri Lanka',
                'country_code' => 'LK',
                'un_region' => 'Southern Asia'
            ],
            '100340266' => [
                'country' => 'Sudan',
                'country_code' => 'SD',
                'un_region' => 'Northern Africa'
            ],
            '100342297' => [
                'country' => 'Suriname',
                'country_code' => 'SR',
                'un_region' => 'South America'
            ],
            '100341239' => [
                'country' => 'Svalbard and Jan Mayen',
                'country_code' => 'SJ',
                'un_region' => 'Northern Europe'
            ],
            '100342663' => [
                'country' => 'Sweden',
                'country_code' => 'SE',
                'un_region' => 'Northern Europe'
            ],
            '100047361' => [
                'country' => 'Switzerland',
                'country_code' => 'CH',
                'un_region' => 'Western Europe'
            ],
            '100343063' => [
                'country' => 'Syrian Arab Republic',
                'country_code' => 'SY',
                'un_region' => 'Western Asia'
            ],
            '100352871' => [
                'country' => 'Taiwan, Province of China',
                'country_code' => 'TW',
                'un_region' => 'Eastern Asia'
            ],
            '100350531' => [
                'country' => 'Tajikistan',
                'country_code' => 'TJ',
                'un_region' => 'Central Asia'
            ],
            '100352901' => [
                'country' => 'Tanzania, United Republic of',
                'country_code' => 'TZ',
                'un_region' => 'Eastern Africa'
            ],
            '100343599' => [
                'country' => 'Thailand',
                'country_code' => 'TH',
                'un_region' => 'South-eastern Asia'
            ],
            '100350967' => [
                'country' => 'Timor-Leste',
                'country_code' => 'TL',
                'un_region' => 'South-eastern Asia'
            ],
            '100343572' => [
                'country' => 'Togo',
                'country_code' => 'TG',
                'un_region' => 'Western Africa'
            ],
            '100350956' => [
                'country' => 'Tokelau',
                'country_code' => 'TK',
                'un_region' => 'Polynesia'
            ],
            '100351536' => [
                'country' => 'Tonga',
                'country_code' => 'TO',
                'un_region' => 'Polynesia'
            ],
            '100351542' => [
                'country' => 'Trinidad and Tobago',
                'country_code' => 'TT',
                'un_region' => 'Caribbean'
            ],
            '100351558' => [
                'country' => 'Tunisia',
                'country_code' => 'TN',
                'un_region' => 'Northern Africa'
            ],
            '100351851' => [
                'country' => 'Turkey',
                'country_code' => 'TR',
                'un_region' => 'Western Asia'
            ],
            '100350960' => [
                'country' => 'Turkmenistan',
                'country_code' => 'TM',
                'un_region' => 'Central Asia'
            ],
            '100343138' => [
                'country' => 'Turks and Caicos Islands',
                'country_code' => 'TC',
                'un_region' => 'Caribbean'
            ],
            '100352861' => [
                'country' => 'Tuvalu',
                'country_code' => 'TV',
                'un_region' => 'Polynesia'
            ],
            '100356776' => [
                'country' => 'Uganda',
                'country_code' => 'UG',
                'un_region' => 'Eastern Africa'
            ],
            '100363308' => [
                'country' => 'Ukraine',
                'country_code' => 'UA',
                'un_region' => 'Eastern Europe'
            ],
            '100001527' => [
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'un_region' => 'Western Asia'
            ],
            '100130478' => [
                'country' => 'United Kingdom of Great Britain and Northern Ireland',
                'country_code' => 'GB',
                'un_region' => 'Northern Europe'
            ],
            '100364199' => [
                'country' => 'United States of America',
                'country_code' => 'US',
                'un_region' => 'Northern America'
            ],
            '100363965' => [
                'country' => 'United States Minor Outlying Islands',
                'country_code' => 'UM',
                'un_region' => 'Micronesia'
            ],
            '100363975' => [
                'country' => 'Uruguay',
                'country_code' => 'UY',
                'un_region' => 'South America'
            ],
            '100367399' => [
                'country' => 'Uzbekistan',
                'country_code' => 'UZ',
                'un_region' => 'Central Asia'
            ],
            '100379914' => [
                'country' => 'Vanuatu',
                'country_code' => 'VU',
                'un_region' => 'Melanesia'
            ],
            '100367583' => [
                'country' => 'Venezuela (Bolivarian Republic of)',
                'country_code' => 'VE',
                'un_region' => 'South America'
            ],
            '100367977' => [
                'country' => 'Viet Nam',
                'country_code' => 'VN',
                'un_region' => 'South-eastern Asia'
            ],
            '100367947' => [
                'country' => 'Virgin Islands (British)',
                'country_code' => 'VG',
                'un_region' => 'Caribbean'
            ],
            '100367953' => [
                'country' => 'Virgin Islands (U.S.)',
                'country_code' => 'VI',
                'un_region' => 'Caribbean'
            ],
            '100379984' => [
                'country' => 'Wallis and Futuna',
                'country_code' => 'WF',
                'un_region' => 'Polynesia'
            ],
            '100074571' => [
                'country' => 'Western Sahara',
                'country_code' => 'EH',
                'un_region' => 'Northern Africa'
            ],
            '100380099' => [
                'country' => 'Yemen',
                'country_code' => 'YE',
                'un_region' => 'Western Asia'
            ],
            '100385027' => [
                'country' => 'Zambia',
                'country_code' => 'ZM',
                'un_region' => 'Eastern Africa'
            ],
            '100385110' => [
                'country' => 'Zimbabwe',
                'country_code' => 'ZW',
                'un_region' => 'Eastern Africa'
            ]
        ];
    }
}
