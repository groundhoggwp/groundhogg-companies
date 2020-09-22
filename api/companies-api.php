<?php
namespace GroundhoggCompanies\Api;

use Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Groundhogg\Api\V3\Base;
use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Companies_Api extends Base
{

    public function register_routes()
    {

        $auth_callback = $this->get_auth_callback();

        register_rest_route(self::NAME_SPACE, '/companies', [
            [
	            'methods' => WP_REST_Server::READABLE,
	            'callback' => [ $this, 'get_companies' ],
	            'permission_callback' => $auth_callback,

            ]
        ] );



    }

    /**
     * Get a list of sms which match a given query
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_companies( WP_REST_Request $request )
    {

	    return self::SUCCESS_RESPONSE( [ 'success' ] );
    }

}