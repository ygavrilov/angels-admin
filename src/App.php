<?php
namespace src;

use src\controllers\authorization;
use src\controllers\peppers;
use src\controllers\items;

class App {

    private $routes = [
        '/?q=auth/login' => [
            'controller'    => 'authorization',
            'action'        => 'login',
            'require_auth'  => false
        ],
        '/?q=auth/check' => [
            'controller'    => 'authorization',
            'action'        => 'check_auth',
            'require_auth'  => false
        ],
        /**
         * 
         * routes for items
         * 
         */
        '/?q=items/list' => [
            'controller'    => 'items',
            'action'        => 'list',
            'require_auth'  => false
        ],
        '/?q=items/get-by-id/*' => [
            'controller'    => 'items',
            'action'        => 'get_by_id',
            'require_auth'  => false
        ],
        '/?q=items/create' => [
            'controller'    => 'items',
            'action'        => 'create',
            'require_auth'  => true
        ],
        '/?q=items/update' => [
            'controller'    => 'items',
            'action'        => 'update',
            'require_auth'  => true
        ],
        '/?q=items/remove-image/*' => [
            'controller'    => 'items',
            'action'        => 'remove_image',
            'require_auth'  => true
        ],

        /**
         * 
         * routes for peppers
         * 
         */

        '/?q=peppers/list' => [
            'controller'    => 'peppers',
            'action'        => 'list',
            'require_auth'  => false
        ],
        '/?q=peppers/get-by-id/*' => [
            'controller'    => 'peppers',
            'action'        => 'get_by_id',
            'require_auth'  => false
        ],
        '/?q=peppers/create' => [
            'controller'    => 'peppers',
            'action'        => 'create',
            'require_auth'  => true
        ],
        '/?q=peppers/update' => [
            'controller'    => 'peppers',
            'action'        => 'update',
            'require_auth'  => true
        ],
        '/?q=peppers/remove-image/*' => [
            'controller'    => 'peppers',
            'action'        => 'remove_image',
            'require_auth'  => true
        ],
    ];

    private $controllers;

    public function __construct()
    {
        //  what do we do here? 
        $this->controllers = (object) [];
        $this->controllers->authorization = new authorization();
        $this->controllers->peppers = new peppers();
        $this->controllers->items = new items();
    }

    public function run() 
    {
        /**
         * 
         * decompose URI and try to get controller & execute action
         * the request itself will do the auth logic, at first we just authorize all requests
         * 
        */

        $request_route_array = explode('/', $_SERVER['REQUEST_URI']);
        $check_string = '/' . $request_route_array[1] . '/' . $request_route_array[2];
        $params = null;
        if (array_key_exists(3, $request_route_array)) 
        {
            $check_string .= '/*';
            unset($request_route_array[0]);
            unset($request_route_array[1]);
            unset($request_route_array[2]);
            $params = implode('/', $request_route_array);
        }
        if (array_key_exists($check_string, $this->routes) === false) 
        {
            echo json_encode([
                'code' => 404, 
                'message' => 'route not found', 
                'check' => $check_string,
                'available routes' => array_keys($this->routes)
            ]);
            return;
        }
        
        if ($this->routes[$check_string]['require_auth'])
        {
            $auth_result = $this->controllers->authorization->check_token();
            if (!$auth_result) 
            {
                echo json_encode(['code' => 403, 'message' => 'not authorized', 'auth' => $auth_result]);
                return;
            }
        }

        //  route found, lets match controller & actionm we assume they exist
        $this
            ->controllers
            ->{$this->routes[$check_string]['controller']}
            ->{$this->routes[$check_string]['action']}($params)
        ;
    }
}
