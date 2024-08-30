<?php

namespace GroundhoggCompanies;

use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_array_var;

class Roles extends \Groundhogg\Roles {

	/**
	 * Returns an array  of role => [
	 *  'role' => '',
	 *  'name' => '',
	 *  'caps' => []
	 * ]
	 *
	 * In this case caps should just be the meta cap map for other WP related stuff.
	 *
	 * @return array[]
	 */
	public function get_roles() {
		return [];
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		switch ( $cap ) {
			case 'edit_company':
			case 'view_company':
			case 'delete_company':

				$caps = [];

				$parts       = explode( '_', $cap );
				$action      = $parts[0];

				$caps[] = $action . '_companies';

				$object = $args[0];

				// didn't pass the full object
				if ( ! is_object( $object ) || ! method_exists( $object, 'get_id' ) ) {
					$object = new Company( $object );
				}

				// Not a real object
				if ( ! $object->exists() ) {
					$caps[] = 'do_not_allow';
					break;
				}

				if ( $object->get_owner_id() !== $user_id ) {
					$caps[] = $action . '_others_companies';
				}

				break;
			case 'download_file':

				$file_path = $args[0];

				// Compat for WooCommerce usage of download file
				if ( ! is_string( $file_path ) ) {
					return $caps;
				}

				$caps = [];

				$file_path = wp_normalize_path( $file_path );

				$path = explode( '/', $file_path );

				if ( $path[0] !== 'companies' ){
					return $caps;
				}

				$caps = [ 'edit_companies' ];

				$request        = $args[1];
				$company        = get_array_var( $request, 'id' );
				$company_folder = $path[1];

				$company = new Company( $company );

				if ( ! $company->exists() ) {
					$caps[] = 'do_not_allow';
					break;
				}

				// Trying to cheat the system
				if ( $company_folder !== $company->get_upload_folder_basename() ) {
					$caps[] = 'do_not_allow';
					break;
				}

				// Trying to download files of contacts that don't belong to them
				if ( $company->get_owner_id() !== $user_id ) {
					$caps[] = 'view_others_companies';
				}

				break;

		}

		return $caps;
	}

	public function get_administrator_caps() {
		return [
			'view_companies',
			'view_others_companies',
			'add_companies',
			'edit_companies',
			'edit_others_companies',
			'delete_companies',
			'delete_others_companies',
		];
	}

	public function get_sales_rep_manager_caps() {
		return [
			'view_companies',
			'add_companies',
			'edit_companies',
		];
	}

	public function get_sales_manager_caps() {
		return [
			'view_companies',
			'view_others_companies',
			'add_companies',
			'edit_others_companies',
			'edit_companies',
		];
	}

	public function get_marketer_caps() {
		return [
			'view_companies',
			'view_others_companies',
			'add_companies',
			'edit_companies',
			'edit_others_companies',
			'delete_companies',
			'delete_others_companies',
		];
	}

	/**
	 * Return a cap to check against the admin to ensure caps are also installed.
	 *
	 * @return mixed
	 */
	protected function get_admin_cap_check() {
		return 'edit_companies';
	}
}
