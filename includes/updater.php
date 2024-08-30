<?php

namespace GroundhoggCompanies;

use GroundhoggCompanies\Bulk_Jobs\Migrate_Notes;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\array_map_to_class;
use function Groundhogg\db;
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

	protected function get_plugin_file() {
		return GROUNDHOGG_COMPANIES__FILE__;
	}

	/**
	 * Get a list of updates which are available.
	 *
	 * @return mixed
	 */
	protected function get_available_updates() {
		return [
			'3.2' => [
				'automatic' => true,
				'description' => __( 'Update the company database tables.', 'groundhogg-companies' ),
				'callback' => function () {
					// update columns
					db()->companies->create_table();
				}
			]
		];
	}
}
