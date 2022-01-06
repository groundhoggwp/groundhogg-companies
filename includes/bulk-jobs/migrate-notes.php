<?php

namespace GroundhoggCompanies\Bulk_Jobs;

use Groundhogg\Bulk_Jobs\Bulk_Job;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\get_db;
use function GroundhoggCompanies\migrate_notes;


class Migrate_Notes extends Bulk_Job{

	public function get_action() {
		return 'gh_companies_migrate_notes_3_0';
	}

	public function max_items( $max, $items ) {
		return 500;
	}

	public function query( $items ) {
		return wp_list_pluck( get_db( 'companies' )->query(), 'ID' );
	}

	protected function pre_loop() {
	}

	protected function process_item( $item ) {
		migrate_notes( new Company( $item ) );
	}

	protected function post_loop() {
	}

	protected function clean_up() {
	}
}
