<?php

namespace GroundhoggCompanies\Background;

use Groundhogg\Background\Export_Contacts;
use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\file_access_url;
use function Groundhogg\files;
use function Groundhogg\get_contactdata;
use function Groundhogg\html;
use function Groundhogg\notices;
use function Groundhogg\white_labeled_name;
use function GroundhoggCompanies\properties;

class Export_Companies extends Export_Contacts {

	public function __construct( array $query, string $fileName, array $columns, int $batch = 0 ) {

		$this->query    = $query;
		$this->user_id  = get_current_user_id();
		$this->filePath = files()->get_csv_exports_dir( $fileName );
		$this->columns  = $columns;
		$this->batch    = $batch;

		$theQuery = new Table_Query( 'companies' );
		$theQuery->set_query_params( $query );
		$this->items = $theQuery->count();
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title() {

		$fileName = bold_it( basename( $this->filePath ) );

		if ( $this->get_progress() >= 100 ) {
			$fileName = html()->e( 'a', [
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], $fileName );
		}

		return sprintf( 'Export %s companies to %s', bold_it( _nf( $this->items ) ), $fileName );
	}

	/**
	 * Export the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$query_args = array_merge( [
			'limit'      => self::BATCH_LIMIT,
			'offset'     => $this->batch * self::BATCH_LIMIT,
		], $this->query );

		$query = new Table_Query( 'companies' );
		$query->set_query_params( $query_args );

		$items = $query->get_objects();

		if ( empty( $items ) ) {

			$message = sprintf( __( 'Your companies export %s is ready for download!', 'groundhogg' ), html()->e( 'a', [
//				'class' => 'gh-button primary',
				'href' => file_access_url( '/exports/' . basename( $this->filePath ), true )
			], __( bold_it( basename( $this->filePath ) ), 'groundhogg' ) ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			$subject = sprintf( __( "[%s] Export ready!" ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		foreach ( $items as $item ) {

			if ( ! user_can( $this->user_id, 'view_company', $item ) ) {
				continue;
			}

			$owner = get_userdata( $item->owner_id );
			$contact = new Contact( $item->primary_contact_id );

			$line = [
				$item->name,
				$item->domain,
				$item->address,
				$item->phone,
				$item->industry,
				$owner->display_name,
				$item->countRelatedContacts(),
				$contact->get_full_name(),
				$contact->get_email()
			];

			$groups = properties()->get_groups();

			foreach ( $groups as $group ){
				$fields = properties()->get_fields( $group['id'] );

				foreach ( $fields as $field ){
					$line[] = $item->get_meta( $field['name'] );
				}
			}

			fputcsv( $this->filePointer, $line );
		}

		$this->batch ++;

		return false;
	}
}
