<?php

namespace GroundhoggCompanies\Classes;

use Groundhogg\Base_Object_With_Meta;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use function Groundhogg\get_db;

class Company extends Base_Object_With_Meta
{
    protected function get_meta_db()
    {
        return get_db('company_meta');
    }

    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    protected function get_db()
    {

        return get_db('companies');
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( $this->ID );
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function get_contact_count()
    {
        return absint( $this->contact_count );
    }

    public function get_domain()
    {
        return $this->domain;
    }

    public function  get_profile_picture()
    {
        return 'THis is picture' ; //todo
    }


    public function  get_address()
    {
        return 'THis is Address' ;
    }


    public function  get_notes()
    {
        return 'THis is Notes' ;
    }

}
