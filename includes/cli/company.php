<?php

namespace GroundhoggCompanies\Cli;

use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_email_address_hostname;
use function Groundhogg\is_free_email_provider;
use function WP_CLI\Utils\make_progress_bar;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

class Company {

	/**
	 * Pull missing information about the company from primary contact records
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-company pull-info
	 *
	 * @subcommand pull-info
	 */
	function pull_info() {

		$query = new Table_Query( 'companies', [
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'found_rows' => true,
		] );

		$items    = $query->get_objects();
		$progress = make_progress_bar( 'Pulling company info...', $query->get_found_rows() );

		while ( ! empty( $items ) ) {

			$item = array_pop( $items );

			// if there is no primary contact ID
			if ( ! $item->primary_contact_id ) {

				// if there was no contact to be set as the primary
				if ( ! $item->set_primary_contact_to_first_created() ) {
					continue;
				}
			}

			$contact = get_contactdata( $item->primary_contact_id );

			// if the domain is not set and the contact has a private email address
			if ( ! $item->domain && ! is_free_email_provider( $contact->get_email() ) ) {
				// set the domain to the host name of the email of the primary contact
				$item->update( [
					'domain' => 'https://' . get_email_address_hostname( $contact->get_email() ),
				] );
			}

			$progress->tick();
		}

		$progress->finish();

		\WP_CLI::success( 'All companies have been updated.' );
	}

}

\WP_CLI::add_command( 'groundhogg-company', __NAMESPACE__ . '\Company' );
