<?php

if (!class_exists('WP_List_Table')) {
  require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Multi_Vendor_Campaign_Campaign_Order_List extends WP_List_Table
{

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

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
      'singular'  => esc_html__('Order', 'multi_vendor_campaign'),     //singular name of the listed records
      'plural'    => esc_html__('Orders', 'multi_vendor_campaign'),   //plural name of the listed records
      'ajax'      => false
    ));
  }

  /**
   * Prepare the items for the table to process
   *
   * @return Void
   */
  public function prepare_items()
  {
    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();

    $data = $this->table_data();
    usort($data, array(&$this, 'sort_data'));

    $perPage = 10;
    $currentPage = $this->get_pagenum();
    $totalItems = count($data);

    $this->set_pagination_args(array(
      'total_items' => $totalItems,
      'per_page'    => $perPage
    ));

    $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

    $this->_column_headers = array($columns, $hidden, $sortable);
    $this->items = $data;
  }

  /**
   * Override the parent columns method. Defines the columns to use in your listing table
   *
   * @return Array
   */
  public function get_columns()
  {
    $columns = array(
      'order_id'      => esc_html__('Order ID', 'multi_vendor_campaign'),
      'customer'      => esc_html__('Customer', 'multi_vendor_campaign'),
      'date'          => esc_html__('Date', 'multi_vendor_campaign'),
      'status'        => esc_html__('Status', 'multi_vendor_campaign'),
      'total'         => esc_html__('Total', 'multi_vendor_campaign'),
      'campaign'       => esc_html__('Campaign', 'multi_vendor_campaign'),
    );

    return $columns;
  }

  /**
   * Define which columns are hidden
   *
   * @return Array
   */
  public function get_hidden_columns()
  {
    return array();
  }

  /**
   * Define the sortable columns
   *
   * @return Array
   */
  public function get_sortable_columns()
  {
    return array(
      'title' => array('title', false),
      'date' => array('date', false)
    );
  }

  /**
   * Get the table data
   *
   * @return Array
   */
  private function table_data()
  {
    if(!function_exists("wc_get_orders")) {
      return array();
    }
    
    if ( !empty( $_REQUEST['s'] ) ) {
      $search = str_replace('#', '', esc_sql( $_REQUEST['s'] ));
      $data = [ wc_get_order( $search ) ];
    } else {
      $data = wc_get_orders(array(
        'limit'        => -1,
        'orderby'      => 'date',
        'order'        => 'DESC',
        'meta_key'     => '_rad_mvc_campaign_ids',
        'meta_compare' => 'EXISTS'
      ));
    }

    if($data == null)
    {
      return array();
    }

    return $data;
  }

  /**
   * Define what data to show on each column of the table
   *
   * @param  Array $item        Data
   * @param  String $column_name - Current column name
   *
   * @return Mixed
   */
  public function column_default($data, $column_name)
  {

    if ( !$data ) {
      return ( $column_name == 'order_id' )
        ? esc_html__( 'No items found.', 'multi_vendor_campaign' ) 
        : '';
    }

    switch ($column_name) {
      case 'order_id':
        $order_id = $data->get_id();
        return '<a href="' . esc_url(admin_url('post.php?post=' . absint($order_id)) . '&action=edit') . '" class="order-view"><strong>#' . esc_attr($data->get_order_number()) . '</strong></a>';
      case 'customer':
        return $data->get_billing_first_name() . " " . $data->get_billing_last_name();
      case 'date':
        return strftime('%Y-%m-%d', strtotime($data->get_date_created()));
      case 'status':
        return '<span class="order-status ' . esc_attr(sanitize_html_class('status-' . $data->get_status())) . '"><span> ' . esc_html(wc_get_order_status_name($data->get_status())) . '</span></span>';
      case 'total':
        return wp_kses_post($data->get_formatted_order_total());
      case 'campaign':
        $order    = wc_get_order($data->get_id());
        $campaign_ids     = rad_mvc_get_order_meta($order, '_rad_mvc_campaign_ids');
        $prev_campaign_id = 0;
        $last_campaign_id = end($campaign_ids);

        if (is_array($campaign_ids) || count($campaign_ids) > 0) {
          $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
          foreach ($campaign_ids as $campaign_id) {

            // Do not repeat same campaign multiple times
            if ( $campaign_id == $prev_campaign_id ) continue;
            $prev_campaign_id = $campaign_id;

            $title = $campaignsClass->get_title_of_campaign($campaign_id);

            $html = sprintf(
              '<a href="?page=%s&id=%s">%s</a>',
              $this->plugin_name . '-new-campaigns',
              $campaign_id,
              esc_html($title)
            );

            if ( $last_campaign_id != $campaign_id ) {
              $html .= ', '; // Add a separator if there are more than one campaign
            }

            echo wp_kses( 
              $html, [
                'a' => [
                  'id' => [],
                  'href' => []
                ]
            ] );
          }
        }

      default:
        // return print_r( $data, true ) ;
    }
  }

  /**
   * Allows you to sort the data by the variables set in the $_GET
   *
   * @return Mixed
   */
  private function sort_data($a, $b)
  {
    // Set defaults
    $orderby = 'title';
    $order = 'asc';

    // If orderby is set, use this as the sort column
    if (!empty($_GET['orderby'])) {
      $orderby = sanitize_title_for_query( $_GET['orderby'] );
    }

    // If order is set use this as the order
    if (!empty($_GET['order'])) {
      $order = sanitize_title_for_query( $_GET['order'] );
    }


    $result = strcmp($a->$orderby, $b->$orderby);

    if ($order === 'asc') {
      return $result;
    }

    return -$result;
  }
}
