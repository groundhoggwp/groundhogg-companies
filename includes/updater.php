<?php

namespace GroundhoggCompanies;

use GroundhoggCompanies\Bulk_Jobs\Migrate_Notes;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\array_map_to_class;
use function Groundhogg\get_db;
use function Groundhogg\words_to_key;

class Updater extends \Groundhogg\Updater {

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	protected function get_updater_name() {
		return words_to_key( GROUNDHOGG_COMPANIES_NAME );
	}

	/**
	 * Get a list of updates which are available.
	 *
	 * @return string[]
	 */
	protected function get_available_updates() {
		return [
			'1.1.1',
			'3.0'
		];
	}

	protected function get_update_descriptions() {
		return [
			'1.1.1' => __( 'Migrate company notes to new format', 'groundhogg-companies' ),
			'3.0'   => __( 'Migrate company relationships to object relations table', 'groundhogg-companies' ),
		];
	}

	protected function get_plugin_file() {
		return GROUNDHOGG_COMPANIES__FILE__;
	}

	/**
	 * Add pipeline meta table
	 * update deals table
	 * convert to object relationships
	 * move deals back to stage if closed
	 */
	public function version_1_1_1() {

		// If there are more than 500 deals use a bulk job.
		if ( get_db( 'companies' )->count() > 500 ) {
			$this->remember_version_update( '1.1.1' );
			$job = new Migrate_Notes();
			$job->start();

			return;
		}

		$companies = get_db( 'companies' )->query();

		array_map_to_class( $companies, Company::class );

		/**
		 * @var $company Company
		 */
		foreach ( $companies as $company ) {
			migrate_notes( $company );
		}
	}

	/**
	 * Migrate company relationships to the Object Relationships table
	 * Add owner_id to the companies table
	 */
	function version_3_0() {
		get_db( 'companies' )->create_table();
		get_db( 'company_relationships' )->migrate_to_object_relationships();
	}
}
