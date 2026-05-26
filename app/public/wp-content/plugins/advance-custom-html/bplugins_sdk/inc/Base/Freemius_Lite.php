<?php

if(!class_exists('Freemius_Lite')){
    class Freemius_Lite {
        protected $api_endpoint = 'https://api.bplugins.com/wp-json/freemius/v1/middleware/';
        // protected $api_endpoint = 'http://localhost/freemius/wp-json/freemius/v1/middleware/';
        public $api = null;
        protected $_scope = null;
        public $headers = [];
        function __construct($scope = null, $id = null, $public_key = null, $secret_key = null){
            $this->api_endpoint .= time();
            if($scope && $id && $public_key){
                $this->headers = $this->generate_authorization_header('', $scope, $id, $public_key, $secret_key);
            }
        }
    
        public function get_site(){
            $result = $this->FS_Api('?sdk_version=2.5.12&fields=site_id,plugin_id,user_id,title,url,version,language,platform_version,sdk_version,programming_language_version,plan_id,license_id,trial_plan_id,trial_ends,is_premium,is_disconnected,is_active,is_uninstalled,is_beta,public_key,secret_key,id,updated,created,_is_updated');

            if(!$result->success){
                return false;
            }
            return $result->data;
        }

        public function plugin_deactivated($path,$uid){
            return $this->FS_Api($path, 'PUT', wp_json_encode([
                'is_active' => false,
                'uid' => $uid
            ])); 
        }

        public function plugin_uninstall($path,$uid){
            return $this->FS_Api($path, 'PUT', wp_json_encode([
                'is_active' => false,
                'is_uninstalled' => true,
                'uid' => $uid
            ])); 
        }

        public function plugin_activated($path, $uid, $version){
            $user = wp_get_current_user();
            global $wp_version;
            return $this->FS_Api($path, 'PUT', wp_json_encode([
                "sdk_version" => "2.5.12",
                "platform_version" => $wp_version,
                "programming_language_version" => phpversion(),
                "url" => site_url(),
                "language" => "en-US",
                "title" => get_bloginfo('name'),
                "version" => $version,
                "is_premium" => false,
                "is_active" => true,
                "is_uninstalled" => false,
                "uid" => $uid,
            ])); 
        }

        public function Api($method = 'GET', $params = [], $headers = []){
            try {
                $response = wp_remote_request($this->api_endpoint, [
                    'method' => $method,
                    'headers' => $headers,
                    'body' =>  $params
                ]);
                $body = json_decode(wp_remote_retrieve_body($response));
                return $body;
            } catch (\Throwable $th) {
                throw new Exception('Something went wrong!');
            }
        }

        public function FS_Api($path = '', $method="GET", $params = []){
            $this->headers['path'] = $path;
            $result = $this->Api($method, $params, $this->headers );
            return $result;
        }

        function permission_update($fs_accounts, $config, $params = null){
            $site = isset($fs_accounts['sites'][$config->slug]) && $fs_accounts['sites'][$config->slug] ? (object) $fs_accounts['sites'][$config->slug] : null;
            // return $fs_accounts;
            if(!is_object($site) || gettype($site->public_key) === NULL || $params === null || $site === null) {
               $fs_accounts['plugin_data'][$config->slug]['is_anonymous'] = [
                'is' => true,
                'timestamp' => time()
               ];
               update_option('fs_accounts', $fs_accounts);
               return false;
            }

            if(!$site->public_key || !$site->secret_key || !$site->install_id) {
                return false;
            }
            
            $headers = $this->generate_authorization_header('/permissions.json?sdk_version=2.5.12&url='.site_url(), 'install', $site->install_id, $site->public_key, $site->secret_key);

            // return ['message' => $params];

            $result = $this->_permission_update($params, $headers);

            if( isset($result->data->error) || (isset($result->data->code) && ($result->data->code === 'rest_invalid_json' || $result->data->code === 'unauthorized_access'))){
                return [
                    'success' => false,
                    'message' => isset($result->data->message) ? $result->data->message : 'error line 96'
                ];
            }
            
            if(isset($result->data->permissions)){
                $fs_accounts['plugin_data'][$config->slug]['is_user_tracking_allowed'] = $result->data->permissions->user;
                $fs_accounts['plugin_data'][$config->slug]['is_site_tracking_allowed'] = $result->data->permissions->site;
                $fs_accounts['plugin_data'][$config->slug]['is_events_tracking_allowed'] = $result->data->permissions->site;
                $fs_accounts['plugin_data'][$config->slug]['is_extensions_tracking_allowed'] = $result->data->permissions->extensions;
                $fs_accounts['just_testing'] = $result->data->permissions;
                update_option('fs_accounts', $fs_accounts);
                return [
                    'success' => true,
                    'data' => $fs_accounts
                ];
            }
            
            return false;
        }

        function _permission_update($params, $headers){
            $result = $this->Api('PUT', wp_json_encode($params), $headers);
            return $result;
        }

        private function generate_authorization_header($path, $scope, $id, $public_key,  $secret_key) {
            $headers = array(
                'path' => $path,
                'scope' => $scope,
                'id' => $id,
                'public' => $public_key,
                'secret' => $secret_key,
                'Content-Type' => 'application/json'
            );
            return $headers;     
        }

    }
    
}


