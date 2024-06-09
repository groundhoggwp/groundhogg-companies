<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\array_map_to_class;
use function Groundhogg\array_map_to_method;
use function Groundhogg\file_access_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function GroundhoggCompanies\generate_company_with_map;
use function GroundhoggCompanies\get_company_exports_dir;
use function GroundhoggCompanies\get_company_imports_dir;
use function GroundhoggCompanies\get_company_imports_url;
use function GroundhoggCompanies\properties;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Companies
 *
 * @since       File available since Release 1.0
 * @subpackage  Admin/Companies
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */
class Companies_Page extends Admin_Page {

	// UNUSED FUNCTIONS
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_company_upload_file', [ $this, 'ajax_upload_file' ] );
		add_action( 'wp_ajax_groundhogg_company_upload_import_file', [ $this, 'ajax_upload_import_file' ] );
		add_action( 'wp_ajax_groundhogg_import_companies', [ $this, 'handle_import_companies' ] );
		add_action( 'wp_ajax_groundhogg_export_companies', [ $this, 'handle_export_companies' ] );
	}

	/**
	 * Generate CSV list of all companies
	 *
	 * @return void
	 */
	public function handle_export_companies() {

		if ( ! current_user_can( 'export_companies' ) ) {
//			wp_send_json_error();
		}

		$offset      = absint( get_post_var( 'offset' ) );
		$limit       = absint( get_post_var( 'limit' ) );
		$file_suffix = sanitize_key( get_post_var( 'suffix' ) );
		$fields      = properties()->get_fields();
		$file_name   = 'companies-' . $file_suffix . '.csv';

		$path = get_company_exports_dir( $file_name, true );

		if ( ! file_exists( $path ) || filesize( $path ) === 0 ) {

			$headers = [
				__( 'Name', 'groundhogg-companies' ),
				__( 'Website', 'groundhogg-companies' ),
				__( 'Address', 'groundhogg-companies' ),
				__( 'Phone', 'groundhogg-companies' ),
				__( 'Industry', 'groundhogg-companies' ),
				__( 'Owner', 'groundhogg-companies' ),
				__( 'Contacts', 'groundhogg-companies' ),
			];

			foreach ( $fields as $field ) {
				$headers[] = $field['label'];
			}

			$fp = fopen( $path, 'w' );
			fputcsv( $fp, $headers );
		} else {
			$fp = fopen( $path, 'a' );
		}

		$companies = get_db( 'companies' )->query( [
			'offset' => $offset,
			'limit'  => $limit
		] );

		array_map_to_class( $companies, Company::class );

		/**
		 * @var $company Company
		 */
		foreach ( $companies as $company ) {

			$contacts = $company->get_related_objects( 'contact' );

			$row = [
				$company->get_name(),
				$company->get_domain(),
				$company->get_address(),
				$company->get_meta( 'phone' ),
				$company->get_meta( 'industry' ),
				$company->owner_id,
				implode( ', ', array_map_to_method( $contacts, 'get_email' ) ),
			];

			foreach ( $fields as $field ) {
				$val   = $company->get_meta( $field['name'] );
				$val   = is_array( $val ) ? implode( ', ', $val ) : $val;
				$row[] = $val;
			}

			fputcsv( $fp, $row );
		}

		fclose( $fp );

		wp_send_json_success( [
			'file' => file_access_url( '/exports/' . $file_name )
		] );
	}

	public function handle_import_companies() {

		if ( ! current_user_can( 'add_companies' ) ) {
			wp_send_json_error();
		}

		$file               = get_post_var( 'file' );
		$map                = json_decode( get_post_var( 'map' ), true );
		$offset             = absint( get_post_var( 'offset' ) );
		$limit              = absint( get_post_var( 'limit' ) );
		$link_with_contacts = get_post_var( 'link_similar' );

		if ( ! file_exists( $file ) ) {
			wp_send_json_error( new \WP_Error( 'error', 'Import file not found', [ 'file' => $file ] ) );
		}

		// Headers are not returned when associative is true!
		$items = get_items_from_csv( $file );

		// Do not import all companies at once
		$items = array_slice( $items, $offset, $limit );

		foreach ( $items as $item ) {
			$company = generate_company_with_map( $item, $map );

			// Link contacts with email addresses that match this company's domain
			if ( $link_with_contacts && $company->get_domain() ) {

				// Find contacts with email address ending with the hostname of the company
				$query    = new Contact_Query();
				$contacts = $query->query( [
					'filters' => [
						[
							[
								'type'    => 'email',
								'compare' => 'ends_with',
								'value'   => '@' . parse_url( $company->get_domain(), PHP_URL_HOST )
							]
						]
					]
				], true );

				foreach ( $contacts as $contact ) {
					$company->create_relationship( $contact );
				}
			}
		}

		wp_send_json_success();
	}


	public function help() {
	}

	public function scripts() {

		if ( get_url_var( 'company' ) ) {
			wp_enqueue_editor();
			wp_enqueue_media();
			wp_enqueue_style( 'groundhogg-companies-admin' );
			wp_enqueue_script( 'groundhogg-companies-admin' );
			wp_localize_script( 'groundhogg-companies-admin', 'GroundhoggCompany', [
				'company'                      => new Company( get_request_var( 'company' ) ),
				'gh_company_custom_properties' => properties()->get_all()
			] );
		} else {

			$this->enqueue_table_filters( [
				'stringColumns' => [
					'name'     => 'Name',
					'address'  => 'Address',
					'industry' => 'Industry',
					'phone'    => 'Phone',
					'domain'   => 'Website',
				],
				'dateColumns'   => [
					'date_created' => 'Date created'
				]
			] );

			wp_add_inline_script( 'groundhogg-companies-company-filters', 'const GroundhoggCompanyProperties = ' . wp_json_encode( properties()->get_all() ), 'before' );

            wp_enqueue_script( 'groundhogg-companies-table-admin' );
			wp_enqueue_style( 'groundhogg-admin-element' );
			wp_enqueue_style( 'jquery-ui' );

		}
	}


	protected function add_additional_actions() {

	}

	public function get_slug() {
		return 'gh_companies';
	}

	public function get_name() {
		return _x( 'Companies', 'page_title', 'groundhogg-companies' );
	}

	public function get_cap() {
		return 'edit_companies';
	}

	public function get_item_type() {
		return 'company';
	}

	public function get_item_type_plural() {
		return 'companies';
	}

	public function get_priority() {
		return 6;
	}

	/**
	 * @return string
	 */
	protected function get_title() {
		switch ( $this->get_current_action() ) {
			default:
			case 'add':
			case 'view':
				return $this->get_name();
				break;
			case 'edit':
				return _x( 'Edit Company', 'page_title', 'groundhogg' );
				break;
		}
	}

	protected function get_title_actions() {
		return [
			[
				'link'   => '#',
				'action' => __( 'Add New', 'groundhogg' ),
				'id'     => 'add-company',
				'target' => '_self',
			],
			[
				'link'   => '#',
				'action' => __( 'Import', 'groundhogg' ),
				'id'     => 'import-companies',
				'target' => '_self',
			],
			[
				'link'   => '#',
				'action' => __( 'Export', 'groundhogg' ),
				'id'     => 'export-companies',
				'target' => '_self',
			]
		];
	}

	protected $uploads_path;

	/**
	 * Change the default upload directory
	 *
	 * @param $param
	 *
	 * @return mixed
	 */
	public function files_upload_dir( $param ) {
		$param['path']   = $this->uploads_path['path'];
		$param['url']    = $this->uploads_path['url'];
		$param['subdir'] = $this->uploads_path['subdir'];

		return $param;
	}

	/**
	 * Initialize the base upload path
	 */
	private function set_uploads_path() {
		$this->uploads_path['subdir'] = Plugin::$instance->utils->files->get_base_uploads_dir();
		$this->uploads_path['path']   = get_company_imports_dir();
		$this->uploads_path['url']    = get_company_imports_url();
	}

	/**
	 * Upload a file to the Groundhogg file directory
	 *
	 * @param $file array
	 * @param $config
	 *
	 * @return array|bool|\WP_Error
	 */
	private function handle_file_upload( $file ) {
		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->set_uploads_path();

		add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

		if ( isset( $mfile['error'] ) ) {

			if ( empty( $mfile['error'] ) ) {
				$mfile['error'] = _x( 'Could not upload file.', 'error', 'groundhogg' );
			}

			return new \WP_Error( 'BAD_UPLOAD', $mfile['error'] );
		}

		return $mfile;
	}

	public function ajax_upload_import_file() {

//		if ( ! current_user_can( 'add_companies' ) ){
//			return;
//		}

		$file = $_FILES['file-upload'];

		$validate = wp_check_filetype( $file['name'], [ 'csv' => 'text/csv' ] );

		if ( $validate['ext'] !== 'csv' || $validate['type'] !== 'text/csv' ) {
			wp_send_json_error( new \WP_Error( 'invalid_csv', sprintf( 'Please upload a valid CSV. Expected mime type of <i>text/csv</i> but got <i>%s</i>', esc_html( $file['type'] ) ) ) );
		}

		$file_name = str_replace( '.csv', '', $file['name'] );
		$file_name .= '-' . current_time( 'mysql' ) . '.csv';

		$file['name'] = sanitize_file_name( $file_name );

		$result = $this->handle_file_upload( $file );

		if ( is_wp_error( $result ) ) {

			if ( is_multisite() ) {
				wp_send_json_error( new \WP_Error( 'multisite_add_csv', 'Could not import because CSV is not an allowed file type on this subsite. Please add CSV to the list of allowed file types in the network settings.' ) );
			}

			wp_send_json_error( $result );
		}

		$items       = get_items_from_csv( $result['file'], false, false );
		$headers     = $items[0];
		$total_items = count( $items ) - 1;

		wp_send_json_success( [
			'file'        => $result,
			'headers'     => $headers,
			'results'     => array_slice( $items, 1, 20 ),
			'total_items' => $total_items
		] );

	}

	public function ajax_upload_file() {

		$id      = absint( get_post_var( 'company' ) );
		$company = new Company( $id );

		if ( ! $company->exists() ) {
			wp_send_json_error( new \WP_Error( 'error', 'Company not found' ) );
		}

		$file = $_FILES['file-upload'];

		if ( ! get_array_var( $file, 'error' ) ) {
			$e = $company->upload_file( $file );

			if ( is_wp_error( $e ) ) {
				wp_send_json_error( $e );
			}
		}

		wp_send_json_success( [
			'files' => $company->get_files()
		] );
	}

	/**
	 * Delete company from the admin
	 *
	 * @return bool|\WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_companies' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			$company = new Company( $id );
			$company->delete_pictures();
			$company->delete_files();
			$company->delete();
		}

		$this->add_notice(
			'deleted',
			sprintf( _nx( '%d company deleted.', '%d companies deleted.', count( $this->get_items() ), 'notice', 'groundhogg-company' ),
				count( $this->get_items() )
			)
		);

		return false;
	}

	public function view() {
		if ( ! class_exists( 'Companies_Table' ) ) {
			include dirname( __FILE__ ) . '/companies-table.php';
		}

		$this->table_filters();

		$companies_table = new Companies_Table();

		$companies_table->views();
		$this->search_form( __( 'Search', 'groundhogg' ) );
		?>
        <form method="post" class="wp-clearfix">
			<?php $companies_table->prepare_items(); ?>
			<?php $companies_table->display(); ?>
        </form>
		<?php
	}

	public function edit() {
		if ( ! current_user_can( 'edit_companies' ) ) {
			$this->wp_die_no_access();
		}

		?>
        <div class="space-between align-top company-columns">
            <div id="primary" class="primary">
                <div id="company-info-card" class="gh-panel"></div>
                <div class="company-more"></div>
            </div>
            <div class="directory full-width">
                <div id="directory">
                </div>
            </div>
        </div>
		<?php
	}
}
