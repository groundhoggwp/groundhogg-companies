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

            ],  [
		        'methods'             => WP_REST_Server::CREATABLE,
		        'callback'            => [ $this, 'add_companies' ],
		        'permission_callback' => $auth_callback,

	        ],
	        [
		        'methods'             => WP_REST_Server::DELETABLE,
		        'callback'            => [ $this, 'delete_companies' ],
		        'permission_callback' => $auth_callback,

	        ],
	        [
		        'methods'             => WP_REST_Server::EDITABLE,
		        'callback'            => [ $this, 'edit_companies' ],
		        'permission_callback' => $auth_callback,

	        ]


        ] );



    }

    /**
     * Get a list of companies
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_companies( WP_REST_Request $request )
    {

	    return self::SUCCESS_RESPONSE( [ 'success' ] );
    }

	/**
	 * Creates a new companies
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function add_companies( WP_REST_Request $request ) {

		return self::SUCCESS_RESPONSE();
	}

	/**
	 *  Deletes the companies and relevant data
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_companies( WP_REST_Request $request ) {

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * update the companies
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function edit_companies( WP_REST_Request $request ) {

		return self::SUCCESS_RESPONSE();
	}






}