<?php

namespace GroundhoggCompanies\DB;

use Groundhogg\DB\Meta_DB;

/**
 * Payment Meta Table.
 *
 * Store meta information about payment.
 *
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Company_Meta extends Meta_DB {
	public function get_db_suffix() {
		return 'gh_companymeta';
	}

	public function get_db_version() {
		return '2.0';
	}

	public function get_object_type() {
		return 'company';
	}
}