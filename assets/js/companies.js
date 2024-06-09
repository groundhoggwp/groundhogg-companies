( ($) => {

  const { company, gh_company_custom_properties } = GroundhoggCompany
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
    loadingDots,
    spinner,
    tooltip,
    textarea,
    bold,
    adminPageURL,
    confirmationModal,
  } = Groundhogg.element
  const { companies: CompaniesStore, contacts: ContactsStore } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { currentUser, propertiesEditor, noteEditor } = Groundhogg
  const { userHasCap, getOwner } = Groundhogg.user
  const { patch, routes, delete: _delete, get } = Groundhogg.api
  const { quickEditContactModal, selectContactModal, addContactModal } = Groundhogg.components
  const { formatNumber } = Groundhogg.formatting

  const sendEmail = (contact) => {

    let email = {
      to: [contact.data.email],
      from_email: currentUser.data.user_email,
      from_name: currentUser.data.display_name,
    }

    if (contact.data.owner_id && currentUser.ID != contact.data.owner_id) {
      email.cc = [Groundhogg.filters.owners.find(u => u.ID == contact.data.owner_id).data.user_email]
    }

    Groundhogg.components.emailModal(email)
  }

  const emailAll = () => {

    let email = {
      to: relatedContacts.map(c => c.data.email),
      from_email: currentUser.data.user_email,
      from_name: currentUser.data.display_name,
      cc: relatedContacts.filter(c => c.data.owner_id && c.data.owner_id != currentUser.ID).
        map(c => Groundhogg.filters.owners.find(u => u.ID == c.data.owner_id).data.user_email).
        filter((v, i, a) => a.indexOf(v) === i),
    }

    Groundhogg.components.emailModal(email)
  }

  CompaniesStore.itemsFetched([company])

  const {
    Modal,
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
    Dashicon,
    makeEl,
    Table,
    TBody,
    THead,
    Th,
    Td,
    Tr,
    Span,
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
        id: 'general',
        name: __('General'),
        render: () => `<div class="gh-panel top-left-square"><div id="general-info" class="inside"></div></div>`,
        onMount: () => {

          const MetaChanges = Groundhogg.createState({})
          const DataChanges = Groundhogg.createState({})

          morphdom(document.getElementById('general-info'), Div({}, [

            Div({
              id: 'company-info-wrapper',
              className: 'display-grid gap-10',
            }, [

              // Name
              Div({ className: 'half display-flex column gap-5' }, [
                Label({
                  for: 'company-name',
                }, __('Name', 'groundhogg-companies')),
                Input({
                  name: 'company_name',
                  id: 'company-name',
                  value: getCompany().data.name,
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
                  name: 'company_industry',
                  id: 'company-industry',
                  value: getCompany().meta.industry ?? '',
                  fetchResults: async (search) => {
                    return Groundhogg.companyIndustries.filter(string => string.match(new RegExp(search, 'i'))).map(s => ( { id: s, text: s } ))
                  },
                  onChange: e => {
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
                    name: 'company_url',
                    id: 'company-url',
                    className: 'full-width',
                    value: getCompany().data.domain,
                    type: 'url',
                    onInput: e => {
                      MetaChanges.set({
                        domain: e.target.value,
                      })
                    },
                  }),
                  Button({
                    className: 'gh-button secondary small',
                    id: 'url-visit',
                    onClick: e => {
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
                  name: 'company_phone',
                  id: 'company-phone',
                  type: 'tel',
                  value: getCompany().meta.phone ?? '',
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
                  id: `select-owner`,
                  noneSelected: __('Select an owner...', 'groundhogg'),
                  selected: { id: getCompany().data.owner_id, text: getOwner(getCompany().data.owner_id).data.display_name },
                  multiple: false,
                  style: {
                    flexGrow: 1,
                  },
                  fetchOptions: async (search) => {
                    search = new RegExp(search, 'i')

                    return Groundhogg.filters.owners.map(u => ( { id: u.ID, text: u.data.display_name } )).filter(({ text }) => text.match(search))
                  },
                  onChange: item => {
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
                  name: 'company_address',
                  id: 'company-address',
                  value: getCompany().meta.address ?? '',
                  onInput: e => {
                    MetaChanges.set({
                      address: e.target.value,
                    })
                  },
                }),
              ]),

              Div({
                className: 'display-flex flex-end full',
              }, [
                Button({
                  className: 'gh-button primary',
                  id: 'save-general-changes',
                  onClick: async e => {

                    await CompaniesStore.patch(getCompany().ID, {
                      data: DataChanges.get(),
                      meta: MetaChanges.get(),
                    })

                    dialog({
                      message: __('Changes saved!', 'groundhogg-companies'),
                    })

                  },
                }, __('Save Changes', 'groundhogg')),
              ]),

            ]),
          ]), {
            childrenOnly: true,
          })
        },
      },
      {
        id: 'notes',
        name: __('Notes'),
        render: () => `<div class="gh-panel top-left-square"><div id="notes-here" class="inside"></div></div>`,
        onMount: () => {
          noteEditor('#notes-here', {
            object_id: company.ID,
            object_type: 'company',
            title: '',
          })
        },
      },
      {
        id: 'files',
        name: __('Files'),
        render: () => {
          // language=HTML
          return `
              <div class="gh-panel top-left-square">
                  <div id="file-actions" class="inside">
                      <div class="gh-input-group">
                          ${ input({
                              placeholder: __('Search files...'),
                              type: 'search',
                              id: 'search-files',
                          }) }
                          <button id="upload-file" class="gh-button secondary">${ __('Upload Files') }</button>
                      </div>
                  </div>
                  <div id="bulk-actions" class="hidden inside" style="padding-top: 0">
                      <button id="bulk-delete-files" class="gh-button danger icon"><span
                              class="dashicons dashicons-trash"></span></button>
                  </div>
                  <table class="wp-list-table widefat striped" style="border: none">
                      <thead></thead>
                      <tbody id="files-here">
                      </tbody>
                  </table>
              </div>`
        },
        onMount: () => {

          let selectedFiles = []

          let fileSearch = ''

          $('#bulk-delete-files').on('click', () => {
            dangerConfirmationModal({
              confirmText: __('Delete'),
              alert: `<p>${ sprintf(
                _n('Are you sure you want to delete %d file?', 'Are you sure you want to delete %d files?', selectedFiles.length, 'groundhogg'),
                selectedFiles.length) }</p>`,
              onConfirm: () => {
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
            content: __('Bulk delete files'),
            position: 'right',
          })

          const renderFile = (file) => {
            //language=HTML
            return `
                <tr class="file">
                    <th scope="row" class="check-column">${ input({
                        type: 'checkbox',
                        name: 'select[]',
                        className: 'file-toggle',
                        value: file.name,
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

                items: [
                  {
                    key: 'download',
                    text: __('Download'),
                  },
                  {
                    key: 'delete',
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
                        alert: `<p>${ sprintf(__('Are you sure you want to delete %s?', 'groundhogg'), _file) }</p>`,
                        onConfirm: () => {
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
              action: 'groundhogg_company_upload_file',
              nonce: '',
              beforeUpload: (fd) => fd.append('company', company.ID),
              onUpload: (json, file) => {
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

        },
      },
    ]

    let customTabState = gh_company_custom_properties || {
      tabs: [],
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

    const allTabs = () => {
      return [
        ...tabs,
        ...customTabState.tabs.map(t => ( {
          id: t.id,
          isCustom: true,
          name: t.name,
          render: () => `<div class="tab-content-wrapper custom-tab gh-panel top-left-square active" data-tab-content="${ activeTab }">
				<div class="inside">
					<div id="custom-fields-here">
					</div>
					<p>
						<button id="save-meta" class="gh-button primary">${ __('Save Changes') }</button>
						<button id="cancel-meta-changes" class="gh-button danger text">${ __('Cancel') }</button>
					</p>
				</div>
			</div>`,
          onMount: () => {
            // Groups belonging to this tab
            let groups = customTabState.groups.filter(g => g.tab === activeTab)
            // Fields belonging to the groups of this tab
            let fields = customTabState.fields.filter(f => groups.find(g => g.id === f.group))

            $('#save-meta').on('click', commitMetaChanges)
            $('#cancel-meta-changes').on('click', cancelMetaChanges)

            $('#tab-more').on('click', e => {
              e.preventDefault()

              moreMenu(e.currentTarget, {
                items: [
                  {
                    key: 'rename',
                    cap: 'manage_options',
                    text: __('Rename'),
                  },
                  {
                    key: 'delete',
                    cap: 'manage_options',
                    text: `<span class="gh-text danger">${ __('Delete') }</span>`,
                  },
                ],
                onSelect: k => {

                  switch (k) {

                    case 'delete':

                      dangerConfirmationModal({
                        confirmText: __('Delete'),
                        alert: `<p>${ sprintf(__('Are you sure you want to delete %s?', 'groundhogg'),
                          bold(customTabState.tabs.find(t => t.id === activeTab).name)) }</p>`,
                        onConfirm: () => {
                          // Groups belonging to this tab
                          let groups = customTabState.groups.filter(g => g.tab === activeTab)
                          // Fields belonging to the groups of this tab
                          let fields = customTabState.fields.filter(f => groups.find(g => g.id === f.group)).map(f => f.id)

                          customTabState.tabs = customTabState.tabs.filter(t => t.id !== activeTab)
                          customTabState.groups = customTabState.groups.filter(g => g.tab !== activeTab)
                          customTabState.fields = customTabState.fields.filter(f => !fields.includes(f.id))

                          updateTabState()
                          activeTab = 'notes'
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
                                        id: 'tab-name',
                                        value: customTabState.tabs.find(t => t.id === activeTab).name,
                                        placeholder: __('Tab name', 'groundhogg'),
                                    }) }
                                    <button id="update-tab" class="gh-button primary">
                                        ${ __('Save') }
                                    </button>
                                </div>
                            </div>`,
                        onOpen: ({ close }) => {

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
              values: {
                ...getCompany().meta,
                ...metaChanges,
              },
              properties: {
                groups,
                fields,
              },
              onPropertiesUpdated: ({ groups = [], fields = [] }) => {

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
                  ...groups.map(g => ( { ...g, tab: activeTab } )),
                ]

                updateTabState()

              },
              onChange: (meta) => {
                metaChanges = {
                  ...metaChanges,
                  ...meta,
                }
              },
              canEdit: () => {
                return userHasCap('edit_companies')
              },

            })
          },
        } )),
      ]
    }

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
                      <button type="button" id="add-tab"><span
                              class="dashicons dashicons-plus-alt2"></span>
                      </button>
                      ${ _tabs.find(t => t.id === activeTab).isCustom
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

      tooltip('#add-tab', {
        content: __('Add Tab', 'groundhogg'),
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
                          id: 'tab-name',
                          placeholder: __('Tab name', 'groundhogg'),
                      }) }
                      <button id="create-tab" class="gh-button primary">
                          ${ __('Create') }
                      </button>
                  </div>
              </div>`,
          onOpen: ({ close }) => {

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

    mount()

  }

  const companyQuickEdit = (contact, onEdit) => quickEditContactModal({
    contact,
    additionalFields: ({ prefix, contact }) => {

      let { job_title = '', company_department = '', company_phone = '' } = contact.meta

      // language=HTML
      return `
          <div class="gh-row">
              <div class="gh-col">
                  <label>${ __('Position', 'groundhogg-companies') }</label>
                  ${ input({
                      id: `${ prefix }-job-title`,
                      value: job_title,
                  }) }
              </div>
              <div class="gh-col">
                  <label>${ __('Department', 'groundhogg-companies') }</label>
                  ${ input({
                      id: `${ prefix }-company-department`,
                      name: 'company_department',
                      value: company_department,
                  }) }
              </div>
              <div class="gh-col">
                  <label>${ __('Work Phone', 'groundhogg-companies') }</label>
                  ${ input({
                      id: `${ prefix }-work-phone`,
                      type: 'tel',
                      name: 'company_phone',
                      value: company_phone,
                  }) }
              </div>
          </div>`

    },
    additionalFieldsOnMount: ({ prefix, contact, setPayload, getPayload }) => {
      $(`#${ prefix }-company-department`).autocomplete({
        source: Groundhogg.companyDepartments,
      })
      $(`#${ prefix }-company-department, #${ prefix }-work-phone`).on('change input', (e) => {
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
    onEdit: (c) => {
      dialog({
        message: __('Contact updated!', 'groundhogg'),
      })
      onEdit(c)
    },
  })

  const DirectoryState = Groundhogg.createState({
    page: 0,
    per_page: 5,
    contacts: [],
    total: getCompany().totalContacts,
  })

  const Pagination = () => {

    if (DirectoryState.total <= DirectoryState.per_page) {
      return null
    }

    return Div({
      className: 'display-flex gap-10 pagination',
    }, [

      DirectoryState.page > 0 ? Button({
        className: 'gh-button secondary'
      }, __('Prev')) : null,
      ( DirectoryState.page + 1 ) * DirectoryState.per_page < DirectoryState.total ? Button({
        className: 'gh-button secondary'
      }, __('Next')) : null,

    ])

  }

  const Directory = () => {

    return Div({
      className: 'display-flex column gap-10',
    }, [

      Div({
        className: 'space-between',
      }, [
        InputGroup([
          Select({
            name: 'filter',
            options: {
              name: __('Name'),
              email: __('Email'),
              phone: __('Phone'),
              position: __('Position'),
              department: __('Department'),
            },
          }),
          Input({
            type: 'search',
            name: 'search',
          }),
        ]),
        Pagination(),
      ]),
      StaffTable(DirectoryState.contacts),

    ])

  }

  const StaffTable = (contacts) => {
    return Table({
      className: 'wp-list-table widefat striped gh-panel',
      style: {
        border: 'none',
      },
    }, [
      THead({}, [
        Tr({}, [
          Td(),
          Th({}, __('Name')),
          Th({}, __('Contact Info')),
          Th({}, __('Position')),
          Th({}, __('Department')),
          Td(),
        ]),
      ]),
      TBody({}, [
        ...contacts.map(contact => Tr({}, [
          Td({}, makeEl('a', { href: contact.admin }, makeEl('img', {
            src: contact.data.gravatar,
            width: 30,
            height: 30,
            style: {
              borderRadius: '50%',
            },
          }))),
          Td({}, [
            makeEl('a', {
              className: 'row-title',
              href: contact.admin,
            }, contact.data.full_name.trim() || '-'),
            Div({ className: 'row-actions' }, [
              makeEl('a', { href: contact.admin }, __('View')),
              ' | ',
              makeEl('a', {
                id: `quick-edit-${ contact.ID }`, href: '#', onClick: e => {
                  e.preventDefault()
                  companyQuickEdit(contact, c => {})
                },
              }, __('Quick Edit')),
            ]),
          ]),
          Td({
            className: 'display-flex column',
          }, [
            Span({}, contact.data.email),
            contact.meta.mobile_phone ? Span({}, contact.meta.mobile_phone) : null,
            contact.meta.primary_phone ? Span({}, contact.meta.primary_phone) : null,
          ]),
          Td({}, contact.meta.job_title ?? '-'),
          Td({}, contact.meta.department ?? '-'),
          Td({}, [
            Div({ className: 'row-actions' }, [
              Button({
                className: 'gh-button secondary text icon',
              }, icons.verticalDots),
            ]),
          ]),
        ])),
      ]),

    ])

  }

  const morphDirectory = () => morphdom(document.getElementById(), '')

  const manageStaffDirectory = async () => {

    let contacts = await ContactsStore.fetchItems({
      filters: [
        [
          {
            type: 'secondary_related',
            object_type: 'company',
            object_id: getCompany().ID,
          },
        ],
      ],
      limit: DirectoryState.per_page,
      offset: DirectoryState.per_page * DirectoryState.page,
    })

    DirectoryState.set({
      contacts,
    })

    morphdom(document.getElementById('directory'), Directory())

  }

  const CompanyInfo = () => {

    let hostname = ''

    try {
      let url = new URL(getCompany().data.domain)
      hostname = url.hostname
    }
    catch (e) {

    }

    return Div({
      className: 'display-grid inside gap-10',
    }, [

      makeEl('img', {
        className: 'quarter',
        src: `https://icons.duckduckgo.com/ip3/${ hostname }.ico`, width: 40, height: 40,
      }),

      Div({ className: 'span-9 display-flex gap-10 column' }, [
        `<h2 style="margin: 0">${ getCompany().data.name }</h2>`,
        `<a href="${ getCompany().data.domain }" target="_blank">${ getCompany().data.domain }</a>`,
        `<a href="tel:${ getCompany().meta.phone }">${ getCompany().meta.phone }</a>`,
        `<span>${ getCompany().meta.industry }</span>`,
        // `<span>${ getCompany().meta.industry }</span>`,
      ]),

      Div({
        className: 'full display-flex gap-10 center',
      }, [

        Button({
          className: 'gh-button secondary text icon',
        }, icons.contact),

        Button({
          className: 'gh-button secondary text icon',
        }, icons.phone),

        Button({
          className: 'gh-button secondary text icon',
        }, icons.email),

        Button({
          className: 'gh-button secondary text icon',
        }, icons.verticalDots),

      ]),

    ])
  }

  $(() => {

    morphdom(document.getElementById('company-info-card'), Div({}, CompanyInfo()), { childrenOnly: true })

    // manageCompany()
    companyTabs()
    manageStaffDirectory()
  })

} )(jQuery)
