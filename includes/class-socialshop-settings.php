<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SocialShop_Settings {

	/**
	 * The single instance of Instashop_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		$this->parent = $parent;
		$this->base = 'socialshop';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item () {
		$page = add_options_page( __( 'SocialShop', 'socialshop' ) , __( 'SocialShop', 'socialshop' ) , 'manage_options' , $this->parent->_token . '_settings' ,  array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'socialshop' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {
		$settings['standard'] = array(
			'title'					=> __( 'Product Galleries', 'socialshop' ),
			'description'			=> __( 'Display posts from your feed that contain the product being viewed (requires Business license).', 'socialshop' ),
			'fields'				=> array(
				array(
					'id' 			=> 'product_gallery_id',
					'label'			=> __( 'Gallery ID' , 'socialshop' ),
					'description'	=> sprintf( __( 'You can find this <a href="%s" target="_blank">here</a>.', 'socialshop' ), 'https://my.instashopapp.com/embed/wp-product'),
					'type'			=> 'text',
					'default'		=> '',
                    'placeholder'   => '',
				),
				array(
					'id' 			=> 'product_gallery_location',
					'label'			=> __( 'Gallery Location' , 'socialshop' ),
					'description'	=> __( 'Choose where the gallery will appear on a product page.', 'socialshop' ),
					'type'			=> 'select',
					'default'		=> '',
					'options'		=> array(
						'hide'          => __( 'Hide', 'socialshop' ),
						'above_data'    => __( 'Above Product Data', 'socialshop' ),
						'below_data'    => __( 'Below Product Data', 'socialshop' ),
						'below_upsell' 	=> __( 'Below Upsell', 'socialshop' ),
						'below_related'	=> __( 'Below Related Products', 'socialshop' ),
					),
				),
				array(
					'id' 			=> 'product_gallery_template',
					'label'			=> __( 'Gallery Template' , 'socialshop' ),
					'description'	=> __( 'HTML template used to display your gallery. <a href="https://support.instashopapp.com/kb/faq.php?id=43" target="_blank">Read more</a>', 'socialshop' ),
					'type'			=> 'textarea',
					'default'		=> '<section class="instashop related product" id="instashop-product-gallery">'."\n"
										.'<h2>Shop the Look</h2>'."\n"
										.'<div class="instashop-gallery" {data}>'."\n"
										.'</div>'."\n"
										.'</section>',
                    'placeholder'   => '',
				),
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
            $current_section = '';
            $tab             = filter_input( INPUT_POST, 'tab', FILTER_SANITIZE_STRING );

            if ( ! $tab ) {
                $tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
            }

            if ( $tab ) {
                $current_section = $tab;
            }

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
	}

	public function settings_section ( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {
        $tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			$html .= '<h2>' . __( 'SocialShop' , 'socialshop' ) . '</h2>' . "\n";

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;

				foreach ( $this->settings as $section => $data ) {
					// Set tab class
					$class = 'nav-tab';

					if ( $section === $tab || ( ! $tab && $c === 0 ) ) {
                        $class .= ' nav-tab-active';
                    }

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );

					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();
				settings_fields( $this->parent->_token . '_settings' );
				do_settings_sections( $this->parent->_token . '_settings' );
				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'socialshop' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	public function default_folders_updated ( $old_value, $new_value ) {
		$old_names = explode( ',', $old_value );
		$new_names = explode( ',', $new_value );

		array_walk( $old_names, 'trim' );
		array_walk( $new_names, 'trim' );

		$removed = array_diff( $old_names, $new_names );
		$new = array_diff( $new_names, $old_names );

		if ( count( $removed ) ) {
			Instashop_Folders::delete_from_all_groups_if_empty( $removed );
		}

		if ( count( $new ) ) {
			Instashop_Folders::add_to_all_groups_if_not_exists( $new );
		}

		var_dump($removed);
		var_dump($new);
		exit;
	}

	/**
	 * Get a setting value
	 *
	 * @param string $name
	 * @param mixed $default Value to return if setting not found
	 * @return string|mixed
	 */
	public function get ( $name, $default = false ) {
		return get_option( $this->base . $name, $default );
	}

	/**
	 * Main Instashop_Settings Instance
	 *
	 * Ensures only one instance of Instashop_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Instashop()
	 * @return Main Instashop_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
