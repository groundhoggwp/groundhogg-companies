<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Ability;
use Groundhogg\Classes\Note;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class List_Company_Notes extends Ability {

	use Has_Company_Lookup;

	protected const NAME       = 'groundhogg-companies/list-company-notes';
	protected const CATEGORY   = 'groundhogg-companies';
	protected const CAPABILITY = 'view_notes';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Company Notes', 'groundhogg-companies' ),
			'description' => __( 'List the notes on a company, most recent first.', 'groundhogg-companies' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'company_id' ],
				'properties'           => [
					'company_id' => [ 'type' => 'integer', 'minimum' => 1 ],
					'limit'      => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 100 ],
					'offset'     => [ 'type' => 'integer', 'minimum' => 0, 'default' => 0 ],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total_items' => [ 'type' => 'integer' ],
					'notes'       => [
						'type'  => 'array',
						'items' => Company_Schema::note_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$company = self::get_company_for( absint( $input['company_id'] ), 'view' );

		if ( is_wp_error( $company ) ) {
			return $company;
		}

		$db      = get_db( 'notes' );
		$results = $db->query( [
			'object_type' => 'company',
			'object_id'   => $company->get_id(),
			'limit'       => ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 200 ) : 100,
			'offset'      => ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0,
			'found_rows'  => true,
			'orderby'     => 'date_created',
			'order'       => 'DESC',
		] );

		// Capture before anything else runs a query.
		$total_items = $db->found_rows();

		return [
			'total_items' => $total_items,
			'notes'       => array_map( function ( $note ) {
				return Company_Schema::transform_note( new Note( $note ) );
			}, $results ),
		];
	}
}
