<?php

namespace GroundhoggCompanies;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\Api\V4\Base_Api;
use Groundhogg\DB\Manager;
use Groundhogg\Extension;
use GroundhoggCompanies\Admin\Companies\Companies_Page;
use GroundhoggCompanies\Admin\Tools\Import_Companies_Tools;
use GroundhoggCompanies\Admin\Tools\sync_companies_Tools;
use GroundhoggCompanies\Api\Companies_Api;
use GroundhoggCompanies\Bulk_Jobs\Import_companies;
use GroundhoggCompanies\Bulk_Jobs\Sync_Companies;
use GroundhoggCompanies\Bulk_Jobs\Migrate_Notes;
use GroundhoggCompanies\DB\Companies;
use GroundhoggCompanies\DB\Company_Meta;
use GroundhoggCompanies\DB\Company_Relationships;

class Plugin extends Extension {


	/**
	 * Override the parent instance.
	 *
	 * @var Plugin
	 */
	public static $instance;

	/**
	 * Include any files.
	 *
	 * @return void
	 */
	public function includes() {
		require GROUNDHOGG_COMPANIES_PATH . '/includes/functions.php';
	}

	/**
	 * Init any components that need to be added.
	 *
	 * @return void
	 */
	public function init_components() {
		$this->installer = new Installer();
		$this->updater   = new Updater();
		$this->roles     = new Roles();

		if ( is_admin() ) {
			new Sync_Companies_Tools();
			new Import_Companies_Tools();
		}
	}

	/**
	 * @param Manager $db_manager
	 */
	public function register_dbs( $db_manager ) {
		$db_manager->companies             = new Companies();
		$db_manager->company_meta          = new Company_Meta();
		$db_manager->company_relationships = new Company_Relationships();
	}

	/**
	 * Code to register admin menu in the list.
	 *
	 * @param Admin_Menu $admin_menu
	 */
	public function register_admin_pages( $admin_menu ) {
		$admin_menu->companies = new Companies_Page();
	}


	public function register_bulk_jobs( $manager ) {
		$manager->company_migrate_notes = new Migrate_Notes();
		$manager->sync_companies        = new Sync_Companies();
		$manager->import_companies      = new Import_companies();
	}

	/**
	 * Register the company info card
	 *
	 * @param \Groundhogg\Admin\Contacts\Info_Cards $cards
	 */
	public function register_contact_info_cards( $cards ) {
		$cards::register( 'companies', __('Companies', 'groundhogg-companies'), function ( $contact ) {
			include __DIR__ . '/../admin/companies-card.php';
		}, 10, 'view_companies' );
	}

	/**
	 * Get the ID number for the download in EDD Store
	 *
	 * @return int
	 */
	public function get_download_id() {
		return 37360;
	}

	public function register_v4_apis( $api_manager ) {
		$api_manager->companies = new Companies_Api();
	}

	/**
	 * Get the version #
	 *
	 * @return mixed
	 */
	public function get_version() {
		return GROUNDHOGG_COMPANIES_VERSION;
	}

	public function register_admin_styles() {
		wp_register_style( 'groundhogg-companies-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . '/css/companies.css', [
			'groundhogg-admin-element'
		], GROUNDHOGG_COMPANIES_VERSION );
	}


	public function register_admin_scripts( $is_minified, $dot_min ) {
		wp_register_script( 'groundhogg-companies-data-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/data.js', [
			'groundhogg-admin-data',
			'groundhogg-admin-components'
		], GROUNDHOGG_COMPANIES_VERSION );

		wp_localize_script( 'groundhogg-companies-data-admin', 'GroundhoggCompanies', [
			'route' => rest_url( Base_Api::NAME_SPACE . '/companies' )
		] );

		wp_register_script( 'groundhogg-companies-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/companies.js', [
			'groundhogg-companies-data-admin',
			'groundhogg-admin-notes',
			'groundhogg-admin-properties',
			'groundhogg-admin-components',
			'wp-i18n'
		], GROUNDHOGG_COMPANIES_VERSION, true );
	}

	/**
	 * @return string
	 */
	public function get_plugin_file() {
		return GROUNDHOGG_COMPANIES__FILE__;
	}

	/**
	 * Register autoloader.
	 *
	 * Groundhogg autoloader loads all the classes needed to run the plugin.
	 *
	 * @since  1.6.0
	 * @access private
	 */
	protected function register_autoloader() {
		require GROUNDHOGG_COMPANIES_PATH . 'includes/autoloader.php';
		Autoloader::run();
	}
}

Plugin::instance();