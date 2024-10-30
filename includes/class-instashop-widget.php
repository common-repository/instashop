<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class InstaShopGalleryWidget extends WP_Widget {

	/**
	 * Widget setup
	 */
	public function __construct () {
		parent::__construct( 'instashop-gallery', __('SocialShop Gallery (deprecated)', 'socialshop' ), array(
			'description' => __( 'Display a SocialShop gallery.', 'socialshop' )
        ) );
	}

	/**
	 * Display widget
     * 
     * @param  array $args
     * @param  array $instance
     * @access public
	 * @since  1.0.0
	 */
	public function widget ( $args, $instance ) {
		extract( $args, EXTR_SKIP );

		$title = apply_filters( 'widget_title', $instance['title'] );
		$gallery_id = $instance['gallery_id'];

        if ( !empty( $gallery_id ) ) {
            echo $before_widget;

            if ( ! empty( $title ) ) {
                echo $before_title . $title . $after_title;
            }

            echo sprintf( '<div class="instashop-gallery" data-gallery="%s"></div>', esc_attr( $gallery_id ) );
            echo $after_widget;

            $socialShop = SocialShop();

            wp_register_script( $socialShop->_token . '-gallery', SocialShop::get_js_gallery_url(), SocialShop::get_js_gallery_version() );
            wp_enqueue_script( $socialShop->_token . '-gallery' );
        }
	}

	/**
	 * Update widget
     * 
     * @param  array $new_instance
     * @param  array $old_instance
     * @access public
	 * @since  1.0.0
     * @return array
	 */
	public function update ( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = esc_attr( $new_instance['title'] );
		$instance['gallery_id'] = $new_instance['gallery_id'];

		return $instance;

	}

	/**
	 * Widget setting
     * 
     * @param  array $instance
     * @access public
	 * @since  1.0.0
	 */
	public function form ( $instance ) {

		/* Set up some default widget settings. */
        $defaults = array(
            'title' => 'Shop Instagram',
            'gallery_id' => '',
        );
        
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = esc_attr( $instance['title'] );
		$gallery_id = $instance['gallery_id'];
	?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'socialshop' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>" />
		</p><p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'gallery_id' ) ); ?>"><?php _e( 'Gallery ID:', 'socialshop' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'gallery_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'gallery_id' ) ); ?>" type="text" value="<?php echo $gallery_id; ?>" />
		</p>

	<?php
	}

}
