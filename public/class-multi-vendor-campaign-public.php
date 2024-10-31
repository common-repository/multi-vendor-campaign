<?php

require_once( 'class-multi-vendor-campaign-filter-widget.php' );

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/public
 * @author     Your Name <email@example.com>
 */
class Multi_Vendor_Campaign_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name . '-public', plugin_dir_url( dirname( __FILE__ ) ) . '/assets/front/css/public.css', array(), $this->version, 'all' );




		if(class_exists('WeDevs_Dokan') && !is_admin() &&  rad_mvc_is_dokan_vendor_dashboard(get_the_ID() ) ){
        	//load admin css & js files in front-end to handle vendor panel of Dokan
        	wp_enqueue_style( $this->plugin_name . '-shared', plugin_dir_url( dirname( __FILE__ ) ) . '/assets/shared/css/shared.css', array(), $this->version, 'all' );
        }

	}

    public function enqueue_scripts() {

		if(class_exists('WeDevs_Dokan') && !is_admin() &&  rad_mvc_is_dokan_vendor_dashboard(get_the_ID() ) ){
	        wp_enqueue_script( $this->plugin_name . '-shared', plugin_dir_url( dirname( __FILE__ ) ) . '/assets/shared/js/shared.js', array( 'jquery' ), $this->version, false );
	        wp_localize_script( $this->plugin_name. '-shared', 'rad_mvc_object',
	            array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'security'  => wp_create_nonce( 'rad-mvc-security-nonce' ) ) );

	    }
    }


    public function order_by_change( $order_by, $query )
    {

        global $wpdb;

        if( function_exists('WC') && version_compare(WC()->version, 3.0) <= 0 )
            return $order_by;

        $is_product_query = (!empty($query->query_vars[ 'post_type' ]) && $query->query_vars[ 'post_type' ] == 'product') || is_tax(  get_object_taxonomies( 'product', 'names' ) );

        if( $query->is_main_query() && $query->is_archive && $is_product_query ) {

        	if( is_shop() ) //shop page
        	{
        		$campaign_position = 'shop';
        	}
        	elseif( is_tax() ) // category pages
        	{
        		$campaign_position = 'category';
        	}

        	$campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        	$campaignProducts = $campaignsClass->get_approved_products($campaign_position);

	        if(!$campaignProducts)
	            return $order_by;


            foreach ($campaignProducts as $product){
                $feture_product_id[] = $product->product_id;
            }

            if( is_array( $feture_product_id ) && !empty($feture_product_id)  )
            {

                if( empty( $order_by ) ) {
                    $order_by =  "FIELD(". $wpdb->base_prefix ."posts.ID,'".implode("','",$feture_product_id)."') DESC ";
                }
                else
                {
                    $order_by =  "FIELD(". $wpdb->base_prefix ."posts.ID,'".implode("','",$feture_product_id)."') DESC, " . $order_by;
                }
            }
        }

        return $order_by;

    }

    public function add_badge(){
        global $product;

        $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        $badge = $campaignsClass->get_badge_status($product->get_id());

        if ( $badge->badge && $badge->badge_text ) {
					if ( is_shop() && $badge->badge == 1 ) {
						echo '<span class="rad-mvc-badge">' . esc_html($badge->badge_text) . '</span>';
					} 

					do_action( 'mvc_badge', $badge );
        }
    }

		public function single_product_campaign_description( $content ) {

			if ( function_exists( 'is_product' ) && is_product() ) {

				$campaign_plugin = new Multi_Vendor_Campaign_Campaigns();
				$product_campaign = $campaign_plugin->get_campaign_of_product( get_the_ID() );

				if ( $product_campaign ) {
					$content .= $product_campaign->description;
				}

			}

			return $content;
		}

    public function add_product_class_name( $classes ) {
        global $post;

        $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        $class_name = $campaignsClass->get_class_name($post->ID);


        if ( $class_name->class_name ) {
            $classes[] = $class_name->class_name;
        }

        return $classes;
    }

		public function register_campaign_filter_widget() {

			register_widget( 'Multi_Vendor_Campaign_Filter_Widget' );

		}

		function filter_campaign_products_query( $query ) {
 
			$campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_SANITIZE_NUMBER_INT);

			// Get all products if campaign_id is not set
			if ( $campaign_id == -1 ) return;

			if ( isset( $campaign_id ) ) {

				$campaign = new Multi_Vendor_Campaign_Campaigns();
				$products = $campaign->get_products_of_campaign( $campaign_id );

				if ( !$products ) {
					$products = array( -1 );
				}
	
				$query->set( 'post__in', (array) $products );
				
			}
	}
}
