<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Multi_Vendor_Campaign
 * @subpackage Multi_Vendor_Campaign/admin
 * @author     Your Name <email@example.com>
 */

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require_once ( 'tables/campaign-list.php' );
require_once ( 'tables/campaign-order-list.php' );
require_once ( 'tables/campaign-product-list.php' );
require_once ( 'tables/campaign-products-list.php' );

class Multi_Vendor_Campaign_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
        wp_enqueue_style('thickbox');
		wp_enqueue_style( $this->plugin_name . '-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin/css/admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . '-shared', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/shared/css/shared.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
		wp_enqueue_script( $this->plugin_name . '-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin/js/admin.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name. '-admin', 'rad_mvc_object',
            array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'security'  => wp_create_nonce( 'rad-mvc-security-nonce' ) ) );

        wp_enqueue_script( $this->plugin_name . '-shared', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/shared/js/shared.js', array( 'jquery' ), $this->version, false );

	}

    function replace_thickbox_text($translated_text, $text, $domain)
    {
        if ('Insert into Post' == $text) {

            $texts = $this->get_mediaBox_strings();

            foreach($texts as $key => $value)
            {
                $referer = strpos( wp_get_referer(), $key );

                if ( $referer !== false ) {
                    return $value;
                }

            }

        }

        return $translated_text;
    }

    function get_mediaBox_strings()
    {
        return array(
            'rad_mvc_campaigns' => esc_html__('Insert Image to Campaign', 'multi_vendor_campaign' ),
        );
    }

	/**
	 * Show list of campaigns
	 *
	 * @since    1.0.0
	 */
	public function add_menu() {
		add_menu_page(
	        esc_html__( 'Campaigns', 'multi_vendor_campaign' ),
            esc_html__('Multi Vendor Campaign', 'multi_vendor_campaign' ).$this->new_pneding_products_for_admin(),
	        'manage_options',
	        $this->plugin_name . '-campaigns',
			array( $this, 'show_campaigns' ),
	        plugins_url( '../assets/admin/images/icon.png', __FILE__ ),
	        6
    	);

        add_submenu_page(
                $this->plugin_name . '-campaigns',
                esc_html__( 'Campaigns', 'multi_vendor_campaign' ),
                esc_html__( 'Campaigns', 'multi_vendor_campaign' ),
                'manage_options',
                $this->plugin_name . '-campaigns',
                array( $this, 'show_campaigns' )
        );
        add_submenu_page(
            $this->plugin_name . '-campaigns',
            esc_html__( 'New Campaign', 'multi_vendor_campaign' ),
            esc_html__( 'New Campaign', 'multi_vendor_campaign' ),
            'manage_options',
            $this->plugin_name . '-new-campaigns',
            array( $this, 'add_new_campaign' )
        );

        add_submenu_page(
            $this->plugin_name . '-campaigns',
            esc_html__( 'Products', 'multi_vendor_campaign' ),
            esc_html__( 'Products', 'multi_vendor_campaign' ),
            'manage_options',
            $this->plugin_name . '-subscribed-products',
            array( $this, 'all_subscribed_products' )
        );

        add_submenu_page(
            $this->plugin_name . '-campaigns',
            esc_html__( 'Orders', 'multi_vendor_campaign' ),
            esc_html__( 'Orders', 'multi_vendor_campaign' ),
            'manage_options',
            $this->plugin_name . '-orders',
            array( $this, 'show_orders' )
        );

        // add_submenu_page(
        //     $this->plugin_name . '-campaigns',
        //     esc_html__( 'About Us', 'multi_vendor_campaign' ),
        //     esc_html__( 'About Us', 'multi_vendor_campaign' ),
        //     'manage_options',
        //     $this->plugin_name . '-about-us',
        //     array( $this, 'about_us_page' )
        // );

        add_submenu_page(
            null,
            esc_html__( 'Add Campaign', 'multi_vendor_campaign' ),
            esc_html__( 'Add Campaign', 'multi_vendor_campaign' ),
            'manage_options',
            $this->plugin_name . '-campaign-products',
            array( $this, 'show_campaign_products' )
        );

        $user = wp_get_current_user();

        if ( (class_exists('WC_Vendors') && in_array( 'vendor', (array) $user->roles ))
            || (class_exists('WeDevs_Dokan') && in_array( 'seller', (array) $user->roles ))
            || (class_exists('WCMp') && in_array( 'dc_vendor', (array) $user->roles ))
            || (class_exists('YITH_Vendors') && in_array( 'yith_vendor', (array) $user->roles ))) {

            add_menu_page(
                esc_html__( 'Campaigns', 'multi_vendor_campaign' ),
                esc_html__('Campaigns', 'multi_vendor_campaign'). $this->new_campaigns_for_vendor(),
                'edit_products',
                $this->plugin_name . '-user-campaigns',
                array( $this, 'show_campaigns_to_vendor' ),
                'dashicons-awards',
                100
            );

        }
    }

    public function get_campaign_positions() {
        return apply_filters(
            'mvc_campaign_positions', 
            [
                'shop' => esc_html__( 'Shop Page', 'multi_vendor_campaign' ),
                'category' => esc_html__( 'Product Category Page', 'multi_vendor_campaign' )
            ]
        );
    }


    public function add_new_campaign(){

        global $wpdb;
        $table_name = $wpdb->base_prefix . 'rad_mvc_table_campaign'; // do not forget about tables prefix

        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $item = array(
            'id' => isset($_REQUEST['id'])? intval($_REQUEST['id']):0,
            'title' => isset($_REQUEST['title'])? sanitize_text_field($_REQUEST['title']):'',
            'description' => isset($_REQUEST['description'])? stripslashes_deep($_REQUEST['description']):'',
            'commission_fixed' => isset($_REQUEST['commission_fixed'])? floatval($_REQUEST['commission_fixed']):0,
            'commission_fee' => isset($_REQUEST['commission_fee'])? floatval($_REQUEST['commission_fee']):0,
            'commission_percentage' => isset($_REQUEST['commission_percentage'])? floatval($_REQUEST['commission_percentage']):0,
            'discount_value' => isset($_REQUEST['discount_value'])? intval($_REQUEST['discount_value']):0,
            'activation_date' => isset($_REQUEST['activation_date'])? sanitize_text_field($_REQUEST['activation_date']):strftime('%Y-%m-%dT%H:%M:%S', strtotime(date('Y-m-d H:i:s'))),
            'deactivation_date' => isset($_REQUEST['deactivation_date'])? sanitize_text_field($_REQUEST['deactivation_date']):strftime('%Y-%m-%dT%H:%M:%S', strtotime(date('Y-m-d H:i:s',strtotime('+1 years')))),
            'subscribe_start_date' => isset($_REQUEST['subscribe_start_date'])? sanitize_text_field($_REQUEST['subscribe_start_date']):strftime('%Y-%m-%dT%H:%M:%S', strtotime(date('Y-m-d H:i:s'))),
            'subscribe_end_date' => isset($_REQUEST['subscribe_end_date'])? sanitize_text_field($_REQUEST['subscribe_end_date']):strftime('%Y-%m-%dT%H:%M:%S', strtotime(date('Y-m-d H:i:s',strtotime('+1 years')))),
            'number_of_vendors' => isset($_REQUEST['number_of_vendors'])? intval($_REQUEST['number_of_vendors']):1,
            'number_of_vendor_products' => isset($_REQUEST['number_of_vendor_products'])? intval($_REQUEST['number_of_vendor_products']):1,
            'campaign_position' => isset($_REQUEST['campaign_position'])? sanitize_text_field($_REQUEST['campaign_position']):'',
            'rule_type' => isset($_REQUEST['rule_type'])? sanitize_text_field($_REQUEST['rule_type']):'',
            'rule_operator' => isset($_REQUEST['rule_operator'])? $this->sanitize_rule_operator($_REQUEST['rule_operator']):'>',
            'rule_value' => isset($_REQUEST['rule_value'])? floatval($_REQUEST['rule_value']):0,
            'out_of_stack' => isset($_REQUEST['out_of_stack'])? intval($_REQUEST['out_of_stack']):0,
            'enable' => isset($_REQUEST['enable'])? intval($_REQUEST['enable']):1,
            'thumbnail' => isset($_REQUEST['thumbnail'])? sanitize_text_field($_REQUEST['thumbnail']):'',
            'badge' => isset($_REQUEST['badge'])? intval($_REQUEST['badge']):0,
            'badge_text' => isset($_REQUEST['badge_text'])? sanitize_text_field($_REQUEST['badge_text']):'',
            'class_name' => isset($_REQUEST['class_name'])? sanitize_html_class($_REQUEST['class_name']):'',
        );


        // here we are verifying does this request is post back and have correct nonce
        if (isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $item_valid = $this->validate_campaign_data($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if ($result) {
                        $message = esc_html__('Campaign was successfully saved', 'multi_vendor_campaign');
                    } else {
                        $notice = esc_html__('There was an error while saving campaign', 'multi_vendor_campaign');
                    }
                } else {
                    $result = $wpdb->update($table_name, $item, array('id' => $item['id']), array('%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%f', '%d', '%d', '%s','%d', '%s', '%s'));
                    if ($result === false) {
                        $notice =  esc_html__('There was an error while updating campaign', 'multi_vendor_campaign');
                    } else {
                        $message = esc_html__('Campaign was successfully updated', 'multi_vendor_campaign');
                    }
                }
            } else {
                // if $item_valid not true it contains error message(s)
                $notice = $item_valid;
            }
        }
        else {

            // if this is not post back we load item to edit or give new one to create
            if (isset($_REQUEST['id'])) {
                $item = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE id = %d", 
                        intval( $_REQUEST['id'] )
                    )
                    , ARRAY_A
                );
                if (!$item) {
                    $notice = esc_html__('Campaign not found', 'multi_vendor_campaign');
                } else {
                    // Change the page title dynamically based on campaign title to avoid confusion
                    ?><script>document.title = "<?php echo esc_attr($item['title']).' < '.str_replace('-', ' ', ucwords($this->plugin_name, '-')) ?>";</script><?
                }
            }
        }

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php esc_html_e('Campaign', 'multi_vendor_campaign')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $this->plugin_name . '-campaigns');?>"><?php _e('Back to List', 'multi_vendor_campaign')?></a>
            </h2>

            <?php if (!empty($notice)) { ?>
                <div id="notice" class="error"><p><?php echo esc_html( $notice ) ?></p></div>
            <?php } ?>
            <?php if (!empty($message)) { ?>
                <div id="message" class="updated"><p><?php echo esc_html( $message ) ?></p></div>
            <?php } ?>

            <?php if ($item) { ?>


                <form id="form" method="POST">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                    <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>
                        <div id="post-body" class="rad-mvc-form">
                            <label for="title"><?php esc_html_e('Campaign Name', 'multi_vendor_campaign'); ?> </label>
                            <input name="title" type="text" id="title" value="<?php echo esc_attr( $item['title'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required>
                            <div class="post-body-content">
                                <table cellspacing="3" cellpadding="5" class="form-table">
                                    <tr class="form-field">
                                        <td colspan="2"><h2><?php esc_html_e('General Settings', 'multi_vendor_campaign'); ?></h2></td>
                                        <td class="help"></td>
                                    </tr>
                                    <!--enable-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="enable"><?php esc_html_e('Enable', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><select name="enable" id="enable">
                                                <option value="1" <?php selected( $item['enable'], "1" ); ?>><?php esc_html_e('Yes', 'multi_vendor_campaign'); ?></option>
                                                <option value="0" <?php selected( $item['enable'], "0" ); ?>><?php esc_html_e('No', 'multi_vendor_campaign'); ?></option>
                                            </select>
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Disable or enable the campaign manually', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--campaign description-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="description"><?php esc_html_e('Description', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><textarea name="description" id="description" aria-required="true" autocapitalize="none" autocorrect="off" rows="5" /><?php echo esc_textarea( $item['description'] ); ?></textarea></td>
                                        <td class="help"><p><?php esc_html_e('Enter your description about this campaign. Your description will be displayed on vendors panel.', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--thumbnail-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="thumbnail"><?php esc_html_e('Campaign Image', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="thumbnail" type="url" id="thumbnail" value="<?php echo esc_attr( $item['thumbnail'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" />
                                            <input name="upload_thumb_button" id="upload_thumb_button" type="button" class="button" value="<?php esc_attr_e( 'Upload thumbnail image', 'multi_vendor_campaign' ); ?>" />
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Choose an image to display along with campaign in vendors panel.', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="post-body-content">
                                <table cellspacing="3" cellpadding="5" class="form-table">
                                    <tr class="form-field">
                                        <td colspan="2"><h2><?php esc_html_e('Campaign Settings', 'multi_vendor_campaign'); ?></h2></td>
                                        <td class="help"></td>
                                    </tr>
                                    <!--subscriptionStartDate-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="subscribe_start_date"><?php esc_html_e('Subscription Start Date', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="subscribe_start_date" type="datetime-local" id="subscribe_start_date" value="<?php echo strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['subscribe_start_date'])) ; ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('The beginning date to start product subscription by vendors', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--subscriptionEndDate-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="subscribe_end_date"><?php esc_html_e('Subscription End Date', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="subscribe_end_date" type="datetime-local" id="subscribe_end_date" value="<?php echo strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['subscribe_end_date'])) ; ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('The ending date to end product subscription by vendors', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                   <!--activationDate-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="activation_date"><?php esc_html_e('Activation Date', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="activation_date" type="datetime-local" id="activation_date" value="<?php echo strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['activation_date'])) ; ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('The start date of displaying products', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--deactivationDate-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="deactivation_date"><?php esc_html_e('Deactivation Date', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="deactivation_date" type="datetime-local" id="deactivation_date" value="<?php echo strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['deactivation_date'])) ; ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('The end date of displaying products', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                    <!-- campaignPosition -->
                                    <tr class="form-field">
                                        <th scope="row"><label for="campaign_position"><?php esc_html_e('Display Position', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields">
                                            <select name="campaign_position" id="campaign_position">
                                                <?php foreach( $this->get_campaign_positions() as $key => $value ) : ?>
                                                    <option value="<?php echo esc_attr( $key ) ?>" <?php selected($item['campaign_position'], $key) ?>><?php echo esc_html( $value ) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="help">
                                            <p>
                                            <?php esc_html_e('Where do you want to display the products?', 'multi_vendor_campaign'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                    <!--numberOfVendorProducts-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="number_of_vendor_products"><?php esc_html_e('Number of Products for Each Vendor', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="number_of_vendor_products" type="number" id="number_of_vendor_products" value="<?php echo esc_attr( $item['number_of_vendor_products'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('Enter the number of product that each vendor can subscribe.', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="post-body-content">
                                <table cellspacing="3" cellpadding="5" class="form-table">
                                    <tr>
                                        <td colspan="2"><h2><?php esc_html_e('Commission Settings', 'multi_vendor_campaign'); ?></h2></td>
                                        <td class="help"></td>
                                    </tr>
                                    <?php
                                    if (class_exists('WCMp')) { 
                                        global $WCMp;

                                        $sharing_mode = "vendor";
                                        $positive_class = 'positive';
                                        $negative_class = 'negative';

                                        $sharing_mode_text = esc_html__("Vendor Commissions", 'multi_vendor_campaign');
                                        $sharing_mode_help = esc_html__("Vendor Commission is what you, the site admin, pay the vendor.", 'multi_vendor_campaign');

                                        if ($WCMp->vendor_caps->payment_cap['revenue_sharing_mode'] == 'admin') {
                                            $sharing_mode_text = esc_html__("Admin Fees", 'multi_vendor_campaign');
                                            $sharing_mode_help = esc_html__("Admin Fees is what you, the site admin, charge.", 'multi_vendor_campaign');
                                            $sharing_mode = "admin";
                                            $positive_class = 'negative';
                                            $negative_class = 'positive';
                                        }

                                        ?>
                                        <tr class="form-field">
                                            <th scope="row"><label><?php esc_html_e("Mode", 'multi_vendor_campaign'); ?> </label></th>
                                            <th scope="row"><label><?php echo esc_html( $sharing_mode_text ); ?> </label></th>
                                            <td class="help"><p><?php echo esc_html( $sharing_mode_help ); ?></p></td>
                                        </tr>
                                        <!--commissionPercentage-->
                                        <?php
                                        $commission_type = $WCMp->vendor_caps->payment_cap['commission_type'];
                                        $has_fixed_commission = [ 'fixed', 'fixed_with_percentage', 'fixed_with_percentage_qty', 'commission_by_product_price', 'commission_by_purchase_quantity' ];
                                        $has_percentage_commission = [ 'percent', 'fixed_with_percentage', 'fixed_with_percentage_qty', 'commission_by_product_price', 'commission_by_purchase_quantity', 'fixed_with_percentage_per_vendor' ];


                                        if ( in_array( $commission_type, $has_percentage_commission ) ) {
                                        ?>
                                            <tr class="form-field">
                                                <th scope="row"><label for="commission_percentage"><?php esc_html_e('Extra Commission Percentage', 'multi_vendor_campaign'); ?></label></th>
                                                <td class="fields"><input name="commission_percentage" type="number" id="commission_percentage" value="<?php echo esc_attr( $item['commission_percentage'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                                                    <span class="input_number_label">
                                                        <span class="<?php echo esc_attr( $negative_class ) ?>"><?php esc_html_e('You, the site admin, get', 'multi_vendor_campaign'); ?></span>
                                                        <span class="<?php echo esc_attr( $positive_class ) ?>"><?php esc_html_e('Vendors get', 'multi_vendor_campaign'); ?></span>
                                                        <span class="value"><?php echo esc_attr( $item['commission_percentage'] ); ?></span>
                                                        <span class="more"><?php esc_html_e('% more', 'multi_vendor_campaign'); ?></span>
                                                    </span>
                                                </td>
                                                <td class="help">
                                                    <p><?php esc_html_e('The Commission percentage that be added to original commission percentage. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        <?php
                                        } 
                                        
                                        if ( in_array( $commission_type, $has_fixed_commission ) ) {
                                        ?>
                                            <!--commissionFee-->
                                            <tr class="form-field">
                                                <th scope="row"><label for="commission_fixed"><?php esc_html_e('Extra Commission Fixed Amount', 'multi_vendor_campaign'); ?></label></th>
                                                <td class="fields"><input name="commission_fixed" type="number" id="commission_fixed" value="<?php echo esc_attr( $item['commission_fixed'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                                                    <span class="input_number_label">
                                                        <span class="<?php echo esc_attr( $negative_class ); ?>">
                                                            <?php
                                                            esc_html_e('You, the site admin, get', 'multi_vendor_campaign');
                                                            echo ' ' . get_woocommerce_currency_symbol();
                                                            ?>
                                                        </span>
                                                        <span class="<?php echo esc_attr( $positive_class ) ?>">
                                                            <?php esc_html_e('Vendors get', 'multi_vendor_campaign');
                                                            echo ' ' . get_woocommerce_currency_symbol();
                                                            ?>
                                                        </span>
                                                        <span class="value"><?php echo esc_attr( $item['commission_fixed'] ); ?></span>
                                                        <span class="more"><?php esc_html_e('more', 'multi_vendor_campaign'); ?></span>
                                                    </span>
                                                </td>
                                                <td class="help"><p><?php esc_html_e('Add an extra commision fixed amount to original fixed value. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?></p></td>
                                            </tr>
                                        <?php
                                        }

                                        if ( !in_array( $commission_type, array_merge( $has_fixed_commission, $has_percentage_commission ) ) ) {
                                            ?>
                                            <tr class="form-field">
                                                <th scope="row"><label for="commission_fee">
                                                    <span><?php esc_html_e("Commission type is not supported.", 'multi_vendor_campaign') ?></span>
                                                </th>
                                                <td class="fields">
                                                </td>
                                                <td class="help">
                                                    <p><?php esc_html_e("Supported commission types: Percentage, Fixed, %age + Fixed (per transaction), and %age + Fixed (per unit).", 'multi_vendor_campaign') ?></p>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    elseif(class_exists('WeDevs_Dokan'))
                                    { 
                                        $commision_type = dokan_get_commission_type();
                                        if ( $commision_type == "percentage" || $commision_type == "flat" ) {
                                        ?>
                                        <!--commissionFee-->
                                        <tr class="form-field">
                                            <th scope="row"><label for="commission_fee"><?php esc_html_e('Extra Commission', 'multi_vendor_campaign'); ?></label></th>
                                            <td class="fields"><input name="commission_fee" type="number" id="commission_fee" value="<?php echo esc_attr( $item['commission_fee'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                                                <span class="input_number_label">
                                                    <span class="negative"><?php esc_html_e('Vendors get ', 'multi_vendor_campaign'); ?></span>
                                                    <span class="positive"><?php esc_html_e('You, the site admin, get ', 'multi_vendor_campaign'); ?></span>
                                                    <span class="value"><?php echo esc_attr( $item['commission_fee'] ); ?></span>
                                                    <span class="more"><?php esc_html_e('more units', 'multi_vendor_campaign'); ?></span>
                                                </span>

                                            </td>
                                            <td class="help">
                                                <p><?php esc_html_e('Add an extra commision to original commission. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                        else {
                                            ?>
                                            <tr class="form-field">
                                                <th scope="row"><label for="commission_fee">
                                                    <span><?php esc_html_e("Commission type is not supported.", 'multi_vendor_campaign') ?></span>
                                                </th>
                                                <td class="fields">
                                                </td>
                                                <td class="help">
                                                    <p><?php esc_html_e("Supported commission types: Percentage, and Flat.", 'multi_vendor_campaign') ?></p>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    // WC Vendors
                                    elseif(class_exists('WCVendors') || class_exists('WCVendors_Pro'))
                                    {
                                        $this->render_commission_inputs_for_wcv( $item );
                                    }
                                    else // YITH vendors (Free & premium)
                                    {
                                    ?>
                                        <!--commissionPercentage-->
                                        <tr class="form-field">
                                            <th scope="row"><label for="commission_percentage"><?php esc_html_e('Extra Commission Percentage', 'multi_vendor_campaign'); ?></label></th>
                                            <td class="fields"><input name="commission_percentage" type="number" id="commission_percentage" value="<?php echo esc_attr( $item['commission_percentage'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                                            <span class="input_number_label">
                                                <span class="negative"><?php esc_html_e('You, the site admin, get', 'multi_vendor_campaign'); ?></span>
                                                <span class="positive"><?php esc_html_e('vendors get', 'multi_vendor_campaign'); ?></span>
                                                <span class="value"><?php echo esc_attr( $item['commission_percentage'] ); ?></span>
                                                <span class="more"><?php esc_html_e('% more', 'multi_vendor_campaign'); ?></span>
                                            </span>
                                            </td>
                                            <td class="help"><p><?php esc_html_e('The Commission percentage that be added to original commission percentage. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?></p></td>
                                        </tr>
                                    <?php
                                    }
                                    
                                    
                                    ?>
                                </table>
                            </div>
                            <div class="post-body-content">
                                <table cellspacing="3" cellpadding="5" class="form-table">
                                    <tr class="form-field">
                                        <td colspan="2"><h2><?php esc_html_e('Rule Settings', 'multi_vendor_campaign'); ?></h2></td>
                                        <td class="help"></td>
                                    </tr>
                                    <!-- ruleType -->
                                    <tr class="form-field">
                                        <th scope="row"><label for="rule_type"><?php esc_html_e('Rule Type', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><select name="rule_type" id="rule_type">
                                                <option value="no_rule" <?php selected( $item['rule_type'], 'no_rule' ); ?>><?php esc_html_e('No Condition', 'multi_vendor_campaign'); ?></option>
                                                <option value="vendor_sale" <?php selected( $item['rule_type'], 'vendor_sale' ); ?>><?php esc_html_e('Vendor Sale', 'multi_vendor_campaign'); ?></option>
                                                <option value="vendor_age" <?php selected( $item['rule_type'], 'vendor_age' ); ?>><?php esc_html_e('Vendor Age', 'multi_vendor_campaign'); ?></option>
                                                <option value="product_sale" <?php selected( $item['rule_type'], 'product_sale' ); ?>><?php esc_html_e('Product Sale', 'multi_vendor_campaign'); ?></option>
                                                <option value="product_age" <?php selected( $item['rule_type'], 'product_age' ); ?>><?php esc_html_e('Product Age', 'multi_vendor_campaign'); ?></option>
                                            </select>
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Show campaigns to vendors with this qualification', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!-- ruleOperator -->
                                    <tr id="rule_operator_field" class="form-field">
                                        <th scope="row"><label for="rule_operator"><?php esc_html_e('Rule Operator', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><select name="rule_operator" id="rule_operator">
                                                <option value=">" <?php selected( $item['rule_operator'], '>' ); ?>><?php esc_html_e('Greater than', 'multi_vendor_campaign'); ?></option>
                                                <option value=">=" <?php selected( $item['rule_operator'], '>=' ); ?>><?php esc_html_e('Greater or Equal to', 'multi_vendor_campaign'); ?></option>
                                                <option value="=" <?php selected( $item['rule_operator'], '=' ); ?>><?php esc_html_e('Equal to', 'multi_vendor_campaign'); ?></option>
                                                <option value="<=" <?php selected( $item['rule_operator'], '<=' ); ?>><?php esc_html_e('Less or Equal to', 'multi_vendor_campaign'); ?></option>
                                                <option value="<" <?php selected( $item['rule_operator'], '<' ); ?>><?php esc_html_e('Less than', 'multi_vendor_campaign'); ?></option>
                                            </select>
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Select a Rule operator', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--ruleValue-->
                                    <tr id="rule_value_field" class="form-field">
                                        <th scope="row"><label for="rule_value"><?php esc_html_e('Rule Value', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="rule_value" type="number" id="rule_value" value="<?php echo esc_attr( $item['rule_value'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('The rule compared to this value', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                            </table>
                            </div>
                            <div class="post-body-content">
                                <table cellspacing="3" cellpadding="5" class="form-table">
                                    <tr class="form-field">
                                        <td colspan="2"><h2><?php esc_html_e('Other Options ', 'multi_vendor_campaign'); ?></h2></td>
                                        <td class="help"></td>
                                    </tr>
                                    <!--discount_value-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="discount_value"><?php esc_html_e('Discount Value','multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><input name="discount_value" type="number" id="discount_value" value="<?php echo esc_attr( $item['discount_value'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" required></td>
                                        <td class="help"><p><?php esc_html_e('Show products as on-sale product with entered discount','multi_vendor_campaign'); ?></p></td>

                                    </tr>

                                    <!--outOfStack-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="out_of_stack"><?php esc_html_e('Display Out of Stock Products', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields"><select name="out_of_stack" id="out_of_stack">
                                                <option value=0 <?php selected( $item['out_of_stack'], 0 ); ?>><?php esc_html_e('No', 'multi_vendor_campaign'); ?></option>
                                                <option value=1 <?php selected( $item['out_of_stack'], 1 ); ?>><?php esc_html_e('Yes','multi_vendor_campaign'); ?></option>
                                            </select>
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Whether or not to show the out-of-stock products?', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--badge-->
                                    <?php 
                                        $badge_options = apply_filters( 
                                            'mvc_badge_options', 
                                            [
                                                esc_html__('Disable', 'multi_vendor_campaign'),
                                                esc_html__('Shop Page', 'multi_vendor_campaign')
                                            ] 
                                        );
                                    ?>
                                    <tr class="form-field">
                                        <th scope="row"><label for="badge"><?php esc_html_e('Badge', 'multi_vendor_campaign'); ?></label></th>
                                        <td class="fields">
                                            <select name="badge" id="badge">
                                                <?php foreach( $badge_options as $key => $value ) : ?>
                                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $item['badge'], $key ); ?>>
                                                        <?php echo esc_html( $value ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="help"><p><?php esc_html_e('Show an optional badge with your text for products of this campaign', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>

                                    <!--badge text-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="badge_text"><?php esc_html_e('Badge Text', 'multi_vendor_campaign'); ?> </label></th>
                                        <td class="fields"><input name="badge_text" type="text" id="badge_text" value="<?php echo esc_attr( $item['badge_text'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60"></td>
                                        <td class="help"><p><?php esc_html_e('The desired text on the product badges', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                    <!--class name-->
                                    <tr class="form-field">
                                        <th scope="row"><label for="class_name"><?php esc_html_e('Class Name', 'multi_vendor_campaign'); ?> </label></th>
                                        <td class="fields"><input name="class_name" type="text" id="class_name" value="<?php echo esc_attr( $item['class_name'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60"></td>
                                        <td class="help"><p><?php esc_html_e('Add a class name to subscribed products of this campaign', 'multi_vendor_campaign'); ?></p></td>
                                    </tr>
                                    <tr class="form-field">
                                        <th scope="row"><input type="submit" value="<?php esc_attr_e('Save', 'multi_vendor_campaign')?>" id="submit" class="button-primary" name="submit"></th>
                                        <td class="fields"></td>
                                        <td class="help"></td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                </form>
            <?php } ?>
        </div>
        <?php
    }

    public function render_commission_inputs_for_wcv( $item ) {
        if ( 
            is_wcv_pro_active() && 
            Multi_Vendor_Campaign_Campaigns::is_mvc_pro_active() 
        ) {
            // If WC Vendor Pro and Multi-vendor Campaign Pro is active
            do_action( 'mvc_wcvendors_pro_inputs', $item );
        } else if ( 
            is_wcv_pro_active() && 
            !Multi_Vendor_Campaign_Campaigns::is_mvc_pro_active() 
        ) {
            // If WC Vendor Pro is active but Multi-vendor Campaign Pro is not active
            // In this situation, only support percentage commission type
            $commission_type = get_option('wcvendors_commission_type', 'percent');
            if ( $commission_type === 'percent' ) {
                ?>
                    <tr class="form-field">
                        <th scope="row"><label for="commission_percentage"><?php esc_html_e('Extra Commission Percentage', 'multi_vendor_campaign'); ?></label></th>
                        <td class="fields"><input name="commission_percentage" type="number" id="commission_percentage" value="<?php echo esc_attr( $item['commission_percentage'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                        <span class="input_number_label">
                            <span class="negative"><?php esc_html_e('You, the site admin, get', 'multi_vendor_campaign'); ?></span>
                            <span class="positive"><?php esc_html_e('vendors get', 'multi_vendor_campaign'); ?></span>
                            <span class="value"><?php echo esc_attr( $item['commission_percentage'] ); ?></span>
                            <span class="more"><?php esc_html_e('% more', 'multi_vendor_campaign'); ?></span>
                        </span>
                        </td>
                        <td class="help"><p><?php esc_html_e('The Commission percentage that be added to original commission percentage. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?></p></td>
                    </tr>
                <?php
            } else {
                // If MVC Pro is not active show unsupported message
                ?>
                <tr class="form-field">
                        <th style="color: crimson;" scope="row">Unsupported Commission Type</label></th>
                        <td class="fields">
                            <p> <?php printf( 
                                    esc_html__( 
                                        'We only support for Percent commission type in our free version. Please upgrade to PRO version to unlock support for %s commission type.' , 
                                        'multi_vendor_campaign' 
                                    ), 
                                    $commission_type 
                                ); ?> </p>   
                        </td>
                        <td class="help"><p><?php esc_html_e('You can change your commission type from: WP Dashboard > WC Vendors > Settings > Commission > Global Commission Type', 'multi_vendor_campaign');?></p></td>
                    </tr>
                <?php
            }
        } else if ( !is_wcv_pro_active() ) {
            // WC Vendor free version
            ?>
                <!--commissionPercentage-->
                <tr class="form-field">
                    <th scope="row"><label for="commission_percentage"><?php esc_html_e('Extra Commission Percentage', 'multi_vendor_campaign'); ?></label></th>
                    <td class="fields"><input name="commission_percentage" type="number" id="commission_percentage" value="<?php echo esc_attr( $item['commission_percentage'] ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" maxlength="60" class="labeled_input_number" required>
                    <span class="input_number_label">
                        <span class="negative"><?php esc_html_e('You, the site admin, get', 'multi_vendor_campaign'); ?></span>
                        <span class="positive"><?php esc_html_e('vendors get', 'multi_vendor_campaign'); ?></span>
                        <span class="value"><?php echo esc_attr( $item['commission_percentage'] ); ?></span>
                        <span class="more"><?php esc_html_e('% more', 'multi_vendor_campaign'); ?></span>
                    </span>
                    </td>
                    <td class="help"><p><?php esc_html_e('The Commission percentage that be added to original commission percentage. This value could be positive, negative, or zero.', 'multi_vendor_campaign'); ?></p></td>
                </tr>
            <?php
        }
    }

    public function sanitize_rule_operator($input) {
        if($input == '>' || $input == '>=' || $input == '=' || $input == '<=' || $input == '<')
        {
            return $input;
        }
        
        return '>';
    }

    public function validate_campaign_data($item){
        $messages = array();

        $subscription_date = strtotime($item['subscribe_start_date']);
        $subscription_end_date = strtotime($item['subscribe_end_date']);
        $activation_date = strtotime($item['activation_date']);
        $deactivation_date = strtotime($item['deactivation_date']);

        if (empty($item['title']))
            $messages[] = esc_html__('Campaign Name is required', 'multi_vendor_campaign');

        if (empty($item['description']))
            $messages[] = esc_html__('Campaign description is required', 'multi_vendor_campaign');

        if ($item['enable'] =! 0 &&  $item['enable'] =! 1)
            $messages[] = esc_html__('Wrong enable value', 'multi_vendor_campaign');

        if (isset($item['commission_fixed']) && !is_float(floatval($item['commission_fixed'])))
            $messages[] = esc_html__('Commission fixed in wrong format', 'multi_vendor_campaign');

        if (isset($item['commission_fee']) && !is_float(floatval($item['commission_fee'])))
            $messages[] = esc_html__('Commission Fee in wrong format', 'multi_vendor_campaign');

        if (isset($item['commission_percentage']) && !is_float(floatval($item['commission_percentage'])) || floatval($item['commission_percentage']) > 100 || floatval($item['commission_percentage']) < -100)
            $messages[] = esc_html__('Commission Percentage in wrong format', 'multi_vendor_campaign');

        if (!is_int($item['discount_value']) || intval($item['discount_value']) < 0 || intval($item['discount_value']) > 100 )
            $messages[] = esc_html__('Discount value in wrong format', 'multi_vendor_campaign');

        if (strtotime($item['subscribe_start_date'])==='0000-00-00 00:00:00')
            $messages[] = esc_html__('Subscribe time is required', 'multi_vendor_campaign');

        if (strtotime($item['subscribe_end_date'])==='0000-00-00 00:00:00')
            $messages[] = esc_html__('Subscribe ending time is required', 'multi_vendor_campaign');

        if (strtotime($item['activation_date'])==='0000-00-00 00:00:00')
            $messages[] = esc_html__('Activation time is required', 'multi_vendor_campaign');

        if (strtotime($item['deactivation_date'])==='0000-00-00 00:00:00')
            $messages[] = esc_html__('Deactivation time is required', 'multi_vendor_campaign');

        if($subscription_date >= $subscription_end_date)
            $messages[] = esc_html__('The end time of subscription should be after the start time', 'multi_vendor_campaign');

        if($activation_date >= $deactivation_date)
            $messages[] = esc_html__('The time of deactivation should be after the activation time', 'multi_vendor_campaign');

        if (!is_int($item['number_of_vendor_products']) || intval($item['number_of_vendor_products']) <= 0)
            $messages[] = esc_html__('Number Of products for each vendor should be a positive number', 'multi_vendor_campaign');

        if ($item['out_of_stack'] =! 0 &&  $item['out_of_stack'] =! 1)
            $messages[] = esc_html__('out of stack in wrong format', 'multi_vendor_campaign');

        if ($item['campaign_position'] != 'shop' && $item['campaign_position'] != 'category' && $item['campaign_position'] != 'search' && $item['campaign_position'] != 'page' )
            $messages[] = esc_html__('Campaign position is wrong', 'multi_vendor_campaign');

        if (
            $item['rule_type'] != 'vendor_sale'     && 
            $item['rule_type'] != 'vendor_age'      && 
            $item['rule_type'] != 'product_sale'    && 
            $item['rule_type'] != 'product_age'     && 
            $item['rule_type'] != 'no_rule' 
        )
            $messages[] = esc_html__('Rule type is wrong', 'multi_vendor_campaign');

        if ($item['rule_operator'] != '>' && $item['rule_operator'] != '<' && $item['rule_operator'] != '=' && $item['rule_operator'] != '>=' && $item['rule_operator'] != '<=' )
            $messages[] = esc_html__('Rule operator is wrong', 'multi_vendor_campaign');

        if (!is_float(floatval($item['rule_value'])) || floatval($item['rule_value']) < 0 )
            $messages[] = esc_html__('Rule Value in wrong format', 'multi_vendor_campaign');
        
        if ($item['badge'] =! 0 &&  $item['badge'] =! 1 && $item['badge'] =! 2 && $item['badge'] =! 3)
            $messages[] = esc_html__('badge in wrong format', 'multi_vendor_campaign');

        if (empty($messages)) return true;
        return implode('<br />', $messages);
    }

     /**
	 * Show list of campaigns
	 *
	 * @since    1.0.0
	 */
	public function show_campaigns() {

        // Create an instance of our campaign class...
        $campaignListTable = new Multi_Vendor_Campaign_Campaign_List($this->plugin_name);
        // Fetch, prepare, sort, and filter our data...
        $campaignListTable->prepare_items();

        $message = '';
        if ('delete' === $campaignListTable->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(esc_html__('Items deleted: %d', 'multi_vendor_campaign'), count(explode( ',', $_REQUEST['id'] ))) . '</p></div>';
        }

        ?>
        <div class="wrap">

            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php esc_html_e('List of Campaigns', 'multi_vendor_campaign')?>
                <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $this->plugin_name . '-new-campaigns');?>"><?php esc_html_e('Add new', 'multi_vendor_campaign')?></a>
            </h2>
            <?php echo wp_kses( $message, [ 'div' => [], 'p' => [] ] ); ?>
            
            <form action="" method="GET">
                <?php $campaignListTable->search_box( esc_html__( 'Search', 'multi_vendor_campaign' ), 'search_id' ); ?>
                <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>"/>
            </form>

            <?php 
                if ( isset($_REQUEST['s']) && $_REQUEST['s'] ) {
                    echo '<p>Showing search results for: <strong>' . esc_html( $_REQUEST['s'] ) . '</strong></p>';
                }

                $campaignListTable->display(); 
            ?>

        </div>
        <?php

	}

    /**
     * Show list of products of a campaign
     *
     * @since    1.0.0
     */
    public function show_campaign_products() {

        if (isset($_REQUEST['id'])) {
            $campaign_id = intval( $_REQUEST['id'] );
            // Create an instance of our campaign class...
            $campaignListTable = new Multi_Vendor_Campaign_Campaign_Product_List($this->plugin_name,$campaign_id);
            // Fetch, prepare, sort, and filter our data...
            $campaignListTable->prepare_items();

            ?>


            <div class="wrap campaign-products-wrapper">
                <a class="add-new-h2 page-title-action" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $this->plugin_name . '-campaigns');?>"><?php esc_html_e('Back to Campaigns', 'multi_vendor_campaign')?></a>
                
                <div class="alignright">
                    <div class="products-input-container">
                        <input class="products-input-search" type="text" data-campaignid="<?php echo esc_attr( $_GET['id'] ); ?>" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="<?php esc_html_e('Search for a product…', 'multi_vendor_campaign'); ?>">
                        <a data-campaignid="<?php echo esc_attr( $_GET['id'] ); ?>" class="add-new-h2 page-title-action subscribe-product-btn" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=' . $this->plugin_name . '-campaigns');?>"><?php esc_html_e('Add Product', 'multi_vendor_campaign')?></a>
                        <ul class="product-search-result"></ul>
                    </div>

                </div>

                <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
                <form id="campaigns-table" method="GET">
                    <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>"/>
                    <?php $campaignListTable->display() ?>
                </form>

            </div>
            <?php
        }

    }

    /**
	 * Show orders that include product with campagne
	 *
	 * @since    1.0.0
	 */
	public function show_orders() {

        
        $ordersListTable = new Multi_Vendor_Campaign_Campaign_Order_List($this->plugin_name);
        $ordersListTable->prepare_items();

        ?>
        <div class="wrap">

            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php esc_html_e('Orders', 'multi_vendor_campaign')?></h2>

            <form action="" method="GET">
                <?php $ordersListTable->search_box( esc_html__( 'Search', 'multi_vendor_campaign' ), 'search_id' ); ?>
                <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>"/>
            </form>

            <?php 
                if (isset($_REQUEST['s']) &&  $_REQUEST['s'] ) {
                    echo '<p>Showing search results for: <strong>' . esc_html( $_REQUEST['s'] ) . '</strong></p>';
                }

                $ordersListTable->display(); 
            ?>

        </div>
        <?php

	}

    /**
	 * Show About Us page
	 *
	 * @since    1.0.0
	 */
	public function about_us_page() {
        ?>
        <div class="wrap multi-vendor-campaign-wrap">
            <h2><?php esc_html_e('Welcome to Multi Vendor Campaign', 'multi_vendor_campaign')?> </h2>
             
            Congratulations! You are about to use most powerful time saver for WordPress ever - page builder plugin with Frontend and Backend editors by WPBakery.
        </div>
        <?php
	}

    /**
	 * Show all products that has been registered
	 */
	public function all_subscribed_products() {
        
        $productsListTable = new Multi_Vendor_Campaign_Products_List($this->plugin_name);
        $productsListTable->prepare_items();

        ?>
        <div class="wrap">

            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php esc_html_e('Products', 'multi_vendor_campaign')?></h2>

            <form action="" method="GET">
                <?php $productsListTable->search_box( esc_html__( 'Search', 'multi_vendor_campaign' ), 'search_id' ); ?>
                <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>"/>
            </form>

            <?php 
                if ( isset($_REQUEST['s']) && $_REQUEST['s'] ) {
                    echo '<p>Showing search results for: <strong>' . esc_html( $_REQUEST['s'] ) . '</strong></p>';
                }

                $productsListTable->display(); 
            ?>

        </div>
        <?php

	}


    /**
     * Save viewed campaigns by vendor
     *
     * @since    1.0.0
     */
    public function viewed_campaigns_by_vendor($campaignsList)
    {

        $key = 'rad_mvc_viewed_campaign';
        if(!$campaignsList)
        {
            return;
        }

        $user = wp_get_current_user();
        $campaignIDs = array();

        $viewed_campaigns = get_user_meta( $user->ID, $key , true);

        if($viewed_campaigns == '')
        {
            $viewed_campaigns = array();
        }

        foreach ($campaignsList as $campaign) {
            $campaignIDs[] = $campaign['id'];
        }

        $campaignIDs = array_unique(array_merge($campaignIDs, $viewed_campaigns), SORT_REGULAR);
        update_user_meta($user->ID, $key , $campaignIDs);
    }

    /**
     * Show number of new campaigns to vendor
     *
     * @since    1.0.0
     */
    public function new_campaigns_for_vendor($html_output = true)
    {
        $campaignsClass=new Multi_Vendor_Campaign_Campaigns();
        $campaignsList=$campaignsClass->get_all_campaigns();
        $key = 'rad_mvc_viewed_campaign';
        if(!$campaignsList)
        {
            return;
        }

        $user = wp_get_current_user();

        $new_campaigns = 0;

        $viewed_campaigns = get_user_meta( $user->ID, $key, true );
        if($viewed_campaigns == '')
        {
            $viewed_campaigns = array();
        }

        foreach ($campaignsList as $campaign)
        {
            if( !in_array($campaign['id'], $viewed_campaigns) )
            {
                $new_campaigns++;
            }
        }

        if($new_campaigns != 0)
        {
            if($html_output)
                return '<span class="update-plugins"><span class="update-count">'. $new_campaigns . '</span></span>';
            else
                return '(' . $new_campaigns . ')';
        }
        return '';
    }

    /**
     * Show number of new pending products
     *
     * @since    1.0.0
     */
    public function new_pneding_products_for_admin()
    {
        $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        $pending_products_num = $campaignsClass->get_number_of_pending_products();

        if($pending_products_num != 0)
        {
            return '<span class="update-plugins"><span class="update-count">'. $pending_products_num . '</span></span>';
        }
        return '';
    }

    /**
     * Show all campaigns to vendor
     *
     * @since    1.0.0
     */
    public function show_campaigns_to_vendor() {

        $campaignsClass  =   new Multi_Vendor_Campaign_Campaigns();
        $campaignsList   =   $campaignsClass->get_all_campaigns();

        $this->viewed_campaigns_by_vendor($campaignsList);

        if(!$campaignsList)
        {
            ?>
            <div id="message" class="notice"><p><?php esc_html_e('There is no campaign to subscribe.', 'multi_vendor_campaign'); ?></p></div>
            <?php
            return;
        }

        ?>
        <div class="rad-mvc-campaigns-list">
        <?php

        foreach ($campaignsList as $item){
            ?>

            <div class="campaign-item" data-campaignid="<?php echo esc_attr( $item['id'] ); ?>">
                <div class="title"><h3><?php echo esc_attr( $item['title'] ); ?></h3></div>
                    <div class="description">
                        <div class="activation-date">
                            <span><?php echo esc_html(  date('F j, Y H:i:s', strtotime($item['activation_date']))    ); ?></span>
                             <?php esc_html_e('to', 'multi_vendor_campaign'); ?>
                             <span><?php echo esc_html( date('F j, Y H:i:s', strtotime($item['deactivation_date'] ))); ?></span>
                         </div>

                    </div>
                <?php
                if( $item['thumbnail'] != '')
                {
                    ?><div class="campaign-item-header" style="background: url(<?php echo esc_url( $item['thumbnail'] ); ?>);"><?php
                } else {
                    ?><div class="campaign-item-no-header"><?php
                }
                ?>
                
                </div>
                <div class="campaign-item-detail">
                    <div class="commission-desc"><p><?php echo esc_html( $item['description'] ); ?></p></div>
                    <div class="number-of-vendor-products"><?php esc_html_e('Max allowed number of products to subscribe: ', 'multi_vendor_campaign'); ?><strong><?php echo esc_attr( $item['number_of_vendor_products'] ); ?></strong> </div>
                    <div class="campaign-position"><?php esc_html_e('Display Position of products: ', 'multi_vendor_campaign'); ?>
                        <strong>
                            <?php
                            if($item['campaign_position'] == 'shop')
                            {
                                esc_html_e('In top of main shop page', 'multi_vendor_campaign');
                            }
                            elseif($item['campaign_position'] == 'category')
                            {
                                esc_html_e('In top of category archive', 'multi_vendor_campaign');
                            }
                            elseif ($item['campaign_position'] == 'search')
                            {
                                esc_html_e('In top of search results', 'multi_vendor_campaign');
                            }
                            else
                            {
                                esc_html_e('In the site(in a separate page)', 'multi_vendor_campaign');
                            }
                            ?>
                            </strong>
                    </div>
                    <div class="campaign-subscription">
                        <?php
                        $starting_date = date('F j, Y H:i:s', strtotime($item['subscribe_start_date']));
                        $ending_date = date('F j, Y H:i:s', strtotime($item['subscribe_end_date']));
                        esc_html_e('Subscription date : ', 'multi_vendor_campaign');
                        echo esc_html( $starting_date ) . ' ' . esc_html__('to', 'multi_vendor_campaign') . ' ' . esc_html( $ending_date );
                        ?>
                    </div>
                </div>
                <div class="products_heading">
                    <span><?php esc_html_e('Product', 'multi_vendor_campaign'); ?></span>
                    <span class="status"><?php esc_html_e('Status', 'multi_vendor_campaign'); ?></span>
                </div>
                <ul class="selected-items">
                    <?php
                    $subscribedProducts = $campaignsClass->get_subscribed_products_of_campaign($item['id']);
                    foreach ($subscribedProducts as $product_id => $product_info){
                        ?>
                        <li data-productid="<?php echo esc_attr( $product_id ); ?>">
                            <span class="img">
                                <?php 
                                    echo wp_kses( 
                                        $product_info['img'], 
                                        [
                                            'img' => [
                                                'width' => [], 
                                                'height' => [], 
                                                'src' => [], 
                                                'class' => [], 
                                                'alt' => [], 
                                                'id' => []
                                            ]
                                        ] 
                                    ); 
                                ?>
                            </span>
                            <?php
                            $statusClass = 'pending';
                            if($product_info['status'] == 1) {
                                $statusClass = 'approved';
                            }
                            elseif($product_info['status'] == 2)
                            {
                                $statusClass = 'rejected';
                            }

                            // Approved and pending products are removable
                            if($statusClass == 'pending' || $statusClass == 'approved')
                            {
                                echo '<span class="remove"></span>';
                            }

                            echo esc_html( $product_info['title'] );

                            ?>
                            <span class="status <?php echo esc_attr( $statusClass ); ?>">
                                <span class="rejected"><?php esc_html_e('Rejected', 'multi_vendor_campaign'); ?></span>
                                <span class="approved"><?php esc_html_e('Approved', 'multi_vendor_campaign'); ?></span>
                                <span class="pending"><?php esc_html_e('Pending…', 'multi_vendor_campaign'); ?></span>
                                <span class="loading">
                                  <span class="bounce1"></span>
                                  <span class="bounce2"></span>
                                  <span class="bounce3"></span>
                                </span>
                            </span>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
                <?php
                $is_subscription_time = $campaignsClass->valid_campaign_to_subscribe($item);
                $is_qualified = $campaignsClass->is_qualified_to_show($item);//check qualification based on campaign rule
                $can_subscribe_more_products = count($subscribedProducts) < $item['number_of_vendor_products'];

                if(
                    $is_subscription_time   && 
                    $is_qualified           &&
                    $can_subscribe_more_products
                ) {
                ?>
                    <div class="products-input-container">
                        <p><?php esc_html_e('Add your desired products'); ?></p>
                        <span class="loading">
                          <span class="bounce1"></span>
                          <span class="bounce2"></span>
                          <span class="bounce3"></span>
                        </span>
                        <input class="products-input-search" type="text" data-campaignid="<?php echo esc_attr( $item['id'] ); ?>"" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="<?php esc_html_e('Search for a product…', 'multi_vendor_campaign'); ?>">
                        <ul class="product-search-result">
                        </ul>
                    </div>
                    <span class="button_subscribe_campaign disable" data-campaignid="<?php echo esc_attr( $item['id'] ); ?>"><?php esc_html_e('Subscribe', 'multi_vendor_campaign'); ?>
                        <span class="loading">
                          <span class="bounce1"></span>
                          <span class="bounce2"></span>
                          <span class="bounce3"></span>
                        </span>
                    </span>
                <?php
                }
                else
                {
                    if(!$is_subscription_time)
                    {
                        $now = date("Y-m-d");
                        $starting_date = strftime( '%Y-%m-%d', strtotime( $item['subscribe_start_date'] ) );

                        $message = $now < $starting_date
                            ? esc_html__("Subscription has not started yet!", 'multi_vendor_campaign')
                            : esc_html__('Subscription time is over!', 'multi_vendor_campaign');

                        echo '<p class="rad-mvc-campaign-message"><span class="dashicons dashicons-warning"></span>' . $message . '</p>';
                    }
                    else if(!$is_qualified)
                    {
                        echo '<p class="rad-mvc-campaign-message"><span class="dashicons dashicons-warning"></span>' . esc_html__('You are not qualified for this campaign!', 'multi_vendor_campaign') . '</p>';
                    }
                    else if(!$can_subscribe_more_products)
                    {
                        echo '<p class="rad-mvc-campaign-message"><span class="dashicons dashicons-warning"></span>' . esc_html__('You are not allowed to subscribe more products.', 'multi_vendor_campaign') . '</p>';
                    }

                }
                ?>
            </div>
            <?php
        }
        ?>
        </div>
        <?php

    }

    public function wc_market_dashboard_nav($vendor_nav){

        $vendor_nav['campaigns'] = array(
            'label' => esc_html__('Campaigns', 'multi_vendor_campaign') . $this->new_campaigns_for_vendor(false), 
            'url' => admin_url('admin.php?page=' .$this->plugin_name . '-user-campaigns'), 
            'capability' => true, 
            'position' => 80, 
            'submenu' => array(), 
            'link_target' => '_self', 
            'nav_icon' => 'dashicons dashicons-awards'
        );

        return $vendor_nav;
    }

    function dokan_load_document_menu( $query_vars ) {
        $query_vars[] = 'campaigns';
        return $query_vars;
    }

    function dokan_rewrite_urls($custom_store_url) {
        flush_rewrite_rules();
    }

    function dokan_add_campaigns_menu( $urls ) {
        $urls['campaigns'] = array(
            'title' => esc_html__( 'Campaigns', 'dokan') . $this->new_campaigns_for_vendor(),
            'icon'  => '<i class="dashicons dashicons-awards"></i>',
            'url'   => dokan_get_navigation_url( 'campaigns' ),
            'pos'   => 51,
            'permission' => 'dokan_view_order_menu'
        );
        return $urls;
    }

    function dokan_load_template( $query_vars ) {

        ob_get_clean();
        if ( isset( $query_vars['campaigns'] ) ) {
            ?>
            <div class="dokan-dashboard-wrap">
                <?php
                do_action( 'dokan_dashboard_content_before' );
                ?>
                <div class="dokan-dashboard-content">
                    <?php
                    do_action( 'dokan_help_content_inside_before' );
                    $this->show_campaigns_to_vendor();
                    do_action( 'dokan_dashboard_content_inside_after' );
                    ?>
                </div><!-- .dokan-dashboard-content -->
                <?php
                do_action( 'dokan_dashboard_content_after' );
                ?>
            </div>
            <?php
        }

        ob_flush();
        // return ob_get_clean();
    }

    public function admin_footer_text( $footer_text ) {
        if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_screen_ids' ) ) {
            return $footer_text;
        }

        $current_screen = get_current_screen();
        $wc_pages       = wc_get_screen_ids();

        $wc_pages = array( $this->plugin_name . '-campaigns', $this->plugin_name . '-new-campaigns', $this->plugin_name . '-campaign-products', $this->plugin_name . '-user-campaigns' );

        // Check to make sure we're on one of Multi Vendor Campaign admin pages
        if ( isset( $current_screen->parent_base ) && in_array( $current_screen->parent_base, $wc_pages ) ) {
            // Change the footer text
            $footer_text = sprintf(__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'woocommerce' ),
                sprintf( '<strong>%s</strong>', esc_html__( 'Multi Vendor Campaign', 'multi_vendor_campaign' ) ),
                '<a href="#" target="_blank" class="rad-mvc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'multi_vendor_campaign' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        }

        return $footer_text;
    }

    public function wc_order_campaign_column( $columns ) {
        $columns['campaign'] = __('Campaigns', 'multi_vendor_campaign');
        return $columns;
    }

    /**
     * Adds 'campaign' column content to 'Orders' page immediately.
     *
     * @param string[] $column name of column being displayed
     */
    public function wc_order_campaign_column_content( $column ) {
        global $post;

        if ( 'campaign' === $column ) {

            $order    = wc_get_order( $post->ID );
            $campaign_ids     = rad_mvc_get_order_meta( $order, '_rad_mvc_campaign_ids' );

            if( is_array($campaign_ids) && count($campaign_ids) > 0 )
            {
                echo '<span class="rad-mvc-has-campaign dashicons dashicons-awards"></span>';
                return;
            }
            echo '<span class="rad-mvc-no-campaign dashicons dashicons-awards"></span>';
        }
    }

    public function wc_order_campaign_add_meta_boxes()
    {
        add_meta_box( 'rad_mvc_order_campaign', __('Campaign', 'multi_vendor_campaign'), array($this, 'wc_order_campaign_meta_boxes'), 'shop_order', 'side', 'core' );
    }

    public function wc_order_campaign_meta_boxes()
    {
        global $post;

        $order    = wc_get_order( $post->ID );
        $campaign_ids     = rad_mvc_get_order_meta( $order, '_rad_mvc_campaign_ids' );

        if( !is_array($campaign_ids) || count($campaign_ids) == 0 )
        {
            echo '<p>' . __('No campaign used in this order', 'multi_vendor_campaign'). '</p>';
            return;
        }
        
        $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        foreach ($campaign_ids as $campaign_id) {
            $title = $campaignsClass->get_title_of_campaign($campaign_id);
            echo '<p><span class="rad-mvc-has-campaign dashicons dashicons-awards"></span>' . $title . '</p>';
        }

    }

}
