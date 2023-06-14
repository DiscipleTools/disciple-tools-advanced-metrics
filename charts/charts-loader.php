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

        require_once( 'contact-location-by-country.php' );
        new DT_Advanced_Metrics_Chart_Contact_Location_By_Country();

    } // End __construct
}
DT_Advanced_Metrics_Charts::instance();
