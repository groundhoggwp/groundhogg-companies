<?php

namespace GroundhoggCompanies\Abilities;

use Groundhogg\Abilities\Schemas\Schema;
use Groundhogg\Classes\Note;
use GroundhoggCompanies\Classes\Company;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Company, as returned by the groundhogg-companies/* abilities.
 *
 * Standard columns plus industry, address and phone are always returned. 'meta' and
 * 'contacts' are bounded-but-optional and only computed when passed in $include.
 */
class Company_Schema extends Schema {

	/**
	 * The optional sections a caller can ask for.
	 *
	 * @return string[]
	 */
	public static function expand_options(): array {
		return [ 'meta', 'contacts' ];
	}

	public static function get_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id'                 => [ 'type' => 'integer' ],
				'name'               => [ 'type' => 'string' ],
				'domain'             => [ 'type' => 'string' ],
				'description'        => [ 'type' => 'string' ],
				'owner_id'           => [
					'type'        => 'integer',
					'description' => __( 'The WordPress user ID of the company owner.', 'groundhogg-companies' ),
				],
				'primary_contact_id' => [
					'type'        => 'integer',
					'description' => __( 'The contact ID of the primary contact, or 0 if none.', 'groundhogg-companies' ),
				],
				'industry'           => [ 'type' => 'string' ],
				'phone'              => [ 'type' => 'string' ],
				'address'            => [ 'type' => 'string' ],
				'date_created'       => [ 'type' => 'string' ],
				'admin_url'          => [ 'type' => 'string', 'format' => 'uri' ],
				'meta'               => [
					'type'                 => 'object',
					'description'          => __( 'Only present when "meta" is passed in expand. Raw meta key => value pairs, including custom properties.', 'groundhogg-companies' ),
					'additionalProperties' => true,
				],
				'contacts'           => [
					'type'        => 'array',
					'description' => __( 'Only present when "contacts" is passed in expand. The contacts related to this company.', 'groundhogg-companies' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'id'         => [ 'type' => 'integer' ],
							'email'      => [ 'type' => 'string' ],
							'first_name' => [ 'type' => 'string' ],
							'last_name'  => [ 'type' => 'string' ],
						],
					],
				],
			],
		];
	}

	/**
	 * @param Company|int|object $object
	 * @param array              $include Optional sections: 'meta', 'contacts'.
	 *
	 * @return array
	 */
	public static function transform( $object, array $include = [] ): array {

		if ( ! $object instanceof Company ) {
			$object = new Company( $object );
		}

		$data = [
			'id'                 => $object->get_id(),
			'name'               => (string) $object->get_name(),
			'domain'             => (string) $object->get_domain(),
			'description'        => (string) $object->get_description(),
			'owner_id'           => $object->get_owner_id(),
			'primary_contact_id' => absint( $object->primary_contact_id ),
			'industry'           => (string) $object->get_meta( 'industry' ),
			'phone'              => (string) $object->get_meta( 'phone' ),
			'address'            => (string) $object->get_address(),
			'date_created'       => (string) $object->date_created,
			'admin_url'          => $object->admin_link(),
		];

		if ( in_array( 'meta', $include, true ) ) {

			$meta = [];

			foreach ( $object->get_meta() as $key => $value ) {
				// private meta, e.g. the cleaned _phone number
				if ( strpos( $key, '_' ) === 0 ) {
					continue;
				}

				$meta[ $key ] = $value;
			}

			$data['meta'] = (object) $meta;
		}

		if ( in_array( 'contacts', $include, true ) ) {
			$data['contacts'] = array_map( function ( $contact ) {
				return [
					'id'         => $contact->get_id(),
					'email'      => $contact->get_email(),
					'first_name' => $contact->get_first_name(),
					'last_name'  => $contact->get_last_name(),
				];
			}, $object->get_contacts() );
		}

		return $data;
	}

	/**
	 * JSON schema for a note attached to a company.
	 *
	 * @return array
	 */
	public static function note_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'id'           => [ 'type' => 'integer' ],
				'company_id'   => [ 'type' => 'integer' ],
				'user_id'      => [
					'type'        => 'integer',
					'description' => __( 'The WordPress user who authored the note.', 'groundhogg-companies' ),
				],
				'summary'      => [ 'type' => 'string' ],
				'content'      => [ 'type' => 'string' ],
				'date_created' => [ 'type' => 'string' ],
			],
		];
	}

	/**
	 * @param Note $note
	 *
	 * @return array
	 */
	public static function transform_note( Note $note ): array {
		return [
			'id'           => $note->get_id(),
			'company_id'   => absint( $note->object_id ),
			'user_id'      => $note->get_owner_id(),
			'summary'      => (string) $note->summary,
			'content'      => (string) $note->content,
			'date_created' => (string) $note->date_created,
		];
	}
}
