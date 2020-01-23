<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Contact;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use WP_List_Table;
use function Groundhogg\html;
use function GroundhoggCompanies\get_job_title;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Contacts_Table extends WP_List_Table
{
    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct()
    {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'contact',     // Singular name of the listed records.
            'plural' => 'contacts',    // Plural name of the listed records.
            'ajax' => false,       // Does this table support ajax?
        ) );
    }

    /**
     * @return array An associative array containing column information.
     * @see WP_List_Table::::single_row_columns()
     */
    public function get_columns()
    {
        $columns = array(
//            'cb'                => '<input type="checkbox" />', // Render a checkbox instead of text.
            'name' => _x( 'Name', 'Column label', 'groundhogg-companies' ),
            'role' => _x( 'Job Title', 'Column label', 'groundhogg-companies' ),
            'email' => _x( 'Email', 'Column label', 'groundhogg-companies' ),
            'phone' => _x( 'phone', 'Column label', 'groundhogg-companies' ),
            'action' => _x( 'Actions', 'Column label', 'groundhogg-companies' ),
        );
        return apply_filters( 'groundhogg/companies/admin/contacts/table/get_columns', $columns );
    }


    /**
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array( 'last_name', false ),
            'email' => array( 'email', false ),
        );
        return $sortable_columns;
    }


    /**
     * Generates content for a single row of the table
     *
     * @param object $item The current item
     * @since 3.1.0
     *
     */
    public function single_row( $item )
    {
        echo '<tr>';

        $this->single_row_columns( new Contact( absint( $item->ID ) ) );
        echo '</tr>';
    }

    /**
     * @param $contact Contact
     * @return string
     */
    protected function column_name( $contact )
    {
        return html()->e( 'a', [ 'href' => admin_page_url( 'gh_contacts', [
            'action' => 'edit',
            'contact' => $contact->get_id()
        ] ) ], $contact->get_full_name() );
    }


    /**
     * @param $contact Contact
     * @return string
     */
    protected function column_email( $contact )
    {
        return $contact->get_email();
    }


    /**
     * @param $contact Contact
     * @return string
     */
    protected function column_role( $contact )
    {
        return get_job_title( $contact ) ? : '-';
    }


    /**
     * @param $contact Contact
     * @return string
     */
    protected function column_phone( $contact )
    {
        return $contact->get_phone_number();
    }


    /**
     * @param $contact Contact
     * @return string
     */
    protected function column_action( $contact )
    {

        return html()->e( 'span', [ 'class' => 'row-actions' ], [
            html()->e( 'span', [ 'class' => 'delete' ],
                html()->e( 'a', [
                    'class' => 'delete',
                    'href' => wp_nonce_url( admin_page_url( 'gh_companies', [
                        'action' => 'remove_contact',
                        'company' => absint( get_request_var( 'company' ) ), //TODO
                        'contact' => $contact->get_id(),
                    ] ) ) ], __( "Remove", 'groundhogg-companies' ) ) )

        ] );


    }


    /**
     * Get default column value.
     * @param object $company A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $company , $column_name )
    {

        return do_action( "groundhogg/companies/admin/contacts/table/{$column_name}", $company );

    }


//    /**
//     * @param  $contact Contact A singular item (one full row's worth of data).
//     * @return string Text to be placed inside the column <td>.
//     */
//    protected function column_cb( $contact ) {
//        return sprintf(
//            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
//            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
//            $contact->get_id()               // The value of the checkbox should be the record's ID.
//        );
//    }

//    /**
//     * @return array An associative array containing all the bulk steps.
//     */
//    protected function get_bulk_actions() {
//        $actions = array(
//            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg-companies' ),
//        );
//
//        return apply_filters( 'groundhogg/companies/admin/contacts/table/bulk_actions', $actions );
//    }

    function extra_tablenav( $which )
    {
        parent::extra_tablenav( $which ); // TODO: Change the autogenerated stub

        if ( $which !== 'top' ) {
            return;
        }

        ?>

        <div id="add-contact-form">
            <?php

            wp_nonce_field();
            echo html()->input( [
                'type' => 'hidden',
                'name' => 'operation',
                'value' => 'add_contact'
            ] );

            echo html()->dropdown_contacts( [
                'name' => 'contact',
                'multiple' => false
            ] );

            submit_button( 'Add Contact', 'secondary', 'submit', false, [ 'style' => 'margin-left:10px;' ] );

            ?>
        </div>
        <?php
    }

    /**
     * Prepares the list of items for displaying.
     * @global wpdb $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items()
    {

        $columns = $this->get_columns();
        $hidden = array(); // No hidden columns
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page = 20;

        $paged = $this->get_pagenum();
        $offset = $per_page * ( $paged - 1 );
        $order = strtoupper( get_url_var( 'order', 'DESC' ) );
        $orderby = get_url_var( 'orderby', 'ID' );

        $contact_ids = wp_parse_id_list( wp_list_pluck( get_db( 'company_relationships' )->query( [
            'company_id' => absint( get_request_var( 'company' ) )
        ] ), 'contact_id' ) );

        $contacts = [];

        if ( !empty( $contact_ids ) ) {
            $args = array(
                'include' => $contact_ids,
                'limit' => $per_page,
                'offset' => $offset,
                'order' => $order,
                'orderby' => $orderby,
            );

            $contacts = get_db( 'contacts' )->query( $args );
        }

        $total = count( $contacts );

        $this->items = $contacts;

        // Add condition to be sure we don't divide by zero.
        // If $this->per_page is 0, then set total pages to 1.
        $total_pages = $per_page ? ceil( (int) $total / (int) $per_page ) : 1;

        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page' => $per_page,
            'total_pages' => $total_pages,
        ) );
    }
}