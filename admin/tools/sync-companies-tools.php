<?php

namespace GroundhoggCompanies\Admin\Tools;

use Groundhogg\Plugin;

class sync_companies_Tools {
	public function __construct() {
		add_filter( 'groundhogg/admin/tools/tabs', [ $this, 'register_tab' ], 10 );
		add_action( 'groundhogg/admin/gh_tools/display/companies_view', [ $this, 'render' ], 10 );
		add_filter( 'groundhogg/admin/gh_tools/process_sync_companies', [ $this, 'process' ], 10 );
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
			'name' => __( 'Companies' ),
			'slug' => 'companies'
		];

		return $tabs;
	}

	/**
	 * Render the tab action
	 */
	public function render() {
		?>
		<div class="show-upload-view">
			<div class="upload-plugin-wrap">
				<div class="upload-plugin">
					<p class="install-help"><?php _e( 'Create Companies', 'groundhogg-companies' ); ?></p>
					<form method="post" class="gh-tools-box">
						<?php wp_nonce_field(); ?>
						<?php echo Plugin::$instance->utils->html->input( [
							'type'  => 'hidden',
							'name'  => 'action',
							'value' => 'sync_companies',
						] ); ?>
						<p><?php _e( 'This will create company records with any existing company data in the contact records.', 'groundhogg-companies' ); ?></p>
						<p class="submit" style="text-align: center;padding-bottom: 0;margin: 0;">
							<button style="width: 100%" class="button-primary" name="validate_contacts"
							        value="sync"><?php _ex( 'Create Companies', 'action', 'groundhogg-companies' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	public function process( $exitcode ) {
		Plugin::$instance->bulk_jobs->sync_companies->start();

		return $exitcode;
	}
}