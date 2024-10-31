<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Multi_Vendor_Campaign_Campaign_List extends WP_List_Table
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
            'singular'  => esc_html__('Campaign', 'multi_vendor_campaign'),     //singular name of the listed records
            'plural'    => esc_html__('Campaigns', 'multi_vendor_campaign'),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ));
    }

    function column_name($item)
    {

        //Build row actions
        $actions = array(
            'edit'   => sprintf(
                '<a href="?page=%s&id=%s">%s</a>', 
                $this->plugin_name . '-new-campaigns', 
                esc_attr( $item['id'] ), 
                esc_html__('Edit', 'multi_vendor_campaign')
            ),
            'delete' => sprintf(
                '<a href="?page=%s&action=delete&id=%s">%s</a>', 
                esc_attr( $_REQUEST['page'] ), 
                esc_attr( $item['id'] ), 
                esc_html__('Delete', 'multi_vendor_campaign')
            ),
        );

        //Return the title contents
        return sprintf(
            '%1$s %2$s',
            /*$1%s*/
            $item['title'],
            /*$2%s*/
            $this->row_actions($actions)
        );
    }

    function column_activation_status($item)
    {

        $now = strftime('%Y-%m-%dT%H:%M:%S', strtotime('now'));
        $campaign_begin = strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['activation_date']));
        $campaign_end = strftime('%Y-%m-%dT%H:%M:%S', strtotime($item['deactivation_date']));

        // it's out of date
        if (($now < $campaign_begin) || ($now > $campaign_end)) {
            $condition = '<span class="rad_mvc_
rad_mvc_disable"></span>';
        } else {
            $condition = '<span class="rad_mvc_
rad_mvc_enable"></span>';
        }

        //Return the title contents
        return sprintf(
            '%1$s',
            /*$1%s*/
            $condition
        );
    }

    function column_publish($item)
    {

        if ($item['enable'] == 1)
            $condition = '<span class="rad_mvc_
rad_mvc_enable"></span>';
        else
            $condition = '<span class="rad_mvc_
rad_mvc_disable"></span>';

        //Return the title contents
        return sprintf(
            '%1$s',
            /*$1%s*/
            $condition
        );
    }

    function column_activation_date($item)
    {

        $begin = strftime('%Y-%m-%d %H:%M:%S', strtotime($item['activation_date']));
        $end = strftime('%Y-%m-%d %H:%M:%S', strtotime($item['deactivation_date']));

        //Return the title contents
        return sprintf(
            '%1$s to %1$s',
            $begin,
            $end
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/
            $item['id']                //The value of the checkbox should be the record's id
        );
    }

    function column_products_link($item)
    {
        return sprintf(
            '<a href="?page=%s&id=%s"><span class="rad-mvc-icon-product dashicons-admin-post"></span>%s</a>',
            $this->plugin_name . '-campaign-products',
            $item['id'],
            esc_html__('Products', 'multi_vendor_campaign')
        );
    }

    function column_pending_num($item)
    {
        $campaignsClass = new Multi_Vendor_Campaign_Campaigns();
        $pending_products_num = $campaignsClass->get_number_of_pending_products($item['id']);
        $class = 'rad-mvc-pending-products_num';
        if ($pending_products_num > 0) {
            $class .= ' new';
        }

        return sprintf(
            '<span class="%s">%d</span>',
            $class,
            $pending_products_num
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb'               => '<input type="checkbox" />', //Render a checkbox instead of text
            'name'            => esc_html__('Name', 'multi_vendor_campaign'),
            'products_link'    => esc_html__('Subscribed Products', 'multi_vendor_campaign'),
            'pending_num'    => esc_html__('Pending Products', 'multi_vendor_campaign'),
            'activation_date'  => esc_html__('Activation Date', 'multi_vendor_campaign'),
            'publish'           => esc_html__('Published', 'multi_vendor_campaign'),
            'activation_status' => esc_html__('Active', 'multi_vendor_campaign'),

        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'name'     => array('name', false),
            'publish'     => array('publish', false),
            'activation_date'     => array('activation_date', false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete'    => esc_html__('Delete', 'multi_vendor_campaign')
        );
        return $actions;
    }

    function process_bulk_action()
    {

        //Detect when a bulk action is being triggered...
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'rad_mvc_table_campaign'; // do not forget about tables prefix
        $table_campaign_product = $wpdb->base_prefix . 'rad_mvc_table_campaign_product';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) 
                ? intval( $_REQUEST['id'] )
                : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $table_name WHERE id IN(%s)",
                        $ids
                    )
                );
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $table_campaign_product WHERE campaign_id IN(%s)",
                        $ids
                    )
                );
            }
        }
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
         * case, we'll handle them within our campaign just to keep things clean.
         */
        $this->process_bulk_action();

        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own campaign classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * campaign slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
         * our data. In a real-world implementation, you will probably want to
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $data = self::get_campaigns($per_page, $current_page);



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
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_title_for_query($_REQUEST['orderby']) : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? sanitize_title_for_query( $_REQUEST['order'] ) : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');


        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own campaign classes.
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
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_campaigns($per_page = 5, $page_number = 1)
    {

        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->base_prefix}rad_mvc_table_campaign";

        if ( !empty( $_REQUEST['s'] ) ) {
            $search = strtolower( esc_sql($_REQUEST['s']) );
            $sql .= " WHERE LOWER(title) LIKE %s";
          }

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY %s';
            $sql .= !empty($_REQUEST['order']) ? ' %s' : ' ASC';
        }

        $query = $wpdb->prepare(
            $sql,
            '%' . $wpdb->esc_like($search) . '%',
            esc_sql($_REQUEST['orderby']),
            esc_sql($_REQUEST['order'])
        );

        $result = $wpdb->get_results($query, 'ARRAY_A');

        return $result;
    }
}
