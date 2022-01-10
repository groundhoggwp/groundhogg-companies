(($) => {

  const { company, gh_company_custom_properties } = GroundhoggCompany
  const {
    icons,
    regexp,
    input,
    uuid,
    dialog,
    modal,
    moreMenu,
    dangerConfirmationModal,
    loadingDots,
    spinner,
    tooltip,
    bold
  } = Groundhogg.element
  const { companies: CompaniesStore, contacts: ContactsStore } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { currentUser, propertiesEditor, noteEditor } = Groundhogg
  const { userHasCap } = Groundhogg.user
  const { patch, routes } = Groundhogg.api
  const { quickEditContactModal, selectContactModal, addContactModal } = Groundhogg.components

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

      if (editing) {
        // language=HTML
        return `
			<div class="inside">
				<p>
					<button id="save-changes" class="gh-button primary">${__('Save Changes')}</button>
					<button id="cancel-changes" class="gh-button danger text">${__('Cancel')}</button>
				</p>
			</div>`
      }

      // language=HTML
      return `
		  <div class="inside">
			  <div class="space-between align-left align-top gap-20">
				  ${getCompany().logo ? `<div class="upload-image has-logo"><img class="logo" src="${getCompany().logo}" alt="${__('Company logo', 'groundhogg-companies')}" title="${getCompany().data.name}"></div>` : `<div class="upload-image">${icons.company}</div>`}
				  <div class="company-details">
					  <div class="name"><b>${getCompany().data.name}</b></div>
					  <div class="website"><a href="${getCompany().data.domain}">${getCompany().data.domain}</a></div>
					  <div class="phone"><a href="tel:${getCompany().meta.phone}">${getCompany().meta.phone}</a></div>
				  </div>
          <button id="edit-company" class="gh-button secondary text icon"><span class="dashicons dashicons-edit"></span></button>
			  </div>
		  </div>`
    }

    const mount = () => {
      $('#primary #form').html(companyUI())
      onMount()

    }

    const onMount = () => {

      tooltip('#edit-company', {
        content: __('Edit Details', 'groundhogg')
      })

      $('#edit-company').on('click', e => {

        editing = true

        mount()

      })

      $('#cancel-changes').on('click', e => {

        editing = false

        mount()

      })

      // Uploading files
      var file_frame

      tooltip('.upload-image', {
        content: __('Change Image', 'groundhogg-companies')
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

  const companyTabs = () => {

    let activeTab = 'notes'

    let tabs = [
      {
        id: 'notes',
        name: __('Notes'),
        render: () => `<div class="gh-panel top-left-square"><div id="notes-here" class="inside">//notes</div></div>`,
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
        render: () => `<div class="gh-panel top-left-square"><div id="files-here" class="inside">//files</div></div>`,
        onMount: () => {},
      }
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
                          activeTab = 'general'
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

    $('#add-contact-to-company').on('click', (e) => {
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
    })

    const contactCardUI = (contact) => {
      //language=HTML
      return `
		  <div class="contact gh-panel">
			  <div style="padding: 10px" class="align-left-space-between">
				  <img width="40" class="avatar" src="${contact.data.gravatar}"/>
				  <div class="details">
					  <div class="name">${contact.data.full_name}
						  ${contact.meta.job_title ? `| ${contact.meta.job_title}` : ''}
					  </div>
					  <div class="email">${contact.data.email}</div>
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

    const mount = () => {

      $('#directory').html(relatedContacts.length === 0 ? spinner() : relatedContacts.filter(c => {

        let __regex = regexp(search)

        let { job_title = '', primary_phone = '', mobile_phone = '' } = c.meta

        return c.data.full_name.match(__regex)
          || c.data.email.match(__regex)
          || primary_phone.match(__regex)
          || mobile_phone.match(__regex)
          || job_title.match(__regex)

      }).map(c => contactCardUI(c)).join(''))

      onMount()
    }

    const onMount = () => {

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
                  onEdit: (c) => {
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

    const fetchContacts = () => {

      return CompaniesStore.fetchRelationships(company.ID, { other_type: 'contact' }).then(contacts => {
        relatedContacts = contacts
        ContactsStore.itemsFetched(contacts)
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