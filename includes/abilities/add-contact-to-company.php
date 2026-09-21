<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Add_Contact_To_Company extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/add-contact-to-company';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'edit_companies';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Add Contact To Company', 'groundhogg-companies' ),
			'description' => __( 'Link a contact to a company. If the company has no primary contact yet, this contact becomes the primary contact. Does nothing if they are already linked.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'company_id', 'contact_id' ],
				'properties'           => [
					'company_id' => [ 'type' => 'integer', 'minimum' => 1 ],
					'contact_id' => [ 'type' => 'integer', 'minimum' => 1 ],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'company' => Company_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$company = self::get_company_for( absint( $input['company_id'] ), 'edit' );

		if ( is_wp_error( $company ) ) {
			return $company;
		}

		$contact = get_contactdata( absint( $input['contact_id'] ) );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg-companies' ) );
		}

		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_contact', __( 'You do not have permission to edit this contact.', 'groundhogg-companies' ) );
		}

		// create_relationship() returns the new row id, which is 0 for this table, so only a strict false is a failure
		if ( ! $company->is_related( $contact ) && $company->create_relationship( $contact ) === false ) {
			return new WP_Error( 'groundhogg_relationship_failed', __( 'Unable to link the contact to the company.', 'groundhogg-companies' ) );
		}

		return [
			'company' => Company_Schema::transform( $company, [ 'contacts' ] ),
		];
	}
}
