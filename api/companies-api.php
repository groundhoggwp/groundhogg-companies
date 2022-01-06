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


}
