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
        $namespace = 'disciple-tools-advanced-metrics/v1';

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
            $namespace, '/get_gender_ratio_chart', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_gender_ratio_chart' ],
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
            $namespace, '/get_contacts_corr_data', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_contacts_corr_data' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_groups_insights', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_groups_insights' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_contacts_data', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_contacts_data' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_contacts_insights', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_contacts_insights' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_average_contact_journey_data', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_average_contact_journey_data' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_average_contact_journey_text', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_average_contact_journey_text' ],
            ]
        );

        register_rest_route(
            $namespace, '/get_population_pyramid', [
                'methods' => 'GET',
                'callback' => [ $this, 'get_population_pyramid' ],
            ]
        );
    }

    // Get population pyramid
    public function get_population_pyramid() {
        $data = [
            '<19' => [ 'male' => 0, 'female' => 0 ],
            '<26' => [ 'male' => 0, 'female' => 0 ],
            '<41' => [ 'male' => 0, 'female' => 0 ],
            '>41' => [ 'male' => 0, 'female' => 0 ],
            'not-set' => [ 'male' => 0, 'female' => 0 ],
        ];

        $contact_ids = self::get_ids( 'contacts' );

        foreach ( $contact_ids as $id ) {
            $gender = self::get_postmeta_value( $id, 'gender' );
            if ( ! isset( $gender ) || empty( $gender ) ) {
                continue;
            }
            $age = self::get_postmeta_value( $id, 'age' );

            switch ( $age ) {
                case '<19':
                    $data['<19'][ $gender ] ++;
                    break;

                case '&lt;19':
                    $data['<19'][ $gender ] ++;
                    break;

                case '<26':
                    $data['<26'][ $gender ] ++;
                    break;

                case '&lt;26':
                    $data['<26'][ $gender ] ++;
                    break;

                case '<41':
                    $data['<41'][ $gender ] ++;
                    break;

                case '&lt;41':
                    $data['<41'][ $gender ] ++;
                    break;

                case '>41':
                    $data['>41'][ $gender ] ++;
                    break;

                case '&gt;41':
                    $data['>41'][ $gender ] ++;
                    break;

                case null:
                    $data['not-set'][ $gender ] ++;
                    break;
            }
        }
        $output[] = [ 'category' => 'Under 18 years old', 'male' => $data['<19']['male'], 'female' => $data['<19']['female'] ];
        $output[] = [ 'category' => '18 - 25 years old', 'male' => $data['<26']['male'], 'female' => $data['<26']['female'] ];
        $output[] = [ 'category' => '26 - 40 years old', 'male' => $data['<41']['male'], 'female' => $data['<41']['female'] ];
        $output[] = [ 'category' => 'Over 40 years old', 'male' => $data['>41']['male'], 'female' => $data['<41']['female'] ];
        $output[] = [ 'category' => 'Not set', 'male' => $data['not-set']['male'], 'female' => $data['not-set']['female'] ];
        return $output;
    }

    public function get_gender_ratio_chart() {
        $contact_ids = self::get_ids( 'contacts' );
        $data = [];
        $data['male'] = 0;
        $data['female'] = 0;
        $data['not-set'] = 0;


        foreach ( $contact_ids as $id ) {
            $gender = self::get_postmeta_value( $id, 'gender' );
            if ( ! isset( $gender ) || empty( $gender ) ) {
                $gender = 'not-set';
            }
            $data[$gender]++;
        }

        $output = [
            [ 'gender' => 'male', 'count' => $data['male'] ],
            [ 'gender' => 'female', 'count' => $data['female'] ],
            [ 'gender' => 'not-set', 'count' => $data['not-set'] ],
        ];
        return $output;
    }

    // Get the amount of men compared to women
    public function get_gender_ratio() {
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
            $is_text = self::get_text( 'is', 'are', $male_ratio );

            $output['description'] = "There $is_text $male_ratio $male_text for every $female_ratio $female_text.";
            return $output;
        }
    }

    // Get the amount of male leaders compared to female leaders
    public function get_leader_gender_ratio() {
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
    public function get_bible_reading_ratio() {
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

    // Get relevant data for groups
    public function get_groups_data() {
        //Check for cached data
        $cached_data = get_transient( 'dt_advanced_metrics_groups' );
        if ( $cached_data ) {
            return $cached_data;
        }

        $group_ids = self::get_ids( 'groups' );
        $columns = [];
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

        // Cache the data
        set_transient( 'dt_advanced_metrics_groups', $columns, 60 *60 *24 );
        return $columns;
    }

    // Get relevant data for contacts
    public function get_contacts_data() {
        //Check for cached data
        $cached_data = get_transient( 'dt_advanced_metrics_contacts' );
        if ( $cached_data ) {
            return $cached_data;
        }

        $contact_ids = self::get_ids( 'contacts' );
        $columns = [];

        // Get contact gender
        foreach ( $contact_ids as $id ) {
            $contact_gender = self::get_postmeta_value( $id, 'gender' );

            switch ( $contact_gender ) {
                case 'male':
                    $contact_gender = 1;
                    break;

                case 'female':
                    $contact_gender = -1;
                    break;

                default:
                    $contact_gender = 0;
                    break;
            }

            $columns['gender'][] = $contact_gender;

            // One-hot these meta_values
            $relevant_post_metas = [
                'milestone_has_bible',
                'milestone_reading_bible',
                'milestone_belief',
                'milestone_can_share',
                'milestone_sharing',
                'milestone_baptized',
                'milestone_baptizing',
                'milestone_in_group',
                'milestone_planting'
            ];

            // Run through all relevant post metas and return 1 if it's set, else 0
            foreach ( $relevant_post_metas as $post_meta ) {
                $post_meta_result = 0;
                $curr_meta = self::check_postmeta_value_exists( $id, $post_meta );
                if ( ! empty( $curr_meta ) ) {
                    $post_meta_result = 1;
                }
                $columns[$post_meta][] = $post_meta_result;
            }

            // One-hot these meta_values
            $columns['faith_status_seeker'][] = self::check_postmeta_key_value_exists( $id, 'faith_status', 'seeker' );
            $columns['faith_status_believer'][] = self::check_postmeta_key_value_exists( $id, 'faith_status', 'believer' );
            $columns['faith_status_leader'][] = self::check_postmeta_key_value_exists( $id, 'faith_status', 'leader' );
            $columns['contact_type_user'][] = self::check_postmeta_key_value_exists( $id, 'type', 'user' );
            $columns['contact_type_personal'][] = self::check_postmeta_key_value_exists( $id, 'type', 'personal' );
            $columns['contact_type_access'][] = self::check_postmeta_key_value_exists( $id, 'type', 'access' );
            $columns['contact_type_placeholder'][] = self::check_postmeta_key_value_exists( $id, 'type', 'placeholder' );
            $columns['contact_type_create_update_contacts'][] = self::check_postmeta_key_value_exists( $id, 'type', 'create_update_contacts' );

            $columns['age_under_18'][] = 0;
            $columns['age_18_to_25'][] = 0;
            $columns['age_26_to_40'][] = 0;
            $columns['over_40'][] = 0;

            // Due to encoding issues, the DB shows different values for the same age group.
            // This checks if a value appears for any of both encodings and returns 1 if true, else 0.
            if ( intval( self::check_postmeta_key_value_exists( $id, 'age', '<19' ) ) + intval( self::check_postmeta_key_value_exists( $id, 'age', '&lt;19' ) ) !== 0 ) {
                $columns['age_under_18'][] = 1;
            }

            if ( intval( self::check_postmeta_key_value_exists( $id, 'age', '<26' ) ) + intval( self::check_postmeta_key_value_exists( $id, 'age', '&lt;26' ) ) !== 0 ) {
                $columns['age_18_to_25'][] = 1;
            }

            if ( intval( self::check_postmeta_key_value_exists( $id, 'age', '<41' ) ) + intval( self::check_postmeta_key_value_exists( $id, 'age', '&lt;41' ) ) !== 0 ) {
                $columns['age_26_to_40'][] = 1;
            }

            if ( intval( self::check_postmeta_key_value_exists( $id, 'age', '>41' ) ) + intval( self::check_postmeta_key_value_exists( $id, 'age', '&gt;41' ) ) !== 0 ) {
                $columns['over_40'][] = 1;
            }

            $contact_type = self::get_postmeta_value( $id, 'type' );
            $columns['contact_type'][] = self::get_encoded_label( 'type', $contact_type );
        }

        // Cache the data
        set_transient( 'dt_advanced_metrics_contacts', $columns, 60 *60 *24 );
        return $columns;
    }

    // Returns the correlation values for a dataset
    public function get_corr_data( $data ) {
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

    // Gets the correlated data for all data columns
    public function get_groups_corr_data() {
        //Check for cached data
        $cached_data = get_transient( 'dt_advanced_metrics_groups_corr' );
        if ( $cached_data ) {
            return $cached_data;
        }

        $data = self::get_groups_data();
        $groups_corr_data = self::get_corr_data( $data );

        // Cache the data
        set_transient( 'dt_advanced_metrics_groups_corr', $groups_corr_data, 60 *60 *24 );

        return $groups_corr_data;
    }

    public function get_contacts_corr_data() {
        //Check for cached data
        $cached_data = get_transient( 'dt_advanced_metrics_contacts_corr' );
        if ( $cached_data ) {
            return $cached_data;
        }

        $data = self::get_contacts_data();
        $contacts_corr_data = self::get_corr_data( $data );

        // Cache the data
        set_transient( 'dt_advanced_metrics_contacts_corr', $contacts_corr_data, 60 *60 *24 );
        return $contacts_corr_data;
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
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' always ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value !== 1 && $value >= 0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' almost always ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value >= 0.75 && $value < 0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' usually ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value >= -0.75 && $value < -0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' seldom ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key === 'corr' && $value !== -1 && $value <= -0.9 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' almost never ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }

                    if ( $key ==='corr' && $value === -1 ) {
                        $insights[] = 'In this particular movement, ' . $definitions[ $corr['col_1'] ] . ' never ' . $definitions_verbs[ $corr['col_2'] ] . '.';
                        $already_mentioned[] = $corr_hash;
                    }
                }
            }
        }
        return $insights;
    }

    public function get_contacts_insights() {
        $correlations = self::get_contacts_corr_data();
        $insights = [];
        $already_mentioned = []; // This array will prevent a/b correlations to show up if its b/a counterpart correlation has already been mentioned.

        $definitions = [
            'gender' => 'contact gender',
            'milestone_has_bible' => 'contacts that have a Bible',
            'milestone_reading_bible' => 'contacts that read their Bible',
            'milestone_belief' => 'contacts that state belief',
            'milestone_can_share' => 'contacts that can share the gospel or a testimony',
            'milestone_sharing' => 'contacts that are sharing the gospel or a testimony',
            'milestone_baptized' => 'contacts that are baptized',
            'milestone_baptizing' => 'contacts that are baptizing others',
            'milestone_in_group' => 'contacts that are in a group or church',
            'milestone_planting' => 'contacts that are planting churches',
            'faith_status_seeker' => 'contacts that are in a spiritual search',
            'faith_status_believer' => 'contacts that are believers',
            'faith_status_leader' => 'spiritual leaders',
            'contact_type_user' => 'DT system users',
            'contact_type_personal' => 'DT system personal contacts',
            'contact_type_access' => 'DT system access contacts',
            'contact_type_placeholder' => 'DT system placeholder contacts',
            'contact_type_create_update_contacts' => 'DT system create update contacts',
            'age_under_18' => 'contacts under the age of 18',
            'age_18_to_25' => 'contacts between the age of 18 and 25',
            'age_26_to_40' => 'contacts between the age of 26 and 40',
            'over_40' => 'contacts over the age of 40',
            'contact_type' => 'contact types',
        ];

        $definitions_verbs = [
            'gender' => 'contact gender',
            'milestone_has_bible' => 'have a Bible',
            'milestone_reading_bible' => 'read their Bible',
            'milestone_belief' => 'state belief',
            'milestone_can_share' => 'can share the gospel or a testimony',
            'milestone_sharing' => 'are sharing the gospel or a testimony',
            'milestone_baptized' => 'are baptized',
            'milestone_baptizing' => 'are baptizing others',
            'milestone_in_group' => 'are in a group or church',
            'milestone_planting' => 'are planting churches',
            'faith_status_seeker' => 'are in a spiritual search',
            'faith_status_believer' => 'are believers',
            'faith_status_leader' => 'are spiritual leaders',
            'contact_type_user' => 'DT system users',
            'contact_type_personal' => 'DT system personal contacts',
            'contact_type_access' => 'DT system access contacts',
            'contact_type_placeholder' => 'DT system placeholder contacts',
            'contact_type_create_update_contacts' => 'DT system create update contacts',
            'age_under_18' => 'are under the age of 18',
            'age_18_to_25' => 'are between the age of 18 and 25',
            'age_26_to_40' => 'are between the age of 26 and 40',
            'over_40' => 'are over the age of 40',
            'contact_type' => 'are contact types',
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
        return $insights;
    }

    // Get the average contact data in order to compare it to a specific contact's progress
    public function get_average_contact_journey_data() {
        $contact_ids = self::get_ids( 'contacts' );
        $today = gmdate( 'Y-m-d H:i:s', time() );
        $all_elapsed_times = null;
        $output = null;

        $output = [];

        $average_times['first_contact_established'] = [
            'label' => 'first_contact_established',
            'name' => 'First contact established',
            'description' => 'The time it takes for a contact to be contacted for the first time',
            'all_times' => null,
        ];
        $average_times['first_no_answer'] = [
            'label' => 'first_no_answer',
            'name' => 'First no-answer',
            'description' => 'The time it takes someone to attempt contacting a contact without an answer',
            'all_times' => null,
        ];
        $average_times['first_meeting_complete'] = [
            'label' => 'first_meeting_complete',
            'name' => 'First meeting complete',
            'description' => 'The time it takes for a contact to have their first meeting after creating the contact',
            'all_times' => null,
        ];
        $average_times['first_contact_to_meeting_complete'] = [
            'label' => 'first_contact_to_meeting_complete',
            'name' => 'First contact to meeting-complete',
            'description' => 'The time it takes for a contact to have their first meeting after the first contact was established',
            'all_times' => null,
        ];
        $average_times['has_bible'] = [
            'label' => 'has_bible',
            'name' => 'Has Bible',
            'description' => 'The average time a contact takes to get a Bible',
            'all_times' => null,
        ];
        $average_times['reading_bible'] = [
            'label' => 'reading_bible',
            'name' => 'Is reading Bible',
            'description' => 'The average time a contact takes to start reading his Bible after receiving it',
            'all_times' => null,
        ];
        $average_times['states_belief'] = [
            'label' => 'states_belief',
            'name' => 'States belief',
            'description' => 'The average time a contact to state belief after their first meeting',
            'all_times' => null,
        ];
        $average_times['can_share_gospel'] = [
            'label' => 'can_share_gospel',
            'name' => 'Can share Gospel',
            'description' => 'The average time a contact to be able to share the gospel or a testimony after stating belief',
            'all_times' => null,
        ];
        $average_times['is_sharing_gospel'] = [
            'label' => 'is_sharing_gospel',
            'name' => 'Is sharing Gospel',
            'description' => 'The average time a contact to be able to actually share the gospel or a testimony after being able to',
            'all_times' => null,
        ];
        $average_times['is_baptized'] = [
            'label' => 'is_baptized',
            'name' => 'Is baptized',
            'description' => 'The average time a contact takes to be baptized after stating belief',
            'all_times' => null,
        ];
        $average_times['is_baptizing'] = [
            'label' => 'is_baptizing',
            'name' => 'Is baptizing',
            'description' => 'The average time a contact takes to be baptizing others after being baptized himself',
            'all_times' => null,
        ];
        $average_times['in_church'] = [
            'label' => 'in_church',
            'name' => 'Is in a church',
            'description' => 'The average time a contact takes to be in a church or group after stating belief',
            'all_times' => null,
        ];
        $average_times['starting_churches_after_stating_belief'] = [
            'label' => 'starting_churches_after_stating_belief',
            'name' => 'Starting churches after stating belief',
            'description' => 'The average time a contact takes to be starting churches after stating belief',
            'all_times' => null,
        ];
        $average_times['starting_churches_after_baptized'] = [
            'label' => 'starting_churches_after_baptized',
            'name' => 'Starting churches after being baptized',
            'description' => 'The average time a contact takes to be starting churches after being baptized',
            'all_times' => null,
        ];
        $average_times['starting_churches_after_in_church'] = [
            'label' => 'starting_churches_after_in_church',
            'name' => 'Starting churches after in a church',
            'description' => 'The average time a contact takes to be starting churches after going to a church himself',
            'all_times' => null,
        ];
        $average_times['seeker_to_believer'] = [
            'label' => 'seeker_to_believer',
            'name' => 'Seeker to believer',
            'description' => 'The average time a seeker takes to become a believer',
            'all_times' => null,
        ];
        $average_times['believer_to_leader'] = [
            'label' => 'believer_to_leader',
            'name' => 'Believer to leader',
            'description' => 'The average time a believer takes to become a leader',
            'all_times' => null,
        ];
        $average_times['seeker_to_leader'] = [
            'label' => 'seeker_to_leader',
            'name' => 'Seeker to leader',
            'description' => 'The average time a seeker takes to become a leader',
            'all_times' => null,
        ];
        $average_times['leader_to_date'] = [
            'label' => 'leader_to_date',
            'name' => 'Leader to date',
            'description' => 'The average time a contact has been a leader',
            'all_times' => null,
        ];

        foreach ( $contact_ids as $id ) {
            $creation_date = self::get_contact_creation_date( $id );
            $seeker_date = self::get_contact_faith_status_date( $id, 'seeker' );
            $believer_date = self::get_contact_faith_status_date( $id, 'believer' );
            $leader_date = self::get_contact_faith_status_date( $id, 'leader' );

            // Contact created until first contact established
            $first_contact_date = self::get_activity_date( $id, 'Added Contact Established: 1' );
            $first_contact_elapsed_seconds = self::elapsed_seconds( $creation_date, $first_contact_date );
            $average_times['first_contact_established']['all_times'][] = $first_contact_elapsed_seconds;

            // Contact created until first no answer
            $first_no_answer_date = self::get_activity_date( $id, 'Added No Answer: 1' );
            $first_no_answer_elapsed_seconds = self::elapsed_seconds( $creation_date, $first_no_answer_date );
            $average_times['first_no_answer']['all_times'][] = $first_no_answer_elapsed_seconds;
            // Contact called until first contact


            // Contact created until first meeting
            $first_meeting_complete_date = self::get_activity_date( $id, 'Added Meeting Complete: 1' );
            $first_meeting_complete_elapsed_seconds = self::elapsed_seconds( $creation_date, $first_meeting_complete_date );
            $average_times['first_meeting_complete']['all_times'][] = $first_meeting_complete_elapsed_seconds;


            // First contact established until first meeting
            $first_contact_to_meeting_complete_elapsed_seconds = self::elapsed_seconds( $first_contact_date, $first_meeting_complete_date );
            $average_times['first_contact_to_meeting_complete']['all_times'][] = $first_contact_to_meeting_complete_elapsed_seconds;

            // Contact created until has Bible
            $has_bible_date = self::get_activity_date( $id, 'Added Faith Milestones: Has Bible' );
            $has_bible_elapsed_seconds = self::elapsed_seconds( $creation_date, $has_bible_date );
            $average_times['has_bible']['all_times'][] = $has_bible_elapsed_seconds;

            // Contact has Bible until read Bible
            $reading_bible_date = self::get_activity_date( $id, 'Added Faith Milestones: Reading Bible' );
            $reading_bible_elapsed_seconds = self::elapsed_seconds( $first_meeting_complete_date, $reading_bible_date );
            $average_times['reading_bible']['all_times'][] = $reading_bible_elapsed_seconds;

            // Contact first meeting complete until states belief
            $states_belief_date = self::get_activity_date( $id, 'Added Faith Milestones: States Belief' );
            $states_belief_elapsed_seconds = self::elapsed_seconds( $first_meeting_complete_date, $states_belief_date );
            $average_times['states_belief']['all_times'][] = $states_belief_elapsed_seconds;

            // Contact states belief until can share gospel or testimony
            $can_share_gospel_date = self::get_activity_date( $id, 'Added Faith Milestones: Can Share Gospel/Testimony' );
            $can_share_gospel_elapsed_seconds = self::elapsed_seconds( $states_belief_date, $can_share_gospel_date );
            $average_times['can_share_gospel']['all_times'][] = $can_share_gospel_elapsed_seconds;

            // Contact actually shares gospel or testimony after being able to
            $is_sharing_gospel_date = self::get_activity_date( $id, 'Added Faith Milestones: Sharing Gospel/Testimony' );
            $is_sharing_gospel_elapsed_seconds = self::elapsed_seconds( $can_share_gospel_date, $is_sharing_gospel_date );
            $average_times['is_sharing_gospel']['all_times'][] = $is_sharing_gospel_elapsed_seconds;

            // Contact has been baptized after stating belief
            $is_baptized_date = self::get_activity_date( $id, 'Added Faith Milestones: Baptized' );
            $is_baptized_elapsed_seconds = self::elapsed_seconds( $states_belief_date, $is_baptized_date );
            $average_times['is_baptized']['all_times'][] = $is_baptized_elapsed_seconds;

            // Contact is baptizing after being baptized himself
            $is_baptizing_date = self::get_activity_date( $id, 'Added Faith Milestones: Baptizing' );
            $is_baptizing_elapsed_seconds = self::elapsed_seconds( $is_baptized_date, $is_baptizing_date );
            $average_times['is_baptizing']['all_times'][] = $is_baptizing_elapsed_seconds;

            // Contact is in group or church after stating belief
            $in_church_date = self::get_activity_date( $id, 'Added Faith Milestones: In Church/Group' );
            $in_church_elapsed_seconds = self::elapsed_seconds( $states_belief_date, $in_church_date );
            $average_times['in_church']['all_times'][] = $in_church_elapsed_seconds;

            // Contact is starting churches after stating belief
            $starting_churches_date = self::get_activity_date( $id, 'Added Faith Milestones: Starting Churches' );
            $starting_churches_after_belief_elapsed_seconds = self::elapsed_seconds( $states_belief_date, $starting_churches_date );
            $average_times['starting_churches_after_stating_belief']['all_times'][] = $starting_churches_after_belief_elapsed_seconds;

            // Contact is starting churches after being baptized
            $starting_churches_after_belief_elapsed_seconds = self::elapsed_seconds( $is_baptized_date, $starting_churches_date );
            $average_times['starting_churches_after_baptized']['all_times'][] = $starting_churches_after_belief_elapsed_seconds;

            // Contact is starting churches after going to church
            $starting_churches_after_in_church_elapsed_seconds = self::elapsed_seconds( $in_church_date, $starting_churches_date );
            $average_times['starting_churches_after_in_church']['all_times'][] = $starting_churches_after_in_church_elapsed_seconds;

            // Seeker is a believer
            $average_times['seeker_to_believer']['all_times'][] = self::elapsed_seconds( $seeker_date, $believer_date );

            // Believer is a leader
            $average_times['believer_to_leader']['all_times'][] = self::elapsed_seconds( $believer_date, $leader_date );

            // Seeker is a leader
            $average_times['seeker_to_leader']['all_times'][] = self::elapsed_seconds( $seeker_date, $leader_date );

            // Leader has been a leader
            $average_times['leader_to_date']['all_times'][] = self::elapsed_seconds( $leader_date, $today );
        }

        // Get all average elapsed times and format them accordingly
        $ouptut = [];
        foreach ( $average_times as $average_time ) {
            $average_seconds = self::process_elapsed_times( $average_time['all_times'] );
            $output[$average_time['label']] = [
                'name' => $average_time['name'],
                'description' => $average_time['description'],
                'average_seconds' => $average_seconds,
                'average_days' => $average_seconds / 86400,
                'formatted_time' => self::seconds_to_time( $average_seconds ),
            ];
        }
        return $output;
    }

    // Returns average contact journey data as text insights
    public function get_average_contact_journey_text() {
        $average_times = self::get_average_contact_journey_data();
        $output = [];

        foreach ( $average_times as $average_time ) {
            $output[] = $average_time['description'] . ' is: ' . $average_time['formatted_time'];
        }
        return $output;
    }

    // Calculate the average elapsed time from all elapsed times
    private function process_elapsed_times( $arr_times ) {
        if ( $arr_times === null || empty( $arr_times ) ) {
            return;
        }
        $arr_times = array_filter( $arr_times );
        $avg_time = array_sum( $arr_times ) / count( $arr_times );
        return $avg_time;
    }

    private function get_contact_creation_date( $contact_id ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
            SELECT post_date FROM
            $wpdb->posts
            WHERE id = %d;", $contact_id )
        );
        return $response;
    }

    // Get the date in which a contact changes their faith status to a specific status
    private function get_contact_faith_status_date( $contact_id, $faith_status ) {
        $faith_status = ucfirst( $faith_status );
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
            SELECT hist_time FROM
            $wpdb->dt_activity_log WHERE
            object_id = %d AND
            object_subtype = 'faith_status' AND
            object_note = CONCAT( 'Added Faith Status: ', %s ) OR
            object_note REGEXP CONCAT( 'Faith Status changed from \".+?\" to \"', %s, '\"' )
            ORDER BY histid DESC
            LIMIT 1;", $contact_id, $faith_status, $faith_status )
        );
        if ( isset( $response ) ) {
            $timestamp = gmdate( 'Y-m-d H:i:s', $response );
            return $timestamp;
        }
    }

    private function get_activity_date( $contact_id, $activity_note ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
                SELECT hist_time
                FROM $wpdb->dt_activity_log
                WHERE object_note = %s
                AND object_id = %d
                ", $activity_note, $contact_id )
        );
        if ( isset( $response ) ) {
            $timestamp = gmdate( 'Y-m-d H:i:s', $response );
            return $timestamp;
        }
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
        $response = $wpdb->get_col(
            $wpdb->prepare( "
                SELECT ID
                FROM wp_posts
                WHERE post_type = %s;", $post_type )
        );
        return $response;
    }

    private function get_meta_value( $post_id, $column_name ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
                SELECT $column_name
                FROM $wpdb->posts
                WHERE ID = %s;", $post_id )
        );
        return $response;
    }

    private function get_postmeta_value( $post_id, $meta_key ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
                SELECT meta_value
                FROM $wpdb->postmeta
                WHERE post_id = %d
                AND meta_key = %s;", $post_id, $meta_key )
        );
        return $response;
    }

    private function check_postmeta_value_exists( $post_id, $meta_value ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
                SELECT meta_value
                FROM $wpdb->postmeta
                WHERE post_id = %d
                AND meta_value = %s;", $post_id, $meta_value )
        );

        $output = 0;
        if ( $wpdb->num_rows > 0 ) {
            $output = 1;
        }

        return $output;
    }

    private function check_postmeta_key_value_exists( $post_id, $meta_key, $meta_value ) {
        global $wpdb;
        $response = $wpdb->get_var(
            $wpdb->prepare( "
                SELECT meta_key, meta_value
                FROM $wpdb->postmeta
                WHERE post_id = %d
                AND meta_key = %s
                AND meta_value = %s;", $post_id, $meta_key, $meta_value )
        );

        $output = 0;
        if ( $wpdb->num_rows > 0 ) {
            $output = 1;
        }

        return $output;
    }

    // Get encoded label from postmeta key
    // This function is currently not being used as one-hotting is considered more efficient for correlating the data
    private function get_encoded_label( $meta_key, $meta_value ) {
        global $wpdb;

        $distinct_values = $wpdb->get_col(
            $wpdb->prepare( "
                SELECT DISTINCT( meta_value )
                FROM $wpdb->postmeta
                WHERE meta_key = %s;", $meta_key )
        );
        // Get all label types
        asort( $distinct_values );
        $distinct_values = array_values( $distinct_values );

        $encoded_label = array_search( $meta_value, $distinct_values );
        return $encoded_label;
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

    private function elapsed_time( $start_date, $end_date ) {
        if ( $start_date === null || empty( $start_date ) || $end_date === null || empty( $end_date ) ) {
            return;
        }
        $start_date = new DateTime( $start_date );
        $end_date = new DateTime( $end_date );
        $elapsed_time = date_diff( $start_date, $end_date, true );
        return $elapsed_time;
    }

    private function elapsed_seconds( $start_date, $end_date ) {
        $elapsed_time = self::elapsed_time( $start_date, $end_date );
        if ( $elapsed_time === null || empty( $elapsed_time ) ) {
            return;
        }
        $total_seconds = $elapsed_time->y * 31556926;
        $total_seconds += $elapsed_time->m * 2629743;
        $total_seconds += $elapsed_time->d * 6400;
        $total_seconds += $elapsed_time->h * 3600;
        $total_seconds += $elapsed_time->i * 60;
        $total_seconds += $elapsed_time->s;
        return $total_seconds;
    }


    private function seconds_to_time( $seconds ) {
        $secs = $seconds % 60;
        $mins = floor( ( $seconds % 3600 ) / 60 );
        $hours = floor( ( $seconds % 86400 ) / 3600 );
        $days = floor( ( $seconds % 2592000 ) / 86400 );
        $months = floor( $seconds / 2592000 );

        return "$months months, $days days, $hours hours, $mins minutes, $secs seconds";
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
