<?php

namespace GroundhoggCompanies\Bulk_Jobs;

use Groundhogg\Bulk_Jobs\Bulk_Job;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_items_from_csv;
use Groundhogg\Plugin;
use function Groundhogg\get_url_var;
use function GroundhoggCompanies\generate_company_with_map;
use function GroundhoggCompanies\get_company_imports_dir;
use function GroundhoggCompanies\recount_company_contacts_count;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_companies extends Bulk_Job {

	protected $field_map = [];

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_import_companies';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'add_companies' ) ) {
			return $items;
		}

		$file_name = sanitize_file_name( get_url_var( 'import' ) );
		$file_path = wp_normalize_path( get_company_imports_dir( $file_name ) );

		return get_items_from_csv( $file_path );
	}

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max   int
	 * @param $items array
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		$item   = array_shift( $items );
		$fields = count( array_keys( $item ) );

		$max       = intval( ini_get( 'max_input_vars' ) );
		$max_items = floor( $max / $fields );

		$max_override = absint( get_url_var( 'max_items' ) );

		if ( $max_override > 0 ) {
			return $max_override;
		}

		return min( $max_items, 100 );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function process_item( $item ) {
		generate_company_with_map( $item, $this->field_map );
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
		$this->field_map = Plugin::$instance->settings->get_transient( 'gh_import_companies_map' );
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		Plugin::$instance->settings->delete_transient( 'gh_import_companies_map' );
		recount_company_contacts_count();
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_companies' );
	}
}