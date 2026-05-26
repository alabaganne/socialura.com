<?php
/**
 * @license GPL-2.0+
 *
 * Modified by popupmaker on 30-May-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */
/**
 * Includes the composer Autoloader used for packages and classes in the classes/ directory.
 *
 * @package CodeAtlantic\Autoloader
 */

namespace PopupMaker\Vendor\CodeAtlantic\Autoloader;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Static-only class.
	 */
	private function __construct() {
	}

	/**
	 * Require the autoloader and return the result.
	 *
	 * If the autoloader is not present, let's log the failure and display a nice admin notice.
	 *
	 * @param string $name Plugin name for error messaging.
	 * @param string $path Plugin path.
	 * @param string $failure_notice Failure notice to display.
	 *
	 * @return boolean
	 */
	public static function init( $name = '', $path = '', $failure_notice = null ) {
		$autoloader = $path . '/vendor/autoload.php';

		if ( ! \is_readable( $autoloader ) ) {
			self::missing_autoloader( $name, $failure_notice );

			return false;
		}

		return require $autoloader;
	}

	/**
	 * If the autoloader is missing, add an admin notice.
	 *
	 * @param string $plugin_name Plugin name for error messaging.
	 * @param string $failure_notice Failure notice to display.
	 */
	protected static function missing_autoloader( $plugin_name = '', $failure_notice = null ) {
		/* translators: 1. Plugin name */
		$message = $failure_notice ? $failure_notice : sprintf( esc_html__( 'Your installation of %1$s is incomplete. If you installed %1$s from GitHub, please refer to this document to set up your development environment.' ), $plugin_name );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				$message
			);
		}

		add_action(
			'admin_notices',
			function () use ( $message ) {
				?>
					<div class="notice notice-error">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				<?php
			}
		);
	}
}
