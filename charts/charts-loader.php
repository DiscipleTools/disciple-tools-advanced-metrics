<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Advanced_Metrics_Charts
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){

        require_once( 'one-page-chart-template.php' );
        new DT_Advanced_Metrics_Chart_Template();

        require_once( 'activity.php' );
        require_once( 'streams.php' );
        new DT_Advanced_Metrics_Chart_Activity();
        new DT_Advanced_Metrics_Chart_Streams();

        /**
         * @todo add other charts like the pattern above here
         */

        require_once( 'contact-location-by-un-region.php' );
        new DT_Advanced_Metrics_Chart_Contact_Location_By_Country();
    } // End __construct

    // Drops personal and placeholder contacts the current user cannot view from drill-down rows.
    public static function remove_hidden_private_contacts( $rows ){
        if ( empty( $rows ) || !is_array( $rows ) ){
            return $rows;
        }
        update_meta_cache( 'post', array_map( 'intval', wp_list_pluck( $rows, 'id' ) ) );

        return array_values( array_filter( $rows, function ( $row ){
            $post_id = (int) $row['id'];
            $type = get_post_meta( $post_id, 'type', true );
            return !in_array( $type, [ 'personal', 'placeholder' ], true ) || DT_Posts::can_view( 'contacts', $post_id );
        } ) );
    }
}
DT_Advanced_Metrics_Charts::instance();
