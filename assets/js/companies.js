( ($) => {

  const {
    company,
    gh_company_custom_properties,
  } = GroundhoggCompany
  const {
    debounce,
  } = Groundhogg.functions
  const {
    icons,
    regexp,
    input,
    uuid,
    dialog,
    select,
    modal,
    moreMenu,
    dangerConfirmationModal,
    dangerDeleteModal,
    loadingDots,
    spinner,
    tooltip,
    textarea,
    bold,
    adminPageURL,
    confirmationModal,
    loadingModal,
  } = Groundhogg.element
  const {
    companies: CompaniesStore,
    contacts : ContactsStore,
  } = Groundhogg.stores
  const {
    __,
    _x,
    _n,
    _nx,
    sprintf,
  } = wp.i18n
  const {
    currentUser,
    propertiesEditor,
    noteEditor,
  } = Groundhogg
  const {
    userHasCap,
    getOwner,
  } = Groundhogg.user
  const {
    patch,
    routes,
    delete: _delete,
    get,
  } = Groundhogg.api
  const {
    quickEditContactModal,
    selectContactModal,
    addContactModal,
  } = Groundhogg.components
  const { formatNumber } = Groundhogg.formatting

  const {
    ImageInput,
    ImagePicker,
  } = Groundhogg.components

  const State = Groundhogg.createState({
    primaryTab  : 'general',
    secondaryTab: 'directory',
  })

  const sendEmail = (contact) => {

    let email = {
      to        : [contact.data.email],
      from_email: currentUser.data.user_email,
      from_name : currentUser.data.display_name,
    }

    if (contact.data.owner_id && currentUser.ID != contact.data.owner_id) {
      email.cc = [Groundhogg.filters.owners.find(u => u.ID == contact.data.owner_id).data.user_email]
    }

    Groundhogg.components.emailModal(email)
  }

  const emailAll = async () => {

    let contacts = await fetchContacts({
      limit : 999,
      offset: 0,
      search: '',
    })

    if (!contacts.length) {
      dialog({
        message: 'No associated contacts',
        type   : 'error',
      })
      return
    }

    let email = {
      to        : contacts.map(c => c.data.email),
      from_email: currentUser.data.user_email,
      from_name : currentUser.data.display_name,
      cc        : contacts.filter(c => c.data.owner_id && c.data.owner_id != currentUser.ID).
        map(c => Groundhogg.filters.owners.find(u => u.ID == c.data.owner_id).data.user_email).
        filter((v, i, a) => a.indexOf(v) === i),
    }

    Groundhogg.components.emailModal(email)
  }

  CompaniesStore.itemsFetched([company])

  const {
    Fragment,
    Autocomplete,
    Div,
    Input,
    Select,
    InputGroup,
    Textarea,
    ItemPicker,
    Label,
    Button,
    Modal,
    Dashicon,
    makeEl,
    Pg,
    Table,
    TBody,
    THead,
    Th,
    Td,
    Tr,
    Span,
    ToolTip,
  } = MakeEl

  // language=HTML
  icons.company = `
      <svg viewBox="0 0 468 468" xmlns="http://www.w3.org/2000/svg">
          <path
                  d="M104.884 131.227h71.492v30.017h-71.492zM104.884 199.484h71.492v30.017h-71.492zM104.884 267.74h71.492v30.017h-71.492zM104.884 335.997h71.492v30.017h-71.492z"/>
          <g>
              <path
                      d="M249.46 405.983V160.716l156.74-.183v165.459h30V145.507c0-8.188-6.841-15.009-15.018-15.009l-186.74.218c-8.277.01-14.982 6.726-14.982 15.009v260.258H61.8V108.987l157.66-42.413v34.075h30V46.997c0-9.71-9.511-17.018-18.895-14.494L42.905 82.986A15.008 15.008 0 0 0 31.8 97.48v308.503H0V436h468v-30.017z"/>
              <path
                      d="M406.2 353.008h30v27.961h-30zM291.624 199.484h71.492v30.017h-71.492zM291.624 267.74h71.492v30.017h-71.492zM291.624 335.997h71.492v30.017h-71.492z"/>
          </g>
      </svg>`

  const getCompany = () => {
    return CompaniesStore.get(company.ID)
  }

  let files = []

  const companyTabs = () => {

    let activeTab = 'general'

    let tabs = [
      {
        id     : 'general',
        name   : __('General'),
        render : () => `<div class="gh-panel top-left-square"><div id="general-info" class="inside"></div></div>`,
        onMount: () => {

          const MetaChanges = Groundhogg.createState({})
          const DataChanges = Groundhogg.createState({})

          morphdom(document.getElementById('general-info'), Div({}, [

            Div({
              id       : 'company-info-wrapper',
              className: 'display-grid gap-10',
            }, [

              // Name
              Div({ className: 'half display-flex column gap-5' }, [
                Label({
                  for: 'company-name',
                }, __('Name', 'groundhogg-companies')),
                Input({
                  name   : 'company_name',
                  id     : 'company-name',
                  value  : getCompany().data.name,
                  onInput: e => {
                    DataChanges.set({
                      name: e.target.value,
                    })
                  },
                }),
              ]),

              // Industry
              Div({ className: 'half display-flex column gap-5' }, [
                Label({
                  for: 'company-industry',
                }, __('Industry', 'groundhogg-companies')),
                Autocomplete({
                  name        : 'company_industry',
                  id          : 'company-industry',
                  value       : getCompany().meta.industry ?? '',
                  fetchResults: async (search) => {
                    return Groundhogg.companyIndustries.filter(string => string.match(new RegExp(search, 'i'))).
                      map(s => ( {
                        id  : s,
                        text: s,
                      } ))
                  },
                  onChange    : e => {
                    MetaChanges.set({
                      industry: e.target.value,
                    })
                  },
                }),
              ]),

              // Website
              Div({ className: 'full display-flex column gap-5' }, [
                Label({
                  for: 'company-url',
                }, __('Website', 'groundhogg-companies')),
                InputGroup([
                  Input({
                    name     : 'company_url',
                    id       : 'company-url',
                    className: 'full-width',
                    value    : getCompany().data.domain,
                    type     : 'url',
                    onInput  : e => {
                      DataChanges.set({
                        domain: e.target.value,
                      })
                    },
                  }),
                  Button({
                    className: 'gh-button secondary small',
                    id       : 'url-visit',
                    onClick  : e => {
                      window.open(document.getElementById('company-url').value, '_blank')
                    },
                  }, Dashicon('external')),
                ]),

              ]),

              // Phone
              Div({ className: 'half display-flex column gap-5' }, [
                Label({
                  for: 'company-phone',
                }, __('Phone', 'groundhogg-companies')),
                Input({
                  name   : 'company_phone',
                  id     : 'company-phone',
                  type   : 'tel',
                  value  : getCompany().meta.phone ?? '',
                  onInput: e => {
                    MetaChanges.set({
                      phone: e.target.value,
                    })
                  },
                }),
              ]),

              // Owner
              Div({ className: 'half display-flex column gap-5' }, [
                Label({
                  for: 'company-owner',
                }, __('Owner', 'groundhogg-companies')),

                ItemPicker({
                  id          : `select-owner`,
                  noneSelected: __('Select an owner...', 'groundhogg'),
                  selected    : {
                    id  : getCompany().data.owner_id,
                    text: getOwner(getCompany().data.owner_id).data.display_name,
                  },
                  multiple    : false,
                  style       : {
                    flexGrow: 1,
                  },
                  fetchOptions: async (search) => {
                    search = new RegExp(search, 'i')

                    return Groundhogg.filters.owners.map(u => ( {
                      id  : u.ID,
                      text: u.data.display_name,
                    } )).filter(({ text }) => text.match(search))
                  },
                  onChange    : item => {
                    if (item) {
                      DataChanges.set({
                        owner_id: item.id,
                      })
                    }
                  },
                }),
              ]),

              // Address
              Div({ className: 'full display-flex column gap-5' }, [
                Label({
                  for: 'company-address',
                }, __('Address', 'groundhogg-companies')),
                Textarea({
                  name   : 'company_address',
                  id     : 'company-address',
                  value  : getCompany().meta.address ?? '',
                  onInput: e => {
                    MetaChanges.set({
                      address: e.target.value,
                    })
                  },
                }),
              ]),

              Div({ className: 'full display-flex column gap-5' }, [
                Label({
                  for: 'company-logo-src',
                }, __('Logo', 'groundhogg-companies')),
                ImageInput({
                  value   : getCompany().meta.logo ?? '',
                  id      : 'company-logo',
                  onChange: logo => {
                    MetaChanges.set({
                      logo,
                    })
                  },
                }),
                makeEl('i',{}, __('For best results use a square image.' ) )
              ]),
            ]),
            Div({
              className: 'sticky-submit has-box-shadow',
            }, [
              Button({
                className: 'gh-button primary',
                id       : 'save-general-changes',
                onClick  : async e => {

                  await CompaniesStore.patch(getCompany().ID, {
                    data: DataChanges.get(),
                    meta: MetaChanges.get(),
                  })

                  if (DataChanges.domain) {
                    checkForPotentialContactMatches()
                  }

                  dialog({
                    message: __('Changes saved!', 'groundhogg-companies'),
                  })

                  DataChanges.clear()
                  MetaChanges.clear()

                  morphInfoCard()

                },
              }, __('Save Changes', 'groundhogg')),
            ]),
          ]), {
            childrenOnly: true,
          })
        },
      },
    ]

    let customTabState = gh_company_custom_properties || {
      tabs  : [],
      fields: [],
      groups: [],
    }

    const __groups = () =>
      customTabState.groups.filter(g => g.tab === activeTab)

    const __fields = () => customTabState.fields.filter(
      f => __groups().find(g => g.id === f.group))

    let timeout
    let metaChanges = {}
    let deleteKeys = []

    const commitMetaChanges = () => {

      let { stop } = loadingDots('#save-meta')
      $('#save-meta').prop('disabled', true)

      Promise.all([
        CompaniesStore.patchMeta(getCompany().ID, metaChanges),
        CompaniesStore.deleteMeta(getCompany().ID, deleteKeys),
      ]).then(() => {

        metaChanges = {}
        deleteKeys = []

        stop()
        mount()
        dialog({
          message: __('Changes saved!'),
        })
      })
    }

    const cancelMetaChanges = () => {
      metaChanges = {}
      deleteKeys = []

      mount()
    }

    const updateTabState = () => {

      if (timeout) {
        clearTimeout(timeout)
      }

      timeout = setTimeout(() => {
        patch(routes.v4.options, {
          gh_company_custom_properties: customTabState,
        }).then(() => {
          dialog({
            message: __('Changes saved!', 'groundhogg'),
          })
        })
      }, 1500)

    }

    const allTabs = () => [
      ...tabs,
      ...customTabState.tabs.map(t => ( {
        id      : t.id,
        isCustom: true,
        name    : t.name,
        render  : () => `<div class="tab-content-wrapper custom-tab gh-panel top-left-square active" data-tab-content="${ activeTab }">
				<div class="inside">
					<div id="custom-fields-here">
					</div>
					<div class="sticky-submit has-box-shadow">
						<button id="cancel-meta-changes" class="gh-button danger text">${ __('Cancel') }</button>
						<button id="save-meta" class="gh-button primary">${ __('Save Changes') }</button>
					</div>
				</div>
			</div>`,
        onMount : () => {
          // Groups belonging to this tab
          let groups = customTabState.groups.filter(g => g.tab === activeTab)
          // Fields belonging to the groups of this tab
          let fields = customTabState.fields.filter(f => groups.find(g => g.id === f.group))

          $('#save-meta').on('click', commitMetaChanges)
          $('#cancel-meta-changes').on('click', cancelMetaChanges)

          $('#tab-more').on('click', e => {
            e.preventDefault()

            moreMenu(e.currentTarget, {
              items   : [
                {
                  key : 'rename',
                  cap : 'manage_options',
                  text: __('Rename'),
                },
                {
                  key : 'delete',
                  cap : 'manage_options',
                  text: `<span class="gh-text danger">${ __('Delete') }</span>`,
                },
              ],
              onSelect: k => {

                switch (k) {

                  case 'delete':

                    dangerConfirmationModal({
                      confirmText: __('Delete'),
                      alert      : `<p>${ sprintf(__('Are you sure you want to delete %s?', 'groundhogg'),
                        bold(customTabState.tabs.find(t => t.id === activeTab).name)) }</p>`,
                      onConfirm  : () => {
                        // Groups belonging to this tab
                        let groups = customTabState.groups.filter(g => g.tab === activeTab)
                        // Fields belonging to the groups of this tab
                        let fields = customTabState.fields.filter(f => groups.find(g => g.id === f.group)).map(f => f.id)

                        customTabState.tabs = customTabState.tabs.filter(t => t.id !== activeTab)
                        customTabState.groups = customTabState.groups.filter(g => g.tab !== activeTab)
                        customTabState.fields = customTabState.fields.filter(f => !fields.includes(f.id))

                        updateTabState()
                        activeTab = 'general'
                        mount()
                      },
                    })

                    break

                  case 'rename':
                    modal({
                      // language=HTML
                      content: `
                          <div>
                              <h2>${ __('Rename tab', 'groundhogg') }</h2>
                              <div class="align-left-space-between">
                                  ${ input({
                                      id         : 'tab-name',
                                      value      : customTabState.tabs.find(t => t.id === activeTab).name,
                                      placeholder: __('Tab name', 'groundhogg'),
                                  }) }
                                  <button id="update-tab" class="gh-button primary">
                                      ${ __('Save') }
                                  </button>
                              </div>
                          </div>`,
                      onOpen : ({ close }) => {

                        let tabName

                        $('#tab-name').on('change input', (e) => {
                          tabName = e.target.value
                        }).focus()

                        $('#update-tab').on('click', () => {

                          customTabState.tabs.find(t => t.id === activeTab).name = tabName

                          updateTabState()

                          mount()
                          close()

                        })

                      },
                    })
                    break

                }

              },
            })
          })

          propertiesEditor('#custom-fields-here', {
            values             : {
              ...getCompany().meta,
              ...metaChanges,
            },
            properties         : {
              groups,
              fields,
            },
            onPropertiesUpdated: ({
              groups = [],
              fields = [],
            }) => {

              customTabState.fields = [
                // Filter out any fields that are part of any group belonging to
                // the current tab
                ...customTabState.fields.filter(
                  field => !__fields().find(f => f.id === field.id)),
                // Any new fields
                ...fields,
              ]

              customTabState.groups = [
                // Filter out groups that are part of the current tab
                ...customTabState.groups.filter(
                  group => !__groups().find(g => g.id === group.id)),
                // The groups that were edited and any new groups
                ...groups.map(g => ( {
                  ...g,
                  tab: activeTab,
                } )),
              ]

              updateTabState()

            },
            onChange           : (meta) => {
              metaChanges = {
                ...metaChanges,
                ...meta,
              }
            },
            canEdit            : () => {
              return userHasCap('manage_options')
            },

          })
        },
      } )),
    ]

    const template = () => {

      let _tabs = allTabs()

      // language=HTML
      return `
          <div id="primary-tabs">
              <h2 class="nav-tab-wrapper primary gh">
                  ${ _tabs.map(({
                      id,
                      name,
                  }) => `<a href="#" data-tab="${ id }" class="nav-tab ${ activeTab === id ? 'nav-tab-active' : '' }">${ name }</a>`).join('') }
                  <div id="tab-actions" class="space-between">
                      ${ userHasCap('manage_options') ? `<button type="button" id="add-tab"><span
                              class="dashicons dashicons-plus-alt2"></span>
                      </button>` : '' }
                      ${ _tabs.find(t => t.id === activeTab)?.isCustom && userHasCap('manage_options')
                         ? `<button class="gh-button tab-more secondary text icon" type="button" id="tab-more">${ icons.verticalDots }</button>`
                         : '' }
                  </div>
              </h2>
              ${ _tabs.find(t => t.id === activeTab).render() }
          </div>`
    }

    const mount = () => {
      $('.company-more').html(template())
      onMount()
    }

    const onMount = () => {
      allTabs().find(t => t.id === activeTab).onMount()

      $('.nav-tab-wrapper.primary .nav-tab').on('click', (e) => {
        e.preventDefault()
        activeTab = e.target.dataset.tab
        mount()
      })

      if (userHasCap('manage_options')) {

        tooltip('#add-tab', {
          content : __('Add Tab', 'groundhogg'),
          position: 'right',
        })

        $('#add-tab').on('click', (e) => {
          e.preventDefault()

          modal({
            // language=HTML
            content: `
                <div>
                    <h2>${ __('Add a new tab', 'groundhogg') }</h2>
                    <div class="align-left-space-between">
                        ${ input({
                            id         : 'tab-name',
                            placeholder: __('Tab name', 'groundhogg'),
                        }) }
                        <button id="create-tab" class="gh-button primary">
                            ${ __('Create') }
                        </button>
                    </div>
                </div>`,
            onOpen : ({ close }) => {

              let tabName

              $('#tab-name').on('change input', (e) => {
                tabName = e.target.value
              }).focus()

              $('#create-tab').on('click', () => {

                let id = uuid()

                customTabState.tabs.push({
                  id,
                  name: tabName,
                })

                activeTab = id
                updateTabState()

                mount()
                close()

              })

            },
          })
        })

      }
    }

    mount()

  }

  const QuickAddEditParts = {
    additionalFields       : ({
      prefix,
      contact = {},
    }) => {

      let {
        job_title = '',
        company_department = '',
        company_phone = '',
        company_phone_extension = '',
      } = contact?.meta ?? {}

      // language=HTML
      return `
          <div class="gh-row">
              <div class="gh-col">
                  <label>${ __('Position', 'groundhogg-companies') }</label>
                  ${ input({
                      id   : `${ prefix }-job-title`,
                      value: job_title ?? '',
                  }) }
              </div>
              <div class="gh-col">
                  <label>${ __('Department', 'groundhogg-companies') }</label>
                  ${ input({
                      id   : `${ prefix }-company-department`,
                      name : 'company_department',
                      value: company_department ?? '',
                  }) }
              </div>
          </div>
          <div class="gh-row">
              <div class="gh-col">
                  <div class="gh-row">
                      <div class="gh-col">
                          <label>${ __('Work Phone', 'groundhogg-companies') }</label>
                          ${ input({
                              id   : `${ prefix }-work-phone`,
                              type : 'tel',
                              name : 'company_phone',
                              value: company_phone ?? '',
                          }) }
                      </div>
                      <div class="gh-col">
                          <label>${ __('Ext.', 'groundhogg-companies') }</label>
                          ${ input({
                              id   : `${ prefix }-work-phone-ext`,
                              name : 'company_phone_extension',
                              value: company_phone_extension ?? '',
                          }) }
                      </div>
                  </div>
              </div>
              <div class="gh-col"></div>
          </div>`

    },
    additionalFieldsOnMount: ({
      prefix,
      setPayload,
      getPayload,
    }) => {
      $(`#${ prefix }-company-department`).autocomplete({
        source: Groundhogg.companyDepartments,
      })
      $(`#${ prefix }-company-department, #${ prefix }-work-phone, #${ prefix }-work-phone-ext`).on('change input', (e) => {
        setPayload({
          meta: {
            ...getPayload().meta,
            [e.target.name]: e.target.value,
          },
        })
      })
      $(`#${ prefix }-job-title`).autocomplete({
        source: Groundhogg.companyPositions,
      }).on('change', (e) => {

        setPayload({
          meta: {
            ...getPayload().meta,
            job_title: e.target.value,
          },
        })

      })
    },
  }

  const companyQuickEdit = (contact, onEdit) => quickEditContactModal({
    contact,
    ...QuickAddEditParts,
    onEdit: (c) => {
      dialog({
        message: __('Contact updated!', 'groundhogg'),
      })
      onEdit(c)
    },
  })

  const DirectoryState = Groundhogg.createState({
    page    : 0,
    per_page: 10,
    contacts: [],
    total   : getCompany().totalContacts,
    selected: [],
    query   : {},
    orderby : 'first_name',
    order   : 'DESC',
  })

  const TotalItems = () => Span({ className: 'total-items displaying-num' },
    sprintf(_n('%s contact', '%s contacts', DirectoryState.total), formatNumber(DirectoryState.total)))

  const Pagination = () => {

    if (DirectoryState.total <= DirectoryState.per_page) {
      return TotalItems()
    }

    const totalPages = Math.ceil(DirectoryState.total / DirectoryState.per_page)

    return Div({
      className: 'tablenav-pages',
    }, [
      TotalItems(),
      Span({
        className: 'pagination-links',
      }, [
        makeEl('a', {
          className: 'prev-page button',
          id       : 'prev-page',
          disabled : DirectoryState.page === 0,
          onClick  : e => {

            DirectoryState.set({
              page: DirectoryState.page - 1,
            })

            fetchContacts().then(() => morphDirectory())
          },
        }, '&lsaquo;'),
        '&nbsp;',
        Span({
          className: 'paging-input',
        }, [
          Input({
            type     : 'number',
            value    : DirectoryState.page + 1,
            min      : 0,
            max      : totalPages,
            onChange : e => {
              DirectoryState.set({
                page: e.target.value - 1,
              })
              fetchContacts().then(() => morphDirectory())
            },
            className: 'current-page',
            id       : 'current-page-selector',
          }),
          '&nbsp;',
          Span({
            className: 'tablenav-paging-text',
          }, sprintf('of %s', totalPages)),
        ]),
        '&nbsp;',
        makeEl('a', {
          className: 'next-page button',
          id       : 'next-page',
          disabled : DirectoryState.page === totalPages - 1,
          onClick  : e => {

            DirectoryState.set({
              page: DirectoryState.page + 1,
            })

            fetchContacts().then(() => morphDirectory())
          },
        }, '&rsaquo;'),
      ]),
    ])

  }

  const buildQuery = () => {

    let query = {}

    const {
      search = '',
      filter = '',
    } = DirectoryState

    if (search) {

      switch (filter) {
        default:
        case 'name':
          query.full_name_like = search
          break
        case 'email':
          query.email_like = search
          break
        case 'phone':
          query = {
            ...query,
            meta_query: {
              relation: 'OR',
              0       : {
                key    : 'primary_phone',
                value  : search,
                compare: 'RLIKE',
              },
              1       : {
                key    : 'mobile_phone',
                value  : search,
                compare: 'RLIKE',
              },
              2       : {
                key    : 'company_phone',
                value  : search,
                compare: 'RLIKE',
              },
            },
          }
          break
        case 'position':
          query = {
            ...query,
            meta_key    : 'job_title',
            meta_value  : search,
            meta_compare: 'RLIKE',
          }
          break
        case 'department':
          query = {
            ...query,
            meta_key    : 'department',
            meta_value  : search,
            meta_compare: 'RLIKE',
          }
          break
      }
    }

    return query
  }

  let searchTimeout

  const maybeFetchContactsAndMorph = () => {

    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }

    searchTimeout = setTimeout(() => {
      fetchContacts().then(morphDirectory)
    }, 500)
  }

  const fetchContacts = (query = {}) => ContactsStore.fetchItems({
    filters: [
      [
        {
          type       : 'secondary_related',
          object_type: 'company',
          object_id  : getCompany().ID,
        },
      ],
    ],
    limit  : DirectoryState.per_page,
    offset : DirectoryState.per_page * DirectoryState.page,
    orderby: DirectoryState.orderby,
    order  : DirectoryState.order,
    ...buildQuery(),
    ...query,
  }).then(contacts => {
    DirectoryState.set({
      loaded  : true,
      total   : ContactsStore.getTotalItems(),
      contacts: contacts.map(c => c.ID),
    })
    return contacts
  })

  /**
   * Remove contact relationships from a company record
   *
   * @param ids
   * @returns {any}
   */
  const removeContacts = (ids = []) => CompaniesStore.deleteRelationships(getCompany().ID, ids.map(id => ( {
    child_type: 'contact',
    child_id  : id,
  } ))).then(r => {

    ContactsStore.clearResultsCache()

    // remove
    fetchContacts().then(morphDirectory)

    dialog({
      message: sprintf(_n('%s contact removed', '%s contacts removed', ids.length, 'groundhogg-companies'), ids.length),
    })

    return r
  })

  const Directory = () => {

    // not loaded
    if (!DirectoryState.loaded) {
      fetchContacts().then(morphDirectory)
      return Div({ id: 'directory' }, spinner())
    }

    return Div({
      id       : 'directory',
      className: 'display-flex column gap-10',
    }, [
      `<p></p>`,
      Div({
        className: 'display-flex space-between',
      }, [
        Div({
          className: 'display-flex gap-10',
        }, [
          Button({
            className: 'gh-button secondary icon small display-flex gap-10',
            onClick  : e => {
              addContactsMenu(e.currentTarget)
            },
          }, [
            icons.createContact,
            __('Add contacts!'),
          ]),
        ]),
        InputGroup([
          Select({
            name    : 'filter',
            options : {
              name      : __('Name'),
              email     : __('Email'),
              phone     : __('Phone'),
              position  : __('Position'),
              department: __('Department'),
            },
            selected: DirectoryState.filter ?? '',
            onChange: e => {
              DirectoryState.set({
                filter: e.target.value,
              })

              if (DirectoryState.search) {
                maybeFetchContactsAndMorph()
              }
            },
          }),
          Input({
            type   : 'search',
            name   : 'search',
            value  : DirectoryState.search ?? '',
            onInput: e => {
              DirectoryState.set({
                search: e.target.value,
              })
              maybeFetchContactsAndMorph()
            },
          }),
        ]),
      ]),
      Div({
        className: 'tablenav space-between',
      }, [
        DirectoryState.selected.length ? Div({
          className: 'display-flex',
          id       : 'bulk-actions',
        }, [
          Button({
            id       : 'bulk-remove',
            className: 'gh-button danger icon text small',
            onClick  : e => {
              dangerConfirmationModal({
                alert    : `<p>${ sprintf(__('Are you sure you want to remove %s contacts from %s?'), bold(`${ DirectoryState.selected.length }`),
                  bold(getCompany().data.name)) }</p>`,
                onConfirm: () => {

                  removeContacts(DirectoryState.selected).then(r => {
                    // reset selected
                    DirectoryState.set({
                      selected: [],
                    })

                    morphDirectory()
                  })
                },
              })
            },
          }, [
            Dashicon('trash'),
            ToolTip('Remove'),
          ]),
          Button({
            id       : 'bulk-cc',
            className: 'gh-button secondary text icon small',
            onClick  : e => {
              e.preventDefault()

              let email = {
                to        : [ContactsStore.get(getCompany().data.primary_contact_id)?.data.email],
                cc        : DirectoryState.selected.filter(id => id != getCompany().data.primary_contact_id).map(id => ContactsStore.get(id)?.data.email),
                from_email: currentUser.data.user_email,
                from_name : currentUser.data.display_name,
              }

              Groundhogg.components.emailModal(email)
            },
          }, [
            Dashicon('email'),
            ToolTip('CC All'),
          ]),
          Button({
            id       : 'bulk-bcc',
            className: 'gh-button secondary text icon small',
            onClick  : e => {
              e.preventDefault()

              let email = {
                to        : [ContactsStore.get(getCompany().data.primary_contact_id)?.data.email],
                bcc       : DirectoryState.selected.filter(id => id != getCompany().data.primary_contact_id).map(id => ContactsStore.get(id)?.data.email),
                from_email: currentUser.data.user_email,
                from_name : currentUser.data.display_name,
              }

              Groundhogg.components.emailModal(email)
            },
          }, [
            Dashicon('email-alt'),
            ToolTip('BCC All'),
          ]),
        ]) : Div({ id: 'bulk-actions' }),
        Pagination(),
      ]),
      StaffTable(),
    ])

  }

  const ContactMethod = (icon, content) => Div({
    className: 'display-flex gap-10 align-center',
  }, [
    Dashicon(icon),
    content,
  ])

  const SortableColumn = (id, text) => Th({
    id,
    scope    : 'col',
    className: [
      'manage-column',
      `column-${ id }`,
      DirectoryState.orderby === id ? 'sorted' : 'sortable',
      DirectoryState.order === 'DESC' ? 'desc' : 'asc',
    ].join(' '),
  }, makeEl('a', {
    id     : `sort-${ id }`,
    href   : '#',
    onClick: e => {
      // only change order
      if (DirectoryState.orderby === id) {
        DirectoryState.set({
          order: DirectoryState.order === 'DESC' ? 'ASC' : 'DESC',
        })
      }
      else {
        DirectoryState.set({
          orderby: id,
          order  : 'DESC',
        })
      }

      fetchContacts().then(morphDirectory)
    },
  }, [
    Span({}, text),
    Span({
      className: 'sorting-indicators',
    }, [
      Span({ className: 'sorting-indicator asc' }),
      Span({ className: 'sorting-indicator desc' }),
    ]),
  ]))

  const StaffTable = () => {

    let contacts = DirectoryState.contacts.map(id => ContactsStore.get(id))

    return Div({ className: 'scrolling-table gh-panel overflow-hidden' }, Div({ className: 'table-scroll' }, Table({
      className: 'wp-list-table widefat striped',
      style    : {
        border: 'none',
      },
    }, [
      THead({}, [
        Tr({}, [
          Td({
            className: 'check-column',
          }, [
            Input({
              type    : 'checkbox',
              value   : 1,
              name    : 'check_all',
              checked : contacts.every(({ ID }) => DirectoryState.selected.includes(ID)) && contacts.length,
              id      : `contact-check-all`,
              onChange: e => {
                if (e.target.checked) {
                  DirectoryState.set({
                    selected: [...DirectoryState.contacts],
                  })
                }
                else {
                  DirectoryState.set({
                    selected: [],
                  })
                }

                morphDirectory()
              },
            }),
          ]),
          SortableColumn('first_name', __('Name')),
          SortableColumn('email', __('Contact Info')),
          SortableColumn('cm.job_title', __('Position')),
          SortableColumn('cm.department', __('Department')),
        ]),
      ]),
      TBody({}, [
        !contacts.length ? Tr({ className: 'no-items' }, Td({
          colspan  : 6,
          className: 'colspanchange',
        }, 'No contacts found.')) : null,
        ...contacts.map(contact => Tr({}, [
          Th({ className: 'check-column' }, Input({
            type    : 'checkbox',
            value   : contact.ID,
            name    : 'contacts[]',
            checked : DirectoryState.selected.includes(contact.ID),
            id      : `contact-check-${ contact.ID }`,
            onChange: e => {
              if (e.target.checked) {
                DirectoryState.set({
                  selected: [
                    ...DirectoryState.selected,
                    contact.ID,
                  ],
                })
              }
              else {
                DirectoryState.set({
                  selected: DirectoryState.selected.filter(id => id != contact.ID),
                })
              }

              morphDirectory()
            },
          })),
          Td({}, [
            makeEl('a', { href: contact.admin }, makeEl('img', {
              src   : contact.data.gravatar,
              width : 40,
              height: 40,
              style : {
                borderRadius: '5px',
                float       : 'left',
                marginRight : '10px',
              },
            })),
            makeEl('a', {
              className: 'row-title',
              href     : contact.admin,
            }, contact.data.full_name.trim() || contact.data.email),
            contact.ID == getCompany().data.primary_contact_id ? ' — ' : null,
            contact.ID == getCompany().data.primary_contact_id ? Span({ className: 'post-state' }, 'Primary') : null,
            Div({ className: 'row-actions' }, [
              makeEl('a', { href: contact.admin }, __('View')),
              ' | ',
              makeEl('a', {
                id     : `quick-edit-${ contact.ID }`,
                href   : '#',
                onClick: e => {
                  e.preventDefault()
                  companyQuickEdit(contact, c => {
                    morphDirectory()
                  })
                },
              }, __('Quick Edit')),
              contact.ID == getCompany().data.primary_contact_id ? null : ' | ',
              contact.ID == getCompany().data.primary_contact_id ? null : makeEl('a', {
                id       : `set-primary-${ contact.ID }`,
                className: 'set-primary',
                href     : '#',
                onClick  : e => {
                  e.preventDefault()

                  CompaniesStore.patch(company.ID, {
                    data: {
                      primary_contact_id: contact.ID,
                    },
                  }).then(item => {

                    dialog({
                      message: __('Primary contact changed!', 'groundhogg-companies'),
                    })

                    morphDirectory()
                  })
                },
              }, __('Set Primary')),
              ' | ',
              makeEl('a', {
                id       : `remove-${ contact.ID }`,
                className: 'danger trash',
                href     : '#',
                onClick  : e => {
                  e.preventDefault()
                  dangerConfirmationModal({
                    alert    : `<p>${ sprintf(__('Are you sure you want to remove %s from %s?'), bold(contact.data.full_name),
                      bold(getCompany().data.name)) }</p>`,
                    onConfirm: () => {
                      removeContacts([contact.ID]).then(r => {
                        morphDirectory()
                      })
                    },
                  })
                },
              }, __('Remove')),
            ]),
          ]),
          Td({
            className: 'display-flex column contact-methods',
          }, [
            ContactMethod('email-alt', makeEl('a', {
              href   : '#',
              onClick: e => {
                e.preventDefault()
                sendEmail(contact)
              },
            }, contact.data.email)),
            contact.meta.primary_phone ? ContactMethod('phone', makeEl('a', { href: `tel:${ contact.meta.primary_phone }` },
              `${ contact.meta.primary_phone }${ contact.meta.primary_phone_extension ? ` ext. ${ contact.meta.primary_phone_extension }` : '' }`)) : null,
            contact.meta.mobile_phone
            ? ContactMethod('smartphone', makeEl('a', { href: `tel:${ contact.meta.mobile_phone }` }, contact.meta.mobile_phone))
            : null,
            contact.meta.company_phone ? ContactMethod('building', makeEl('a', { href: `tel:${ contact.meta.company_phone }` },
              `${ contact.meta.company_phone }${ contact.meta.company_phone_extension ? ` ext. ${ contact.meta.company_phone_extension }` : '' }`)) : null,
          ]),
          Td({}, contact.meta.job_title ?? '-'),
          Td({}, contact.meta.company_department ?? '-'),
        ])),
      ]),
    ])))

  }

  const Notes = () => Div({
    className: 'gh-panel top-left-square',
  }, Div({
    className: 'inside',
    id       : 'notes-here',
    onCreate : el => {
      setTimeout(() => {
        noteEditor('#notes-here', {
          object_id  : company.ID,
          object_type: 'company',
          title      : '',
        })
      }, 10)
    },
  }))

  const Files = () => Div({
    onCreate: () => {
      setTimeout(() => {
        let selectedFiles = []

        let fileSearch = ''

        $('#bulk-delete-files').on('click', () => {
          dangerConfirmationModal({
            confirmText: __('Delete'),
            alert      : `<p>${ sprintf(
              _n('Are you sure you want to delete %d file?', 'Are you sure you want to delete %d files?', selectedFiles.length, 'groundhogg'),
              selectedFiles.length) }</p>`,
            onConfirm  : () => {
              _delete(`${ CompaniesStore.route }/${ company.ID }/files`, selectedFiles).then(({ items }) => {
                selectedFiles = []
                files = items
                mount()
              })
            },
          })
        })

        $('#search-files').on('input change', e => {
          fileSearch = e.target.value
          mount()
        })

        tooltip('#bulk-delete-files', {
          content : __('Bulk delete files'),
          position: 'right',
        })

        const renderFile = (file) => {
          //language=HTML
          return `
              <tr class="file">
                  <th scope="row" class="check-column">${ input({
                      type     : 'checkbox',
                      name     : 'select[]',
                      className: 'file-toggle',
                      value    : file.name,
                  }) }
                  </th>
                  <td class="column-primary"><a class="row-title" href="${ file.url }"
                                                target="_blank">${ file.name }</a></td>
                  <td>${ file.date_modified }</td>
                  <td>
                      <div class="space-between align-right">
                          <button data-file="${ file.name }" class="file-more gh-button secondary text icon">
                              ${ icons.verticalDots }
                          </button>
                      </div>
                  </td>
              </tr>`
        }

        const mount = () => {

          $('#files-here').html(files.filter(f => !fileSearch || f.name.match(regexp(fileSearch))).map(f => renderFile(f)).join(''))
          onMount()
        }

        const onMount = () => {

          const maybeShowBulkActions = () => {
            if (selectedFiles.length) {
              $('#bulk-actions').removeClass('hidden')
            }
            else {
              $('#bulk-actions').addClass('hidden')
            }
          }

          $('.file-more').on('click', e => {

            let _file = e.currentTarget.dataset.file

            moreMenu(e.currentTarget, {

              items   : [
                {
                  key : 'download',
                  text: __('Download'),
                },
                {
                  key : 'delete',
                  text: `<span class="gh-text danger">${ __('Delete') }</span>`,
                },
              ],
              onSelect: k => {
                switch (k) {
                  case 'download':
                    window.open(files.find(f => f.name === _file).url, '_blank').focus()
                    break
                  case 'delete':

                    dangerConfirmationModal({
                      confirmText: __('Delete'),
                      alert      : `<p>${ sprintf(__('Are you sure you want to delete %s?', 'groundhogg'), _file) }</p>`,
                      onConfirm  : () => {
                        _delete(`${ CompaniesStore.route }/${ company.ID }/files`, [
                          _file,
                        ]).then(({ items }) => {
                          selectedFiles = []
                          files = items
                          mount()
                        })
                      },
                    })

                    break
                }
              },
            })
          })

          $('.file-toggle').on('change', e => {
            if (e.target.checked) {
              selectedFiles.push(e.target.value)
            }
            else {
              selectedFiles.splice(selectedFiles.indexOf(e.target.value), 1)
            }
            maybeShowBulkActions()
          })

        }

        $('#upload-file').on('click', e => {
          e.preventDefault()

          Groundhogg.components.fileUploader({
            action      : 'groundhogg_company_upload_file',
            nonce       : '',
            beforeUpload: (fd) => fd.append('company', company.ID),
            onUpload    : (json, file) => {
              // console.log( json )
              files = json.data.files
              mount()
            },
          })
        })

        if (!files.length) {
          CompaniesStore.fetchFiles(company.ID).then(_files => {
            files = _files
            mount()
          })
        }

        mount()
      }, 10)
    },
  }, [
    // language=HTML
    `
        <div class="gh-panel top-left-square">
            <div id="file-actions" class="inside">
                <div class="gh-input-group">
                    ${ input({
                        placeholder: __('Search files...'),
                        type       : 'search',
                        id         : 'search-files',
                    }) }
                    <button id="upload-file" class="gh-button secondary">${ __('Upload Files') }</button>
                </div>
            </div>
            <div id="bulk-actions" class="hidden inside" style="padding-top: 0">
                <button id="bulk-delete-files" class="gh-button danger small text icon"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <table class="wp-list-table widefat striped" style="border: none">
                <thead></thead>
                <tbody id="files-here">
                </tbody>
            </table>
        </div>
    `,
  ])

  const SecondaryTabs = () => {

    let tabs = [
      {
        id  : 'directory',
        name: 'Contacts',
        view: () => Directory(),
      },
      {
        id  : 'notes',
        name: 'Notes',
        view: () => Notes(),
      },
      {
        id  : 'files',
        name: 'Files',
        view: () => Files(),
      },
    ]

    return Div({}, [
      makeEl('h2', {
        className: `nav-tab-wrapper ${ State.secondaryTab === 'directory' ? '' : 'gh' } secondary`,
      }, tabs.map(({
        id,
        name,
      }) => makeEl('a', {
        className: `nav-tab ${ State.secondaryTab === id ? 'nav-tab-active' : '' }`,
        onClick  : e => {
          e.preventDefault()
          State.set({
            secondaryTab: id,
          })
          morphSecondaryTabs()
        },
      }, name))),
      tabs.find(t => t.id === State.secondaryTab).view(),
    ])
  }

  const addContactsMenu = target => moreMenu(target, [
    {
      key     : 'select',
      cap     : 'view_contacts',
      text    : __('Select an existing contact', 'groundhogg-companies'),
      onSelect: () => {
        selectContactModal({
          exclude : DirectoryState.contacts,
          onSelect: (contact) => {
            CompaniesStore.createRelationships(company.ID, {
              child_type: 'contact',
              child_id  : contact.ID,
            }).then(() => {
              ContactsStore.clearResultsCache()
              maybeFetchContactsAndMorph()
            })
          },
        })
      },
    },
    {
      key     : 'add',
      cap     : 'add_contacts',
      text    : __('Create a new contact', 'groundhogg-companies'),
      onSelect: () => {

        addContactModal({
          ...QuickAddEditParts,
          onCreate: (contact) => {
            CompaniesStore.createRelationships(company.ID, {
              child_type: 'contact',
              child_id  : contact.ID,
            }).then(() => {
              ContactsStore.clearResultsCache()
              maybeFetchContactsAndMorph()
            })
          },
        })

      },
    },
  ].filter(i => userHasCap(i.cap)))

  const ParentState = Groundhogg.createState({
    loaded: false,
    parent: 0,
  })

  const ParentCompany = () => {

    if (!ParentState.loaded) {
      CompaniesStore.fetchRelationships(getCompany().ID, {
        parent_type: 'company',
      }).then(items => {
        // they are companies after all
        CompaniesStore.itemsFetched(items)
        ParentState.set({
          parent: items[0].ID,
          loaded: true,
        })
        // todo morph
      })
      return Div({})
    }

    if (!ParentState.parent) {
      return ItemPicker()
    }

    return Div({})

  }

  const getHostname = company => {
    let hostname, url = ''
    try {
      url = new URL(company.data.domain)
      return url.hostname
    }
    catch (e) {
      return ''
    }
  }

  const CompanyInfo = () => {

    let hostname, url = ''
    hostname = getHostname(getCompany()) || 'example.com'

    const Detail = (icon, content) => Div({
      className: 'display-flex gap-10',
    }, [
      Dashicon(icon),
      content,
    ])

    let address = getCompany().meta.address?.split('\n').join(', ')

    let { logo = '' } = getCompany().meta
    let img
    if (logo) {
      img = Div({
        className: 'has-box-shadow border-radius-5',
        style    : {
          backgroundColor: '#ffffff',
          backgroundImage   : `url(${ logo })`,
          backgroundSize    : 'contain',
          aspectRatio       : '1 / 1',
          backgroundPosition: 'center',
          backgroundOrigin  : 'content-box',
          backgroundRepeat  : 'no-repeat',
          padding: '5px',
          float : 'left',
          margin: '0 20px 16px 20px',
          width : '100px',
          height: '100px',
          boxSizing: 'border-box'
        },
      })
    }
    else {
      img = makeEl('img', {
        className: 'has-box-shadow',
        src      : `https://www.google.com/s2/favicons?domain=${ hostname }&sz=40`,
        width    : 40,
        height   : 40,
        style    : {
          float       : 'left',
          marginRight : '10px',
          marginBottom: '-10px',
          borderRadius: '5px',
        },
      })
    }

    return Div({}, [
      img,
      `<h1 style="margin: 0; font-weight: 500">${ getCompany().data.name }</h1>`,
      Div({
        className: 'gh-panel',
      }, [
        Div({
          className: 'inside full display-flex gap-10',
          style: {
            paddingLeft: logo ? '0' : 'initial'
          }
        }, [

          Button({
            id       : 'add-contacts',
            className: 'gh-button secondary text icon',
            onClick  : e => {
              addContactsMenu(e.currentTarget)
            },
          }, [
            icons.createContact,
            ToolTip(__('Add a new contact')),
          ]),

          getCompany().meta.phone ? makeEl('a', {
            className: 'gh-button secondary text icon',
            href     : `tel:${ getCompany().meta.phone }`,
          }, [
            icons.phone,
            ToolTip(__('Call this business')),
          ]) : null,

          Button({
            id       : 'email-contacts',
            className: 'gh-button secondary text icon',
            onClick  : e => {
              moreMenu(e.currentTarget, [
                {
                  key     : 'email-primary',
                  text    : 'Email the primary contact',
                  onSelect: () => {

                    if (!getCompany().data.primary_contact_id) {
                      dialog({
                        message: 'No primary contact set',
                        type   : 'error',
                      })
                      return
                    }

                    sendEmail(ContactsStore.get(getCompany().data.primary_contact_id))
                  },
                },
                {
                  key     : 'email-all',
                  text    : 'Email all contacts',
                  onSelect: () => emailAll(),
                },
              ])
            },
          }, [
            icons.email,
            ToolTip(__('Send an email')),
          ]),

          Button({
            id       : 'company-more',
            className: 'gh-button secondary text icon',
            onClick  : e => {
              moreMenu(e.currentTarget, [
                {
                  key     : 'merge',
                  cap     : 'delete_companies',
                  text    : __('Merge'),
                  onSelect: () => {

                    const MergeState = Groundhogg.createState({
                      search: '',
                      loaded: false,
                      items : [],
                    })

                    Modal({
                      dialogClasses: 'no-padding',
                    }, ({
                      close,
                      morph,
                    }) => {

                      const fetchItems = () => CompaniesStore.fetchItems({
                        exclude: [getCompany().ID],
                        search : MergeState.search,
                      }).then(items => {
                        MergeState.set({
                          items,
                          loaded: true,
                        })

                        morph()
                      })

                      const fetchItemsDebounced = debounce(() => {
                        MergeState.set({ loaded: false })
                        morph()
                        return fetchItems()
                      }, 300)

                      if (!MergeState.loaded) {
                        fetchItems()
                      }

                      return Fragment([
                        Div({
                          id   : 'search-form',
                          style: {
                            width: '400px',
                          },
                        }, Input({
                          type       : 'search',
                          placeholder: 'Search by name',
                          value      : MergeState.search,
                          onInput    : e => {
                            MergeState.set({
                              search: e.target.value,
                            })
                            fetchItemsDebounced()
                          },
                        })),
                        !MergeState.loaded ? spinner() : Div({
                          id: 'search-results',
                        }, [
                          Table({}, TBody({}, MergeState.items.map(co => Tr({
                            dataId: co.ID,
                          }, [
                            Td({}, makeEl('img', { src: `https://www.google.com/s2/favicons?domain=${ getHostname(co) }&sz=40` })),
                            Td({}, [
                              `<b>${ co.data.name }</b><br/>${ getHostname(co) }`,
                            ]),
                            Td({}, Button({
                              className: 'gh-button primary text',
                              id       : `select-${ co.ID }`,
                              onClick  : e => {
                                close()
                                confirmationModal({
                                  confirmText: __('Merge'),
                                  width      : 500,
                                  // language=HTML
                                  alert    : `<p>${ sprintf(
                                          __('Are you sure you want to merge %1$s with %2$s? This action cannot be undone.',
                                                  'groundhogg'),
                                          bold(co.data.name),
                                          bold(getCompany().data.name)) }</p>`,
                                  onConfirm: () => {

                                    loadingModal()

                                    Groundhogg.api.post(`${ CompaniesStore.route }/${ getCompany().ID }/merge`, [
                                      co.ID,
                                    ]).then(() => {
                                      dialog({
                                        message: 'Company merged!',
                                      })
                                      location.reload()
                                    })
                                  },
                                })
                              },
                            }, 'Select')),
                          ])))),
                        ]),
                      ])
                    })
                  },
                },
                {
                  key     : 'delete',
                  cap     : 'delete_companies',
                  text    : `<span class="gh-text danger">${ __('Delete') }</span>`,
                  onSelect: () => {
                    dangerDeleteModal({
                      name     : bold(getCompany().data.name),
                      onConfirm: () => {
                        CompaniesStore.delete(getCompany().ID).then(() => {
                          dialog({
                            message: 'Company deleted.',
                          })
                          window.location.href = adminPageURL('gh_companies')
                        })
                      },
                    })
                  },
                },
              ].filter(({ cap }) => userHasCap(cap)))
            },
          }, [
            icons.verticalDots,
            ToolTip(__('More options')),
          ]),

        ]),
        Div({ className: 'inside display-flex gap-10 column' }, [
          getCompany().data.domain ? Detail('admin-site', `<a href="${ getCompany().data.domain }" target="_blank">${ hostname }</a>`) : null,
          getCompany().meta.phone ? Detail('phone', `<a href="tel:${ getCompany().meta.phone }">${ getCompany().meta.phone }</a>`) : null,
          getCompany().meta.industry ? Detail('store', `<span>${ getCompany().meta.industry }</span>`) : null,
          getCompany().meta.address ? Detail('location', makeEl('a', { href: `http://maps.google.com/?q=${ address }` }, address)) : null,
        ]),
      ]),
    ])
  }

  const checkForPotentialContactMatches = () => {
    let hostname

    try {
      hostname = getHostname(getCompany())
    }
    catch (e) {
      return
    }

    if (!hostname) {
      return
    }

    ContactsStore.fetchItems({
      exclude_filters: [
        [
          {
            type       : 'secondary_related',
            object_id  : getCompany().ID,
            object_type: 'company',
          },
        ],
      ],
      filters        : [
        [
          {
            type   : 'email',
            compare: 'ends_with',
            value  : '@' + hostname,
          },
        ],
      ],
    }).then(contacts => {

      if (!contacts.length) {
        return
      }

      confirmationModal({
        width      : 500,
        alert      : `<p>${ sprintf(_n('We found %s contact that has an email address ending with %s. Would you like to relate them to this company?',
          'We found %s contacts that have an email address ending with %s. Would you like to associate them with this company?', contacts.length,
          'groundhogg-companies'), bold(formatNumber(contacts.length)), bold('@' + hostname)) }</p>`,
        confirmText: __('Yes, add them!'),
        cancelText : __('No'),
        onConfirm  : () => {

          CompaniesStore.createRelationships(company.ID, contacts.map(c => ( {
            child_id  : c.ID,
            child_type: 'contact',
          } ))).then(() => {
            ContactsStore.clearResultsCache()
            maybeFetchContactsAndMorph()
          })

        },
      })
    })
  }

  const morphDirectory = () => morphdom(document.getElementById('directory'), Directory())
  const morphSecondaryTabs = () => morphdom(document.getElementById('tabs-secondary'), SecondaryTabs(), { childrenOnly: true })
  const morphInfoCard = () => morphdom(document.getElementById('company-info-card'), Div({}, CompanyInfo()), { childrenOnly: true })

  $(() => {
    morphInfoCard()
    morphSecondaryTabs()
    companyTabs()
    checkForPotentialContactMatches()
  })

} )(jQuery)
