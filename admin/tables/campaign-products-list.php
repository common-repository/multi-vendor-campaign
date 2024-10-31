<?php

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Multi_Vendor_Campaign_Products_List extends WP_List_Table
{

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;
  private $campaign_id;

  /** ************************************************************************
   * REQUIRED. Set up a constructor that references the parent constructor. We
   * use the parent reference to set some default configs.
   ***************************************************************************/
  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   */
  function __construct($plugin_name)
  {

    $this->plugin_name = $plugin_name;
    //Set parent defaults
    parent::__construct(array(
      'singular'  => esc_html__('Product', 'multi_vendor_campaign'),     //singular name of the listed records
      'plural'    => esc_html__('Products', 'multi_vendor_campaign'),   //plural name of the listed records
      'ajax'      => true        //does this table support ajax?
    ));
  }


  function column_status($item)
  {
    $statusClass = 'pending';

    if ($item['status'] == '1') {
      $statusClass = 'approved';
    } elseif ($item['status'] == '2') {
      $statusClass = 'rejected';
    }


    return sprintf(
      '<span class="rad-mvc-product-status %1$s">
                <span class="rejected">%2$s</span>
                <span class="approved">%3$s</span>
                <span class="pending">%4$s</span>
                <span class="loading">
                  <span class="bounce1"></span>
                  <span class="bounce2"></span>
                  <span class="bounce3"></span>
                </span>
            </span>',
      $statusClass,
      esc_html__("Rejected", 'multi_vendor_campaign'),
      esc_html__("Approved", 'multi_vendor_campaign'),
      esc_html__("Pending…", 'multi_vendor_campaign')
    );
  }

  function column_campaign_name($item)
  {

    $campaign_id     = $item['campaign_id'];
    $campaign_title  = $item['campaign_name'];

    //Return the title contents
    return sprintf(
      '<a href="?page=%s&id=%s">%s</a>',
      $this->plugin_name . '-new-campaigns',
      $campaign_id,
      esc_html($campaign_title)
    );
  }

  function column_vendor_name($item)
  {

    $vendor_name = $item['vendor_name'];
    $user_id = $item['vendor_id'];

    $vendor_url = ( get_current_user_id() == $user_id ) 
      ? get_edit_profile_url( $user_id )
      : add_query_arg( 'user_id', $user_id, self_admin_url( 'user-edit.php'));

    return sprintf(
      '<a href="%1$s" target="_blank">%2$s</a>',
      $vendor_url,
      $vendor_name
    );
  }

  function column_product_name($item)
  {

    $product_name = $item['product_name'];
    $product_url = get_edit_post_link($item['product_id']);

    return sprintf(
      '<a href="%1$s" target="_blank">%2$s</a>',
      $product_url,
      $product_name
    );
  }

  function column_subscription_date($item)
  {

    $result = strftime('%Y-%m-%d', strtotime($item['subscription_date']));

    //Return the title contents
    return sprintf(
      '%1$s',
      /*$1%s*/
      $result
    );
  }

  function get_columns()
  {
    $columns = array(
      'product_name'       => esc_html__('Product', 'multi_vendor_campaign'),
      'campaign_name'       => esc_html__("Campaign", 'multi_vendor_campaign'),
      'vendor_name'        => esc_html__('Vendor', 'multi_vendor_campaign'),
      'subscription_date'  => esc_html__('Subscription Date', 'multi_vendor_campaign'),
      'status'             => esc_html__('Status', 'multi_vendor_campaign')
    );
    return $columns;
  }

  function get_sortable_columns()
  {
    $sortable_columns = array(
      'campaign_name'     => array('campaign_name', false),
      'vendor_name'     => array('vendor_name', false),
      'product_name'     => array('product_name', false),
      'subscription_date'     => array('subscription_date', false)
    );
    return $sortable_columns;
  }

  function prepare_items()
  {
    global $wpdb; //This is used only if making any database queries

    /**
     * First, lets decide how many records per page to show
     */
    $per_page = 10;


    /**
     * REQUIRED. Now we need to define our column headers. This includes a complete
     * array of columns to be displayed (slugs & titles), a list of columns
     * to keep hidden, and a list of columns that are sortable. Each of these
     * can be defined in another method (as we've done here) before being
     * used to build the value for our _column_headers property.
     */
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();


    /**
     * REQUIRED. Finally, we build an array to be used by the class for column
     * headers. The $this->_column_headers property takes an array which contains
     * 3 other arrays. One for all columns, one for hidden columns, and one
     * for sortable columns.
     */
    $this->_column_headers = array($columns, $hidden, $sortable);


    /**
     * Optional. You can handle your bulk actions however you see fit. In this
     * case, we'll handle them within our package just to keep things clean.
     */
    $this->process_bulk_action();

    /**
     * REQUIRED for pagination. Let's figure out what page the user is currently
     * looking at. We'll need this later, so you should always include it in
     * your own package classes.
     */
    $current_page = $this->get_pagenum();

    /**
     * Instead of querying a database, we're going to fetch the example data
     * property we created for use in this plugin. This makes this example
     * package slightly different than one you might build on your own. In
     * this example, we'll be using array manipulation to sort and paginate
     * our data. In a real-world implementation, you will probably want to
     * use sort and pagination data to build a custom query instead, as you'll
     * be able to use your precisely-queried data immediately.
     */
    $data = self::get_all_products();



    /**
     * This checks for sorting input and sorts the data in our array accordingly.
     *
     * In a real-world situation involving a database, you would probably want
     * to handle sorting by passing the 'orderby' and 'order' values directly
     * to a custom query. The returned data will be pre-sorted, and this array
     * sorting technique would be unnecessary.
     */
    function usort_reorder($a, $b)
    {
      $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'subscription_date'; //If no sort, default to title
      $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
      $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
      return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
    }
    usort($data, 'usort_reorder');


    /**
     * REQUIRED for pagination. Let's check how many items are in our data array.
     * In real-world use, this would be the total number of items in your database,
     * without filtering. We'll need this later, so you should always include it
     * in your own package classes.
     */
    $total_items = count($data);


    /**
     * The WP_List_Table class does not handle pagination for us, so we need
     * to ensure that the data is trimmed to only the current page. We can use
     * array_slice() to
     */
    $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);



    /**
     * REQUIRED. Now we can add our *sorted* data to the items property, where
     * it can be used by the rest of the class.
     */
    $this->items = $data;


    /**
     * REQUIRED. We also have to register our pagination options & calculations.
     */
    $this->set_pagination_args(array(
      'total_items' => $total_items,                  //WE have to calculate the total number of items
      'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
      'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
    ));
  }

  /**
   * Retrieve campaigns data from the database
   *
   * @return mixed
   */
  public static function get_all_products()
  {

    global $wpdb;
    $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';
    $table_campaigns = $wpdb->base_prefix . 'rad_mvc_table_campaign';
    $table_vendors = $wpdb->base_prefix . 'users';
    $table_products = $wpdb->base_prefix . 'posts';

    $sql = "
      SELECT 
        PP.id, 
        PP.status AS status, 
        PP.product_id AS product_id,
        PCK.id AS campaign_id, 
        PCK.title AS campaign_name, 
        PP.subscription_date AS subscription_date, 
        P.post_title AS product_name, 
        V.display_name AS vendor_name,
        V.id AS vendor_id
      FROM " . $table_campaign_product . " as PP 
      JOIN " . $table_vendors . " as V 
      On V.id = PP.vendor_id 
      JOIN " . $table_campaigns . " as PCK
      On PCK.id = PP.campaign_id
      JOIN " . $table_products . " as P 
      On P.id = PP.product_id "; // ." AND PP.active = 1";

    $search = '';
    if ( !empty( $_REQUEST['s'] ) ) {
      $search = strtolower( esc_sql($_REQUEST['s']) );
      
      $sql .= " 
        WHERE 
          LOWER(P.post_title) LIKE %s OR 
          LOWER(V.display_name) LIKE %s OR 
          LOWER(PCK.title) LIKE %s 
      ";
    }

    if (!empty($_REQUEST['orderby'])) {
      $sql .= ' ORDER BY %s';
      $sql .= !empty($_REQUEST['order']) ? ' %s' : ' ASC';
    }

    $query = $wpdb->prepare(
      $sql,
      '%' . $wpdb->esc_like( $search ) . '%',
      '%' . $wpdb->esc_like( $search ) . '%',
      '%' . $wpdb->esc_like( $search ) . '%',
      esc_sql($_REQUEST['orderby']),
      esc_sql($_REQUEST['order'])
    );

    $result = $wpdb->get_results($query, 'ARRAY_A');

    return $result;
  }
}
