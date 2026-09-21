<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Abilities as Core_Abilities;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Companies abilities with Groundhogg's ability registry. Must be constructed on
 * or before plugins_loaded - see Groundhogg\Abilities\Abilities::add_ability().
 */
class Abilities {

	public function __construct() {

		// Only Groundhogg versions that ship the extensible abilities registry have add_ability().
		if ( ! class_exists( Core_Abilities::class ) || ! method_exists( Core_Abilities::class, 'add_ability' ) ) {
			return;
		}

		$this->extend_contact_schema();

		Core_Abilities::add_category( 'groundhogg-companies', [
			'label'       => __( 'Groundhogg Companies', 'groundhogg-companies' ),
			'description' => __( 'Find and manage companies, their contacts and notes.', 'groundhogg-companies' ),
		] );

		foreach ( [
			List_Companies::class,
			Get_Company::class,
			Create_Company::class,
			Update_Company::class,
			Add_Contact_To_Company::class,
			Remove_Contact_From_Company::class,
			Add_Company_Note::class,
			List_Company_Notes::class,
		] as $ability ) {
			Core_Abilities::add_ability( $ability );
		}
	}

	/**
	 * Add an optional "work_details" section to groundhogg/get-contact, search-contacts, create-contact
	 * and update-contact via their `expand` param: the contact's job title, department and company
	 * details. Has to run before those abilities build their schemas, i.e. on plugins_loaded or earlier.
	 *
	 * @return void
	 */
	protected function extend_contact_schema() {

		if ( ! method_exists( Contact_Schema::class, 'extend' ) ) {
			return;
		}

		$properties = [
			'company_name'            => __( 'The name of the company the contact works for.', 'groundhogg-companies' ),
			'company_website'         => __( 'The company website.', 'groundhogg-companies' ),
			'company_address'         => __( 'The company address.', 'groundhogg-companies' ),
			'company_phone'           => __( 'The contact\'s work phone number.', 'groundhogg-companies' ),
			'company_phone_extension' => __( 'The work phone extension.', 'groundhogg-companies' ),
			'job_title'               => __( 'The contact\'s position / job title.', 'groundhogg-companies' ),
			'company_department'      => __( 'The department the contact works in.', 'groundhogg-companies' ),
		];

		Contact_Schema::extend(
			'work_details',
			__( 'The contact\'s work details (job title, department and company info). Company name, phone, website and address fall back to the first linked company when the contact has none of their own. To change these, pass the same keys in the "meta" param of groundhogg/update-contact.', 'groundhogg-companies' ),
			function ( Contact $contact ) use ( $properties ) {

				$related = $contact->get_related_objects( 'company', false );
				$company = ! empty( $related ) && current_user_can( 'view_company', $related[0] ) ? $related[0] : null;

				// which linked-company field backs each contact field when the contact has no value
				$fallbacks = [
					'company_name'    => 'name',
					'company_website' => 'domain',
					'company_address' => 'address',
					'company_phone'   => 'phone',
				];

				$data = [];

				foreach ( array_keys( $properties ) as $key ) {

					$value = (string) $contact->get_meta( $key );

					if ( $value === '' && $company && isset( $fallbacks[ $key ] ) ) {
						$value = (string) $company->{$fallbacks[ $key ]};
					}

					$data[ $key ] = $value;
				}

				return (object) $data;
			},
			'object'
		);

		// extend() only takes a scalar type, so describe the object's properties too
		add_filter( 'groundhogg/contact_schema/properties', function ( $schema_properties ) use ( $properties ) {

			if ( isset( $schema_properties['work_details'] ) ) {
				$schema_properties['work_details']['properties'] = array_map( function ( $description ) {
					return [ 'type' => 'string', 'description' => $description ];
				}, $properties );
			}

			return $schema_properties;
		} );
	}
}
