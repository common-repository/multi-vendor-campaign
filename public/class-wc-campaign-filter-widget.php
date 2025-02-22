<?php

class Multi_Vendor_Campaign_Filter_Widget extends WP_Widget {

/**
 * Register widget with WordPress.
 */
function __construct() {
  parent::__construct(
    'multi_vendor_campaign_filter_widget',
    esc_html__( 'Multi Vendor Campaign Filter Widget', 'multi_vendor_campaign' ), 
    array( 'description' => esc_html__( 'Filter Products by Campaigns', 'multi_vendor_campaign' ), )
  );
}

/**
 * Front-end display of widget.
 *
 * @see WP_Widget::widget()
 *
 * @param array $args     Widget arguments.
 * @param array $instance Saved values from database.
 */
public function widget( $args, $instance ) {
  echo $args['before_widget'];
  if ( ! empty( $instance['title'] ) ) {
    echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
  }

  $campaign_id = filter_input(INPUT_GET, 'campaign_id', FILTER_SANITIZE_NUMBER_INT);
  $campaigns = array_column( Multi_Vendor_Campaign_Campaign_List::get_campaigns('-1'), 'title', 'id' );

  ?>

  <div class="woocommerce-campaign-filter">
    <select onchange="if (this.value) window.location.href=this.value">
      <option selected value="?campaign_id=-1"><?php esc_html_e('Select Campaign', 'multi_vendor_campaign') ?></option>
      <?php 
        foreach ($campaigns as $value => $label) :	
          echo "<option " . selected($campaign_id, $value) . " value='?campaign_id=$value'>$label</option>";
        endforeach 
      ?>
    </select>
  </div>

  <?php

  echo $args['after_widget'];
}

/**
 * Back-end widget form.
 *
 * @see WP_Widget::form()
 *
 * @param array $instance Previously saved values from database.
 */
public function form( $instance ) {
  $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Campaign Filter', 'multi_vendor_campaign' );
  ?>
  <p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'multi_vendor_campaign' ); ?></label> 
    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
  </p>
  <?php 
}

/**
 * Sanitize widget form values as they are saved.
 *
 * @see WP_Widget::update()
 *
 * @param array $new_instance Values just sent to be saved.
 * @param array $old_instance Previously saved values from database.
 *
 * @return array Updated safe values to be saved.
 */
public function update( $new_instance, $old_instance ) {
  $instance = array();
  $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';

  return $instance;
}

}