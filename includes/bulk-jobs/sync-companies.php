<?php
namespace GroundhoggCompanies\Bulk_Jobs;

use Groundhogg\Bulk_Jobs\Bulk_Job;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function GroundhoggCompanies\add_companies_based_on_contact;

if ( ! defined( 'ABSPATH' ) ) exit;

class Sync_Companies extends Bulk_Job
{


    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'sync_companies';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query( $items )
    {
        if ( ! current_user_can( 'edit_companies' ) ){
            return $items;
        }

        $query = new Contact_Query();
        $args = [];

        $contacts = $query->query( $args );

        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
    }

    /**
     * Get the maximum number of items which can be processed at a time.
     *
     * @param $max int
     * @param $items array
     * @return int
     */
    public function max_items($max, $items)
    {
        if ( ! current_user_can( 'edit_companies' ) ){
            return $max;
        }

        return min( 50, intval( ini_get( 'max_input_vars' ) ) ) ;
    }

    /**
     * Process an item
     *
     * @param $item mixed
     * @return void
     */
    protected function process_item( $item )
    {
        if ( ! current_user_can( 'edit_contacts' ) ){
            return;
        }

        $contact = new Contact($item);

        if(  $company_name = $contact->get_meta( 'company_name' ) ) {

            add_companies_based_on_contact( $contact->get_id() , $company_name );

        }
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop(){
//        $config = get_transient( 'gh_create_user_job_config' );
    }

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop(){}

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up(){
//        delete_transient( 'gh_send_account_email' );
    }


    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        $url = admin_url( 'admin.php?page=gh_tools&tab=companies' );
        return $url;
    }
}