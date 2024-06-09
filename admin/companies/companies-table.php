<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Admin\Table;
use GroundhoggCompanies\Classes\Company;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\html;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Companies_Table extends Table {
	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'company',     // Singular name of the listed records.
			'plural'   => 'companies',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
			'name'     => _x( 'Company', 'Column label', 'groundhogg' ),
			'logo'     => _x( 'Logo', 'Column label', 'groundhogg' ),
			'industry' => _x( 'Industry', 'Column label', 'groundhogg' ),
			'website'  => _x( 'Website', 'Column label', 'groundhogg' ),
			'address'  => _x( 'Address', 'Column label', 'groundhogg' ),
			'phone'  => _x( 'Phone', 'Column label', 'groundhogg' ),
			'owner_id' => _x( 'Owner', 'Column label', 'groundhogg' ),
			'contacts' => _x( 'Contacts', 'Column label', 'groundhogg' ),
		);

		return apply_filters( 'groundhogg/admin/companies/table/get_columns', $columns );
	}

	/**
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {

		$sortable_columns = array(
			'name'     => array( 'name', false ),
			'contacts' => array( 'contact_count', false ),
			'industry' => array( 'industry', false ),
			'owner_id' => array( 'owner_id', false ),
		);

		return $sortable_columns;
	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_name( $company ) {

		?>
        <strong>
            <a class="row-title" href="<?php echo admin_page_url( 'gh_companies', [
				'action'  => 'edit',
				'company' => $company->get_id()
			] ) ?>"><?php echo $company->get_name(); ?></a>
        </strong>
		<?php
	}

	/**
	 * @param $company Company
	 *
	 * @return void
	 */
	protected static function column_owner_id( $company ) {
		echo $company->owner_id ? '<a href="' . admin_page_url( 'gh_companies', [ 'owner_id' => $company->owner_id ] ) . '">' . get_userdata( $company->owner_id )->display_name . '</a>' : '&#x2014;';
	}

	/**
	 * @param $company Company
	 */
	public function column_address( $company ) {

		$address = $company->get_address();

		if ( $address ) {
			echo html()->e( 'a', [
				'href'   => 'http://maps.google.com/?q=' . $company->get_searchable_address(),
				'target' => '_blank'
			], $address );
		}

	}

	/**
	 * @param $company Company
	 */
	public function column_contacts( $company ) {

		$contacts = $company->get_contacts();

		?>
        <div class="avatars">
			<?php foreach ( $contacts as $contact ): ?>
                <img class="avatar" alt="<?php esc_attr_e( 'avatar' ); ?>"
                     title="<?php esc_attr_e( $contact->get_full_name() ); ?>"
                     src="<?php echo esc_url( $contact->get_profile_picture() ); ?>"/>
			<?php endforeach; ?>
        </div>
		<?php

	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_logo( $company ) {

		$logo = $company->get_meta( 'logo' );

		if ( empty( $logo ) ) {
			return;
		}

		echo html()->e( 'img', [
			'src'   => $logo,
			'alt'   => __( 'Company-logo' ),
			'title' => $company->get_name(),
			'style' => [
				'float'        => 'left',
				'margin-right' => '10px'
			],
			'width' => 100
		], '', true );
	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_contact_count( $company ) {
		return $company->get_contact_count();
	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_website( $company ) {
		return ! empty( $company->get_domain() ) ? html()->e( 'a', [
			'target' => '_blank',
			'href'   => $company->get_domain()
		], parse_url( $company->get_domain(), PHP_URL_HOST ) ) : '&#x2014;';
	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_industry( $company ) {
		return esc_html( $company->get_meta( 'industry' ) ) ?: '-';
	}

	/**
	 * @param $company Company
	 *
	 * @return string
	 */
	protected function column_phone( $company ) {

		$phone = $company->get_meta( 'phone' );

		return $phone ? html()->e( 'a', [
			'href' => 'tel:' . $phone
		], $phone ) : '-';
	}

	/**
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = array(
			'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg-companies' ),
		);

		return apply_filters( 'groundhogg/admin/companies/table/bulk_actions', $actions );
	}


	function get_table_id() {
		return 'companies';
	}

	function get_db() {
		return get_db( 'companies' );
	}

    protected function parse_item( $item ) {
	    return new Company( $item );
    }

	protected function get_row_actions( $item, $column_name, $primary ) {
		$actions = [];

		switch ( $this->get_view() ) {
			default:
				$actions[] = [ 'class' => 'edit', 'display' => __( 'Edit' ), 'url' => $item->admin_link() ];

				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Trash' ),
					'url'     => action_url( 'trash', [ 'email' => $item->get_id() ] )
				];
				break;
		}

		return $actions;
	}

	protected function get_views_setup() {
		return [
			[
				'view'    => '',
				'display' => __( 'All' ),
				'query'   => [],
			],
		];
	}

	function get_default_query() {
		return [];
	}
}
