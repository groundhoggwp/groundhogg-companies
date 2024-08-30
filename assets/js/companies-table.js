( ($) => {

  const {
    input,
    select,
    dialog,
    modal,
    adminPageURL,
    regexp,
    searchOptionsWidget,
    progressModal,
    moreMenu,
    textarea,
    bold,
    dangerConfirmationModal,
    confirmationModal,
  } = Groundhogg.element
  const { userHasCap } = Groundhogg.user
  const { ajax } = Groundhogg.api
  const { fileUploader } = Groundhogg.components
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
  const { formatNumber } = Groundhogg.formatting

  $(() => {

    let mappingGroups = {
      basic: __('Basic'),
    }

    let mappingFields = [
      {
        id   : 'name',
        label: __('Name'),
        group: 'basic',
      },
      {
        id   : 'domain',
        label: __('Website'),
        group: 'basic',
      },
      {
        id   : 'address',
        label: __('Address'),
        group: 'basic',
      },
      {
        id   : 'phone',
        label: __('Phone'),
        group: 'basic',
      },
      {
        id   : 'industry',
        label: __('Industry'),
        group: 'basic',
      },
      {
        id   : 'notes',
        label: __('Add to Notes'),
        group: 'basic',
      },

      {
        id   : 'contacts',
        label: __('Add to Contacts'),
        group: 'basic',
      },
      ...GroundhoggCompanyProperties.fields.map(
        ({
          name,
          group,
          label,
        }) => ( {
          id: name,
          group,
          label,
        } )),
    ]

    GroundhoggCompanyProperties.groups.forEach(g => {
      mappingGroups[g.id] = g.name
    })

    $('#import-companies').on('click', e => {

      let results, headers, file, totalItems
      let map = {}
      let linkSimilar = false

      const mappingUI = () => {
        // language=HTML
        return `
            <div class="display-flex column gap-20">
                <h2>${ __('Map your CSV fields', 'groundhogg-companies') }</h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                    <tr>
                        <th>${ __('Header', 'groundhogg-companies') }</th>
                        <th>${ __('Example Data', 'groundhogg-companies') }</th>
                        <th>${ __('Map To', 'groundhogg-companies') }</th>
                    </tr>
                    </thead>
                    <tbody>
                    ${ headers.map((h, i) => {

                        // language=HTML
                        return `<tr>
<th>${ h }</th>
<td><code>${ results[0][h] }</code></td>
<td class="display-flex gap-10">
<button data-index="${ h }" class="gh-button full-width small dropdown map-field">${ map[h]
                                                                                     ? mappingFields.find(f => f.id == map[h]).label
                                                                                     : '-----' }</button>
${ map[h]
   ? `<button data-index="${ h }" class="gh-button secondary text icon small clear-selection"><span class="dashicons dashicons-no-alt"></span></button>`
   : '' }
</td></tr>`
                    }).join('') }
                    </tbody>
                </table>
                ${ Object.values(map).includes('domain') ? `<label>${ input({
                    type   : 'checkbox',
                    id     : 'link-similar',
                    checked: linkSimilar,
                }) } ${ __(
                        'Automatically associate contacts with email addresses similar to the company website?',
                        'groundhogg-companies') }</label>` : '' }
                <div class="display-flex flex-end gap-10">
                    <button id="cancel-import" class="gh-button danger text">
                        ${ __('Cancel') }
                    </button>
                    <button id="start-import" class="gh-button primary">
                        ${ sprintf(__('Import %s companies',
                                        'groundhogg-companies'),
                                formatNumber(totalItems)) }
                    </button>
                </div>
            </div>`
      }

      const mappingOnMount = ({
        reRender,
        close,
      }) => {

        $('#link-similar').on('change', e => {
          linkSimilar = e.target.checked
        })

        $('#start-import').on('click', e => {

          ajax({
            action      : 'groundhogg_import_companies',
            link_similar: linkSimilar,
            map         : JSON.stringify(map),
            file        : file,
          }).then(r => {

            if (!r.success) {
              console.log(r)
              dialog({
                message: __('Something went wrong...'),
                type   : 'error',
              })
              return
            }

            dialog({
              message: __('Companies are being imported!', 'groundhogg-companies'),
            })

            window.open(adminPageURL('gh_companies'), '_self')

          })

          close()

        })

        $('#cancel-import').on('click', e => close())

        $('.clear-selection').on('click', e => {
          delete map[e.currentTarget.dataset.index]
          reRender()
        })

        $('.map-field').on('click', e => {

          searchOptionsWidget({
            target  : e.currentTarget,
            position: 'fixed',
            // filter out hidden codes
            options     : mappingFields,
            groups      : mappingGroups,
            filterOption: ({ label }, search) => label.match(regexp(search)),
            renderOption: (option) => option.label,
            onClose     : () => {
            },
            onSelect    : (option) => {
              map[e.currentTarget.dataset.index] = option.id
              reRender()
            },
            onOpen      : () => {

            },
          }).mount()

        })

      }

      const { close } = fileUploader({
        action  : 'groundhogg_company_upload_import_file',
        multiple: false,
        accept  : '.csv',
        nonce   : '',
        onUpload: (json, _file) => {

          headers = json.data.headers
          results = json.data.results
          totalItems = json.data.total_items
          file = json.data.file

          headers.forEach(header => {

            let _h = header.toLowerCase().replace(/ /g, '_')
            let f = mappingFields.find(f => f.id === _h)

            if (f) {
              map[header] = f.id
            }

          })

          close()

          modal({
            width   : 600,
            canClose: false,
            content : mappingUI(),
            onOpen  : ({
              setContent,
              close,
            }) => {

              const reRender = () => {
                setContent(mappingUI())
                mappingOnMount({
                  reRender,
                  close,
                })
              }

              reRender()
            },
          })

        },
      })
    })

    const {
      getOwner,
      getCurrentUser,
    } = Groundhogg.user

    const {
      Modal,
      Fragment,
      Autocomplete,
      Div,
      Input,
      Textarea,
      ItemPicker,
      Label,
      Button,
      Dashicon,
    } = MakeEl

    document.getElementById('add-company').addEventListener('click', e => {

      e.preventDefault()

      const State = Groundhogg.createState({
        owner_id : getCurrentUser().ID,
        name     : '',
        industry : '',
        phone    : '',
        address  : '',
        domain   : '',
        duplicate: null,
      })

      Modal({
        closeButton: false,
      }, ({ close, morph }) => Fragment([
        Div({
          className: 'gh-header modal-header',
        }, [
          `<h3>Add Company</h3>`,
          Div({ className: 'actions align-right-space-between' }, [
            Button({
              className: 'gh-button dashicon no-border icon text quick-add-cancel',
              id       : 'close-quick-add',
              onClick  : e => close(),
            }, Dashicon('no-alt')),
          ]),
        ]),
        Div({
          className: 'display-grid gap-10',
          style    : {
            width: '400px',
          },
        }, [

          Div({
            className: 'full display-flex column gap-5',
          }, [
            Label({ for: 'company-name' }, __('Company Name')),
            Input({
              id     : 'company-name',
              name   : 'name',
              value  : State.name ?? '',
              onInput: e => State.set({ name: e.target.value }),
            }),
          ]),

          Div({
            className: 'half display-flex column gap-5',
          }, [
            Label({ for: 'company-website' }, __('Website <i>(with https://)</i>')),
            Input({
              id      : 'company-website',
              name    : 'domain',
              type    : 'url',
              value   : State.domain ?? '',
              onInput : e => State.set({
                domain: e.target.value.trim().replace( /\/$/, '' ),
                duplicate: null,
              }),
              onChange: e => {

                if ( State.domain === '' ){
                  morph()
                  return
                }

                CompaniesStore.fetchItems({
                  domain: State.domain,
                }).then(items => {
                  if (items.length) {
                    State.set({ duplicate: items[0] })
                  }
                  else {
                    State.set({ duplicate: null })
                  }

                  morph()
                })
              },
            }),
          ]),

          Div({
            className: 'half display-flex column gap-5',
          }, [
            Label({ for: 'company-industry' }, __('Industry')),
            Autocomplete({
              id          : 'company-industry',
              value       : State.industry ?? '',
              fetchResults: async (search) => {
                return Groundhogg.companyIndustries.filter(string => string.match(new RegExp(search, 'i'))).
                  map(s => ( {
                    id  : s,
                    text: s,
                  } ))
              },
              onInput     : e => State.set({ industry: e.target.value }),
            }),
          ]),


          ! State.duplicate ? null : Div({
            className: 'full',
          }, [
            `The domain <a href="${State.domain}">${State.domain}</a> is already being used by <a href="${State.duplicate.admin}">${State.duplicate.data.name}</a>.`
          ]),

          Div({
            className: 'full display-flex column gap-5',
          }, [
            Label({ for: 'company-phone' }, __('Phone Number')),
            Input({
              id     : 'company-phone',
              name   : 'phone',
              type   : 'tel',
              value  : State.phone ?? '',
              onInput: e => State.set({ phone: e.target.value }),
            }),
          ]),
          Div({
            className: 'full display-flex column gap-5',
          }, [
            Label({ for: 'company-address' }, __('Address')),
            Textarea({
              id     : 'company-address',
              name   : 'address',
              value  : State.address ?? '',
              onInput: e => State.set({ address: e.target.value }),
            }),
          ]),
          Div({
            className: 'full display-flex column gap-5',
          }, [
            Label({ for: 'select-owner' }, __('Owner')),
            ItemPicker({
              id          : `select-owner`,
              noneSelected: __('Select an owner...', 'groundhogg'),
              selected    : State.owner_id ? {
                id  : State.owner_id,
                text: getOwner(State.owner_id).data.display_name,
              } : [],
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
                State.set({
                  owner_id: item.id ?? null,
                })
              },
            }),
          ]),

          Div({
            className: 'full display-flex gap-10 flex-end',
          }, [
            Button({
              id       : 'cancel',
              className: 'gh-button danger text',
              onClick  : close,
            }, __('Cancel')),
            Button({
              // disabled : State.duplicate !== null,
              id       : 'create',
              className: 'gh-button primary',
              onClick  : async e => {

                const {
                  name,
                  industry,
                  address,
                  phone,
                  domain,
                } = State

                let company = await CompaniesStore.post({
                  data: {
                    name,
                    domain,
                  },
                  meta: {
                    industry,
                    address,
                    phone,
                  },
                })

                dialog({
                  message: 'Company created!',
                })

                window.open(company.admin)

              },
            }, __('Create Company')),
          ]),

        ]),
      ]))

    })

    $('#-add-company').on('click', e => {
      e.preventDefault()

      const addCompanyUI = () => {

        //language=HTML
        return `
            <div style="width: 500px">
                <h2 id="modal-header">
                    ${ __('Add Company', 'groundhogg-companies') }</h2>
                <div id="maybe-match"></div>
                <div class="gh-rows-and-columns">
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="name">${ __('Name') }</label>
                            ${ input({
                                id      : 'company-name',
                                name    : 'name',
                                dataType: 'data',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="website">${ __('Website') }</label>
                            ${ input({
                                id      : 'company-website',
                                name    : 'domain',
                                type    : 'url',
                                dataType: 'data',
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="phone">${ __('Phone') }</label>
                            ${ input({
                                id      : 'phone',
                                name    : 'phone',
                                type    : 'tel',
                                dataType: 'meta',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="industry">${ __('Industry') }</label>
                            ${ input({
                                id      : 'industry',
                                name    : 'industry',
                                dataType: 'meta',

                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="address">${ __('Address') }</label>
                            ${ textarea({
                                id       : 'address',
                                name     : 'address',
                                className: 'full-width',
                                dataType : 'meta',
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="owner">${ __('Owner') }</label>
                            ${ select({
                                id      : 'owner',
                                name    : 'owner_id',
                                dataType: 'data',
                            }, Groundhogg.filters.owners.map(u => ( {
                                text : `${ u.data.display_name } &lt;${ u.data.user_email }&gt;`,
                                value: u.ID,
                            } ))) }
                        </div>
                        <div class="gh-col">

                        </div>
                    </div>
                </div>
                <p>
                    <button id="save-changes" class="gh-button primary">
                        ${ __('Add') }
                    </button>
                    <button id="cancel-changes" class="gh-button danger text">
                        ${ __('Cancel') }
                    </button>
                </p>
            </div>`
      }

      modal({
        content: addCompanyUI(),
        onOpen : ({ close }) => {

          let changes = {
            data: {},
            meta: {},
          }

          const commitChanges = () => {
            return CompaniesStore.post(changes)
          }

          $('#industry').autocomplete({
            source: Groundhogg.companyIndustries,
          })

          $('#company-name, #company-website, #address, #phone, #industry, #owner').
            on('input change', e => {
              changes[e.target.dataset.type] = {
                ...changes[e.target.dataset.type],
                [e.target.name]: e.target.value,
              }
            })

          $('#company-name').on('change', e => {

            let newName = e.target.value

            CompaniesStore.fetchItems({
              s: newName,
            }).then(items => {
              if (items.length) {

                // language=HTML
                let notice = `<p>
					<span
                  class="pill yellow">${ sprintf(
                  _n('A company with the name %2$s already exists. Would you like to edit it instead?',
                          'We found %1$d companies with similar names to %2$s.',
                          items.length, 'groundhogg-companies'), items.length,
                  bold(items.length === 1 ? items[0].data.name : newName)) }
					<a href="${ items.length === 1 ? items[0].admin : adminPageURL(
                  'gh_companies', { s: newName }) }">${ items.length > 1
                                                        ? sprintf(__('View %d similar companies'), items.length)
                                                        : sprintf(__('Edit %s'), items[0].data.name) }</a></span>
                </p>`

                $('#maybe-match').html(notice)
              }
              else {
                $('#maybe-match').html('')
              }
            })

          })

          $('#company-website[name="domain"]').on('change', e => {

            let newDomain = e.target.value
            let cleanDomain = newDomain.replace('https://', '').
              replace('http://', '').
              replace('www.', '')

            if (!newDomain) {
              return
            }

            CompaniesStore.fetchItems({
              where: [
                [
                  'domain',
                  'RLIKE',
                  cleanDomain,
                ],
              ],
            }).then(items => {
              if (items.length) {

                // language=HTML
                let notice = `<p>
					<span
                  class="pill yellow">${ sprintf(
                  _n('A company with the domain %2$s already exists. Would you like to edit it instead?',
                          'We found %1$d companies with similar domains to %2$s.',
                          items.length, 'groundhogg-companies'), items.length,
                  bold(items.length === 1 ? items[0].data.domain : newDomain)) }
					<a href="${ items.length === 1 ? items[0].admin : adminPageURL(
                  'gh_companies', { s: cleanDomain }) }">${ items.length > 1
                                                            ? sprintf(__('View %d similar companies'), items.length)
                                                            : sprintf(__('Edit %s'), items[0].data.name) }</a></span>
                </p>`

                $('#maybe-match').html(notice)
              }
              else {
                $('#maybe-match').html('')
              }
            })

          })

          $('#save-changes').on('click', e => {

            commitChanges().then((c) => {
              dialog({
                message: __('Company created!'),
              })

              window.location.href = c.admin

            })

          })

          $('#cancel-changes').on('click', e => {
            close()
          })

        },
      })
    })
  })

  const { base64_json_encode } = Groundhogg.functions

  $(() => {

    $('.bulkactions').
      append(
        `<button type="button" class="more-actions button button-secondary">${ __(
          'More Actions', 'groundhogg') }</button>`)
    $('.more-actions').on('click', (e) => {

      const {
        total_items          : totalItems,
        total_items_formatted: totalItemsFormatted,
        query                : CompanyQuery,
      } = CurrentTable

      let {
        number,
        offset,
        paged,
        ...query
      } = CompanyQuery

      const primaryContactsFilter = base64_json_encode([
        [
          {
            type: 'company_primary_contacts',
            query,
            totalItems,
          },
        ],
      ])

      const allContactsFilter = base64_json_encode([
        [
          {
            type: 'company_all_contacts',
            query,
            totalItems,
          },
        ],
      ])

      const items = [
        {
          key     : 'primary_contacts',
          cap     : 'view_companies',
          text    : __('View primary contacts', 'groundhogg'),
          onSelect: () => {
            window.location.href = adminPageURL('gh_contacts', {
              filters: primaryContactsFilter,
            })
          },
        },
        {
          key     : 'all_contacts',
          cap     : 'view_companies',
          text    : __('View all related contacts', 'groundhogg'),
          onSelect: () => {
            window.location.href = adminPageURL('gh_contacts', {
              filters: allContactsFilter,
            })
          },
        },
        {
          key     : 'edit',
          cap     : 'edit_companies',
          text    : sprintf(__('Edit %s companies', 'groundhogg'),
            totalItemsFormatted),
          onSelect: () => {},
        },
        {
          key     : 'export',
          cap     : 'export_contacts',
          text    : sprintf(__('Export %s companies', 'groundhogg'),
            totalItemsFormatted),
          onSelect: () => {

            const {
              include_filters,
              limit,
              offset,
              ...query_args
            } = query

            window.location.href = adminPageURL('gh_companies', {
              action  : 'export',
              _wpnonce: Groundhogg.nonces._wpnonce,
              query   : {
                ...query_args,
                include_filters: base64_json_encode(query.include_filters),
              },
            })
          },
        },
        {
          key     : 'broadcast',
          cap     : 'schedule_broadcasts',
          text    : sprintf(__('Send a broadcast to %s companies', 'groundhogg'),
            totalItemsFormatted),
          onSelect: () => {

            modal({
              //language=HTML
              content: `<h2>${ __('Send a broadcast', 'groundhogg') }</h2>
              <div id="gh-broadcast-form"></div>`,
              onOpen : () => {
                document.getElementById('gh-broadcast-form').append(Groundhogg.BroadcastScheduler({
                  searchMethod : 'company-primary-contacts',
                  searchMethods: [
                    {
                      id   : 'company-primary-contacts',
                      text : sprintf(__('Primary contacts for the selected %s companies', 'groundhogg'), totalItemsFormatted),
                      query: () => ( {
                        filters: primaryContactsFilter,
                      } ),
                    },
                    {
                      id   : 'company-all-contacts',
                      text : sprintf(__('All associated contacts for the selected %s companies', 'groundhogg'), totalItemsFormatted),
                      query: () => ( {
                        filters: allContactsFilter,
                      } ),
                    },
                  ],
                }))
              },
            })

          },
        },
        {
          key     : 'funnel',
          cap     : 'view_funnels',
          text    : sprintf(__('Add %s companies to a funnel', 'groundhogg'),
            totalItemsFormatted),
          onSelect: () => {

            modal({
              //language=HTML
              content: `<h2>${ __('Add companies to a funnel', 'groundhogg') }</h2>
              <div id="gh-add-to-funnel" style="width: 500px"></div>`,
              onOpen : () => {
                document.getElementById('gh-add-to-funnel').append(Groundhogg.FunnelScheduler({
                  // totalContacts,
                  searchMethod : 'company-primary-contacts',
                  searchMethods: [
                    {
                      id   : 'company-primary-contacts',
                      text : sprintf(__('Primary contacts for the selected %s companies', 'groundhogg'), totalItemsFormatted),
                      query: () => ( {
                        filters: [
                          [
                            {
                              type: 'company_primary_contacts',
                              query,
                            },
                          ],
                        ],
                      } ),
                    },
                    {
                      id   : 'company-all-contacts',
                      text : sprintf(__('All associated contacts for the selected %s companies', 'groundhogg'), totalItemsFormatted),
                      query: () => ( {
                        filters: [
                          [
                            {
                              type: 'company_all_contacts',
                              query,
                            },
                          ],
                        ],
                      } ),
                    },
                  ],
                }))
              },
            })

          },
        },
        {
          key     : 'delete',
          cap     : 'delete_companies',
          text    : `<span class="gh-text danger">${ sprintf(
            __('Delete %s companies', 'groundhogg'),
            totalItemsFormatted) }</span>`,
          onSelect: () => {

            dangerConfirmationModal({
              width    : 600,
              alert    : `<p>${ sprintf(__(
                'Are you sure you want to delete %s companies? This cannot be undone. Consider <i>exporting</i> first!',
                'groundhogg'), `<b>${ totalItemsFormatted }</b>`) }</p>`,
              onConfirm: () => {

                CompaniesStore.deleteMany({
                  ...query,
                  bg: true,
                }).then(r => {

                  confirmationModal({
                    width           : 600,
                    alert           : `<p>${ sprintf(__(
                        '🗑️ %s companies are being deleted in the background. <i>It may take a while.</i> We\'ll let you know when it\'s done!', 'groundhogg'),
                      `<b>${ totalItemsFormatted }</b>`) }</p>`,
                    cancelButtonType: 'hidden',
                    confirmText     : __('Sounds good!', 'groundhogg'),
                  })

                }).catch(err => {
                  dialog({
                    message: err.message,
                    type   : 'error',
                  })
                })
              },
            })

          },
        },
      ].filter(i => userHasCap(i.cap))

      moreMenu(e.currentTarget, items)
    })
  })

} )(jQuery)
