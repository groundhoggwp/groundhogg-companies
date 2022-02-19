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
    textarea,
    bold,
  } = Groundhogg.element
  const { ajax } = Groundhogg.api
  const { fileUploader } = Groundhogg.components
  const {
    companies: CompaniesStore,
    contacts: ContactsStore,
  } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { formatNumber } = Groundhogg.formatting

  $(() => {

    let mappingGroups = {
      basic: __('Basic'),
    }

    let mappingFields = [
      {
        id: 'name',
        label: __('Name'),
        group: 'basic',
      },
      {
        id: 'domain',
        label: __('Website'),
        group: 'basic',
      },
      {
        id: 'address',
        label: __('Address'),
        group: 'basic',
      },
      {
        id: 'phone',
        label: __('Phone'),
        group: 'basic',
      },
      {
        id: 'industry',
        label: __('Industry'),
        group: 'basic',
      },
      {
        id: 'notes',
        label: __('Add to Notes'),
        group: 'basic',
      },

      {
        id: 'contacts',
        label: __('Add to Contacts'),
        group: 'basic',
      },
      ...GroundhoggCompanyProperties.fields.map(
        ({ name, group, label }) => ( { id: name, group, label } )),
    ]

    GroundhoggCompanyProperties.groups.forEach(g => {
      mappingGroups[g.id] = g.name
    })

    $('#export-companies').on('click', e => {

      progressModal({
        beforeProgress: () => {
          return `<h2>${ __('Exporting companies...',
            'groundhogg-companies') }</h2><p class="gh-text danger">${ __(
            'Do not close this window!') }</p>`
        },
        onOpen: ({ setProgress, close }) => {

          let offset = 0
          let limit = 500
          let totalItems
          let suffix = Date.now()

          const _export = () => {

            ajax({
              action: 'groundhogg_export_companies',
              offset,
              limit,
              suffix,
            }).then(r => {

              if (!r.success) {
                console.log(r)
                dialog({
                  message: __('Something went wrong...'),
                  type: 'error',
                })
                return
              }

              offset += limit

              setProgress(( offset / totalItems ) * 100)

              if (offset < totalItems) {
                _export()
                return
              }

              dialog({
                message: __('Export complete!', 'groundhogg-companies'),
              })

              let a = document.createElement('a')
              a.href = r.data.file
              a.click()
              close()
            })

          }

          CompaniesStore.count().then(t => {
            totalItems = t
            _export()
          })
        },
      })

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
<td><code>${ results[0][i] }</code></td>
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
                    type: 'checkbox',
                    id: 'link-similar',
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

      const mappingOnMount = ({ reRender, close }) => {

        $('#link-similar').on('change', e => {
          linkSimilar = e.target.checked
        })

        $('#start-import').on('click', e => {

          close()

          progressModal({
            beforeProgress: () => {
              return `<h2>${ __('Importing companies...',
                'groundhogg-companies') }</h2><p class="gh-text danger">${ __(
                'Do not close this window!') }</p>`
            },
            onOpen: ({ setProgress, close }) => {

              let offset = 0
              let limit = 100

              const _import = () => {

                ajax({
                  action: 'groundhogg_import_companies',
                  offset,
                  limit,
                  link_similar: linkSimilar,
                  map: JSON.stringify(map),
                  file: file.file,
                }).then(r => {

                  if (!r.success) {
                    console.log(r)
                    dialog({
                      message: __('Something went wrong...'),
                      type: 'error',
                    })
                    return
                  }

                  offset += limit

                  setProgress(( offset / totalItems ) * 100)

                  if (offset < totalItems) {
                    _import()
                    return
                  }

                  dialog({
                    message: __('Companies Imported!', 'groundhogg-companies'),
                  })

                  window.open(adminPageURL('gh_companies'), '_self')

                })

              }

              _import()

            },
          })

        })

        $('#cancel-import').on('click', e => close())

        $('.clear-selection').on('click', e => {
          delete map[e.currentTarget.dataset.index]
          reRender()
        })

        $('.map-field').on('click', e => {

          searchOptionsWidget({
            target: e.currentTarget,
            position: 'fixed',
            // filter out hidden codes
            options: mappingFields,
            groups: mappingGroups,
            filterOption: ({ label }, search) => label.match(regexp(search)),
            renderOption: (option) => option.label,
            onClose: () => {
            },
            onSelect: (option) => {
              map[e.currentTarget.dataset.index] = option.id
              reRender()
            },
            onOpen: () => {

            },
          }).mount()

        })

      }

      const { close } = fileUploader({
        action: 'groundhogg_company_upload_import_file',
        multiple: false,
        accept: '.csv',
        nonce: '',
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
            width: 600,
            canClose: false,
            content: mappingUI(),
            onOpen: ({ setContent, close }) => {

              const reRender = () => {
                setContent(mappingUI())
                mappingOnMount({ reRender, close })
              }

              reRender()
            },
          })

        },
      })
    })

    $('#add-company').on('click', e => {
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
                                id: 'company-name',
                                name: 'name',
                                dataType: 'data',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="website">${ __('Website') }</label>
                            ${ input({
                                id: 'company-website',
                                name: 'domain',
                                type: 'url',
                                dataType: 'data',
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="phone">${ __('Phone') }</label>
                            ${ input({
                                id: 'phone',
                                name: 'phone',
                                type: 'tel',
                                dataType: 'meta',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="industry">${ __('Industry') }</label>
                            ${ input({
                                id: 'industry',
                                name: 'industry',
                                dataType: 'meta',

                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="address">${ __('Address') }</label>
                            ${ textarea({
                                id: 'address',
                                name: 'address',
                                className: 'full-width',
                                dataType: 'meta',
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="owner">${ __('Owner') }</label>
                            ${ select({
                                id: 'owner',
                                name: 'owner_id',
                                dataType: 'data',
                            }, Groundhogg.filters.owners.map(u => ( {
                                text: `${ u.data.display_name } &lt;${ u.data.user_email }&gt;`,
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
        onOpen: ({ close }) => {

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
                ['domain', 'RLIKE', cleanDomain],
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

} )(jQuery)
