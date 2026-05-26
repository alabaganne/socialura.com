<?php 

class FSActivate extends FS_LITE{

    protected $url = 'https://api.bplugins.com/wp-json/data/v1/accept-data';
    protected $freemius_install_form_action = 'https://wp.freemius.com/action/service/user/install/';
    protected $status = false;
    protected $post_type = '';
    protected $nonce = null;
    protected $last_check = null;
    protected $marketing_allowed = false;
    protected static $_instance = null;
    protected $email = null;

   function __construct($config, $__FILE__){
        parent::__construct($config, $__FILE__);
        $this->config->version = $this->version;
        $this->register();
    }

    private function register(){
        // date_default_timezone_set('Asia/Dhaka');
        $this->status = get_option("$this->prefix-opt_in", false);
        $this->last_check = get_option("$this->prefix-info-check", time()-1);
        $this->marketing_allowed = get_option("$this->prefix-marketing-allowed", false);
       
        add_filter("plugin_action_links_$this->base_name", [$this, 'opt_in_button'] );
        add_action("wp_ajax_$this->prefix-opt-in-update", [$this, 'opt_in_update']);
        add_action('admin_head', [$this, 'admin_head']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        if(!$this->status){
            add_action('admin_menu', [$this, 'add_opt_in_menu']);
        }

        register_activation_hook($this->__FILE__, [&$this, '_activate_plugin_hook']);

        add_action('admin_footer', [$this, 'opt_in_modal']);
        add_action('admin_footer', [$this, 'initialize_opt_in']);
        
        add_action('wp_ajax_bsdk_fetch_info_'.$this->config->id, [$this, 'fetch_info']);
        add_action('wp_ajax_fs_init', [$this, 'fs_init']);
        add_action('wp_ajax_fs_api_request', [$this, 'fs_api_request']);
        add_action('wp_ajax_fs_notice_dismiss_'.$this->config->slug, [$this, 'fs_notice_dismiss']);
        add_action( 'admin_notices', [$this, 'fs_admin_notice'] );

        register_deactivation_hook($this->__FILE__, [&$this, '_deactivate_plugin_hook']);

        add_action('admin_init', [$this, 'admin_init']);
    }

    function admin_init(){
        $is_skip_activation = sanitize_text_field($_GET['is_skip_activation'] ?? '0') == '1';
        if($is_skip_activation){
            wp_redirect(admin_url($this->config->menu['first-path']));
        }
    }

    function fs_admin_notice(){
        $fs_accounts = $this->get_fs_accounts();
        $notice = $fs_accounts['admin_notices'][$this->config->slug]['activation_pending'] ?? [];
        echo "<div class='fs_notice_board' data-nonce='". esc_attr(wp_create_nonce( 'wp_ajax' ))."' data-slug='". esc_attr($this->config->slug)."' data data-notice='". esc_attr(wp_json_encode($notice))."'></div>";
    }

    function fs_notice_dismiss(){
        if(!wp_verify_nonce( $_POST['nonce'], "wp_ajax")){
            wp_send_json_error();
        }
        $fs_accounts = $this->get_fs_accounts();
        unset($fs_accounts['admin_notices'][$this->config->slug]['activation_pending']);
        update_option('fs_accounts', $fs_accounts);
        wp_send_json_success('notice dismissed ' . $this->config->slug);
    }

    function fs_init(){
        if(!wp_verify_nonce( $_POST['nonce'], "wp_ajax")){
            wp_send_json_error();
        }
       try {
            $info = $this->sanitize_array($_POST['info']);
            $notice = $this->sanitize_array($_POST['notice'] ?? null);
            $fs_accounts = $this->get_fs_accounts();
            if(isset($info['is_skip_activation']) && $info['is_skip_activation']){
                $site = $this->get_data();
                $secret_key = (boolean) $site->secret_key;
                $public_key = (boolean) $site->public_key;
                if($secret_key && $public_key){
                    if(isset($fs_accounts['plugin_data'][$this->config->slug]['is_anonymous'])){
                        unset($fs_accounts['plugin_data'][$this->config->slug]['is_anonymous']);
                    }
                }else {
                    $fs_accounts['plugin_data'][$this->config->slug]['is_anonymous'] = ['is' => true];
                }
            }else {
                if(isset($info['user_id']) && $info['user_id']) {
                    $user_api = new Freemius_Lite('user', $info['user_id'], $info['user_public_key'], $info['user_secret_key']);
                    $site = (object) $this->sanitize_array($_POST['site']);
                    $user_data = $user_api->FS_Api('');
                    $fs_accounts = $this->get_fs_accounts($info['user_id'], $user_data, $site);
                    if(isset($fs_accounts['admin_notices'][$this->config->slug]['activation_pending'])){
                        unset($fs_accounts['admin_notices'][$this->config->slug]['activation_pending']);
                    }
                }else if($notice && isset($info['pending_activation'])){
                    $notice['activation_pending']['message'] = str_replace(['{name}', '{email}'], [$this->plugin_name, wp_get_current_user()->user_email], $notice['activation_pending']['message']);
                    $fs_accounts['admin_notices'][$this->config->slug] = $notice;
                }
                if(isset( $fs_accounts['plugin_data'][$this->config->slug]['is_anonymous'])){
                    unset( $fs_accounts['plugin_data'][$this->config->slug]['is_anonymous']);
                }
            }
            update_option('fs_accounts', $fs_accounts);
            wp_send_json_success(wp_parse_args(['config' => $this->config, 'admin_url' => admin_url()],$fs_accounts));

       } catch (\Throwable $th) {
            wp_send_json_error($th->getMessage());
       }
    }

    function get_request($key = null){
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]):  false;
    }

    function post_request($key = null){
        return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]):  false;
    }

    function _deactivate_plugin_hook(){
        $api = $this->event_hook();
        if($api){
            register_uninstall_hook($this->__FILE__, ['FSActivate', '_uninstall_plugin']);
            $api->plugin_deactivated('?sdk_ver='.$this->fs_version.'&url='.site_url(), $this->get_anonymous_id());
        }
    }

    function _activate_plugin_hook(){
        $api = $this->event_hook(true);
        if($api){
            $api->plugin_activated('', $this->get_anonymous_id(), $this->version);
        }
    }

    function _uninstall_plugin(){
        $api = $this->event_hook();
        if($api){
            $api->plugin_uninstall('?sdk_ver='.$this->fs_version.'&url='.site_url(), $this->get_anonymous_id());
        }
    }

    // function sanitize_array

    function event_hook($is_active = false){
        $site = $this->get_data();
        $plugin_data = $this->get_data('plugin_data');
        if(!$site){
            return false;
        }
        $site->is_active = $is_active;
        $this->update_store('sites', $site);

        if(!isset($plugin_data->is_site_tracking_allowed) ||  !$plugin_data->is_site_tracking_allowed){
            return false;
        }

        $api = new Freemius_Lite('install', $site->id, $site->public_key, $site->secret_key);
        return $api;
    }

    function fs_api_request(){        
        if(!wp_verify_nonce( $_POST['nonce'], "wp_ajax")){
            wp_send_json_error();
        }

        $method = $this->post_request('method');
        $url= $this->post_request('url');
        $headers = $this->sanitize_array( $_POST['headers']);
        $body = $this->sanitize_array( $_POST['body']);

        try {
            $response = wp_remote_request($url, [
                'method' => $method,
                'headers' => $headers,
                'body' =>  wp_json_encode($body)
            ]);
            $body = json_decode(wp_remote_retrieve_body($response));
            wp_send_json_success($body);
        } catch (\Throwable $th) {
            //throw $th;
        }

        wp_send_json_error('error');
    }

    function fetch_info(){
        $nonce = $_POST['nonce'];
        if(!wp_verify_nonce($nonce, "wp_ajax")){
            wp_send_json_error();
        }

        $plugin_data = [];
        unset($this->config->__FILE__);
        $result = [];
        $thread = sanitize_text_field($_POST['thread'] ?? null);
        if($thread === "permission_update"){
            $api = new Freemius_Lite();
            $permissions = sanitize_text_field($_POST['permissions']);
            $is_enabled =  sanitize_text_field($_POST['is_enabled']) === 'true';
            $fs_accounts = $this->get_fs_accounts();
            $result = $api->permission_update($fs_accounts, $this->config, compact('permissions', 'is_enabled'));
            if(!$result['success']){
                wp_send_json_error($result['message']);
            }
        }
        
        wp_send_json_success(wp_parse_args( wp_parse_args($this->extend_config(), []), (array) $this->config ));
    }

   function extend_config(){
        if(!$this->version){
            return;
        }
        global $wp_version;
        $user = wp_get_current_user();
        $type = sanitize_text_field($_POST['type'] ?? 'form');
        $plugin_data = [];
        $site = [];

        if($type === 'modal'){
            $fs_accounts = get_option('fs_accounts', []);
            if(isset($fs_accounts['plugin_data'][$this->config->slug])){
                $plugin_data = $fs_accounts['plugin_data'][$this->config->slug];
            }
            if(isset($fs_accounts['sites'][$this->config->slug])){
                $site = [
                    'scope' => 'install',
                    'id' => $fs_accounts['sites'][$this->config->slug]->install_id,
                    'public_key' => $fs_accounts['sites'][$this->config->slug]->public_key,
                    'secret_key' => $fs_accounts['sites'][$this->config->slug]->secret_key
                ];
            }
        }

        return [
            'freemius_form_action' => $this->freemius_install_form_action,
            'uid' => $this->get_anonymous_id(),
            'platform_version' => $wp_version,
            'programming_language_version' => phpversion(),
            'user_email' =>  $user->user_email,
            'plugin_version' => $this->version,
            'site_name' => get_bloginfo('name'),
            'admin_url' => admin_url(),
            'site_url' => site_url(),
            'nonce' => wp_create_nonce($this->config->slug.'_activate_new'),
            'is_marketing_allowed' => $this->marketing_allowed,
            'user_first_name'=> $user->user_firstname,
            'user_last_name'=> $user->user_lastname,
            'plugin_name' => $this->plugin_name,
            'data' => $plugin_data, // current plugin data (in freemus sdk, here all the plugins data)
            'site' => $site
        ];
    }

   function get_request_decode($key = null){
        $key = isset($_GET[$key]) ? $_GET[$key] : false;
        if($key && strlen($key) === 32){
            $key = sanitize_text_field(str_replace('<', '&lt;', $key) );
            $key = str_replace(['&lt;', '&gt;', '&amp;'], [ '<', '>', '&'], $key);
            return $key;
        }
        return false;
    }

    function initialize_opt_in(){
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                if(typeof bsdkOptInFormHandler === 'function'){
                    bsdkOptInFormHandler('<?php echo esc_html($this->prefix) ?>', '<?php echo esc_html($this->config->slug) ?>');
                }
            });
        </script>
        <?php
    }

    function end_process(){
        $location = admin_url(isset($this->config->first_path) ? $this->config->first_path : 'plugins.php');
        ?><script>window.location.href = '<?php echo esc_html($location) ?>';</script><?php
    }

   function admin_head(){
        $redirect = get_option("$this->prefix-redirect", false);
        if (!$redirect && !strpos($_SERVER['REQUEST_URI'], 'post.php') && !strpos($_SERVER['REQUEST_URI'], 'post-new.php')) {
            update_option("$this->prefix-redirect", true); ?><script>window.location.href = '<?php echo "admin.php?page=" . dirname($this->base_name) ?>'</script><?php
        }
    }

   function opt_in_button($links){
        $fs_accounts = get_option('fs_accounts');
        $classes = "optInBtn ";
        if($this->config->platform === 'freemius'){
            $this->marketing_allowed = $this->get_is_all_tracking_allowed();
            if($this->marketing_allowed !== null){
                $classes .= $this->config->slug;
            }
        }

        $opt_in_link =  admin_url("admin.php?page=" . dirname($this->base_name));

        $settings_link = '<a href="'.$opt_in_link.'" class="'.$classes.'" id="'.esc_attr($this->prefix).'OptInBtn" data-status="'.esc_attr($this->marketing_allowed ? 'agree': 'not-allowed').'">'.esc_html($this->marketing_allowed ? 'Opt Out' : 'Opt In').'</a>';

        array_unshift($links, $settings_link);
        return $links;
    }

    // add a temp page to show opt-in form
   function add_opt_in_menu(){
        add_submenu_page( '', $this->plugin_name, $this->plugin_name, 'manage_options', dirname($this->base_name), [$this, 'opt_in_form']
        );
    }

    // enqueue assets
   function enqueue_assets($hook){
        wp_enqueue_script("bsdk-admin-notice", plugin_dir_url(plugin_dir_path( __DIR__ )).'dist/admin-notice.js', ['react', 'react-dom', 'wp-util'], $this->version);

        wp_register_script("bsdk-opt-in", plugin_dir_url(plugin_dir_path( __DIR__ )).'dist/opt-in-form.js', ['react', 'react-dom', 'wp-util'], $this->version);
        wp_register_style("bsdk-opt-in", plugin_dir_url(plugin_dir_path( __DIR__ )).'dist/opt-in-form.css', [], $this->version);

        wp_enqueue_script("bsdk-opt-in");
        wp_enqueue_style("bsdk-opt-in");
        // if($hook === 'plugins.php' || $hook === "admin_page_".dirname($this->base_name)){
        // }
    }

   function initialize_fs_accounts(){
        $fs_accounts = $this->get_fs_accounts();
        update_option('fs_accounts', $fs_accounts);
    }

    // show the opt-in form
   function opt_in_form(){
        update_option("$this->prefix-redirect", true);
        $this->initialize_fs_accounts();
        ?>
            <div
                data-nonce="<?php echo esc_attr(wp_create_nonce( 'wp_ajax' )) ?>"
                data-plugin-id = "<?php echo esc_attr($this->config->id) ?>"
                id="<?php echo esc_attr($this->prefix); ?>OptInForm">
            </div>
        <?php
    }

    // opt-in modal on plugins.php page
   function opt_in_modal($hook){
        global $wp_version;
        $screen = \get_current_screen();
		if($screen->base === 'plugins'){
        ?>
        <div 
            id="<?php echo esc_attr($this->prefix) ?>OptInModal"
            data-slug="<?php echo esc_attr($this->config->slug) ?>"
            data-nonce="<?php echo esc_attr(wp_create_nonce( 'wp_ajax' )) ?>"
            data-plugin-id = "<?php echo esc_attr($this->config->id) ?>">
        </div>
        <?php
        }
    }

   function get_is_all_tracking_allowed(){
        $fs_accounts = $this->get_fs_accounts();
        $slug = $this->config->slug;
        $plugin_data = isset($fs_accounts['plugin_data'][$slug])? (object) $fs_accounts['plugin_data'][$slug] : null;


        if(!$plugin_data || (isset($plugin_data->is_anonymous) && $plugin_data->is_anonymous['is']) || isset($fs_accounts['admin_notices'][$this->config->slug]['activation_pending'])){
            return null;
        }

        if(!isset($plugin_data->is_user_tracking_allowed) || !$plugin_data->is_user_tracking_allowed){
            return false;
        }

        if($plugin_data->is_user_tracking_allowed && $plugin_data->is_site_tracking_allowed && $plugin_data->is_events_tracking_allowed && $plugin_data->is_extensions_tracking_allowed){
            return true;
        }
        return false;
    }

   
   function sanitize_array($array){
        if(!is_array($array)){
            return false;
        }

        foreach($array as $key => $value){
            if(strpos($key, 'secret_key') !== false && strlen($value) == 32){
                $value = sanitize_text_field(str_replace('<', '&lt;', $value) ); 
                $value = sanitize_text_field($value);
                $array[$key] = str_replace(['&lt;', '&gt;', '&amp;'], [ '<', '>', '&'], $value);
            }else {
                if(is_array($value)){
                    $array[$key] = $this->sanitize_array($value);
                }else {
                    $array[$key] =$value == 'true' ? true : ($value == 'false' ? false :  sanitize_text_field($value));
                }
            }
        }
        return $array;
    }

}