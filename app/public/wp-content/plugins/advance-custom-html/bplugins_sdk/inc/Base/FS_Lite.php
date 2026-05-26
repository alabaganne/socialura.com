<?php
class FS_Lite{

    protected $prefix = '';
    protected $config = null;
    protected $base_name = null;
    protected $plugin_name = '';
    protected $product = "";
    protected $key = null;
    protected $__FILE__ = null;
    protected $_upgraded = false;
    protected $version = false;
    protected $dir = __DIR__;
    protected $path = null;
    protected $blockHandler = null;
    protected $fs_version = '2.5.12';
    protected $accounts_key = 'bs_accounts';

    function __construct($config, $__FILE__){
        $this->config = $config;
        $this->prefix = $this->config->prefix;
        $this->__FILE__ = $__FILE__;
        $this->base_name = plugin_basename( $this->__FILE__ );
        $this->blockHandler = $this->config->blockHandler;
        $this->path =  $this->config->slug .'/'. basename($this->__FILE__);
      
        if(!class_exists('Freemius_Lite') && file_exists(__DIR__.'/Freemius_Lite.php') ){
            require_once(__DIR__.'/Freemius_Lite.php');
        }

        if( ! function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        add_action('init', [$this, 'init']);
    }

    function init(){
        $plugin_data = \get_plugin_data( $this->__FILE__ );
        $this->plugin_name = $plugin_data['Name'];

        $this->version = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost' ? time() :  $plugin_data['Version'];
    }
    
    function get_anonymous_id( $blog_id = null ) {
        $unique_id = get_option( 'unique_id', null, $blog_id );

        
        if ( empty( $unique_id ) || ! is_string( $unique_id ) ) {
            $key = $this->fs_strip_url_protocol( site_url() );
            
            $secure_auth = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
            
            if ( empty( $secure_auth ) ||
                 false !== strpos( $secure_auth, ' ' ) ||
                 'put your unique phrase here' === $secure_auth
                 ) {
                $secure_auth = md5( microtime() );
            }

            $unique_id = md5( $key . $secure_auth );
            
            update_option( 'unique_id', $unique_id);
        }

        return $unique_id;
    }

    function get_data($type = 'sites'){
        $fs_accounts = $this->get_fs_accounts();
        if(isset($fs_accounts[$type][$this->config->slug])){
            return $fs_accounts[$type][$this->config->slug];
        }
        return false;
    }

    function update_store($key, $value){
        $fs_accounts = $this->get_fs_accounts();
        $fs_accounts[$key][$this->config->slug] = $value;
        update_option('fs_accounts', $fs_accounts);
    }

    function fs_starts_with( $haystack, $needle ) {
        $length = strlen( $needle );
        return ( substr( $haystack, 0, $length ) === $needle );
    }

    function fs_strip_url_protocol( $url ) {
        if ( ! $this->fs_starts_with( $url, 'http' ) ) {
            return $url;
        }

        $protocol_pos = strpos( $url, '://' );

        if ( $protocol_pos > 5 ) {
            return $url;
        }
        return substr( $url, $protocol_pos + 3 );
    }

    function get_fs_accounts($user_id = null,  $user_data = [], $site = null){
        $fs_accounts = get_option('fs_accounts', []);
        if(!is_array($fs_accounts)){
            $fs_accounts = [];
        }
        if(!array_key_exists('id_slug_type_path_map', $fs_accounts)){
            $fs_accounts['id_slug_type_path_map'] = [];
        }
        if(!array_key_exists('plugin_data', $fs_accounts)){
            $fs_accounts['plugin_data'] = [];
        }
        if(!array_key_exists('file_slug_map', $fs_accounts)){
            $fs_accounts['file_slug_map'] = [];
        }
        if(!array_key_exists('users', $fs_accounts)){
            $fs_accounts['users'] = [];
        }
        if(!array_key_exists('sites', $fs_accounts)){
            $fs_accounts['sites'] = [];
        }

        if(!$this->path){
            return $fs_accounts;
        }

        $fs_accounts['id_slug_type_path_map'][$this->config->id] = [
            'slug' => $this->config->slug,
            'type' => 'plugin',
            'path' => $this->path
        ];

        if(!isset($fs_accounts['plugin_data'][$this->config->slug]) || $user_id){
            $fs_accounts['plugin_data'][$this->config->slug] = $this->get_plugin_data($user_id);
        }

        $fs_accounts['file_slug_map'][$this->path] = $this->config->slug;

        if($user_id){
            $fs_accounts['users'][$user_id] = $user_data;
        }
        if($site){
            $fs_accounts['sites'][$this->config->slug] = $site;
        }

        return $fs_accounts;
    }

    function get_plugin_data($user_id = null){
        $data =  [
            'plugin_main_file' => (object) [
                'path' => $this->path
            ],
            "is_network_activated" => false,
            "install_timestamp" => time(),
            "sdk_last_version" => $this->fs_version,
            'sdk_version' => $this->fs_version,
            "sdk_upgrade_mode" => "1",
            "sdk_downgrade_mode" => false,
            "plugin_last_version" => $this->version,
            "plugin_version" => $this->version,
            "plugin_upgrade_mode" => true,
            "plugin_downgrade_mode" => false,
            "was_plugin_loaded" => true,
            "is_plugin_new_install" => false,
            "connectivity_test" => [
                "is_connected" => false,
                "host" => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
                "server_ip" => '',
                "is_active" => true,
                "timestamp" => time(),
                "version" => $this->version,
            ],
            "prev_is_premium" => false,
            "sticky_optin_added" => true
        ];

        if($user_id){
            return wp_parse_args([
                'is_diagnostic_tracking_allowed' => true,
                'is_extensions_tracking_allowed' => true,
                'is_user_tracking_allowed' => true,
                'is_site_tracking_allowed' => true,
                'is_events_tracking_allowed' => true,
            ], $data);
        }else {
            return $data;
        }
    }

}


