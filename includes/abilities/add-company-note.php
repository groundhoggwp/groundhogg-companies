<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use Groundhogg\Classes\Note;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Add_Company_Note extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/add-company-note';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'add_notes';

	protected function get_args(): array {

		return [
			'label'       => __( 'Add Company Note', 'groundhogg-companies' ),
			'description' => __( 'Add a note to a company. Each call creates a new note.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'company_id', 'content' ],
				'properties'           => [
					'company_id' => [ 'type' => 'integer', 'minimum' => 1 ],
					'content'    => [
						'type'        => 'string',
						'description' => __( 'The note text. Merge tags are resolved against the company\'s primary contact.', 'groundhogg-companies' ),
					],
					'summary'    => [ 'type' => 'string' ],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'note' => Company_Schema::note_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$company = self::get_company_for( absint( $input['company_id'] ), 'view' );

		if ( is_wp_error( $company ) ) {
			return $company;
		}

		$data = [
			'object_type' => 'company',
			'object_id'   => $company->get_id(),
			'content'     => $input['content'],
		];

		if ( ! empty( $input['summary'] ) ) {
			$data['summary'] = sanitize_text_field( $input['summary'] );
		}

		// Note::create() runs replacements and kses on the content itself.
		$note = new Note();
		$note->create( $data );

		if ( ! $note->exists() ) {
			return new WP_Error( 'groundhogg_note_not_created', __( 'Unable to create the note.', 'groundhogg-companies' ) );
		}

		return [
			'note' => Company_Schema::transform_note( $note ),
		];
	}
}
