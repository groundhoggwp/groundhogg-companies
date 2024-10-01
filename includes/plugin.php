<?php

namespace GroundhoggCompanies;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Api\V4\Base_Api;
use Groundhogg\Bulk_Jobs\Export_Companies;
use Groundhogg\Contact;
use Groundhogg\DB\Manager;
use Groundhogg\Extension;
use GroundhoggCompanies\Admin\Companies\Companies_Page;
use GroundhoggCompanies\Api\Companies_Api;
use GroundhoggCompanies\Bulk_Jobs\Import_companies;
use GroundhoggCompanies\Bulk_Jobs\Migrate_Notes;
use GroundhoggCompanies\Bulk_Jobs\Sync_Companies;
use GroundhoggCompanies\Classes\Company;
use GroundhoggCompanies\DB\Companies;
use GroundhoggCompanies\DB\Company_Meta;
use function Groundhogg\html;

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

		new Replacements();
		new Search_Filters();

		add_action( 'groundhogg/enqueue_api_docs', function () {
			wp_enqueue_script( 'groundhogg-companies-api-docs' );
			wp_add_inline_script( 'groundhogg-companies-company-filters', 'const GroundhoggCompanyProperties = ' . wp_json_encode( properties()->get_all() ), 'before' );
		} );

		add_action( 'groundhogg/admin/gh_contacts/edit/scripts', function (){
			wp_enqueue_script( 'groundhogg-companies-admin' );
		}, 99 );
	}

	/**
	 * @param Manager $db_manager
	 */
	public function register_dbs( $db_manager ) {
		$db_manager->companies             = new Companies();
		$db_manager->company_meta          = new Company_Meta();
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
		$cards::register( 'companies', __( 'Companies', 'groundhogg-companies' ), function ( $contact ) {
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

		wp_register_style( 'groundhogg-companies-table-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . '/css/companies-table.css', [
			'groundhogg-admin-element'
		], GROUNDHOGG_COMPANIES_VERSION );
	}

	public function enqueue_filter_assets() {
		wp_enqueue_script( 'groundhogg-companies-contact-filters' );
	}

	public function register_admin_scripts( $is_minified, $dot_min ) {
		wp_register_script( 'groundhogg-companies-data-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/data.js', [
			'groundhogg-admin-data',
			'groundhogg-admin-components'
		], GROUNDHOGG_COMPANIES_VERSION );

		wp_localize_script( 'groundhogg-companies-data-admin', 'GroundhoggCompanies', [
			'route'  => rest_url( Base_Api::NAME_SPACE . '/companies' ),
			'fields' => get_company_mappable_fields()
		] );

		wp_register_script( 'groundhogg-companies-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/companies.js', [
			'groundhogg-companies-data-admin',
			'groundhogg-admin-notes',
			'groundhogg-admin-tasks',
			'groundhogg-admin-properties',
			'groundhogg-admin-components',
			'jquery-ui-autocomplete',
			'wp-i18n'
		], GROUNDHOGG_COMPANIES_VERSION, true );

		wp_register_script( 'groundhogg-companies-company-filters', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/company-filters.js', [
			'groundhogg-admin-filters',
		], GROUNDHOGG_COMPANIES_VERSION, true );

		wp_register_script( 'groundhogg-companies-table-admin', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/companies-table.js', [
			'groundhogg-companies-data-admin',
			'groundhogg-companies-company-filters',
			'groundhogg-admin-send-broadcast',
			'groundhogg-admin-funnel-scheduler',
			'jquery-ui-autocomplete',
			'wp-i18n',
			'papaparse'
		], GROUNDHOGG_COMPANIES_VERSION, true );

		wp_register_script( 'groundhogg-companies-api-docs', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/api-docs.js', [
			'groundhogg-companies-company-filters',
		], GROUNDHOGG_COMPANIES_VERSION, true );

		wp_register_script( 'groundhogg-companies-contact-filters', GROUNDHOGG_COMPANIES_ASSETS_URL . 'js/contact-filters.js', [
			'groundhogg-companies-data-admin',
			'groundhogg-admin-filter-contacts',
			'wp-i18n'
		] );
	}

	/**
	 * Table columns
	 *
	 * @param $contact   Contact
	 * @param $column_id string
	 *
	 * @return string|void
	 */
	public function company_table_columns_callback( $contact, $column_id ) {

		if ( $column_id === 'job_title' || $column_id === 'company_department' ){
			return $contact->get_meta( $column_id );
		}

		if ( ! $contact->get_meta( $column_id ) ) {

			$companies = $contact->get_related_objects( 'company', false );

			if ( empty( $companies ) ) {
				return;
			}

			/**
			 * @var $company Company
			 */
			$company = array_shift( $companies );

			switch ( $column_id ) {
				default:
					return;
				case 'company_name':
					return html()->e( 'a', [
						'target' => '_blank',
						'href'   => $company->admin_link()
					], $company->get_name() );
				case 'company_website':
					return html()->e( 'a', [
						'target' => '_blank',
						'href'   => $company->get_domain()
					], parse_url( $company->get_domain(), PHP_URL_HOST ) );
				case 'company_address':
					return html()->e( 'a', [
						'href'   => 'http://maps.google.com/?q=' . $company->get_searchable_address(),
						'target' => '_blank'
					], $company->get_searchable_address() );
			}
		}

		if ( $column_id == 'company_name' ) {
			$company = new Company( sanitize_title( $contact->get_meta( 'company_name' ) ), 'slug' );
			if ( $company->exists() ) {
				return html()->e( 'a', [
					'target' => '_blank',
					'href'   => $company->admin_link()
				], $company->get_name() );
			}
		}

		switch ( $column_id ) {
			default:
			case 'company_name':
				return $contact->get_meta( 'company_name' );
			case 'company_phone':

				$phone = $contact->get_meta( 'company_phone' );
				$ext = $contact->get_meta( 'company_phone_extension' );

				return html()->e( 'a', [
					'href' => 'tel:' . $phone
				], $phone . ( $ext ? " ext. $ext" : '') );
			case 'company_website':
				return html()->e( 'a', [
					'target' => '_blank',
					'href'   => $contact->get_meta( 'company_website' )
				], parse_url( $contact->get_meta( 'company_website' ), PHP_URL_HOST ) );
			case 'company_address':
				return html()->e( 'a', [
					'href'   => 'http://maps.google.com/?q=' . $contact->get_meta( 'company_address' ),
					'target' => '_blank'
				], $contact->get_meta( 'company_address' ) );
		}
	}

	/**
	 * @param $columns Contact_Table_Columns
	 *
	 * @return void
	 */
	public function register_contact_table_columns( $columns ) {

		$columns::register_preset( 'company', 'Company' );

		$i = 0;
		foreach ( get_contact_company_fields() as $field_id => $field_name ) {

			if ( $field_id === 'company_phone_extension' ){
				continue;
			}

			$columns::register( $field_id, $field_name, [
				$this,
				'company_table_columns_callback'
			], false, 300, 'view_companies', 'company' );
			$i ++;
		}

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
