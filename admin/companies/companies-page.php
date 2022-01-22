<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Admin\Admin_Page;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\normalize_files;
use function GroundhoggCompanies\get_clean_domain;
use function GroundhoggCompanies\recount_company_contacts_count;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Companies
 *
 * @package     Admin
 * @subpackage  Admin/Companies
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0
 */
class Companies_Page extends Admin_Page {
	// UNUSED FUNCTIONS
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_company_upload_file', [ $this, 'ajax_upload_file' ] );
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
				'gh_company_custom_properties' => get_option( 'gh_company_custom_properties' )
			] );
		} else {
			wp_enqueue_script( 'groundhogg-companies-table-admin' );
			wp_enqueue_style( 'groundhogg-admin-element' );
//			wp_enqueue_style( 'groundhogg-companies-admin' );
		}
	}


	protected function add_additional_actions() {
		if ( isset( $_GET['recount_contacts'] ) ) {
			add_action( 'init', array( $this, 'recount' ) );
		}
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
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'id'     => 'add-company',
				'target' => '_self',
			]
		];
	}

	public function ajax_upload_file() {

		$id      = absint( get_post_var( 'company' ) );
		$company = new Company( $id );

		if( ! $company->exists() ){
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

		$companies_table = new Companies_Table();

		$companies_table->views();
		$this->search_form( __( 'Search Companies', 'groundhogg' ) );
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
				<div id="form" class="gh-panel">
					// Form
				</div>
				<div class="company-more"></div>
			</div>
			<div class="directory full-width">
				<div class="gh-panel">
					<div class="space-between directory-header">
						<h3><?php _e( 'Contacts', 'groundhoggg' ) ?></h3>
						<div class="space-between align-right no-gap directory-actions">
						</div>
					</div>
				</div>
				<div id="directory">
					// Contacts
				</div>
			</div>
		</div>
		<?php
	}
}