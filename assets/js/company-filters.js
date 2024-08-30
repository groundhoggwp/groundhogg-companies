(()=>{

  const {
    contacts: ContactsStore,
  } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const {
    bold,
    orList,
  } = Groundhogg.element

  const { createFilter } = Groundhogg.filters

  const {
    Fragment,
    ItemPicker,
    Autocomplete
  } = MakeEl

  const registerCompanyFilters = ( Registry, group = 'table' ) => {

    Registry.registerFilter(createFilter('contacts', 'Contacts', group, {
      display: ({ contacts = [] }) => {

        if (!contacts.length) {
          return 'Any contact'
        }

        return sprintf('Has %s', orList(contacts.map(id => bold(ContactsStore.get(id).data.full_name.trim() || ContactsStore.get(id).data.email))))
      },
      edit: ({ contacts = [], updateFilter }) => Fragment([
        ItemPicker({
          id: `select-a-contact`,
          noneSelected: __('Select a contact...', 'groundhogg'),
          selected: contacts.map(id => ( { id, text: ContactsStore.get(id).data.email } )),
          multiple: true,
          style: {
            flexGrow: 1,
          },
          fetchOptions: (search) => {
            return ContactsStore.fetchItems({
              search,
            }).then(contacts => contacts.map(({ ID, data: { email } }) => ( { id: ID, text: email } )))
          },
          onChange: items => {
            updateFilter({
              contacts: items.map(({ id }) => id),
            })
          },
        }),
      ]),
      preload: ({ contacts = [] }) => {
        if (contacts.length) {
          return ContactsStore.maybeFetchItems(contacts)
        }
      },
    }))

    Registry.registerFilter(createFilter('industry', 'Industry', group, {
      display: ({ industry = '' }) => {
        return sprintf( __( 'Industry is %s', 'groundhogg' ), bold(industry) )
      },
      edit: ({ industry = '', updateFilter }) => Fragment([
        Autocomplete({
          id: `select-industry`,
          placeholder: __('Select an industry...', 'groundhogg'),
          value: industry,
          style: {
            flexGrow: 1,
          },
          fetchResults: async (search) => {
            return Groundhogg.companyIndustries.filter(string => string.match(new RegExp(search, 'i'))).map(s => ( { id: s, text: s } ))
          },
          onChange: e => {
            updateFilter({
              industry: e.target.value,
            })
          },
        }),
      ]),
    }))

    Registry.registerFromProperties(GroundhoggCompanyProperties)
  }

  Groundhogg.filters.registerCompanyFilters = registerCompanyFilters

  if ( window.GroundhoggTableFilters ){
    registerCompanyFilters( GroundhoggTableFilters.FilterRegistry )
  }

})()
