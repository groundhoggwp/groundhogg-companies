<?php

namespace GroundhoggCompanies\Classes;

use Groundhogg\Base_Object_With_Meta;
use Groundhogg\Contact;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\file_access_url;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_email_address_hostname;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function GroundhoggCompanies\is_free_email_provider;
use function GroundhoggCompanies\sanitize_domain_name;

class Company extends Base_Object_With_Meta {
	protected function post_setup() {
		// TODO: Implement post_setup() method.
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
		$array          = parent::get_as_array();
		$array['logo']  = $this->get_meta( 'logo' ) ?: ( $this->get_picture() ?: false );
		$array['admin'] = admin_page_url( 'gh_companies', [ 'action' => 'edit', 'company' => $this->get_id() ] );

		return $array;
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
			$this->update_meta( 'phone', $company_address );
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
	 * Upload a file
	 *
	 * Usage: $contact->upload_file( $_FILES[ 'file_name' ] )
	 *
	 * @param $file
	 *
	 * @return array|\WP_Error
	 */
	public function upload_file( &$file ) {

		$file['name'] = sanitize_file_name( $file['name'] );

		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}


		$this->get_uploads_folder();
		add_filter( 'upload_dir', [ $this, 'map_upload' ] );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', [ $this, 'map_upload' ] );

		if ( isset_not_empty( $mfile, 'error' ) ) {
			return new \WP_Error( 'bad_upload', __( 'Unable to upload file.', 'groundhogg' ) );
		}

		return $mfile;
	}

	/**
	 * Delete a file
	 *
	 * @param $file_name string
	 */
	public function delete_file( $file_name ) {
		$file_name = basename( $file_name );
		foreach ( $this->get_files() as $file ) {
			if ( $file_name === $file['name'] ) {
				unlink( $file['path'] );
			}
		}
	}

	/**
	 * @var string[]
	 */
	protected $upload_paths;

	/**
	 * get the upload folder for this company
	 */
	public function get_uploads_folder() {
		$paths = [
			'subdir' => sprintf( '/groundhogg/companies/' ),
			'path'   => Plugin::$instance->utils->files->get_uploads_dir( 'companies', $this->get_upload_folder_basename() ),
			'url'    => Plugin::$instance->utils->files->get_uploads_url( 'companies', $this->get_upload_folder_basename() )
		];

		$this->upload_paths = $paths;

		return $paths;
	}


	/**
	 * Get the basename of the path
	 *
	 * @return string
	 */
	public function get_upload_folder_basename() {
		return md5( Plugin::$instance->utils->encrypt_decrypt( $this->get_id() ) );
	}


	/**
	 * @no_access Do not access
	 *
	 * @param $dirs
	 *
	 * @return mixed
	 */
	public function map_upload( $dirs ) {
		$dirs['path']   = $this->upload_paths['path'];
		$dirs['url']    = $this->upload_paths['url'];
		$dirs['subdir'] = $this->upload_paths['subdir'];

		return $dirs;
	}

	/**
	 * Get a list of associated files.
	 */
	public function get_files() {
		$data = [];

		$uploads_dir = $this->get_uploads_folder();

		if ( file_exists( $uploads_dir['path'] ) ) {

			$scanned_directory = array_diff( scandir( $uploads_dir['path'] ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = $uploads_dir['path'] . '/' . $filename;
				$file     = [
					'name'          => $filename,
					'path'          => $filepath,
					'url'           => file_access_url( '/companies/' . $this->get_upload_folder_basename() . '/' . $filename ),
					'date_modified' => date_i18n( get_date_time_format(), convert_to_local_time( filectime( $filepath ) ) ),
				];

				$data[] = $file;

			}
		}

		return $data;
	}

	/**
	 * Get a list of associated files.
	 */
	public function get_picture() {
		$data = [];

		$uploads_dir = $this->get_picture_folder();

		if ( file_exists( $uploads_dir['path'] ) ) {

			$scanned_directory = array_diff( scandir( $uploads_dir['path'] ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = $uploads_dir['path'] . '/' . $filename;
				$file     = [
					'file_name'     => $filename,
					'file_path'     => $filepath,
					'file_url'      => file_access_url( '/companies/picture/' . $this->get_upload_folder_basename() . '/' . $filename ),
					'date_uploaded' => filectime( $filepath ),
				];
				$data[]   = $file;

			}

			if ( ! empty( $data ) ) {
				return $data[0]['file_url'];
			}
		}

		return false;
	}

	/**
	 * Upload a file
	 *
	 * Usage: $contact->upload_file( $_FILES[ 'file_name' ] )
	 *
	 * @deprecated 3.0
	 *
	 * @param $file
	 *
	 * @return array|\WP_Error
	 */
	public function upload_picture( &$file ) {
		$this->delete_pictures();

		$file['name'] = sanitize_file_name( $file['name'] );

		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->get_picture_folder();
		add_filter( 'upload_dir', [ $this, 'map_upload' ] );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', [ $this, 'map_upload' ] );

		if ( isset_not_empty( $mfile, 'error' ) ) {
			return new \WP_Error( 'bad_upload', __( 'Unable to upload file.', 'groundhogg' ) );
		}

		return $mfile;
	}

	/**
	 * get the upload folder for this contact
	 *
	 * @deprecated 3.0
	 */
	public function get_picture_folder() {
		$paths              = [
			'subdir' => sprintf( '/groundhogg/companies/picture/' ),
			'path'   => Plugin::$instance->utils->files->get_uploads_dir( 'companies/picture/', $this->get_upload_folder_basename() ),
			'url'    => Plugin::$instance->utils->files->get_uploads_url( 'companies/picture/', $this->get_upload_folder_basename() )
		];
		$this->upload_paths = $paths;

		return $paths;
	}

	/**
	 * @deprecated 3.0
	 */
	public function delete_pictures() {
		$uploads_dir = $this->get_picture_folder();
		$this->delete_contents( $uploads_dir );
	}

	public function delete_files() {
		$uploads_dir = $this->get_uploads_folder();
		$this->delete_contents( $uploads_dir );
	}

	private function delete_contents( $uploads_dir ) {
		if ( file_exists( $uploads_dir['path'] ) ) {

			$scanned_directory = array_diff( scandir( $uploads_dir['path'] ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				unlink( $filepath = $uploads_dir['path'] . '/' . $filename );
			}

			rmdir( $uploads_dir['path'] );
		}
	}


}
