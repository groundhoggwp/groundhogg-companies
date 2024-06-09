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
  } = MakeEl

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



  const manageStaffDirectory = () => {

    //language=HTML
    let actionsUI = `
        ${ input({
      id: 'search-company-contacts',
      placeholder: __('Search by name, email, phone, or position...', 'groundhogg'),
      type: 'search',
    }) }
        <button id="email-all" class="gh-button secondary text icon">${ icons.email }</button>
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
      content: __('Email All', 'groundhogg'),
    })

    tooltip('#add-contact-to-company', {
      content: __('Add Contact', 'groundhogg'),
    })

    const addToCompany = (e) => {
      moreMenu(e.currentTarget, {
        items: [
          {
            key: 'select',
            cap: 'view_contacts',
            text: __('Select an existing contact', 'groundhogg-pipeline'),
          }, {
            key: 'add',
            cap: 'add_contacts',
            text: __('Create a new contact', 'groundhogg-pipeline'),
          },
        ].filter(i => userHasCap(i.cap)),
        onSelect: (k) => {
          switch (k) {
            case 'select':
              selectContactModal({
                exclude: relatedContacts.map(c => c.ID),
                onSelect: (contact) => {
                  CompaniesStore.createRelationships(company.ID, {
                    other_type: 'contact',
                    other_id: contact.ID,
                  }).then(() => {
                    relatedContacts.push(contact)
                    mount()
                  })
                },
              })
              break
            case 'add':
              addContactModal({
                additionalFields: ({ prefix }) => {

                  // language=HTML
                  return `
                      <div class="gh-row">
                          <div class="gh-col">
                              <label>${ __('Position', 'groundhogg-companies') }</label>
                              ${ input({
                    id: `${ prefix }-job-title`,
                  }) }
                          </div>
                          <div class="gh-col">
                              <label>${ __('Department', 'groundhogg-companies') }</label>
                              ${ input({
                    id: `${ prefix }-company-department`,
                  }) }
                          </div>
                          <div class="gh-col">
                              <label>${ __('Work Phone', 'groundhogg-companies') }</label>
                              ${ input({
                    id: `${ prefix }-work-phone`,
                    type: 'tel',
                  }) }
                          </div>
                      </div>`

                },
                additionalFieldsOnMount: ({ prefix, setPayload, getPayload }) => {
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
                onCreate: (contact) => {
                  CompaniesStore.createRelationships(company.ID, {
                    other_type: 'contact',
                    other_id: contact.ID,
                  }).then(() => {
                    relatedContacts.push(contact)
                    mount()
                  })
                },
              })

              break
          }
        },
      })
    }

    $('#add-contact-to-company').on('click', addToCompany)

    const contactCardUI = (contact) => {

      let { primary_phone = '', mobile_phone = '', company_phone = '' } = contact.meta

      //language=HTML
      return `
          <div class="contact gh-panel">
              <div style="padding: 10px" class="align-left space-between gap-10 align-top">
                  <img width="40" class="avatar" src="${ contact.data.gravatar }"/>
                  <div class="details">
                      <div class="name">${ contact.data.full_name }
                          ${ contact.meta.job_title ? `| ${ contact.meta.job_title }` : '' }
                      </div>
                      <div class="email">${ contact.data.email }</div>
                      <div class="phone ${ primary_phone || mobile_phone || company_phone ? '' : 'hidden' }">
                          ${ primary_phone ? `<span class="dashicons dashicons-phone"></span><a href="tel:${ primary_phone }">${ primary_phone }</a>` : '' }
                          ${ mobile_phone ? `<span class="dashicons dashicons-smartphone"></span><a href="tel:${ mobile_phone }">${ mobile_phone }</a>` : '' }
                          ${ company_phone ? `<span class="dashicons dashicons-building"></span><a href="tel:${ company_phone }">${ company_phone }</a>` : '' }
                      </div>
                  </div>
                  <div class="space-between align-right no-gap actions">
                      <button data-id="${ contact.ID }" class="gh-button secondary text icon send-email">${ icons.email }
                      </button>
                      <button data-id="${ contact.ID }" class="gh-button secondary text icon contact-more">
                          ${ icons.verticalDots }
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
                      ${ icons.contact }
                  </div>
                  <p>${ __('No related contacts') }</p>
                  <button class="gh-button primary" id="add-to-company-none">${ __('Add a contact!') }</button>
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
              text: __('View profile', 'groundhogg'),
            },
            {
              key: 'edit',
              cap: 'edit_contacts',
              text: __('Quick edit', 'groundhogg'),
            },
            contact.ID != getCompany().data.primary_contact_id ? {
              key: 'primary',
              cap: 'edit_contacts',
              text: __('Make Primary', 'groundhogg'),
            } : null,
            {
              key: 'remove',
              cap: 'edit_deals',
              text: `<span class="gh-text danger">${ __('Remove from company', 'groundhogg-companies') }</span>`,
            },
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

                    relatedContacts = relatedContacts.map(_c => _c.ID === c.ID ? c : _c)

                    dialog({
                      message: __('Contact updated!', 'groundhogg'),
                    })
                    mount()
                  },
                })

                break
              case 'view':
                window.open(contact.admin)
                break
              case 'primary':

                CompaniesStore.patch(company.ID, {
                  data: {
                    primary_contact_id: contact.ID,
                  },
                }).then(item => {

                  dialog({
                    message: __('Primary contact changed!', 'groundhogg-companies'),
                  })

                  mount()

                })

                break
              case 'remove':

                dangerConfirmationModal({
                  confirmText: __('Remove'),
                  alert: `<p>${ sprintf(__('Are you sure you want to remove %s from %s?', 'groundhogg'), bold(contact.data.full_name),
                    bold(getCompany().data.name)) }</p>`,
                  onConfirm: () => {
                    CompaniesStore.deleteRelationships(company.ID, {
                      other_id: contact.ID,
                      other_type: 'contact',
                    }).then(() => {
                      relatedContacts.splice(relatedContacts.findIndex(c => c.ID === contact.ID), 1)
                      mount()
                      dialog({
                        message: sprintf(__('%s was removed!', 'groundhogg'), contact.data.full_name),
                      })
                    })
                  },
                })

                break
            }
          },
        })
      })

    }

    const checkForPotentialContactMatches = () => {
      let domain

      try {
        domain = getCompany().data.domain ? new URL(getCompany().data.domain).host : ''
      }
      catch (e) {
        return
      }

      if (!domain) {
        return
      }

      ContactsStore.fetchItems({
        exclude: relatedContacts.map(c => c.ID),
        filters: [
          [
            {
              type: 'email',
              compare: 'ends_with',
              value: '@' + domain,
            },
          ],
        ],
      }).then(contacts => {

        if (contacts.length) {

          confirmationModal({
            width: 500,
            alert: `<p>${ sprintf(_n('We found %s contact that has an email address ending with %s. Would you like to relate them to this company?',
              'We found %s contacts that have an email address ending with %s. Would you like to relate them to this company?', contacts.length,
              'groundhogg-companies'), bold(formatNumber(contacts.length)), bold('@' + domain)) }</p>`,
            confirmText: __('Yes, add them!'),
            cancelText: __('No'),
            onConfirm: () => {

              CompaniesStore.createRelationships(company.ID, contacts.map(c => ( {
                other_id: c.ID,
                other_type: 'contact',
              } ))).then(() => {
                relatedContacts.push(...contacts)
                mount()
              })

            },
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

})(jQuery)
