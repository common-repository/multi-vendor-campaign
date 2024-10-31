<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/includes
 * @author     Your Name <email@example.com>
 */
class Multi_Vendor_Campaign {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Multi_Vendor_Campaign_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'MULTI_VENDOR_CAMPAIGN_VERSION' ) ) {
			$this->version = MULTI_VENDOR_CAMPAIGN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'multi-vendor-campaign';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Multi_Vendor_Campaign_Loader. Orchestrates the hooks of the plugin.
	 * - Multi_Vendor_Campaign_i18n. Defines internationalization functionality.
	 * - Multi_Vendor_Campaign_Admin. Defines all hooks for the admin area.
	 * - Multi_Vendor_Campaign_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-multi-vendor-campaign-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-multi-vendor-campaign-i18n.php';

        /**
         * The class responsible for defining core functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-multi-vendor-campaign-campaigns.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-multi-vendor-campaign-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-multi-vendor-campaign-public.php';


        /**
         * The utilities and helper functions
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/utilities.php';

		$this->loader = new Multi_Vendor_Campaign_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Multi_Vendor_Campaign_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Multi_Vendor_Campaign_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
        $wc_version=  $this->get_wc_version();

		$plugin_admin = new Multi_Vendor_Campaign_Admin( $this->get_multi_vendor_campaign(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );


		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu', 100 );
		if(class_exists('WCMp')){
            $this->loader->add_filter('wcmp_vendor_dashboard_nav', $plugin_admin, "wc_market_dashboard_nav"); //WC marketplace
        }

        if(class_exists('WeDevs_Dokan')){
            $this->loader->add_filter( 'dokan_query_var_filter', $plugin_admin, 'dokan_load_document_menu' );
            $this->loader->add_filter( 'dokan_get_dashboard_nav', $plugin_admin, 'dokan_add_campaigns_menu' );
            $this->loader->add_filter( 'dokan_rewrite_rules_loaded', $plugin_admin, 'dokan_rewrite_urls' );
            $this->loader->add_action( 'dokan_load_custom_template', $plugin_admin, 'dokan_load_template' );
        }

        if(in_array($GLOBALS['pagenow'], array('media-upload.php'))) {
            $this->loader->add_filter('gettext', $plugin_admin, "replace_thickbox_text"  , 1, 3 );

        }

        $this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'admin_footer_text' , 1 );

        $this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'admin_footer_text' , 1 );
        $this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'wc_order_campaign_column',85 );
        $this->loader->add_filter( 'manage_shop_order_posts_custom_column', $plugin_admin, 'wc_order_campaign_column_content',85 );

        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'wc_order_campaign_add_meta_boxes' );


        //core hooks
        $plugin_core = new Multi_Vendor_Campaign_Campaigns();

        //Save campaign_ids to orders
        $this->loader->add_action('woocommerce_payment_complete', $plugin_core, "save_order_metadata", 99, 1);


        // WC vendor
				// Free version
				$this->loader->add_filter('wcv_commission_rate_percent', $plugin_core, "wcv_commission_rate_percent", 99, 2);
				// Pro version
				$this->loader->add_filter('wcvendors_commission_args', $plugin_core, "wcv_pro_commission_rate_percent", 99, 3);
        
        //WC marketplace
        $this->loader->add_filter('wcmp_get_commission_amount', $plugin_core, "wc_marketplace_commission_rate_percent" , 99, 6);
        $this->loader->add_filter('vendor_commission_amount', $plugin_core, "wc_marketplace_variable_commission" , 99, 6);

        //Dokan Multi vendor
        $this->loader->add_filter('dokan_get_earning_by_product' , $plugin_core, "dokan_commission_rate_percent", 99, 3);

        //Yith Multi vendor
        $this->loader->add_filter('yith_wcmv_product_commission', $plugin_core, "yith_commission_rate_percent",99,5);
        $this->loader->add_action( 'yith_wcmv_after_single_register_commission', $plugin_core, "yith_commission_rate_display",99,4 );


        // discount hooks
        if( version_compare( $wc_version, "3.0.1", ">=" ) )
        {
            $this->loader->add_filter( 'woocommerce_product_get_sale_price', $plugin_core, 'wc_get_sale_price', 99, 2 );
            $this->loader->add_filter( 'woocommerce_product_get_price', $plugin_core, 'wc_get_regular_price', 99, 2 );

            //Variations prices
            $this->loader->add_filter( 'woocommerce_product_variation_get_price', $plugin_core, 'wc_get_regular_price', 99, 2 );
            $this->loader->add_filter( 'woocommerce_product_variation_get_sale_price', $plugin_core, 'wc_get_sale_price', 99, 2 );

        }
        else
        {
            $this->loader->add_filter( 'woocommerce_get_sale_price', $plugin_core, 'wc_get_sale_price', 99, 2 );
            $this->loader->add_filter( 'woocommerce_get_price', $plugin_core, 'wc_get_regular_price', 99, 2 );
        }
        //
        $this->loader->add_action( 'wp_ajax_search_products_by_title', $plugin_core, 'search_products_by_title' );
        $this->loader->add_action( 'wp_ajax_subscribe_products_to_campaign', $plugin_core, 'subscribe_products_to_campaign' );
        $this->loader->add_action( 'wp_ajax_update_campaign_product_status', $plugin_core, 'update_campaign_product_status' );
        $this->loader->add_action( 'wp_ajax_unsubscribe_product', $plugin_core, 'unsubscribe_product' );




    }

    function get_wc_version() {
        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            return NULL;
        }
    }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Multi_Vendor_Campaign_Public( $this->get_multi_vendor_campaign(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'widgets_init', $plugin_public, 'register_campaign_filter_widget' );
		$this->loader->add_action('woocommerce_product_query', $plugin_public, 'filter_campaign_products_query');
		$this->loader->add_action( 'woocommerce_before_shop_loop_item_title',$plugin_public, 'add_badge' );
		$this->loader->add_action( 'woocommerce_before_single_product_summary',$plugin_public, 'add_badge' );
		
		$this->loader->add_filter( 'the_content', $plugin_public, 'single_product_campaign_description' );
		$this->loader->add_filter( 'posts_orderby', $plugin_public, 'order_by_change',99,2 );
		$this->loader->add_filter( 'post_class', $plugin_public, 'add_product_class_name' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_multi_vendor_campaign() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Multi_Vendor_Campaign_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
