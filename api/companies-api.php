<?php

namespace GroundhoggCompanies\Api;

use Groundhogg\Api\V4\Base_Object_Api;
use GroundhoggCompanies\Classes\Company;

class Companies_Api extends Base_Object_Api {

	public function get_db_table_name() {
		return 'companies';
	}

	protected function get_object_class() {
		return Company::class;
	}

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, '/companies/(?P<ID>\d+)/files', [
//			[
//				'methods'             => WP_REST_Server::CREATABLE,
//				'callback'            => [ $this, 'create_files' ],
//				'permission_callback' => [ $this, 'create_files_permissions_callback' ]
//			],
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_files' ],
				'permission_callback' => [ $this, 'read_files_permissions_callback' ]
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_files' ],
				'permission_callback' => [ $this, 'delete_files_permissions_callback' ]
			],
		] );
	}

	/**
	 * Get contact files
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read_files( \WP_REST_Request $request ) {
		$ID = absint( $request->get_param( 'ID' ) );

		$company = new Company( $ID );

		if ( ! $company->exists() ) {
			return self::ERROR_404();
		}

		$data = $company->get_files();

		$limit  = absint( $request->get_param( 'limit' ) ) ?: 25;
		$offset = absint( $request->get_param( 'offset' ) ) ?: 0;

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $data ),
			'items'       => array_slice( $data, $offset, $limit )
		] );

	}

	/**
	 * Delete a file from the contact record.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete_files( \WP_REST_Request $request ) {

		$ID = absint( $request->get_param( 'ID' ) );

		$company = new Company( $ID );

		if ( ! $company->exists() ) {
			return self::ERROR_404();
		}

		$files_to_delete = $request->get_json_params();

		if ( empty( $files_to_delete ) ) {
			return self::ERROR_422( 'error', 'Did not specify a file to delete.' );
		}

		foreach ( $files_to_delete as $file_name ) {
			$company->delete_file( $file_name );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $company->get_files() ),
			'items'       => $company->get_files(),
		] );
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_companies' );
	}

	public function create_permissions_callback() {
		return current_user_can( 'add_companies' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'edit_companies' );
	}

	public function delete_permissions_callback() {
		return current_user_can( 'delete_companies' );
	}

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function create_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}


	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function read_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function update_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}

	/**
	 * Permissions callback for files
	 *
	 * @return bool
	 */
	public function delete_files_permissions_callback() {
		return current_user_can( 'download_contact_files' );
	}


}
