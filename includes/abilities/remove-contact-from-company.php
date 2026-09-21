<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remove_Contact_From_Company extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/remove-contact-from-company';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'edit_companies';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Remove Contact From Company', 'groundhogg-companies' ),
			'description' => __( 'Unlink a contact from a company. Neither the contact nor the company is deleted. If the contact was the primary contact, the oldest remaining contact becomes the primary contact.', 'groundhogg-companies' ),

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

		if ( ! $company->is_related( $contact ) ) {
			return new WP_Error( 'groundhogg_contact_not_in_company', __( 'This contact is not linked to the company.', 'groundhogg-companies' ) );
		}

		$company->delete_relationship( $contact );

		return [
			'company' => Company_Schema::transform( $company, [ 'contacts' ] ),
		];
	}
}
