<?php

namespace GroundhoggCompanies;

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