<?php

namespace GroundhoggCompanies\Classes;

use Groundhogg\Base_Object_With_Meta;
use Groundhogg\Classes\Traits\File_Box;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_email_address_hostname;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function GroundhoggCompanies\is_free_email_provider;
use function GroundhoggCompanies\sanitize_domain_name;

class Company extends Base_Object_With_Meta {

	use File_Box;

	protected function post_setup() {
		$this->primary_contact_id = absint( $this->primary_contact_id );
	}

	/**
	 * Gets the oldest contact from the related contacts and makes it the primary contact by default
	 *
	 * @return bool true if a contact was found, false otherwise
	 */
	public function set_primary_contact_to_first_created() {
		$contactQuery = new Contact_Query( [
			'filters' => [
				[
					[
						'type'        => 'secondary_related',
						'object_id'   => $this->ID,
						'object_type' => 'company'
					]
				]
			],
			'orderby' => 'date_created',
			'order'   => 'ASC',
			'limit'   => 1
		] );

		$contacts = $contactQuery->get_objects();

		if ( empty( $contacts ) ){
			return false;
		}

		$this->update([
			'primary_contact_id' => $contacts[0]->ID
		]);

		return true;
	}

	protected function get_meta_db() {
		return get_db( 'company_meta' );
	}

	protected function get_db() {
		return get_db( 'companies' );
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @return int
	 */
	public function get_contact_count() {
		return absint( $this->contact_count );
	}

	public function get_domain() {
		return $this->domain;
	}

	public function get_hostname() {
		return wp_parse_url( $this->get_domain(), PHP_URL_HOST );
	}

	public function get_owner_id() {
		return absint( $this->owner_id );
	}

	public function get_profile_picture() {
		return $this->get_meta( 'picture' );
	}

	public function get_address() {
		return $this->get_meta( 'address' );
	}


	public function get_searchable_address() {
		return implode( ', ', explode( PHP_EOL, $this->get_address() ) );
	}

	/**
	 * For JSON
	 *
	 * @return array|void
	 */
	public function get_as_array() {

		$array                  = parent::get_as_array();
		$array['admin']         = admin_page_url( 'gh_companies', [ 'action' => 'edit', 'company' => $this->get_id() ] );
		$array['totalContacts'] = $this->countRelatedContacts();

		return $array;
	}

	public function countRelatedContacts() {

		$query = new Table_Query( 'object_relationships' );
		$query->where()
		      ->equals( 'primary_object_id', $this->get_id() )
		      ->equals( 'primary_object_type', $this->get_object_type() )
		      ->equals( 'secondary_object_type', 'contact' );

		return $query->count();
	}

	public function update( $data = [] ) {

		// detect name change
		if ( isset_not_empty( $data, 'name' ) && $data['name'] !== $this->get_name() ) {
			$data['slug'] = sanitize_title( $data['name'] );

			// If the new slug is different
			if ( $data['slug'] !== $this->slug ) {
				// detect slug in use and is not same co
				$company = new Company( $data['slug'], 'slug' );
				if ( $company->exists() ) {
					return false;
				}
			}
		}

		return parent::update( $data );
	}

	// store a clean version of the phone number
	public function update_meta( $key, $value = false ) {

		if ( $key === 'phone' ) {
			$_value = preg_replace( '/[^0-9]/', '', $value );
			$this->update_meta( '_phone', $_value );
		}

		return parent::update_meta( $key, $value );
	}

	/**
	 * Update the company from the contact details
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function update_from_contact( $contact ) {

		if ( ! is_a_contact( $contact ) ) {
			return false;
		}

		$company_name    = $contact->get_meta( 'company_name' );
		$company_website = $contact->get_meta( 'company_website' );
		$company_address = $contact->get_meta( 'company_address' );
		$company_phone   = $contact->get_meta( 'company_phone' );

		$args = [];

		if ( ! $company_name ) {
			return false;
		}

		$args['name'] = $company_name;

		if ( $company_website ) {
			$args['domain'] = $company_website;
		} else {
			if ( ! is_free_email_provider( $contact->get_email() ) ) {
				$args['domain'] = 'https://' . get_email_address_hostname( $contact->get_email() );
			}
		}

		if ( $company_website ) {
			$args['domain'] = $company_website;
		}

		// update the company owner to the contact's owner
		if ( $contact->get_ownerdata() ) {
			$args['owner_id'] = $contact->get_owner_id();
		}

		$updated = $this->update( $args );

		if ( ! $updated ) {
			return false;
		}

		if ( $company_address ) {
			$this->update_meta( 'address', $company_address );
		}

		if ( $company_phone ) {
			$this->update_meta( 'phone', $company_phone );
		}

		return $updated;
	}

	/**
	 * @return Contact[]
	 */
	public function get_contacts() {
		return $this->get_related_objects( 'contact' );
	}

	public function admin_link() {
		return admin_page_url( 'gh_companies', [
			$this->get_object_type() => $this->get_id(),
			'action'                 => 'edit'
		] );
	}

	protected function sanitize_columns( $data = [] ) {

		foreach ( $data as $col => &$val ) {
			switch ( $col ) {

				case 'domain':
					$val = sanitize_domain_name( $val );
					break;
				case 'slug':
					$val = sanitize_title( $val );
					break;
				case 'name':
					$val = sanitize_text_field( $val );
					break;
			}
		}

		return $data;
	}

	/**
	 * Wrapper for create relationship to handle setting the primary contact ID
	 *
	 * @param $other
	 * @param $is_parent
	 *
	 * @return int
	 */
	public function create_relationship( $other, $is_parent = true ) {
		$result = parent::create_relationship( $other, $is_parent );

		// If there is no primary contact ID, make the first assigned contact the primary
		if ( $result !== false && is_a_contact( $other ) && $this->primary_contact_id === 0 ) {
			$this->update( [
				'primary_contact_id' => $other->ID
			] );
		}

		return $result;
	}

	/**
	 * Maybe remove the primary_contact_id
	 *
	 * @param $other
	 * @param $is_parent
	 *
	 * @return false|int
	 */
	public function delete_relationship( $other, $is_parent = true ) {
		$result = parent::delete_relationship( $other, $is_parent );

		// If we're deleting the relationship that was the primary contact ID, set it to 0
		if ( $result && $other->_get_object_type() === 'contact' && $this->primary_contact_id == $other->ID ) {
			$this->set_primary_contact_to_first_created();
		}

		return $result;
	}

	public function get_uploads_folder_subdir() {
		return 'companies';
	}
}
