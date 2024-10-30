<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SocialShop {
	const JS_GALLERY_URL = 'https://s3.amazonaws.com/instashop/js-gz/embed/embed-1.6.8.js';
	const JS_GALLERY_VERSION = '1.6.8';

	/**
	 * The single instance of SocialShop.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.2.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * 
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'socialshop';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new SocialShop_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Add our shortcode
		add_shortcode( 'instashop', array( $this, 'gallery_shortcode' ) );
		add_shortcode( 'socialshop', array( $this, 'gallery_shortcode' ) );

		// Register widget
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		// Attempt to prevent search interference for REST requests from other plugins
		add_action('rest_api_init', array( $this, 'disable_search_plugins' ) );
	}

	/**
	 * Load admin CSS.
	 * 
	 * @access  public
	 * @since   1.0.0
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		$screen = get_current_screen();

		if ( $screen && $screen->id == 'toplevel_page_socialshop' ) {
			wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
			wp_enqueue_style( $this->_token . '-admin' );
		}
	}

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		$screen = get_current_screen();

		if ( $screen && $screen->id == 'toplevel_page_socialshop' ) {
			wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/iframe-resizer' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script( $this->_token . '-admin' );
		}
	}

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'socialshop', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain () {
	    $domain = 'socialshop';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Do gallery shortcode, render gallery placeholder and enqueue javascript
	 *
	 * @param   array $attr {
	 * 		An array of attributes for the generated div placeholder. All are optional except must have either valid domain or gallery.
	 *
	 *		@type string $class               Additional CSS classes to add to div
	 *		@type string $source              Fully qualified URL (including trailing slash) indicating location of remote JS (for testing purposes only)
	 * 		@type string $domain              Domain name associated with SocialShop account
	 * 		@type string $gallery             Gallery ID associated with SocialShop account
	 * 		@type string $layout              Gallery layout, must be supported by SocialShop license. One of: grid, layout, collage
	 * 		@type string $theme               Theme to use for display of gallery and modal. One of: light, dark
	 * 		@type bool   $prices              Display prices in modal
	 * 		@type bool   $gallery-pins        Display pins in gallery
	 * 		@type bool   $zoom                Use zoom effect on mouse-over within gallery
	 * 		@type bool   $overlay             Display overlay effect on mouse-over within gallery
	 * 		@type string $overlay-label       Overlay label within gallery overlay effect
	 * 		@type string $pin-type            Displayed pin type within gallery and modal. None hides pins in modal. One of: circle, radar, star, none.
     *      @type string $product-source-lang Use product id from specified source language if WPML is enabled.
	 * }
	 * @access  public
	 * @since   1.0.0
	 * @return  string
	 */
	public function gallery_shortcode ( $attr ) {
        global $product;

		$domain = wp_parse_url( get_home_url(), PHP_URL_HOST);

        $attr = shortcode_atts( array(
            'class'               => false,
            'source'              => false,
            'domain'              => $domain,
            'gallery'             => 'default',
            'layout'              => false,
            'theme'               => false,
            'prices'              => false,
            'gallery-pins'        => false,
            'zoom'                => false,
            'overlay'             => false,
            'overlay-label'       => false,
            'pin-type'            => false,
            'product-source-lang' => null,
            'columns'             => 0,
        ), $attr, 'socialshop' );

        if ( $attr['domain'] == 'false' ) {
			$attr['domain'] = false;
		}

		$data = array('gallery' => $attr['gallery']);

		if ( $attr['source'] !== false ) $data['source'] = $attr['source'];
		if ( $attr['domain'] !== false ) $data['shop-domain'] = $attr['domain'];
		if ( $attr['layout'] !== false ) $data['layout'] = $attr['layout'];
		if ( $attr['theme'] !== false ) $data['theme'] = $attr['theme'];
		if ( $attr['prices'] !== false ) $data['prices'] = $attr['prices'];
		if ( $attr['gallery-pins'] !== false ) $data['gallery-pins'] = $attr['gallery-pins'];
		if ( $attr['zoom'] !== false ) $data['zoom'] = $attr['zoom'];
		if ( $attr['overlay'] !== false ) $data['overlay'] = $attr['overlay'];
		if ( $attr['overlay-label'] !== false ) $data['overlay-label'] = $attr['overlay-label'];
		if ( $attr['pin-type'] !== false ) $data['pin-type'] = $attr['pin-type'];
		if ( (int)$attr['columns'] ) $data['columns'] = (int)$attr['columns'];

		if ( $attr['gallery'] !== 'default' && $product ) {
		    if ( self::woocommerce_version_check() ) {
		        $data['product-id'] = $product->get_id();
            } else {
                $data['product-id'] = $product->id;
            }

            $product_source_language = apply_filters( 'instashop_gallery_product_source_lang', null, $attr['gallery'], $data['product-id'] );
            $product_source_language = apply_filters( 'socialshop_gallery_product_source_lang', $product_source_language, $attr['gallery'], $data['product-id'] );

		    if ( $attr['product-source-lang'] ) {
                $product_source_language = $attr['product-source-lang'];
            }

            if ( $product_source_language ) {
                $data['product-id'] = apply_filters( 'wpml_object_id', $data['product-id'], 'product', true, $product_source_language );
            }
        }

		$source = apply_filters( 'instashop_gallery_source', '' );
		$source = apply_filters( 'socialshop_gallery_source', $source );

		if ( $source ) {
			$data['source'] = $source;
		}

		$data_string = '';

		foreach ( $data as $name => $value ) {
			$data_string .= sprintf(' data-%s="%s"', $name, esc_attr( $value ) );
		}

		wp_register_script( $this->_token . '-gallery', self::get_js_gallery_url(), self::get_js_gallery_version() );
		wp_enqueue_script( $this->_token . '-gallery' );

		$output = sprintf('<div class="socialshop-gallery instashop-gallery%s" %s></div>', $attr['class'] ? ' ' .$attr['class'] : '', $data_string );
		$output = apply_filters( 'instashop_gallery_shortcode', $output, $attr );
		$output = apply_filters( 'socialshop_gallery_shortcode', $output, $attr );

		return $output;
	}

	/**
	 * Register our gallery widget
	 */
	public function register_widget () {
		register_widget( 'InstaShopGalleryWidget' );
		register_widget( 'SocialShopGalleryWidget' );
	}

	/**
	 * Some plugins may interfere with search results for REST product queries,
	 * disable known plugins
	 */
	public function disable_search_plugins () {
		remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
		remove_filter( 'the_posts', 'relevanssi_query', 99 );
	}

	/**
	 * Display product gallery
	 */
	public function display_product_gallery () {
		$gallery_id = $this->settings->get( 'product_gallery_id' );
		$product_id = get_the_ID();
		$template = apply_filters( 'instashop_gallery_template', $this->settings->get( 'product_gallery_template' ) );
		$template = apply_filters( 'socialshop_gallery_template', $template );

		if ( $gallery_id && $product_id ) {
			$data = array(
				'gallery' => $gallery_id,
				'product-id' => $product_id,
			);

            /**
             * Filters the language to use for determining the product ID.
             *
             * WPML plugin duplicates the products, by specifying the source language you can use the original item ID.
             *
             * @since 1.4.0
             *
             * @param string $language_code
             * @param int    $gallery_id
             * @param int    $product_id
             */
			$product_source_language = apply_filters( 'instashop_gallery_product_source_lang', null, $gallery_id, $product_id );
			$product_source_language = apply_filters( 'socialshop_gallery_product_source_lang', $product_source_language, $gallery_id, $product_id );

			if ( $product_source_language ) {
                $data['product-id'] = apply_filters( 'wpml_object_id', $data['product-id'], 'product', true, $product_source_language );
            }

			$source = apply_filters( 'socialshop_gallery_source', '' );
			$source = apply_filters( 'instashop_gallery_source', $source );

			if ( $source ) {
				$data['source'] = $source;
			}

			$data_string = '';

			foreach ( $data as $name => $value ) {
				$data_string .= sprintf(' data-%s="%s"', $name, esc_attr( $value ) );
			}

			wp_register_script( $this->_token . '-gallery', self::get_js_gallery_url(), self::get_js_gallery_version() );
			wp_enqueue_script( $this->_token . '-gallery' );

			$output = str_replace( '{data}', $data_string, $template );
			$output = apply_filters( 'instashop_product_gallery', $output, $data );
			echo apply_filters( 'socialshop_product_gallery', $output, $data );
		}
	}

	/**
	 * Set the settings and optionally hook anything based on them
	 * @param SocialShop_Settings $settings
	 * @param bool $add_hooks
	 */
	public function set_settings ( $settings, $add_hooks = true ) {
		$this->settings = $settings;

		if ($add_hooks) {
			$gallery_id = $this->settings->get( 'product_gallery_id' );
			$location = $this->settings->get( 'product_gallery_location' );

			if ($gallery_id && $location != 'hide') {
				switch ( $location ) {
					case 'above_data':
					default:
						$priority = 9;
						break;

					case 'below_data':
						$priority = 11;
						break;

					case 'below_upsell':
						$priority = 16;
						break;

					case 'below_related':
						$priority = 21;
						break;
				}

				$priority = apply_filters( 'instashop_product_gallery_display_priority', $priority, $location, $gallery_id );
				$priority = apply_filters( 'socialshop_product_gallery_display_priority', $priority, $location, $gallery_id );

				add_action( 'woocommerce_after_single_product_summary', array( $this, 'display_product_gallery' ), $priority );
			}
		}
	}

	/**
	 * Get URL for JS gallery
	 *
	 * @return string
	 */
	public static function get_js_gallery_url  () {
		$url = apply_filters( 'instashop_js_gallery_url', self::JS_GALLERY_URL, self::JS_GALLERY_VERSION );

		return apply_filters( 'socialshop_js_gallery_url', $url, self::JS_GALLERY_VERSION );
	}

	/**
	 * Get version string of JS gallery
	 *
	 * @return string
	 */
	public static function get_js_gallery_version  () {
		$url = apply_filters( 'instashop_js_gallery_version', self::JS_GALLERY_VERSION, self::JS_GALLERY_URL );

		return apply_filters( 'socialshop_js_gallery_version', $url, self::JS_GALLERY_URL );
	}

	/**
	 * Main SocialShop Instance
	 *
	 * Ensures only one instance of SocialShop is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see SocialShop()
	 * @return Main SocialShop instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	}

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install () {
		$this->_log_version_number();
	}

	/**
	 * Log the plugin version number.
	 *
	 * @access  private
	 * @since   1.0.0
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	}

    /**
     * Check WooCommerce version.
     *
     * @since 1.3.0
     *
     * @param string $version
     * @return bool
     */
    public static function woocommerce_version_check( $version = '3.0' ) {
        if ( class_exists( 'WooCommerce' ) ) {
            global $woocommerce;

            if ( version_compare( $woocommerce->version, $version, '>=' ) ) {
                return true;
            }
        }

        return false;
    }
}
