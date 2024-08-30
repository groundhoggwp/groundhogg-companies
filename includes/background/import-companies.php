<?php

namespace GroundhoggCompanies\Background;

use Exception;
use Groundhogg\Background\Import_Contacts;
use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\files;
use function Groundhogg\get_array_var;
use function Groundhogg\isset_not_empty;
use function Groundhogg\notices;
use function Groundhogg\white_labeled_name;
use function GroundhoggCompanies\generate_company_with_map;

class Import_Companies extends Import_Contacts {

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title() {
		return sprintf( 'Import %s rows as companies from %s', bold_it( _nf( $this->get_rows() ) ), bold_it( $this->fileName ) );
	}

	/**
	 * Only runs once at the beginning of the task
	 *
	 * @return bool
	 */
	public function can_run() {
		$this->filePath = wp_normalize_path( files()->get_csv_imports_dir( $this->fileName ) );

		if ( ! file_exists( $this->filePath ) ) {
			return false;
		}

		$this->advance();

		return user_can( $this->user_id, 'add_companies' );
	}

	/**
	 * Process the items
	 *
	 * @return bool
	 */
	public function process(): bool {

		$items = $this->get_items_from_csv();
		$map   = get_array_var( $this->settings, 'field_map' );

		if ( empty( $items ) ) {

			$message = sprintf( __( 'All companies have been imported from %s!', 'groundhogg' ), code_it( $this->fileName ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			$subject = sprintf( __( '[%s] Companies imported!' ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		foreach ( $items as $item ) {

			try {
				$company = generate_company_with_map( $item, $map );
			} catch ( Exception $e ) {
				continue;
			}

			if ( ! $company->exists() ) {
				continue;
			}

			if ( isset_not_empty( $this->settings, 'link_similar' ) && $company->get_domain() ) {

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

		$this->batch ++;

		return false;
	}
}
