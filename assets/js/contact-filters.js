( ($) => {

  const {
    registerFilter,
    registerFilterGroup,
    BasicTextFilter,
  } = Groundhogg.filters.functions

  const {
    metaValuePicker,
  } = Groundhogg.pickers

  const {
    bold
  } = Groundhogg.element

  const {
    companies: CompaniesStore,
  } = Groundhogg.stores

  const { __, _n, sprintf, _x } = wp.i18n

  const { ContactFilterRegistry, createFilter } = Groundhogg.filters

  registerFilterGroup('company', _x('Company'))

  ContactFilterRegistry.registerFilter( createFilter( 'company_primary_contacts', 'Primary contact', 'company', {
    edit: () => 'This filter has no settings',
    display: ({totalItems = 0}) => totalItems ? sprintf( 'Is the primary contact for any of %s companies', bold( totalItems ) ) : 'Is the primary contact of any company',
    preload: () => {}
  } ) )

  ContactFilterRegistry.registerFilter( createFilter( 'company_all_contacts', 'Related contact', 'company', {
    edit: () => 'This filter has no settings',
    display: ({totalItems = 0 }) => totalItems ? sprintf( 'Is related to any of %s companies', bold( totalItems ) ) : 'Is related to any company',
    preload: () => {}
  } ) )

  // registerFilter('linked_company', 'company', __('Linked to Company', 'groundhogg'), {
  //   view () {
  //     return __('Linked to any company')
  //   },
  //   edit () {
  //     // language=html
  //     return ''
  //   },
  //   onMount (filter, updateFilter) {
  //   },
  //   defaults: {},
  // })

  registerFilter('company_name', 'company', __('Company Name', 'groundhogg'), {
    ...BasicTextFilter(__('Company Name', 'groundhogg')),

    onMount (filter, updateFilter) {

      metaValuePicker('#filter-value', 'company_name')

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })

  registerFilter('job_title', 'company', __('Job Title', 'groundhogg'), {
    ...BasicTextFilter(__('Job Title', 'groundhogg')),
    onMount (filter, updateFilter) {

      $(`#filter-value`).autocomplete({
        source: Groundhogg.companyPositions,
      })

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })

  registerFilter('company_department', 'company', __('Department', 'groundhogg'), {
    ...BasicTextFilter(__('Department', 'groundhogg')),
    onMount (filter, updateFilter) {

      $(`#filter-value`).autocomplete({
        source: Groundhogg.companyDepartments,
      })

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })

  registerFilter('company_website', 'company', __('Website', 'groundhogg'), {
    ...BasicTextFilter(__('Website', 'groundhogg')),
    onMount (filter, updateFilter) {

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })

  registerFilter('company_address', 'company', __('Address', 'groundhogg'), {
    ...BasicTextFilter(__('Address', 'groundhogg')),
    onMount (filter, updateFilter) {

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })

  registerFilter('company_phone', 'company', __('Phone', 'groundhogg'), {
    ...BasicTextFilter(__('Phone', 'groundhogg')),
    onMount (filter, updateFilter) {

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
  })



} )(jQuery)
