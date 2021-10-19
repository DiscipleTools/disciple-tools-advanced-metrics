<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Advanced_Metrics
{
    /**
     * @todo Set the permissions your endpoint needs
     * @link https://github.com/DiscipleTools/Documentation/blob/master/theme-core/capabilities.md
     * @var string[]
     */
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


    /**
     * @todo define the name of the $namespace
     * @todo define the name of the rest route
     * @todo defne method (CREATABLE, READABLE)
     * @todo apply permission strategy. '__return_true' essentially skips the permission check.
     */
    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'disciple_tools_advanced_metrics/v1';

        register_rest_route(
            $namespace, '/get_gender_ratio', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_gender_ratio' ],
                // 'permission_callback' => function( WP_REST_Request $request ) {
                //     return $this->has_permission();
                // },
            ]
        );

        register_rest_route(
            $namespace, '/get_bible_reading_ratio', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_bible_reading_ratio' ],
            ]
        );
    }

    // public function get_data( WP_REST_Request $request ) {
    //     global $wpdb;
    //     $output = [];
    //     $response = $wpdb->get_col( $wpdb->prepare( "
    //         SELECT count(post_type) AS contact_count
    //         FROM $wpdb->posts
    //         WHERE post_type = 'contacts'" ) );
    //     $output['contact_count'] = [ intval($response[0]) ];

    //     $response = $wpdb->get_results( $wpdb->prepare( "
    //         SELECT REPLACE( meta_value, '-', '') as date, COUNT( * ) as baptism_count
    //         FROM wp_dt_activity_log
    //         WHERE meta_key = 'baptism_date'
    //         AND object_note LIKE 'Added Baptism Date: %'
    //         GROUP BY meta_value;" ) );
    //         $output['baptism_count'] = $response;

    //     return $output;
    // }

    public function get_gender_ratio( WP_REST_Request $request ) {
        $output = null;
        $contact_ids = self::get_contact_ids();
        $male_count = 0;
        $female_count = 0;

        if ( ! empty( $contact_ids ) ) {
            foreach ( $contact_ids as $id ) {
                $gender = self::get_postmeta_value( $id, 'gender' );
                if ( $gender === 'male' ) {
                    $male_count ++;
                }

                if ( $gender === 'female' ) {
                    $female_count ++;
                }
            }
            if ( $female_count === 0 ) {
                return 'There are no women.';
            }

            $output['male_count'] = $male_count;
            $output['female_count'] = $female_count;

            // Get Male/Female Ratio
            $output['ratio'] = 0;
            if ( $male_count != 0 ) {
                $output['ratio'] = $male_count / $female_count;
            }

            $gcd = self::get_gcd( $male_count, $female_count );
            $male_ratio = $male_count / $gcd;
            $female_ratio = $female_count / $gcd;

            $male_text = 'man';
            $female_text = 'woman';
            $is_text = 'is';

            if ( $male_ratio > 1 ) {
                $male_text = 'men';
                $is_text = 'are';
            }

            if ( $female_ratio > 1 ) {
                $female_text = 'women';
                $is_text = 'are';
            }

            $output['description'] = "There $is_text $male_ratio $male_text for every $female_ratio $female_text.";
            return $output;
        }
    }

    public function get_bible_reading_ratio( WP_REST_Request $request ) {
        global $wpdb;
        $output = null;

        $response = $wpdb->get_col("
            SELECT COUNT(*) FROM
            $wpdb->postmeta WHERE
            meta_value = 'milestone_has_bible';"
        );
        $has_bible_count = $response[0];

        $response = $wpdb->get_col("
            SELECT COUNT(*) FROM
            $wpdb->postmeta WHERE
            meta_value = 'milestone_reading_bible';"
        );
        $reading_bible_count = $response[0];

        $output['has_bible'] = $has_bible_count;
        $output['reading_bible'] = $reading_bible_count;
        $output['ratio'] = 0;

        //Don't divide by 0
        if ( $has_bible_count != 0 ) {
            $output['ratio'] = $reading_bible_count / $has_bible_count;
        }

        //Get Greatest Common Denominators
        $bible_gcd = self::get_gcd( $reading_bible_count, $has_bible_count );

        //Get Have/Read Ratio
        $reading_bible_ratio = $reading_bible_count / $bible_gcd;
        $has_bible_ratio = $has_bible_count / $bible_gcd;

        $is_text = 'is';
        if ( $reading_bible_ratio > 1 ) {
            $is_text = 'are';
        }

        $output['description'] = "$reading_bible_ratio out of $has_bible_ratio contacts $is_text reading their Bible.";

        return $output;
    }



    private function get_factors( $num ) {
        $factors = [];
        for ( $x = 1; $x <= $num; $x ++ ) {
            if ( $num % $x == 0 ) {
                $factors[] = $x;
            }
        }
        return $factors;
    }

    private function get_gcd( $x, $y ) {
        $factors_x = self::get_factors( $x );
        $factors_y = self::get_factors( $y );
        $common_denominators = array_intersect( $factors_x, $factors_y );
        $gcd = array_pop( $common_denominators );
        return $gcd;
    }


    private function get_contact_ids() {
        global $wpdb;
        $response = $wpdb->get_col( "
            SELECT ID
            FROM wp_posts
            WHERE post_type = 'contacts';
            " );
        return $response;
    }



    private function get_meta_value( $post_id, $column_name ) {
        global $wpdb;
        $response = $wpdb->get_var( $wpdb->prepare( "
            SELECT $column_name
            FROM wp_posts
            WHERE ID = %s;
            ", $post_id ) );
        return $response;
    }



    private function get_postmeta_value( $post_id, $meta_key ) {
        global $wpdb;
        $response = $wpdb->get_var( $wpdb->prepare( "
            SELECT meta_value
            FROM $wpdb->postmeta
            WHERE post_id = %s
            AND meta_key = %s;
            ", $post_id, $meta_key ) );
        return $response;
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
}
Disciple_Tools_Advanced_Metrics::instance();
