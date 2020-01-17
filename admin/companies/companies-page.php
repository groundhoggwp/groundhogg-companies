<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Admin\Admin_Page;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\html;
use function GroundhoggCompanies\recount_company_contacts_count;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * View Tags
 *
 * @package     Admin
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2020, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0
 */
class Companies_Page extends Admin_Page
{
    // UNUSED FUNCTIONS
    protected function add_ajax_actions()
    {
    }

    public function help()
    {
    }

    public function scripts()
    {
        if ( get_request_var( 'companies' ) ){
            wp_enqueue_style( 'groundhogg-companies-admin' );
        }
    }


    protected function add_additional_actions()
    {
        if ( isset( $_GET[ 'recount_contacts' ] ) ) {
            add_action( 'init', array( $this, 'recount' ) );
        }
    }

    public function get_slug()
    {
        return 'gh_companies';
    }

    public function get_name()
    {
        return _x( 'Companies', 'page_title', 'groundhogg-companies' );
    }

    public function get_cap()
    {
        return 'edit_tags';
    }

    public function get_item_type()
    {
        return 'companies';
    }

    public function get_priority()
    {
        return 55;
    }

    public function recount()
    {
        recount_company_contacts_count();
    }

    /**
     * @return string
     */
    protected function get_title()
    {
        switch ( $this->get_current_action() ) {
            default:
            case 'add':
            case 'view':
                return $this->get_name();
                break;
            case 'edit':
                return _x( 'Edit Tag', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * Add Tag Process
     *
     * @return \WP_Error|true|false
     */
    public function process_add()
    {

        if ( !current_user_can( 'add_tags' ) ) {
            $this->wp_die_no_access();
        }

//        if ( isset( $_POST[ 'bulk_add' ] ) ) {
//
//            $tag_names = explode( PHP_EOL, trim( sanitize_textarea_field( get_post_var( 'bulk_tags' ) ) ) );
//
//            $ids = [];
//
//            foreach ( $tag_names as $name ) {
//                if ( strlen( $name ) < 50 ) {
//                    $id = Plugin::$instance->dbs->get_db( 'tags' )->add( [ 'tag_name' => $name ] );
//
//                    if ( $id ) {
//                        $ids[] = $id;
//
//                        do_action( 'groundhogg/admin/tags/add', $id );
//                    }
//                }
//
//            }
//
//            if ( empty( $ids ) ) {
//                return new \WP_Error( 'unable_to_add_tags', "Something went wrong adding the tags." );
//            }
//
//            $this->add_notice( 'new-tags', sprintf( _nx( '%d tag created', '%d tags created', count( $tag_names ), 'notice', 'groundhogg' ), count( $tag_names ) ) );
//
//            return true;
//
//        } else {

            $company_name = sanitize_text_field( get_request_var( 'company_name' ) );
            if ( strlen( $company_name ) > 50 ) {
                return new \WP_Error( 'too_long', __( "Maximum length for tag name is 50 characters.", 'groundhogg' ) );
            }

            $company_desc = sanitize_textarea_field( get_request_var( 'company_description' ) );


            $company  = new Company( [
                'name' => $company_name,
                'description' => $company_desc,
            ] );


            if ( $company ) {
                return new \WP_Error( 'unable_to_add_company', "Something went wrong adding the new company." );
            }

            do_action( 'groundhogg/admin/companies/add', $company->get_id() );

            $this->add_notice( 'new-company', _x( 'Company created!', 'notice', 'groundhogg' ) );

//        }

        return false;
    }

    /**
     * @return bool|\WP_Error
     */
    public function process_edit()
    {

        if ( !current_user_can( 'edit_tags' ) ) {
            $this->wp_die_no_access();
        }

        $company = new Company(absint( get_request_var( 'companies' ) ) );

        $company_name = sanitize_text_field( get_post_var( 'company_name' ) );
        $company_desc = sanitize_textarea_field( get_post_var( 'company_description' ) );
        if ( strlen( $company_name ) > 50 ) {
            return new \WP_Error( 'too_long', __( "Maximum length for tag name is 50 characters.", 'groundhogg' ) );
        }

        $company->update([
            'name'        => $company_name,
            'description' => $company_desc,
            'domain' => sanitize_text_field(get_request_var('company_domain') )
        ]);


        $this->add_notice( 'updated', _x( 'Company updated.', 'notice', 'groundhogg' ) );

        if (get_request_var( 'contact_id' )) {
            $contact_id = absint(get_request_var('contact_id'));

            $get_id  = get_db( 'company_relationships' )->query( ['company_id' => $company->get_id() , 'contact_id' => $contact_id  ] );
            if(empty($get_id)) {

                $id = get_db('company_relationships')->add( $company->get_id(), $contact_id );

            }

        }

        do_action( 'groundhogg/admin/tags/edit', $company->get_id() );

        // Return false to return to main page.
        return admin_page_url( 'gh_companies', [
            'action' => 'edit',
            'companies' => $company->get_id()
        ] );

    }

    /**
     * @return bool
     */
    public function process_recount()
    {
        recount_company_contacts_count();

        $this->add_notice( 'recount', __( 'Company associations reset.', 'groundhogg' ) );

        return false;
    }

    /**
     * Delete company from the admin
     *
     * @return bool|\WP_Error
     */
    public function process_delete()
    {

        if ( !current_user_can( 'delete_tags' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            $company = new Company($id);
            $company->delete();
        }

        $this->add_notice(
            'deleted',
            sprintf( _nx( '%d company deleted', '%d company deleted', count( $this->get_items() ), 'notice', 'groundhogg-company' ),
                count( $this->get_items() )
            )
        );

        return false;
    }

    public function view()
    {
        if ( !class_exists( 'Companies_Table' ) ) {
            include dirname( __FILE__ ) . '/companies-table.php';
        }

        $companies_table = new Companies_Table();

        $this->search_form( __( 'Search Companies', 'groundhogg' ) );

        ?>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add new Company', 'groundhogg' ) ?></h2>
                        <form id="addcompany" method="post" action="">
                            <input type="hidden" name="action" value="add">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="company-name"><?php _e( 'company Name', 'groundhogg' ) ?></label>
                                <?php
                                echo html()->input( [
                                    'name' => 'company_name',
                                    'type' => 'text'
                                ] );
                                ?>

                                <p><?php _e( 'Enter a Name of company.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-description-wrap">
                                <label for="company-description"><?php _e( 'Description', 'groundhogg' ) ?></label>
                                <?php
                                echo html()->textarea( [
                                    'name' => 'company_description',
                                ] );
                                ?>
                                <p><?php _e( 'Describe something about a company in details which makes it unique.', 'groundhogg' ); ?></p>
                            </div>
                            <?php do_action( 'groundhogg/admin/companies/add/form' ); ?>

                            <?php submit_button( _x( 'Add New Company', 'action', 'groundhogg' ), 'primary', 'add_company' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php $companies_table->prepare_items(); ?>
                        <?php $companies_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function edit()
    {
        if ( !current_user_can( 'edit_tags' ) ) {
            $this->wp_die_no_access();
        }

        include dirname( __FILE__ ) . '/edit.php';
    }
}