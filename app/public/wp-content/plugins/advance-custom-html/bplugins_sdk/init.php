<?php

/**
 * @package     bPlugins
 * @copyright   Copyright (c) 2015, bPlugins.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 * @since       1.0.0
 */
$this_sdk_version = '2.1.0';
if ( !class_exists( 'BPluginsFSLite' ) ) {
    // require all elements
    require_once dirname( __FILE__ ) . '/require.php';
    class BPluginsFSLite {
        protected $file = null;

        public $prefix = '';

        protected $config = null;

        protected $__FILE__ = __FILE__;

        private $lc = null;

        function __construct( $__FILE__, $config = [] ) {
            $this->__FILE__ = $__FILE__;
            $config_file = plugin_dir_path( $this->__FILE__ ) . 'bsdk_config.json';
            if ( file_exists( $config_file ) ) {
                $this->config = (object) wp_parse_args( json_decode( file_get_contents( $config_file ) ), FS_LITE_CONFIG );
            } else {
                $config_file = plugin_dir_path( $this->__FILE__ ) . basename( __DIR__ ) . '/config.json';
                if ( file_exists( $config_file ) ) {
                    $this->config = (object) wp_parse_args( json_decode( file_get_contents( $config_file ) ), FS_LITE_CONFIG );
                } else {
                    $this->config = (object) wp_parse_args( $config, FS_LITE_CONFIG );
                }
            }
            $this->config = (object) wp_parse_args( $config, (array) $this->config );
            $this->prefix = $this->config->prefix ?? '';
            if ( \is_admin() ) {
                if ( $this->config->features->optIn ) {
                    new FSActivate($this->config, $__FILE__);
                }
            }
            $this->register();
        }

        function register() {
            add_action( 'plugins_loaded', [$this, 'i18n'] );
        }

        function i18n() {
            load_plugin_textdomain( 'bPlugins-sdk', false, plugin_dir_url( __FILE__ ) . '/languages/' );
        }

        public function can_use_premium_feature() {
            return $this->is_premium();
        }

        public function is_premium() {
            return $this->lc->isPipe ?? false;
        }

        public function uninstall_plugin() {
            deactivate_plugins( plugin_basename( $this->__FILE__ ) );
        }

        function can_use_premium_code() {
            return $this->is_premium();
        }

        function set_basename( $uninstall, $__FILE__ ) {
            $basename = basename( $__FILE__ );
            if ( is_plugin_active( $this->config->slug . '/' . $basename ) ) {
                deactivate_plugins( $this->config->slug . '/' . $basename );
            }
            if ( is_plugin_active( $this->config->slug . '-pro/' . $basename ) ) {
                deactivate_plugins( $this->config->slug . '-pro/' . $basename );
            }
        }

    }

}
if ( !function_exists( 'fs_lite_dynamic_init' ) ) {
    function fs_lite_dynamic_init(  $module  ) {
        try {
            $caller = debug_backtrace();
            if ( isset( $caller[0]['file'] ) ) {
                $module['__FILE__'] = $caller[0]['file'];
            }
            if ( !isset( $module['__FILE__'] ) ) {
                throw new Error("No __FILE__");
            }
            if ( dirname( plugin_basename( $module['__FILE__'] ) ) !== $module['slug'] && function_exists( 'fs_dynamic_init' ) ) {
                return fs_dynamic_init( $module );
            }
            $module['platform'] = 'freemius';
            $fs = new BPluginsFSLite($module['__FILE__'], $module);
            return $fs;
        } catch ( \Throwable $th ) {
            echo $th->getMessage();
        }
    }

}