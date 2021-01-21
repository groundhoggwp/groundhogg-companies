<?php

namespace GroundhoggCompanies\Admin\Tools;

use Cassandra\Varint;
use Groundhogg\Admin\Tools\Tools_Page;
use Groundhogg\Plugin;
use WP_Error;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;
use function GroundhoggCompanies\get_company_imports_dir;
use function GroundhoggCompanies\get_company_imports_url;

class Import_Companies_Tools {

	protected $uploads_path = [];


	public function __construct() {

		add_filter( 'groundhogg/admin/tools/tabs', [ $this, 'register_tab' ], 10 );
		add_filter( 'groundhogg/admin/tools/title_action', [ $this, 'title_action' ], 10, 2 );


		/**
		 * Views
		 */
		add_action( 'groundhogg/admin/gh_tools/display/import_companies_view', [
			$this,
			'render_import_companies'
		], 10 );
		add_action( 'groundhogg/admin/gh_tools/display/import_companies_add', [ $this, 'render_import_add' ], 10 );
		add_action( 'groundhogg/admin/gh_tools/display/import_companies_map', [ $this, 'render_import_map' ], 10 );
		/**
		 * Processes
		 */
		add_filter( 'groundhogg/admin/gh_tools/process_import_companies_upload_file', [
			$this,
			'process_import_companies_add'
		], 10 );
		add_filter( 'groundhogg/admin/gh_tools/process_import_companies_delete', [
			$this,
			'process_import_companies_delete'
		], 10 );

		add_filter( 'groundhogg/admin/gh_tools/process_import_companies_map', [
			$this,
			'process_import_companies_map'
		], 10 );


	}


	/**
	 * Map import view
	 */
	public function render_import_map() {
		include __DIR__ . '/map-import.php';
	}


	/**
	 * Register the tab
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	public function register_tab( $tabs ) {

		$tabs [] = [
			'name' => __( 'Import Companies', 'groundhogg-companies' ),
			'slug' => 'import_companies'
		];

		return $tabs;
	}


	/**
	 * Adds button on the top of the page for file upload
	 *
	 * @param $actions array
	 * @param $tools_page Tools_Page
	 *
	 * @return array
	 */
	public function title_action( $actions, $tools_page ) {

		if ( $tools_page->get_current_tab() === 'import_companies' ) {
			$actions[] = [
				'link'   => $tools_page->admin_url( [ 'action' => 'add', 'tab' => 'import_companies' ] ), //todo enable
				'action' => __( 'Import Companies' ),
			];
		}

		return $actions;

	}


	/**
	 * Renders the table for import companies
	 */
	public function render_import_companies() {

		if ( ! class_exists( 'WPGH_Imports_Table' ) ) {
			require_once __DIR__ . '/imports-table-company.php';
		}

		$table = new Imports_Table_Company(); ?>
        <form method="post" class="search-form wp-clearfix">
			<?php $table->prepare_items(); ?>
			<?php $table->display(); ?>
        </form>
		<?php
	}

	/**
	 * Add new import view
	 */
	public function render_import_add() {
		?>
        <div class="gh-tools-wrap">
            <p class="tools-help"><?php _e( 'If you have a .CSV file you can upload it here!', 'groundhogg' ); ?></p>
            <form method="post" enctype="multipart/form-data" class="gh-tools-box">
				<?php wp_nonce_field(); ?>
				<?php echo Plugin::$instance->utils->html->input( [
					'type'  => 'hidden',
					'name'  => 'action',
					'value' => 'upload_file',
				] ); ?>
                <input type="file" name="import_file" id="import_file" accept=".csv">
                <button class="button-primary" name="import_file_button"
                        value="import_companies"><?php _ex( 'Import Companies', 'action', 'groundhogg' ); ?></button>
            </form>
            <p class="description" style="text-align: center"><a
                        href="<?php echo admin_url( 'admin.php?page=gh_tools&tab=import_companies&action=view' ); ?>">&larr;&nbsp;<?php _e( 'Import from existing file.' ); ?></a>
            </p>
        </div>
		<?php

	}

	/**
	 * Renders form to upload a file
	 *
	 * @param $exitcode
	 *
	 * @return array|string|string[]|void|WP_Error
	 */

	public function process_import_companies_add( $exitcode ) {

		$file = get_array_var( $_FILES, 'import_file' );

		$validate = wp_check_filetype( $file['name'], [ 'csv' => 'text/csv' ] );

		if ( $validate['ext'] !== 'csv' || $validate['type'] !== 'text/csv' ) {
			return new \WP_Error( 'invalid_csv', sprintf( 'Please upload a valid CSV. Expected mime type of <i>text/csv</i> but got <i>%s</i>', esc_html( $file['type'] ) ) );
		}

		$file_name = str_replace( '.csv', '', $file['name'] );
		$file_name .= '-' . current_time( 'mysql' ) . '.csv';

		$file['name'] = sanitize_file_name( $file_name );


		$result = $this->handle_file_upload( $file );

		if ( is_wp_error( $result ) ) {

			if ( is_multisite() ) {
				return new \WP_Error( 'multisite_add_csv', 'Could not import because CSV is not an allowed file type on this subsite. Please add CSV to the list of allowed file types in the network settings.' );
			}

			return $result;
		}

		return admin_url( 'admin.php?page=gh_tools&action=map&tab=import_companies&import=' . urlencode( basename( $result['file'] ) ) );

	}


	/**
	 * Change the default upload directory
	 *
	 * @param $param
	 *
	 * @return mixed
	 */
	public function company_upload_dir( $param ) {
		$param['path']   = $this->uploads_path['path'];
		$param['url']    = $this->uploads_path['url'];
		$param['subdir'] = $this->uploads_path['subdir'];

		return $param;
	}

	/**
	 * Initialize the base upload path
	 */
	private function set_uploads_path() {
//		$this->uploads_path['subdir'] = Plugin::$instance->utils->files->get_base( 'company-imports' );

		$this->uploads_path['subdir'] = 'groundhogg/company-imports';
		$this->uploads_path['path']   = get_company_imports_dir();
		$this->uploads_path['url']    = get_company_imports_url();
	}


	/**
	 * Helper function to upload a file
	 *
	 * @param $file
	 *
	 * @return array|string[]|WP_Error
	 */
	private function handle_file_upload( $file ) {
		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->set_uploads_path();

		add_filter( 'upload_dir', array( $this, 'company_upload_dir' ) );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', array( $this, 'company_upload_dir' ) );

		if ( isset( $mfile['error'] ) ) {

			if ( empty( $mfile['error'] ) ) {
				$mfile['error'] = _x( 'Could not upload file.', 'error', 'groundhogg' );
			}

			return new WP_Error( 'BAD_UPLOAD', $mfile['error'] );
		}

		return $mfile;
	}

	/**
	 * Get the affected items on this page
	 *
	 * @return array|bool
	 */
	protected function get_items() {

		$items = get_request_var( 'import', null );

		if ( ! $items ) {
			return false;
		}

		return is_array( $items ) ? $items : array( $items );
	}

	/**
	 * Deletes the file from the company import folder
	 *
	 * @return string|void
	 */
	public function process_import_companies_delete() {

		$files = $this->get_items();
		foreach ( $files as $file_name ) {
			$filepath = get_company_imports_dir( $file_name );
			if ( file_exists( $filepath ) ) {
				unlink( $filepath );
			}
		}

		Plugin::$instance->notices->add( 'file_removed', __( 'Imports deleted.', 'groundhogg' ) );

		return admin_url( 'admin.php?page=gh_tools&action=view&tab=import_companies' );
	}


	public function process_import_companies_map( $exitcode ) {

//	    if ( ! current_user_can( 'add_company' ) ) {
//		    $this->wp_die_no_access();
//	    }

		$map = map_deep( get_post_var( 'map' ), 'sanitize_text_field' );

		if ( ! is_array( $map ) ) {
			wp_die( 'Invalid map provided.' );
		}

		if ( ! in_array( 'company_name', $map ) ) {
			Plugin::$instance->notices->add( "no_company_name", __( "Please map company name to Import companies", 'groundhogg-companies' ), 'error' );

			return admin_url( 'admin.php?page=gh_tools&action=map&tab=import_companies&import=' . get_request_var( 'import' ) );
		}

		$file_name = sanitize_file_name( get_post_var( 'import' ) );

		set_transient( 'gh_import_companies_map', $map, DAY_IN_SECONDS );

		Plugin::$instance->bulk_jobs->import_companies->start( [ 'import' => $file_name ] );

		return $exitcode;

	}


}