<?php

namespace GroundhoggCompanies\Admin\Companies;

use Groundhogg\Admin\Admin_Page;
use GroundhoggCompanies\Classes\Company;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\normalize_files;
use function GroundhoggCompanies\get_clean_domain;
use function GroundhoggCompanies\recount_company_contacts_count;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * View Companies
 *
 * @package     Admin
 * @subpackage  Admin/Companies
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
        if ( get_request_var( 'company' ) ) {
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
        return 'edit_companies';
    }

    public function get_item_type()
    {
        return 'company';
    }

    public function get_item_type_plural()
    {
        return 'companies';
    }

    public function get_priority()
    {
        return 6;
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
                return _x( 'Edit Company', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * Add company Process
     *
     * @return \WP_Error|true|false
     */
    public function process_add()
    {

        if ( !current_user_can( 'add_companies' ) ) {
            $this->wp_die_no_access();
        }

        $company_name = sanitize_text_field( get_request_var( 'company_name' ) );
        if ( strlen( $company_name ) > 50 ) {
            return new \WP_Error( 'too_long', __( "Maximum length for company name is 50 characters.", 'groundhogg' ) );
        }

        $company_desc = sanitize_textarea_field( get_request_var( 'company_description' ) );

        if ( get_db('companies')->exists( sanitize_title( $company_name ), 'slug') ) {

            $data  = get_db('companies')->get_by('slug' , sanitize_title( $company_name )) ;
            $company = new Company( $data->ID );

        } else {

            $company = new Company( [
                'name' => $company_name,
                'slug' => sanitize_title($company_name),
                'description' => $company_desc,
            ] );

            if ( !$company->get_id() ) {
                return new \WP_Error( 'unable_to_add_company', "Something went wrong adding the new company." );
            }
        }

        if ( get_request_var( 'address' ) ) {
            $company->update_meta( 'address', sanitize_textarea_field( get_request_var( 'address' ) ) );
        }

        if ( get_request_var( 'contacts' ) ) {
            $company->add_contacts( wp_parse_id_list( get_request_var( 'contacts' ) ) );
            recount_company_contacts_count();
        }

        do_action( 'groundhogg/admin/companies/add', $company->get_id() );

        $this->add_notice( 'new-company', _x( 'Company created!', 'notice', 'groundhogg' ) );

        return false;
    }


    public function process_remove_contact()
    {

        $company = new Company( get_request_var( 'company' ) );

        $company->remove_contacts( get_request_var( 'contact' ) );

        recount_company_contacts_count();

        return admin_page_url( 'gh_companies', [
            'action' => 'edit',
            'company' => $company->get_id()
        ] );
    }

    /**
     * @return bool|\WP_Error
     */
    public function process_edit()
    {

        if ( !current_user_can( 'edit_companies' ) ) {
            $this->wp_die_no_access();
        }

        $company = new Company( absint( get_request_var( 'company' ) ) );

        switch ( get_request_var( 'operation' ) ) {

            default:
            case 'update_company' :
                $company_name = sanitize_text_field( get_post_var( 'company_name' ) );
                $company_desc = sanitize_textarea_field( get_post_var( 'company_description' ) );
                if ( strlen( $company_name ) > 50 ) {
                    return new \WP_Error( 'too_long', __( "Maximum length for company name is 50 characters.", 'groundhogg' ) );
                }

                $company->update( [
                    'name' => $company_name,
                    'description' => $company_desc,
                    'slug' => sanitize_title( $company_name ),
                    'domain' => get_clean_domain( sanitize_text_field( get_request_var( 'company_domain' ) ) )
                ] );

                if ( get_request_var( 'address' ) ) {
                    $company->update_meta( 'address', sanitize_textarea_field( get_request_var( 'address' ) ) );
                }

                if ( get_request_var( 'add_new_note' ) ) {
                    $company->add_note( get_request_var( 'add_note' ) );
                }

                if ( !empty( $_FILES[ 'pictures' ] ) ) {
                    $files = normalize_files( $_FILES[ 'pictures' ] );
                    foreach ( $files as $file_key => $file ) {
                        if ( !get_array_var( $file, 'error' ) ) {
                            $e = $company->upload_picture( $file );
                            if ( is_wp_error( $e ) ) {
                                return $e;
                            }
                        }
                    }
                }

                break;

            case 'add_files' :

                if ( !empty( $_FILES[ 'files' ] ) ) {
                    $files = normalize_files( $_FILES[ 'files' ] );
                    foreach ( $files as $file_key => $file ) {
                        if ( !get_array_var( $file, 'error' ) ) {
                            $e = $company->upload_file( $file );
                            if ( is_wp_error( $e ) ) {
                                return $e;
                            }
                        }
                    }
                }
                break;
            case 'add_contact':


                if ( get_request_var( 'contact' ) ) {
                    $contact_id = absint( get_request_var( 'contact' ) );
                    $company->add_contacts( $contact_id );

                }
                recount_company_contacts_count();
                break;


        }
        do_action( 'groundhogg/companies/admin/company/edit', $company->get_id() );

        $this->add_notice( 'updated', _x( 'Company updated.', 'notice', 'groundhogg' ) );
        // Return Url to return to same page
        return admin_page_url( 'gh_companies', [
            'action' => 'edit',
            'company' => $company->get_id()
        ] );

    }


    public function process_remove_file()
    {
        if ( !current_user_can( 'edit_companies' ) ) {
            $this->wp_die_no_access();
        }

        $file_name = sanitize_text_field( get_url_var( 'file' ) );

        $company = new Company( absint( get_url_var( 'company' ) ) );

        if ( !$company ) {
            return new \WP_Error( 'error', 'The given company does nto exist.' );
        }

        $folders = $company->get_uploads_folder();
        $path = $folders[ 'path' ];

        $file_path = wp_normalize_path( $path . DIRECTORY_SEPARATOR . $file_name );

        if ( !file_exists( $file_path ) ) {
            return new \WP_Error( 'error', 'The requested file does nto exist.' );
        }

        unlink( $file_path );

        $this->add_notice( 'success', __( 'File deleted.', 'groundhogg' ) );

        // Return to contact edit screen.
        return admin_page_url( 'gh_companies', [ 'action' => 'edit', 'company' => $company->get_id() ] );

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
        if ( !current_user_can( 'delete_companies' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            $company = new Company( $id );
            $company->delete_pictures();
            $company->delete_files();
            $company->delete();
        }

        $this->add_notice(
            'deleted',
            sprintf( _nx( '%d company deleted.', '%d companies deleted.', count( $this->get_items() ), 'notice', 'groundhogg-company' ),
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
                                <label for="company-name"><?php _e( 'Company Name', 'groundhogg' ) ?></label>
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
                            <div class="form-field term-address-wrap">
                                <label for="company-description"><?php _e( 'Address', 'groundhogg' ) ?></label>
                                <?php
                                echo html()->textarea( [
                                    'name' => 'address',
                                ] );
                                ?>
                                <p><?php _e( 'Describe location details of a company in detail.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-contacts-wrap">
                                <label for="company-description"><?php _e( 'Contacts', 'groundhogg' ) ?></label>
                                <?php
                                echo html()->dropdown_contacts( [
                                    'name' => 'contacts[]',
                                    'multiple' => true
                                ] );
                                ?>
                                <p><?php _e( 'Add initial contacts to the newly created company.', 'groundhogg' ); ?></p>
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
        if ( !current_user_can( 'edit_companies' ) ) {
            $this->wp_die_no_access();
        }

        include dirname( __FILE__ ) . '/edit.php';
    }
}