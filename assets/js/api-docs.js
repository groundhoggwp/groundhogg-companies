( () => {

  const {
    ApiRegistry,
    CommonParams,
    setInRequest,
    getFromRequest,
    addBaseObjectCRUDEndpoints,
    currEndpoint,
    currRoute,
  } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
    Input,
    Textarea,
    InputRepeater,
  } = MakeEl

  const FiltersConfig = {
    'group': 'company',
    'name': 'Company',
    'stringColumns': { 'name': 'Name', 'address': 'Address', 'industry': 'Industry', 'phone': 'Phone', 'domain': 'Website' },
    'dateColumns': { 'date_created': 'Date created' },
  }

  ApiRegistry.add('companies', {
    name: __('Companies'),
    description: '',
    endpoints: Groundhogg.createRegistry(),
  })

  addBaseObjectCRUDEndpoints(ApiRegistry.companies.endpoints, {
    plural: __('companies'),
    singular: __('company'),
    route: `${ apiRoot }/companies`,
    searchableColumns: [
      'name',
      'slug',
      'description',
    ],
    orderByColumns: [
      'ID',
      'name',
      'slug',
      'domain',
      'owner_id',
      'date_created',
    ],
    readParams: [
      {
        ...CommonParams.filters('companies'),
        control: ({ param, id }) => {

          let filters = getFromRequest(param)

          if (!Array.isArray(filters)) {
            filters = []
          }

          const filterRegistry = Groundhogg.filters.FilterRegistry({})
          filterRegistry.registerFromConfig(FiltersConfig)
          filterRegistry.registerFromProperties(GroundhoggCompanyProperties)
          Groundhogg.filters.registerCompanyFilters(filterRegistry, 'company')

          return Groundhogg.filters.Filters({
            id,
            filters,
            filterRegistry,
            onChange: filters => {

              filters = filters.map(group => group.map(({ id, ...filter }) => filter))

              if (currEndpoint().method === 'GET') {
                filters = Groundhogg.functions.base64_json_encode(filters)
              }

              setInRequest(param, filters)
            },
          })
        },
      },
    ],
    dataParams: [
      {
        param: 'name',
        description: __('The name of the company.', 'groundhogg'),
        type: 'string',
        required: true,
      },
      {
        param: 'slug',
        description: __('A unique slug to identify the company. If one is not provided it will be generated from the name.', 'groundhogg'),
        type: 'string',
      },
      {
        param: 'description',
        description: __('What the company "does" or anything else relevant.', 'groundhogg'),
        type: 'string',
      },
      {
        param: 'domain',
        description: __('The URL of the company. Must start with <code>https://</code>.', 'groundhogg'),
        type: 'string',
      },
      {
        param: 'owner_id',
        description: __('The ID of the user to assign to the company.', 'groundhogg'),
        type: 'int',
      },
      {
        param: 'primary_contact_id',
        description: __('The ID of the primary contact for the company.', 'groundhogg'),
        type: 'int',
      },
    ],
    meta: true,
    metaParams: [
      {
        param: 'industry',
        type: 'string',
        description: __( "The company's industry.", 'groundhogg-companies' )
      },
      {
        param: 'address',
        type: 'string',
        description: __( "The company's full address.", 'groundhogg-companies' )
      },
      {
        param: 'phone',
        type: 'string',
        description: __( "The company's phone number.", 'groundhogg-companies' )
      }
    ]
  })

} )()
