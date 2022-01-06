<?php

namespace GroundhoggCompanies\Admin\Tools;

use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\get_key_from_column_label;
use function Groundhogg\get_mappable_fields;
use function GroundhoggCompanies\get_company_imports_dir;
use function GroundhoggCompanies\get_company_mappable_fields;

/**
 * Map Import
 *
 * map the fields to company record record fields.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$file_name = sanitize_file_name( urldecode( $_GET['import'] ) );
$file_path = get_company_imports_dir( $file_name );

if ( ! file_exists( $file_path ) ) {
	wp_die( 'The given file does not exist.' );
}

$items = get_items_from_csv( $file_path );

$selected = absint( get_url_var( 'preview_item' ) );

$total_items = count( $items );

$sample_item = $items[ $selected ];

?>
<form method="post">
	<?php wp_nonce_field(); ?>
	<?php echo Plugin::$instance->utils->html->input( [
		'type'  => 'hidden',
		'name'  => 'import',
		'value' => $file_name
	] ); ?>
	<h2><?php _e( 'Map Contact Fields', 'groundhogg' ); ?></h2>
	<p class="description"><?php _e( 'Map your CSV columns to the contact records fields below.', 'groundhogg' ); ?></p>
	<style>
        select {
            vertical-align: top !important;
        }
	</style>
	<div class="tablenav" style="max-width: 900px;">
		<div class="alignright">
			<?php

			$base_admin_url = add_query_arg( [
				'page'   => 'gh_tools',
				'tab'    => 'import_companies',
				'action' => 'map',
				'import' => $file_name,
			], admin_url( 'admin.php' ) );

			if ( $selected > 0 ) {
				echo html()->e( 'a', [
					'href'  => add_query_arg( 'preview_item', $selected - 1, $base_admin_url ),
					'class' => 'button'
				], __( '&larr; Prev' ) );
				echo '&nbsp;';
			}

			if ( $selected < $total_items - 1 ) {
				echo html()->e( 'a', [
					'href'  => add_query_arg( 'preview_item', $selected + 1, $base_admin_url ),
					'class' => 'button'
				], __( 'Next &rarr;' ) );
			}


			?>
		</div>
	</div>
	<table class="form-table">
		<thead>
		<tr>
			<th><?php _e( 'Column Label', 'groundhogg' ) ?></th>
			<td><b><?php _e( 'Example Data / Contact Record Field', 'groundhogg' ) ?></b></td>
		</tr>
		</thead>
		<tbody>
		<?php

		foreach ( $sample_item as $key => $value ):
			?>
			<tr>
				<th><?php echo $key; ?></th>
				<td>
					<?php echo Plugin::$instance->utils->html->input( [
						'name'     => 'no_submit',
						'value'    => $value,
						'readonly' => true,
//                'disabled' => true,
					] );

					echo Plugin::$instance->utils->html->dropdown( [
						'name'        => sprintf( 'map[%s]', $key ),
						'id'          => sprintf( 'map_%s', $key ),
						'selected'    => get_key_from_column_label( $key ),
						'options'     => get_company_mappable_fields(),
						'option_none' => '* Do Not Map *'
					] );
					?>
				</td>
			</tr>
		<?php
		endforeach;
		?>
		</tbody>
	</table>
	<?php submit_button( __( 'Import Contacts', 'groundhogg' ) ) ?>
</form>
