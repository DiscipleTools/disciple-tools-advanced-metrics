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

        register_rest_route(
            $namespace, '/get_leader_gender_ratio', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_leader_gender_ratio' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_groups_data', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_groups_data' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_groups_corr_data', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_groups_corr_data' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_groups_insights', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_groups_insights' ],
            ]
        );
    }


    // Get the amount of men compared to women
    public function get_gender_ratio( WP_REST_Request $request ) {
        $output = null;
        $contact_ids = self::get_ids( 'contacts' );
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

            $output['male_ratio'] = 0;
            $output['female_ratio'] = 0;

            // Get Male/Female Ratio
            $output['ratio'] = 0;
            if ( $male_count > 0 ) {
                $output['ratio'] = $male_count / $female_count;
            }

            $gcd = self::get_gcd( $male_count, $female_count );
            $male_ratio = $male_count / $gcd;
            $female_ratio = $female_count / $gcd;

            $output['male_ratio'] = $male_ratio;
            $output['female_ratio'] = $female_ratio;

            $male_text = self::get_text( 'man', 'men', $male_ratio );
            $female_text = self::get_text( 'woman', 'women', $female_ratio );
            $is_text = self::get_text( 'is', 'are', $female_ratio );

            $output['description'] = "There $is_text $male_ratio $male_text for every $female_ratio $female_text.";
            return $output;
        }
    }


    // Get the amount of male leaders compared to female leaders
    public function get_leader_gender_ratio( WP_REST_Request $request ) {
        $output = null;
        global $wpdb;

        $response = $wpdb->get_results("
            SELECT post_id
            FROM wp_postmeta
            WHERE (meta_key = 'gender' AND meta_value = 'male')
            OR (meta_key = 'faith_status' AND meta_value = 'leader')
            GROUP BY post_id
            HAVING COUNT(post_id) > 1;
            ");

        $male_leader_count = $wpdb->num_rows;


        $response = $wpdb->get_results("
            SELECT post_id
            FROM wp_postmeta
            WHERE (meta_key = 'gender' AND meta_value = 'female')
            OR (meta_key = 'faith_status' AND meta_value = 'leader')
            GROUP BY post_id
            HAVING COUNT(post_id) > 1;
            ");

        $female_leader_count = $wpdb->num_rows;

        $output['male_leader_count'] = $male_leader_count;
        $output['female_leader_count'] = $female_leader_count;

        $output['gender_leader_ratio'] = 0;
        if ( $male_leader_count > 0 ) {
            $output['gender_leader_ratio'] = $female_leader_count / $male_leader_count;
        }

        $output['male_leader_ratio'] = 0;
        $output['female_leader_ratio'] = 0;


        if ( $female_leader_count === 0 ) {
            $leader_text = self::get_text( 'leader', 'leaders', $male_leader_count );
            $is_text = self::get_text( 'is', 'are', $male_leader_count );
            $output['description'] = "There $is_text $male_leader_count male $leader_text and no female leaders.";
        }

        if ( $male_leader_count === 0 ) {
            $leader_text = self::get_text( 'leader', 'leaders', $female_leader_count );
            $is_text = self::get_text( 'is', 'are', $female_leader_count );
            $output['description'] = "There $is_text $female_leader_count $leader_text and no male leaders.";
        }

        if ( $male_leader_count === 0 && $female_leader_count === 0 ) {
            $output['description'] = 'There are no leaders. Yet.';
            return $output;
        }

        if ( $male_leader_count > 0 && $female_leader_count > 0 ) {
            $gcd = self::get_gcd( $female_leader_count, $male_leader_count );
            $male_leader_ratio = $male_leader_count / $gcd;
            $female_leader_ratio = $female_leader_count / $gcd;

            $output['male_leader_ratio'] = $male_leader_ratio;
            $output['female_leader_ratio'] = $female_leader_ratio;

            $is_text = self::get_text( 'is', 'are', $female_leader_ratio );
            $leader_male_text = self::get_text( 'leader', 'leaders', $male_leader_ratio );
            $leader_female_text = self::get_text( 'leader', 'leaders', $female_leader_ratio );

            $output['description'] = "There $is_text $female_leader_ratio female $leader_female_text for every $male_leader_ratio male $leader_male_text.";
        }

        return $output;
    }


    // Get the amount of Bible readers for every Bible owner
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

        $output['reading_bible_ratio'] = 0;
        $output['has_bible_ratio'] = 0;

        $output['description'] = 'No Bibles were distributed yet.';

        // Don't divide by 0
        if ( $has_bible_count > 0 ) {
            $output['ratio'] = $reading_bible_count / $has_bible_count;
        }

        if ( $has_bible_count > 0 && $reading_bible_count === 0 ) {
            $bible_was_text = self::get_text( 'Bible was', 'Bibles were', $has_bible_count );
            $nobody_is_reading_it_text = self::get_text( 'nobody is reading it', 'nobody is reading them', $has_bible_count );
            $output['description'] = "$has_bible_count $bible_was_text distributed but $nobody_is_reading_it_text (that we know of).";
        }

        // Get Have/Read Ratio
        if ( $has_bible_count > 0 && $reading_bible_count > 0 ) {
            // Get Greatest Common Denominators
            $gcd = self::get_gcd( $reading_bible_count, $has_bible_count );

            $reading_bible_ratio = $reading_bible_count / $gcd;
            $has_bible_ratio = $has_bible_count / $gcd;

            $output['has_bible_ratio'] = $has_bible_ratio;
            $output['reading_bible_ratio'] = $reading_bible_ratio;

            $is_text = self::get_text( 'is', 'are', $reading_bible_ratio );
            $contact_is_text = self::get_text( 'contact is', 'contacts are', $has_bible_ratio );
            $his_text = self::get_text( 'his', 'their', $has_bible_ratio );
            $output['description'] = "$reading_bible_ratio out of every $has_bible_ratio $contact_is_text reading $his_text Bible.";
        }

        return $output;
    }

    // Use machine learning to find insights from group data
    public function get_groups_data() {
        $group_ids = self::get_ids( 'groups' );
        $columns = null;
        foreach ( $group_ids as $id ) {
            // Get group status
            $group_status = 0;
            if ( self::get_postmeta_value( $id, 'group_status' ) === 'active' ) {
                $group_status = 1;
            }
            $columns['status'][] = $group_status;

            // Get number of male members
            $columns['male_count'][] = self::get_group_gender_count( $id, 'male' );

            // Get number of female members
            $columns['female_count'][] = self::get_group_gender_count( $id, 'female' );

            // Get member count
            $member_count = self::get_postmeta_value( $id, 'member_count' );
            if ( $member_count === null ) {
                $member_count = 0;
            }
            $columns['member_count'][] = intval( $member_count );

            $leader_count = self::get_postmeta_value( $id, 'leader_count' );
            if ( $leader_count === null ) {
                $leader_count = 0;
            }
            $columns['leader_count'][] = intval( $leader_count );



            // Check if more men than women
            $columns['more_men_than_women'][] = 1;

            // Check if more women than men
            $columns['more_women_than_men'][] = 0;

            // Get number of male leaders
            $columns['male_leaders'][] = self::get_group_leaders_gender_count( $id, 'male' );

            // Get number of female leaders
            $columns['female_leaders'][] = self::get_group_leaders_gender_count( $id, 'female' );

            // Get group health data
            $columns['group_health_practices'][] = self::get_health_metrics_count( $id );

            $health_metrics = [ 'church_baptism', 'church_bible', 'church_commitment', 'church_communion', 'church_fellowship', 'church_giving', 'church_leaders', 'church_praise', 'church_prayer', 'church_sharing' ];

            foreach ( $health_metrics as $health_metric ) {
                $columns[$health_metric][] = self::get_health_metric_status( $id, $health_metric );
            }
        }

        // Now that all group ids have cycled through, compare men member counts and women member counts.
        $group_length = count( $group_ids );
        for ( $i =0; $i < $group_length; $i++ ) {
            if ( $columns['female_count'][$i] > $columns['male_count'][$i] ) {
                $columns['more_women_than_men'][$i] = 1;
                $columns['more_men_than_women'][$i] = 0;
            }

                // If men count and women count is tied, nobody has more than the other (John Madden explanation)
            if ( $columns['female_count'][$i] == $columns['male_count'][$i] ) {
                $columns['more_women_than_men'][$i] = 0;
                $columns['more_men_than_women'][$i] = 0;
            }
        }
        return $columns;
    }

    public function get_groups_corr_data() {
        $data = self::get_groups_data();
        $corr = [];

        // Get column names
        $columns = [];
        foreach ( $data as $key => $value ) {
            $columns[] = $key;
        }

        foreach ( $columns as $col ) {
            $col_length = count( $columns );
            for ( $i = 0; $i < $col_length; $i++ ) {

                // Don't get correlations for columns compared to themseleves
                if ( $columns[$i] === $col ) {
                    continue;
                }

                $corr_name = $col . ' vs ' . $columns[$i];
                $corr[] = [
                    'name' => $corr_name,
                    'col_1' => $col,
                    'col_2' => $columns[$i],
                    'corr' => self::get_corr( $data[$col], $data[ $columns[$i] ] )
                ];
            }
        }
        return $corr;
    }

    public function get_groups_insights() {
        $correlations = self::get_groups_corr_data();
        $insights = [];
        $already_mentioned = []; // This array will prevent a/b correlations to show up if its b/a counterpart correlation has already been mentioned.

        $definitions = [
            'status' => 'groups that are active',
            'member_count' => 'groups with more members',
            'leader_count' => 'groups with more leaders',
            'male_count' => 'groups with lots of men',
            'female_count' => 'groups with lots of women',
            'more_men_than_women' => 'groups with more men than women',
            'more_women_than_men' => 'groups with more women than men',
            'male_leaders' => 'groups with lots of male leaders',
            'female_leaders' => 'groups with lots of female leaders',
            'group_health_practices' => 'groups that practice many health elements',
            'church_baptism' => 'groups that are baptising people',
            'church_bible' => 'groups that read the Bible',
            'church_commitment' => 'groups that have committed to identify themselves as a church',
            'church_communion' => 'groups that partake in communion',
            'church_fellowship' => 'groups in which the members have fellowship amongst themselves',
            'church_giving' => 'groups that give',
            'church_leaders' => 'groups that are forming leaders',
            'church_praise' => 'groups that praise together',
            'church_prayer' => 'groups that pray together',
            'church_sharing' => 'groups that share the gospel',
        ];

        $definitions_verbs = [
            'status' => 'are active',
            'member_count' => 'have many members',
            'leader_count' => 'have many leaders',
            'male_count' => 'have many men',
            'female_count' => 'have many women',
            'more_men_than_women' => 'have more men than women',
            'more_women_than_men' => 'have more women than men',
            'male_leaders' => 'have male leaders',
            'female_leaders' => 'have female leaders',
            'group_health_practices' => 'practice group health elements',
            'church_baptism' => 'baptise people',
            'church_bible' => 'read the Bible',
            'church_commitment' => 'commit to identify themselves as a church',
            'church_communion' => 'partake in communion',
            'church_fellowship' => 'have fellowship amongst themselves',
            'church_giving' => 'give',
            'church_leaders' => 'form leaders',
            'church_praise' => 'praise together',
            'church_prayer' => 'pray together',
            'church_sharing' => 'share the gospel',
        ];

        foreach ( $correlations as $corr ) {
            $corr_hash = [];
            foreach ( $corr as $key => $value ) {
                $corr_hash[] = $corr['col_1'];
                $corr_hash[] = $corr['col_2'];
                sort( $corr_hash );
                if ( ! in_array( $corr_hash, $already_mentioned ) ) {
                    if ( $key === 'corr' && $value === 1 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' always ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value !== 1 && $value >= 0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' almost always ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value >= 0.75 && $value < 0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' usually ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value >= -0.75 && $value < -0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' seldom ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value !== -1 && $value <= -0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' almost never ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key ==='corr' && $value === -1 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' never ' . $definitions_verbs[ $corr['col_2'] ];
                        $already_mentioned[] = $corr_hash;
                    }
                }
            }
        }
        var_export( $insights );
        die();
        return $insights;
    }

    // Get Pearson Correlation between two variables
    private function get_corr( $x, $y ) {
        if ( count( $x ) !== count( $y ) ) {
            return -1;
        }

        $x = array_values( $x );
        $y = array_values( $y );
        $xs = array_sum( $x ) / count( $x );
        $ys = array_sum( $y ) / count( $y );
        $a = 0;
        $bx = 0;
        $by = 0;

        $x_length = count( $x );
        for ( $i = 0; $i < $x_length; $i++ ) {
            $xr =$x[$i] - $xs;
            $yr =$y[$i] - $ys;
            $a += $xr * $yr;
            $bx += pow( $xr, 2 );
            $by += pow( $yr, 2 );
        }

        $b = sqrt( $bx * $by );
        if ( $b == 0 ) {
            return 0;
        }
        return $a /$b;
    }

    // Check how many health metrics are being practiced by a group
    private function get_health_metrics_count( $group_id ) {
        global $wpdb;
        $response = $wpdb->get_results(
            $wpdb->prepare( "
                SELECT meta_value
                FROM $wpdb->postmeta
                WHERE meta_key = 'health_metrics' AND post_id = %d", $group_id)
        );
        $output = $wpdb->num_rows;
        return $output;
    }

    // Check to see if a health metric is being practiced by a group
    private function get_health_metric_status( $group_id, $health_metric ) {
        global $wpdb;
        $response = $wpdb->get_col(
            $wpdb->prepare("
                SELECT meta_value
                FROM $wpdb->postmeta
                WHERE meta_key = 'health_metrics'
                AND meta_value = %s
                AND post_id = %d;
                ", $health_metric, $group_id )
        );
        $output = $wpdb->num_rows;
        return $output;
    }

    // Count the amount of men in a group
    private function get_group_gender_count( $group_id, $gender ) {
        global $wpdb;
        $gender_count = 0;
        $member_ids = self::get_group_members( $group_id );

        foreach ( $member_ids as $id ) {
            if ( self::get_postmeta_value( $id, 'gender' ) === $gender ) {
                $gender_count ++;
            }
        }
        return $gender_count;
    }

    // Counts the amount of group leaders pertaining to a specific gender
    private function get_group_leaders_gender_count( $group_id, $gender ) {
        global $wpdb;
        $gender_count = 0;
        $leader_ids = self::get_group_leaders( $group_id );

        foreach ( $leader_ids as $id ) {
            if ( self::get_postmeta_value( $id, 'gender' ) === $gender ) {
                $gender_count ++;
            }
        }
        return $gender_count;
    }

    // Get the ids for the members of a group
    private function get_group_members( $group_id ) {
        global $wpdb;
        $response = $wpdb->get_col(
            $wpdb->prepare( "
                SELECT p2p_from
                FROM $wpdb->p2p
                WHERE p2p_type = 'contacts_to_groups'
                AND p2p_to = %d", $group_id
            )
        );
        return $response;
    }

    // Get the ids for the leaders of a group
    private function get_group_leaders( $group_id ) {
        global $wpdb;
        $leader_count = 0;

        $response = $wpdb->get_col(
            $wpdb->prepare( "
                SELECT p2p_to
                FROM $wpdb->p2p
                WHERE p2p_type = 'groups_to_leaders'
                AND p2p_from = %d", $group_id )
        );
        return $response;
    }

    /*
     * AUXILIARY FUNCTIONS
     */


    // Check if the wording for a metric should be plural or singular, according to the metric count
    private function get_text( $text_singular, $text_plural, $metric ) {
        $output = $text_singular;
        if ( $metric > 1 ) {
            $output = $text_plural;
        }
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


    private function get_ids( $post_type ) {
        global $wpdb;
        $response = $wpdb->get_col( $wpdb->prepare( "
            SELECT ID
            FROM wp_posts
            WHERE post_type = %s;
            ", $post_type ) );
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
    public function has_permission() {
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
