<?php
/**
 * Prerequisite handler.
 *
 * @package PopupMaker\Vendor\CodeAtlantic\PrerequisiteChecks;
 *
 * @license GPL-2.0+
 * Modified by popupmaker on 30-May-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace PopupMaker\Vendor\CodeAtlantic\PrerequisiteChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Prerequisite handler.
 *
 * @version 1.3.1
 */
class Prerequisites {

	/**
	 * Cache accessible across instances.
	 *
	 * @var array
	 */
	public static $cache = [];

	/**
	 * Array of checks to perform.
	 *
	 * @var array
	 */
	protected $checks = [];

	/**
	 * Array of detected failures.
	 *
	 * @var array
	 */
	protected $failures = [];

	/**
	 * Array of config arguments.
	 * 
	 * @var array
	 */
	protected $config = [];

	/**
	 * Instantiate prerequisite checker.
	 *
	 * @param array $requirements Array of requirements.
	 * @param string $text_domain Text domain.
	 */
	public function __construct( $requirements = [], $config = [] ) {
		$this->config = wp_parse_args( $config, [
			'slug'		  => null,
			'name'		  => null,
			'text_domain' => 'default',
		] );

		foreach ( $requirements as $arguments ) {
			switch ( $arguments['type'] ) {
				case 'php':
					$this->checks[] = wp_parse_args(
						$arguments,
						[
							'type'    => 'php',
							'version' => '5.6',
						]
					);
					break;
				// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText
				case 'wordpress':
				case 'wp':
					$this->checks[] = wp_parse_args(
						$arguments,
						[
							'type'    => 'wp',
							'version' => '5.6',
						]
					);
					break;
				case 'plugin':
					// If slug is set and basename is empty, set basename from slug.
					if ( empty( $arguments['basename'] ) ) {
						// If $slug does not contain / & .php, assume its a folder name & append.
						if ( false === strpos( $arguments['slug'], '/' ) || false === strpos( $arguments['slug'], '.php' ) ) {
							$arguments['basename'] = $arguments['slug'] . '/' . $arguments['slug'] . '.php';
						} else {
							$arguments['basename'] = $arguments['slug'];
						}
					}

					if ( isset( $arguments['check_installed'] ) ) {
						$arguments['required'] = true;
					}

					$this->checks[] = wp_parse_args(
						$arguments,
						[
							'type'            => 'plugin',
							// Slug or basename.
							'slug'            => '',
							'basename'        => '',
							'name'            => '',
							'version'         => '',
							'required'        => false,
							// Deprecated, use required.
							'check_installed' => false,
							'dep_label'       => '',
						]
					);
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Check requirements.
	 *
	 * @param boolean $return_on_fail Whether it should stop processing if one fails.
	 *
	 * @return bool
	 */
	public function check( $return_on_fail = false ) {
		$end_result = true;

		foreach ( $this->checks as $check ) {
			$result = $this->check_handler( $check );

			if ( false === $result ) {
				if ( true === $return_on_fail ) {
					return false;
				}

				$end_result = false;
			}
		}

		return $end_result;
	}

	/**
	 * Render notices when appropriate.
	 */
	public function setup_notices() {
		add_action( 'admin_notices', [ $this, 'render_notices' ] );
	}

	/**
	 * Handle individual checks by mapping them to methods.
	 *
	 * @param array $check Requirement check arguments.
	 *
	 * @return bool
	 */
	public function check_handler( $check ) {
		return method_exists( $this, 'check_' . $check['type'] ) ? $this->{'check_' . $check['type']}( $check ) : false;
	}

	/**
	 * Report failure notice to the queue.
	 *
	 * @param array $check_args Array of check arguments.
	 */
	public function report_failure( $check_args ) {
		$this->failures[] = $check_args;
	}

	/**
	 * Get a list of failures.
	 *
	 * @return array
	 */
	public function get_failures() {
		return $this->failures;
	}

	/**
	 * Check PHP version against args.
	 *
	 * @param array $check_args Array of args.
	 *
	 * @return bool
	 */
	public function check_php( $check_args ) {
		if ( false === version_compare( phpversion(), $check_args['version'], '>=' ) ) {
			$this->report_failure( $check_args );
			return false;
		}

		return true;
	}


	/**
	 * Check PHP version against args.
	 *
	 * @param array $check_args Array of args.
	 *
	 * @return bool
	 */
	public function check_wp( $check_args ) {
		global $wp_version;

		if ( false === version_compare( $wp_version, $check_args['version'], '>=' ) ) {
			$this->report_failure( $check_args );
			return false;
		}

		return true;
	}

	/**
	 * Check plugin requirements.
	 *
	 * @param array $check_args Array of args.
	 *
	 * @return bool
	 */
	public function check_plugin( $check_args ) {
		$plugin_basename = $check_args['basename'];

		$installed = $this->plugin_is_installed( $plugin_basename );

		if ( $check_args['required'] && ! $installed ) {
			// Check if not installed, if so the plugin is not activated.
			$this->report_failure(
				array_merge(
					$check_args,
					[
						// Report not_activated status.
						'not_installed' => true,
					]
				)
			);

			return false;
		} elseif ( ! $check_args['required'] && ! $installed ) {
			// If not required and not installed, we can bail now > true.
			return true;
		}

		$active = $installed && $this->plugin_is_active( $plugin_basename );

		/**
		 * The following checks are performed in this order for performance reasons.
		 *
		 * We start with most cached option, to least in hopes of a hit early.
		 *
		 * 1. If active and not checking version.
		 * 2. If active and outdated.
		 * 3. If not active and installed.
		 * 4. If not installed
		 */
		if ( true === $active ) {
			// If required version is set & plugin is active, check that first.
			if ( isset( $check_args['version'] ) ) {
				$version = $this->get_plugin_data( $plugin_basename, 'Version' );

				// If its higher than the required version, we can bail now > true.
				if ( version_compare( $version, $check_args['version'], '>=' ) ) {
					return true;
				} else {
					// If not updated, report the failure and bail > false.
					$this->report_failure(
						array_merge(
							$check_args,
							[
								// Report not_updated status.
								'not_updated' => true,
							]
						)
					);
					return false;
				}
			} else {
				// If the plugin is active, with no required version, were done > true.
				return true;
			}
		} elseif ( $installed ) {
			$this->report_failure(
				array_merge(
					$check_args,
					[
						// Report not_activated status.
						'not_activated' => true,
					]
				)
			);
			return false;
		}

		return false;
	}

	/**
	 * Internally cached get_plugin_data/get_file_data wrapper.
	 *
	 * @param string $slug Plugins `folder/file.php` slug.
	 * @param string $header Specific plugin header needed.
	 * @return mixed
	 */
	private function get_plugin_data( $slug, $header = null ) {
		if ( ! isset( static::$cache['get_plugin_data'][ $slug ] ) ) {
			$headers = \get_file_data( WP_PLUGIN_DIR . '/' . $slug, [
				'Name'    => 'Plugin Name',
				'Version' => 'Version',
			], 'plugin' );

			static::$cache['get_plugin_data'][ $slug ] = $headers;
		}

		$plugin_data = static::$cache['get_plugin_data'][ $slug ];

		if ( empty( $header ) ) {
			return $plugin_data;
		}

		return isset( $plugin_data[ $header ] ) ? $plugin_data[ $header ] : null;
	}


	/**
	 * Check if an addon is installed.
	 *
	 * @param string $plugin_basename Plugin slug.
	 *
	 * @return bool
	 */
	public function plugin_is_installed( $plugin_basename ) {
		static $installed_plugins = null;

		if ( null === $installed_plugins ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$installed_plugins = \get_plugins();
		}

		return isset( $installed_plugins[ $plugin_basename ] );
	}

	/**
	 * Check if plugin is active.
	 *
	 * @param string $plugin_basename Plugin basename to check for.
	 *
	 * @return bool
	 */
	protected function plugin_is_active( $plugin_basename ) {
		return is_plugin_active( $plugin_basename );
	}

	/**
	 * Get php error message.
	 *
	 * @param array $failed_check_args Check arguments.
	 *
	 * @return string
	 */
	public function get_php_message( $failed_check_args ) {
		return sprintf(
			/* translators: 1. Plugin Name, Requirement name (WordPress|PHP)., 2. Version Number. */
			__( 'The plugin "%1$s" requires <b>%2$s v%3$s</b> or higher in order to run.', $this->config['text_domain'] ),
			isset( $this->config['name'] ) ? $this->config['name'] : '',
			__( 'PHP', 'default', $this->config['text_domain'] ),
			$failed_check_args['version']
		);
	}

	/**
	 * Get wp error message.
	 *
	 * @param array $failed_check_args Check arguments.
	 *
	 * @return string
	 */
	public function get_wp_message( $failed_check_args ) {
		return sprintf(
			/* translators: 1. Plugin Name, Requirement name (WordPress|PHP)., 2. Version Number. */
			__( 'The plugin "%1$s" requires <b>%2$s v%3$s</b> or higher in order to run.', $this->config['text_domain'] ),
			isset( $this->config['name'] ) ? $this->config['name'] : '',
			__( 'WordPress', 'default', $this->config['text_domain'] ),
			$failed_check_args['version']
		);
	}

	/**
	 * Get plugin error message.
	 *
	 * @param array $failed_check_args Get helpful error message.
	 *
	 * @return string
	 */
	public function get_plugin_message( $failed_check_args ) {
		$slug = $failed_check_args['slug'];
		// Without file path.
		$short_slug = explode( '/', $slug );
		$short_slug = $short_slug[0];
		$name       = $failed_check_args['name'];

		if ( isset( $failed_check_args['not_activated'] ) ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $slug ), 'activate-plugin_' . $slug ) );
			$link = '<a href="' . $url . '">' . __( 'activate it', $this->config['text_domain'] ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 4. `activate it` link. */
				__( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!', $this->config['text_domain'] ),
				isset( $this->config['name'] ) ? $this->config['name'] : '',
				'<strong>' . $name . '</strong>',
				$link
			);
		} elseif ( isset( $failed_check_args['not_updated'] ) ) {
			$url  = esc_url( wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $slug ), 'upgrade-plugin_' . $slug ) );
			$link = '<a href="' . $url . '">' . __( 'update it', $this->config['text_domain'] ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 3. Version number, 4. `update it` link. */
				__( 'The plugin "%1$s" requires %2$s v%3$s or higher! Please %4$s to continue!', $this->config['text_domain'] ),
				isset( $this->config['name'] ) ? $this->config['name'] : '',
				'<strong>' . $name . '</strong>',
				'<strong>' . $failed_check_args['version'] . '</strong>',
				$link
			);
		} else {
			$url  = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $short_slug ), 'install-plugin_' . $short_slug ) );
			$link = '<a href="' . $url . '">' . __( 'install it', $this->config['text_domain'] ) . '</a>';

			$text = sprintf(
				/* translators: 1. Plugin Name, 2. Required Plugin Name, 3. `install it` link. */
				__( 'The plugin "%1$s" requires %2$s! Please %3$s to continue!', $this->config['text_domain'] ),
				isset( $this->config['name'] ) ? $this->config['name'] : '',
				'<strong>' . $name . '</strong>',
				$link
			);
		}

		return $text;
	}

	/**
	 * Render needed admin notices.
	 *
	 * @return void
	 */
	public function render_notices() {
		foreach ( $this->failures as $failure ) {
			$class   = 'notice notice-error';
			$message = method_exists( $this, 'get_' . $failure['type'] . '_message' ) ? $this->{'get_' . $failure['type'] . '_message'}( $failure ) : false;

			/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
		}
	}
}
