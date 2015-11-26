<?php
defined( 'ABSPATH' ) OR exit;

/*
 * Setup the settings page for Envira Tamer.
 *
 * @package    PackageName
 * @author     Mike Selander <author@example.com>
 * @since      1.0.0
 */
class EnviraTamerSettingsPage {

	/**
	 * dir
	 * Main directory path.
	 *
	 * @var string
	 * @access private
	 */
    private $dir;

    /**
	 * file
	 * Main file path.
	 *
	 * @var string
	 * @access private
	 */
	private $file;

	/**
	 * settings_base
	 * Prefix for the settings.
	 *
	 * @var string
	 * @access private
	 */
	private $settings_base;

	/**
	 * settings
	 * Array of the settings.
	 *
	 * @var array
	 * @access private
	 */
	private $settings;


	/**
	 * Constructor function.
	 *
	 * @see add_action, add_filter
	 */
	public function __construct( $file ) {

		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->settings_base = 'et_';

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this, 'add_settings_link' ) );

	}

	/**
	 * Initialise settings
	 */
	public function init() {

		$this->settings = $this->settings_fields();

	}


	/**
	 * Add settings page to admin menu.
	 *
	 * @see add_submenu_page
	 */
	public function add_menu_item() {

		add_submenu_page(
			'edit.php?post_type=envira',
			__( 'Envira Post Types', 'envira_tamer' ),
			__( 'Post Types', 'envira_tamer' ),
			'manage_options',
			'envira_tamer',
			array( $this, 'settings_page' )
		);

	}


	/**
	 * Add settings link to plugin list table.
	 *
	 * @param  array $links Existing links
	 * @return array Modified links
	 */
	public function add_settings_link( $links ) {

		$settings_link = '<a href="edit.php?post_type=envira&page=envira_tamer">' . __( 'Settings', 'envira_tamer' ) . '</a>';
  		array_push( $links, $settings_link );

  		return $links;

	}


	/**
	 * Build settings fields.
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {

			$key	= $post_type->name;
			$name	= $post_type->labels->name;

			if ( $key != 'attachment' ){
				$post_type_array[$key] = $name;
			}

		}

		$settings['standard'] = array(
			'title'					=> __( '', 'envira_tamer' ),
			'fields'				=> array(
				array(
					'id' 			=> 'envira_post_types',
					'label'			=> __( 'Post Types', 'plugin_textdomain' ),
					'description'	=> __( 'Choose the post types that you would like Envira to show up on, if any. If you leave all of the checkboxes empty, Envira will only show on the Add New Envira page.', 'plugin_textdomain' ),
					'type'			=> 'checkbox_multi',
					'options'		=> $post_type_array,
					'default'		=> array(  )
				)

			)
		);

		$settings = apply_filters( 'envira_tamer_fields', $settings );

		return $settings;

	}


	/**
	 * Register plugin settings.
	 *
	 *
	 */
	public function register_settings() {

		if ( !is_array( $this->settings ) ) {
			return;
		}

		foreach ( $this->settings as $section => $data ) {

			// Add section to page
			add_settings_section( $section, $data['title'], '', 'envira_tamer' );

			foreach( $data['fields'] as $field ) {

				// Validation callback for field
				$validation = '';
				if( isset( $field['callback'] ) ) {
					$validation = $field['callback'];
				}

				// Register field
				$option_name = $this->settings_base . $field['id'];
				register_setting( 'envira_tamer', $option_name, $validation );

				// Add field to page
				add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'envira_tamer', $section, array( 'field' => $field ) );
			} // end foreach ['fields']

		} // end foreach $settings

	}


	/**
	 * Generate HTML for displaying fields.
	 *
	 * @param  array $args Field data
	 */
	public function display_field( $args ) {

		$field = $args['field'];

		$html = $data = '';

		$option_name = $this->settings_base . $field['id'];
		$option = get_option( $option_name );

		//
		if( isset( $field['default'] ) ) {
			$data = $field['default'];
			if( $option ) {
				$data = $option;
			}
		}

		// Loop through the post type options
		foreach( $field['options'] as $k => $v ) {

			// Check our field if need be.
			$checked = false;
			if( in_array( $k, $data ) ) {
				$checked = true;
			}

			// Main output
			$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label><br>';

		}

		$html .= '<br/><span class="description">' . $field['description'] . '</span>';

		echo $html;

	}


	/**
	 * Validate individual settings field
	 *
	 * @param  string $data Inputted value
	 * @return string       Validated value
	 */
	public function validate_field( $data ) {

		if ( $data && strlen( $data ) > 0 && $data != '' ) {
			$data = urlencode( strtolower( str_replace( ' ' , '-' , $data ) ) );
		}

		return $data;

	}


	/**
	 * Print the page content for the settings section of Envira Tamer.
	 *
	 * @see settings_fields, do_settings_sections
	 */
	public function settings_page() {

		$html = "<style>th{display:none;}</style>";

		// Build page HTML
		$html .= '<div class="wrap" id="envira_tamer">' . "\n";
			$html .= '<h2>' . __( 'Envira Post Types' , 'envira_tamer' ) . '</h2>' . "\n";
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				ob_start();

					settings_fields( 'envira_tamer' );
					do_settings_sections( 'envira_tamer' );

				$html .= ob_get_clean();

				$html .= '<p class="submit">' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'envira_tamer' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

}