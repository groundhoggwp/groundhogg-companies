(($) => {

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
    confirmationModal
  } = Groundhogg.element
  const { companies: CompaniesStore, contacts: ContactsStore } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { currentUser, propertiesEditor, noteEditor } = Groundhogg
  const { userHasCap } = Groundhogg.user
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
      cc: relatedContacts.filter(c => c.data.owner_id && c.data.owner_id != currentUser.ID).map(c => Groundhogg.filters.owners.find(u => u.ID == c.data.owner_id).data.user_email).filter((v, i, a) => a.indexOf(v) === i),
    }

    Groundhogg.components.emailModal(email)
  }

  CompaniesStore.itemsFetched([company])

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

  const manageCompany = () => {

    let editing = false

    const companyUI = () => {

      let { address = '', phone = '', industry = '' } = getCompany().meta

      if (editing) {

        // language=HTML
        return `
			<div class="inside">
				<div class="gh-rows-and-columns">
					<div class="gh-row">
						<div class="gh-col">
							<label for="name">${__('Name')}</label>
							${input({
								id: 'company-name',
								name: 'name',
								value: getCompany().data.name,
								dataType: 'data',
								placeholder: __('Media Inc.')
							})}
						</div>
						<div class="gh-col">
							<label for="website">${__('Website')}</label>
							${input({
								id: 'website',
								name: 'domain',
								type: 'url',
								value: getCompany().data.domain,
								dataType: 'data',
								placeholder: __('https://example.com')
							})}
						</div>
					</div>
					<div class="gh-row">
						<div class="gh-col">
							<label for="phone">${__('Phone')}</label>
							${input({
								id: 'phone',
								name: 'phone',
								value: phone,
								type: 'tel',
								dataType: 'meta',
								placeholder: __('+1 (555) 555-5555')
							})}
						</div>
						<div class="gh-col">
							<label for="industry">${__('Industry')}</label>
							${input({
								id: 'industry',
								name: 'industry',
								value: industry,
								dataType: 'meta',
								placeholder: __('Agriculture', 'groundhogg')
							})}
						</div>
					</div>
					<div class="gh-row">
						<div class="gh-col">
							<label for="address">${__('Address')}</label>
							${textarea({
								id: 'address',
								name: 'address',
								className: 'full-width',
								value: address,
								dataType: 'meta',
							})}
						</div>
					</div>
					<div class="gh-row">
						<div class="gh-col">
							<label for="owner">${__('Owner')}</label>
							${select({
								id: 'owner',
								name: 'owner_id',
								dataType: 'data',
								className: 'full-width'
							}, Groundhogg.filters.owners.map(u => ({
								text: `${u.data.display_name} &lt;${u.data.user_email}&gt;`,
								value: u.ID
							})), parseInt(getCompany().data.owner_id))}
						</div>
					</div>
				</div>
				<p class="space-between align-right gap-10">
					<button id="cancel-changes" class="gh-button danger text">${__('Cancel')}</button>
					<button id="save-changes" class="gh-button primary">${__('Save Changes')}</button>
				</p>
			</div>`
      }

      let hostname

      try {
        hostname = new URL(getCompany().data.domain).host
      } catch (e) {
        // nothing
      }

      // language=HTML
      return `
		  <div class="inside">
			  ${getCompany().logo ? `<div class="upload-image has-logo"><img class="logo" src="${getCompany().logo}" alt="${__('Company logo', 'groundhogg-companies')}" title="${getCompany().data.name}"></div>` : `<div class="upload-image">${icons.company}</div>`}
			  <div class="company-details">
				  <h1 class="name">${getCompany().data.name}</h1>
				  <div class="industry ${industry ? '' : 'hidden'}"><span
					  class="dashicons dashicons-building"></span>${industry}
				  </div>
				  <div class="website ${hostname ? '' : 'hidden'}"><span
					  class="dashicons dashicons-welcome-view-site"></span><a
					  href="${getCompany().data.domain}" target="_blank">${hostname ? hostname : ''}</a>
				  </div>
				  <div class="phone ${phone ? '' : 'hidden'}"><span class="dashicons dashicons-phone"></span><a
					  href="tel:${phone}">${phone}</a></div>
				  <div class="address ${address ? '' : 'hidden'}"><span class="dashicons dashicons-location"></span><a
					  target="_blank" href="http://maps.google.com/?q=${address}">${address}</a>
				  </div>
			  </div>
			  <div class="space-between align-right">
				  <button id="edit-company" class="gh-button secondary text icon"><span
					  class="dashicons dashicons-edit"></span></button>
				  <button id="company-more" class="gh-button secondary text icon">${icons.verticalDots}</button>
			  </div>
		  </div>`
    }

    const mount = () => {
      $('#primary #form').html(companyUI())
      onMount()

    }

    const onMount = () => {

      let changes = {
        data: {},
        meta: {}
      }

      const commitChanges = () => {
        return CompaniesStore.patch(company.ID, changes)
      }

      $('#industry').autocomplete({
        source: Groundhogg.companyIndustries
      })

      $('#company-name, #website, #address, #phone, #industry, #owner').on('input change', e => {
        changes[e.target.dataset.type] = {
          ...changes[e.target.dataset.type],
          [e.target.name]: e.target.value
        }
      })

      $('#company-name').on('change', e => {

        let newName = e.target.value

        if (getCompany().data.name === newName) {
          return
        }

        CompaniesStore.fetchItems({
          name: newName
        }).then(items => {
          if (items.length) {
            alert(sprintf(__('Another company already exists with the name %s'), newName))
          }
        })

      })

      tooltip('#edit-company', {
        content: __('Edit Details', 'groundhogg')
      })

      tooltip('#company-more', {
        content: __('More Actions', 'groundhogg')
      })

      $('#edit-company').on('click', e => {

        editing = true

        mount()

      })

      $('#company-more').on('click', e => {

        moreMenu(e.currentTarget, {
          items: [
            // {
            //   key: 'merge',
            //   text: __('Merge'),
            // },
            {
              key: 'delete',
              text: `<span class="gh-text danger">${__('Delete')}</span>`,
            }
          ],
          onSelect: k => {
            switch (k) {
              case 'delete':

                dangerConfirmationModal({
                  alert: `<p>${sprintf(__('Are you sure you want to delete %s?', 'groundhogg-companies'), bold(getCompany().data.name))}</p>`,
                  onConfirm: () => {
                    CompaniesStore.delete(company.ID).then( () => {
                      window.open( adminPageURL( 'gh_companies' ), '_self' )
                    })
                  }
                })

                break

            }
          }
        })

      })

      $('#save-changes').on('click', e => {

        commitChanges().then(() => {
          editing = false
          dialog({
            message: __('Changes saved!')
          })

          mount()
        })

      })

      $('#cancel-changes').on('click', e => {

        editing = false

        mount()

      })

      // Uploading files
      var file_frame

      tooltip('.upload-image', {
        content: __('Change Logo', 'groundhogg-companies')
      })

      $('.upload-image').on('click', (event) => {

        event.preventDefault()
        // If the media frame already exists, reopen it.
        if (file_frame) {
          // Open frame
          file_frame.open()
          return
        }
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
          title: __('Select a image to upload'),
          button: {
            text: __('Use this image'),
          },
          multiple: false	// Set to true to allow multiple files to be selected

        })

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
          // We set multiple to false so only get one image from the uploader
          var attachment = file_frame.state().get('selection').first().toJSON()

          CompaniesStore.patchMeta(company.ID, {
            logo: attachment.url
          }).then(mount)
        })
        // Finally, open the modal
        file_frame.open()
      })
    }

    mount()
  }

  let files = []

  const companyTabs = () => {

    let activeTab = 'notes'

    let tabs = [
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
        }
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
						  ${input({
							  placeholder: __('Search files...'),
							  type: 'search',
							  id: 'search-files'
						  })}
						  <button id="upload-file" class="gh-button secondary">${__('Upload Files')}</button>
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
              alert: `<p>${sprintf(_n('Are you sure you want to delete %d file?', 'Are you sure you want to delete %d files?', selectedFiles.length, 'groundhogg'), selectedFiles.length)}</p>`,
              onConfirm: () => {
                _delete(`${CompaniesStore.route}/${company.ID}/files`, selectedFiles).then(({ items }) => {
                  selectedFiles = []
                  files = items
                  mount()
                })
              }
            })
          })

          $('#search-files').on('input change', e => {
            fileSearch = e.target.value
            mount()
          })

          tooltip('#bulk-delete-files', {
            content: __('Bulk delete files'),
            position: 'right'
          })

          const renderFile = (file) => {
            //language=HTML
            return `
				<tr class="file">
					<th scope="row" class="check-column">${input({
						type: 'checkbox',
						name: 'select[]',
						className: 'file-toggle',
						value: file.name
					})}
					</th>
					<td class="column-primary"><a class="row-title" href="${file.url}"
					                              target="_blank">${file.name}</a></td>
					<td>${file.date_modified}</td>
					<td>
						<div class="space-between align-right">
							<button data-file="${file.name}" class="file-more gh-button secondary text icon">
								${icons.verticalDots}
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
              } else {
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
                    text: `<span class="gh-text danger">${__('Delete')}</span>`,
                  }
                ],
                onSelect: k => {
                  switch (k) {
                    case 'download':
                      window.open(files.find(f => f.name === _file).url, '_blank').focus()
                      break
                    case 'delete':

                      dangerConfirmationModal({
                        confirmText: __('Delete'),
                        alert: `<p>${sprintf(__('Are you sure you want to delete %s?', 'groundhogg'), _file)}</p>`,
                        onConfirm: () => {
                          _delete(`${CompaniesStore.route}/${company.ID}/files`, [
                            _file
                          ]).then(({ items }) => {
                            selectedFiles = []
                            files = items
                            mount()
                          })
                        }
                      })

                      break
                  }
                }
              })
            })

            $('.file-toggle').on('change', e => {
              if (e.target.checked) {
                selectedFiles.push(e.target.value)
              } else {
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
              }
            })
          })

          if (!files.length) {
            CompaniesStore.fetchFiles(company.ID).then(_files => {
              files = _files
              mount()
            })
          }

          mount()

        }
      },
    ]

    let customTabState = gh_company_custom_properties || {
      tabs: [],
      fields: [],
      groups: [],
    }

    let timeout
    let metaChanges = {}
    let deleteKeys = []

    const commitMetaChanges = () => {

      let { stop } = loadingDots('#save-meta')
      $('#save-meta').prop('disabled', true)

      Promise.all([
        CompaniesStore.patchMeta(getCompany().ID, metaChanges),
        CompaniesStore.deleteMeta(getCompany().ID, deleteKeys)
      ]).then(() => {

        metaChanges = {}
        deleteKeys = []

        stop()
        mount()
        dialog({
          message: __('Changes saved!')
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
          gh_company_custom_properties: customTabState
        }).then(() => {
          dialog({
            message: __('Changes saved!', 'groundhogg')
          })
        })
      }, 1500)

    }

    const allTabs = () => {
      return [
        ...tabs,
        ...customTabState.tabs.map(t => ({
          id: t.id,
          isCustom: true,
          name: t.name,
          render: () => `<div class="tab-content-wrapper custom-tab gh-panel top-left-square active" data-tab-content="${activeTab}">
				<div class="inside">
					<div id="custom-fields-here">
					</div>
					<p>
						<button id="save-meta" class="gh-button primary">${__('Save Changes')}</button>
						<button id="cancel-meta-changes" class="gh-button danger text">${__('Cancel')}</button>
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
                    text: __('Rename')
                  },
                  {
                    key: 'delete',
                    cap: 'manage_options',
                    text: `<span class="gh-text danger">${__('Delete')}</span>`
                  }
                ],
                onSelect: k => {

                  switch (k) {

                    case 'delete':

                      dangerConfirmationModal({
                        confirmText: __('Delete'),
                        alert: `<p>${sprintf(__('Are you sure you want to delete %s?', 'groundhogg'), bold(customTabState.tabs.find(t => t.id === activeTab).name))}</p>`,
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
                        }
                      })

                      break

                    case 'rename':
                      modal({
                        // language=HTML
                        content: `
							<div>
								<h2>${__('Rename tab', 'groundhogg')}</h2>
								<div class="align-left-space-between">
									${input({
										id: 'tab-name',
										value: customTabState.tabs.find(t => t.id === activeTab).name,
										placeholder: __('Tab name', 'groundhogg')
									})}
									<button id="update-tab" class="gh-button primary">
										${__('Save')}
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

                        }
                      })
                      break

                  }

                }
              })
            })

            propertiesEditor('#custom-fields-here', {
              values: {
                ...getCompany().meta,
                ...metaChanges,
              },
              properties: {
                groups,
                fields
              },
              onPropertiesUpdated: ({ groups = [], fields = [] }) => {

                customTabState.groups = [
                  ...groups.map(g => ({ ...g, tab: activeTab })), // new items
                  ...customTabState.groups.filter(group => !groups.find(_group => _group.id == group.id))
                ]

                customTabState.fields = [
                  ...fields, // new items
                  ...customTabState.fields.filter(field => !fields.find(_field => _field.id == field.id))
                ]

                updateTabState()

              },
              onChange: (meta) => {
                metaChanges = {
                  ...metaChanges,
                  ...meta
                }
              },
              canEdit: () => {
                return userHasCap('edit_companies')
              }

            })
          }
        }))
      ]
    }

    const template = () => {

      let _tabs = allTabs()

      // language=HTML
      return `
		  <div id="primary-tabs">
			  <h2 class="nav-tab-wrapper primary gh">
				  ${_tabs.map(({
					  id,
					  name
				  }) => `<a href="#" data-tab="${id}" class="nav-tab ${activeTab === id ? 'nav-tab-active' : ''}">${name}</a>`).join('')}
				  <div id="tab-actions" class="space-between">
					  <button type="button" id="add-tab"><span
						  class="dashicons dashicons-plus-alt2"></span>
					  </button>
					  ${_tabs.find(t => t.id === activeTab).isCustom ? `<button class="gh-button tab-more secondary text icon" type="button" id="tab-more">${icons.verticalDots}</button>` : ''}
				  </div>
			  </h2>
			  ${_tabs.find(t => t.id === activeTab).render()}
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
        position: 'right'
      })

      $('#add-tab').on('click', (e) => {
        e.preventDefault()

        modal({
          // language=HTML
          content: `
			  <div>
				  <h2>${__('Add a new tab', 'groundhogg')}</h2>
				  <div class="align-left-space-between">
					  ${input({
						  id: 'tab-name',
						  placeholder: __('Tab name', 'groundhogg')
					  })}
					  <button id="create-tab" class="gh-button primary">
						  ${__('Create')}
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
                name: tabName
              })

              activeTab = id
              updateTabState()

              mount()
              close()

            })

          }
        })
      })
    }

    mount()

  }

  const manageStaffDirectory = () => {

    //language=HTML
    let actionsUI = `
		${input({
			id: 'search-company-contacts',
			placeholder: __('Search by name, email, phone, or position...', 'groundhogg'),
			type: 'search'
		})}
		<button id="email-all" class="gh-button secondary text icon">${icons.email}</button>
		<button id="add-contact-to-company" class="gh-button secondary text icon"><span
			class="dashicons dashicons-plus-alt2"></span></button>
    `

    $('.directory-actions').html(actionsUI)

    let search = ''

    $('#search-company-contacts').on('input change', e => {
      search = e.target.value
      mount()
    })

    $('#email-all').on('click', emailAll)

    tooltip('#email-all', {
      content: __('Email All', 'groundhogg')
    })

    tooltip('#add-contact-to-company', {
      content: __('Add Contact', 'groundhogg')
    })

    const addToCompany = (e) => {
      moreMenu(e.currentTarget, {
        items: [{
          key: 'select',
          cap: 'view_contacts',
          text: __('Select an existing contact', 'groundhogg-pipeline')
        }, {
          key: 'add',
          cap: 'add_contacts',
          text: __('Create a new contact', 'groundhogg-pipeline')
        }].filter(i => userHasCap(i.cap)),
        onSelect: (k) => {
          switch (k) {
            case 'select':
              selectContactModal({
                exclude: relatedContacts.map(c => c.ID),
                onSelect: (contact) => {
                  CompaniesStore.createRelationships(company.ID, {
                    other_type: 'contact',
                    other_id: contact.ID
                  }).then(() => {
                    relatedContacts.push(contact)
                    mount()
                  })
                }
              })
              break
            case 'add':
              addContactModal({
                additionalFields: ({ prefix }) => {

                  // language=HTML
                  return `
					  <div class="gh-row">
						  <div class="gh-col">
							  <label>${__('Position', 'groundhogg-companies')}</label>
							  ${input({
								  id: `${prefix}-job-title`,
							  })}
						  </div>
						  <div class="gh-col">
							  <label>${__('Department', 'groundhogg-companies')}</label>
							  ${input({
								  id: `${prefix}-company-department`,
							  })}
						  </div>
						  <div class="gh-col">
							  <label>${__('Work Phone', 'groundhogg-companies')}</label>
							  ${input({
								  id: `${prefix}-work-phone`,
								  type: 'tel'
							  })}
						  </div>
					  </div>`

                },
                additionalFieldsOnMount: ({ prefix, setPayload, getPayload }) => {
                  $(`#${prefix}-company-department`).autocomplete({
                    source: Groundhogg.companyDepartments
                  })
                  $(`#${prefix}-company-department, #${prefix}-work-phone`).on('change input', (e) => {
                    setPayload({
                      meta: {
                        ...getPayload().meta,
                        [e.target.name]: e.target.value,
                      }
                    })
                  })

                  $(`#${prefix}-job-title`).autocomplete({
                    source: Groundhogg.companyPositions
                  }).on('change', (e) => {

                    setPayload({
                      meta: {
                        ...getPayload().meta,
                        job_title: e.target.value,
                      }
                    })

                  })
                },
                onCreate: (contact) => {
                  CompaniesStore.createRelationships(company.ID, {
                    other_type: 'contact',
                    other_id: contact.ID
                  }).then(() => {
                    relatedContacts.push(contact)
                    mount()
                  })
                }
              })

              break
          }
        }
      })
    }

    $('#add-contact-to-company').on('click', addToCompany)

    const contactCardUI = (contact) => {

      let { primary_phone = '', mobile_phone = '', company_phone = '' } = contact.meta

      //language=HTML
      return `
		  <div class="contact gh-panel">
			  <div style="padding: 10px" class="align-left space-between gap-10 align-top">
				  <img width="40" class="avatar" src="${contact.data.gravatar}"/>
				  <div class="details">
					  <div class="name">${contact.data.full_name}
						  ${contact.meta.job_title ? `| ${contact.meta.job_title}` : ''}
					  </div>
					  <div class="email">${contact.data.email}</div>
					  <div class="phone ${primary_phone || mobile_phone || company_phone ? '' : 'hidden'}">
						  ${primary_phone ? `<span class="dashicons dashicons-phone"></span><a href="tel:${primary_phone}">${primary_phone}</a>` : ''}
						  ${mobile_phone ? `<span class="dashicons dashicons-smartphone"></span><a href="tel:${mobile_phone}">${mobile_phone}</a>` : ''}
						  ${company_phone ? `<span class="dashicons dashicons-building"></span><a href="tel:${company_phone}">${company_phone}</a>` : ''}
					  </div>
				  </div>
				  <div class="space-between align-right no-gap actions">
					  <button data-id="${contact.ID}" class="gh-button secondary text icon send-email">${icons.email}
					  </button>
					  <button data-id="${contact.ID}" class="gh-button secondary text icon contact-more">
						  ${icons.verticalDots}
					  </button>
				  </div>
			  </div>
		  </div>`
    }

    let fetched = false

    const noContactsUI = () => {

      if (!fetched) {
        return spinner()
      }

      // language=HTML
      return `
		  <div class="no-contacts">
			  <div>
				  <div class="contact">
					  ${icons.contact}
				  </div>
				  <p>${__('No related contacts')}</p>
				  <button class="gh-button primary" id="add-to-company-none">${__('Add a contact!')}</button>
			  </div>

		  </div>`
    }

    const mount = () => {

      $('#directory').html(relatedContacts.length === 0 ? noContactsUI() : relatedContacts.filter(c => {

        let __regex = regexp(search)

        let { job_title = '', primary_phone = '', mobile_phone = '', company_phone = '' } = c.meta

        return c.data.full_name.match(__regex)
          || c.data.email.match(__regex)
          || primary_phone.match(__regex)
          || mobile_phone.match(__regex)
          || company_phone.match(__regex)
          || job_title.match(__regex)

      }).map(c => contactCardUI(c)).join(''))

      onMount()
    }

    const onMount = () => {

      $('#add-to-company-none').on('click', addToCompany)

      $('.send-email').on('click', (e) => {
        sendEmail(ContactsStore.get(e.currentTarget.dataset.id))
      })

      $('.gh-button.contact-more').on('click', (e) => {

        let contact = ContactsStore.get(e.currentTarget.dataset.id)

        moreMenu(e.currentTarget, {
          items: [
            {
              key: 'view',
              cap: 'view_contacts',
              text: __('View profile', 'groundhogg')
            },
            {
              key: 'edit',
              cap: 'edit_contacts',
              text: __('Quick edit', 'groundhogg')
            },
            {
              key: 'remove',
              cap: 'edit_deals',
              text: `<span class="gh-text danger">${__('Remove from company', 'groundhogg-companies')}</span>`
            }
          ].filter(i => i != null).filter(i => userHasCap(i.cap)),
          onSelect: k => {

            switch (k) {
              case 'edit':

                quickEditContactModal({
                  contact,
                  additionalFields: ({ prefix, contact }) => {

                    let { job_title = '', company_department = '', company_phone = '' } = contact.meta

                    // language=HTML
                    return `
						<div class="gh-row">
							<div class="gh-col">
								<label>${__('Position', 'groundhogg-companies')}</label>
								${input({
									id: `${prefix}-job-title`,
									value: job_title
								})}
							</div>
							<div class="gh-col">
								<label>${__('Department', 'groundhogg-companies')}</label>
								${input({
									id: `${prefix}-company-department`,
									name: 'company_department',
									value: company_department,
								})}
							</div>
							<div class="gh-col">
								<label>${__('Work Phone', 'groundhogg-companies')}</label>
								${input({
									id: `${prefix}-work-phone`,
									type: 'tel',
									name: 'company_phone',
									value: company_phone
								})}
							</div>
						</div>`

                  },
                  additionalFieldsOnMount: ({ prefix, contact, setPayload, getPayload }) => {
                    $(`#${prefix}-company-department`).autocomplete({
                      source: Groundhogg.companyDepartments
                    })
                    $(`#${prefix}-company-department, #${prefix}-work-phone`).on('change input', (e) => {
                      setPayload({
                        meta: {
                          ...getPayload().meta,
                          [e.target.name]: e.target.value,
                        }
                      })
                    })
                    $(`#${prefix}-job-title`).autocomplete({
                      source: Groundhogg.companyPositions
                    }).on('change', (e) => {

                      setPayload({
                        meta: {
                          ...getPayload().meta,
                          job_title: e.target.value,
                        }
                      })

                    })
                  },
                  onEdit: (c) => {

                    relatedContacts = relatedContacts.map(_c => _c.ID === c.ID ? c : _c)

                    dialog({
                      message: __('Contact updated!', 'groundhogg')
                    })
                    mount()
                  }
                })

                break
              case 'view':
                window.open(contact.admin)
                break
              case 'remove':

                dangerConfirmationModal({
                  confirmText: __('Remove'),
                  alert: `<p>${sprintf(__('Are you sure you want to remove %s from %s?', 'groundhogg'), bold(contact.data.full_name), bold(getCompany().data.name))}</p>`,
                  onConfirm: () => {
                    CompaniesStore.deleteRelationships(company.ID, {
                      other_id: contact.ID,
                      other_type: 'contact'
                    }).then(() => {
                      relatedContacts.splice(relatedContacts.findIndex(c => c.ID === contact.ID), 1)
                      mount()
                      dialog({
                        message: sprintf(__('%s was removed!', 'groundhogg'), contact.data.full_name)
                      })
                    })
                  }
                })

                break
            }
          }
        })
      })

    }

    const checkForPotentialContactMatches = () => {
      let domain

      try {
        domain = getCompany().data.domain ? new URL(getCompany().data.domain).host : ''
      } catch (e) {
        return
      }

      if (!domain) {
        return
      }

      ContactsStore.fetchItems({
        exclude: relatedContacts.map(c => c.ID),
        filters: [
          [{
            type: 'email',
            compare: 'ends_with',
            value: '@' + domain
          }]
        ]
      }).then(contacts => {

        if (contacts.length) {

          confirmationModal({
            width: 500,
            alert: `<p>${sprintf(_n('We found %s contact that has an email address ending with %s. Would you like to relate them to this company?', 'We found %s contacts that have an email address ending with %s. Would you like to relate them to this company?', contacts.length, 'groundhogg-companies'), bold(formatNumber(contacts.length)), bold('@' + domain))}</p>`,
            confirmText: __('Yes, add them!'),
            cancelText: __('No'),
            onConfirm: () => {

              CompaniesStore.createRelationships(company.ID, contacts.map(c => ({
                other_id: c.ID,
                other_type: 'contact'
              }))).then(() => {
                relatedContacts.push(...contacts)
                mount()
              })

            }
          })

        }

      })

    }

    const fetchContacts = () => {

      return CompaniesStore.fetchRelationships(company.ID, { other_type: 'contact' }).then(contacts => {
        fetched = true
        relatedContacts = contacts
        ContactsStore.itemsFetched(contacts)
        checkForPotentialContactMatches()
      })

    }

    mount()

    fetchContacts().then(mount)

  }

  let relatedContacts = []

  $(() => {
    manageCompany()
    companyTabs()
    manageStaffDirectory()
  })

})(jQuery)