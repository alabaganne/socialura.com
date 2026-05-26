<?php

class Init{
   
    public static function get_services(){
        return [
            Base\FSActivate::class
        ];
    }

    public static function register_services(){
        foreach(self::get_services() as $class){
            $services = self::instantiate($class);
            if(method_exists($services, 'register')){
                $services->register();
            }
        }
    }

    private static function instantiate($class){
        return new $class();
    }
}

