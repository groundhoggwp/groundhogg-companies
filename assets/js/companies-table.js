(($) => {

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
    adminPageURL,
    textarea,
    bold
  } = Groundhogg.element
  const { companies: CompaniesStore, contacts: ContactsStore } = Groundhogg.stores
  const { __, _x, _n, _nx, sprintf } = wp.i18n
  const { currentUser, propertiesEditor, noteEditor } = Groundhogg
  const { userHasCap } = Groundhogg.user
  const { patch, routes } = Groundhogg.api
  const { quickEditContactModal, selectContactModal, addContactModal } = Groundhogg.components

  $(() => {
    $('#add-company').on('click', e => {
      e.preventDefault()

      const addCompanyUI = () => {

        //language=HTML
        return `
			<div style="width: 500px">
				<h2 id="modal-header">${__('Add Company', 'groundhogg-companies')}</h2>
				<div id="maybe-match"></div>
				<div class="gh-rows-and-columns">
					<div class="gh-row">
						<div class="gh-col">
							<label for="name">${__('Name')}</label>
							${input({
								id: 'company-name',
								name: 'name',
								dataType: 'data',
							})}
						</div>
						<div class="gh-col">
							<label for="website">${__('Website')}</label>
							${input({
								id: 'company-website',
								name: 'domain',
								type: 'url',
								dataType: 'data',
							})}
						</div>
					</div>
					<div class="gh-row">
						<div class="gh-col">
							<label for="phone">${__('Phone')}</label>
							${input({
								id: 'phone',
								name: 'phone',
								type: 'tel',
								dataType: 'meta',
							})}
						</div>
						<div class="gh-col">
							<label for="industry">${__('Industry')}</label>
							${input({
								id: 'industry',
								name: 'industry',
								dataType: 'meta',

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
								dataType: 'meta',
							})}
						</div>
					</div>
				</div>
				<p>
					<button id="save-changes" class="gh-button primary">${__('Add')}</button>
					<button id="cancel-changes" class="gh-button danger text">${__('Cancel')}</button>
				</p>
			</div>`
      }

      modal({
        content: addCompanyUI(),
        onOpen: ({ close }) => {

          let changes = {
            data: {},
            meta: {}
          }

          const commitChanges = () => {
            return CompaniesStore.post(changes)
          }

          $('#industry').autocomplete({
            source: Groundhogg.companyIndustries
          })

          $('#company-name, #company-website, #address, #phone, #industry').on('input change', e => {
            changes[e.target.dataset.type] = {
              ...changes[e.target.dataset.type],
              [e.target.name]: e.target.value
            }
          })

          $('#company-name').on('change', e => {

            let newName = e.target.value

            CompaniesStore.fetchItems({
              s: newName
            }).then(items => {
              if (items.length) {

                // language=HTML
                let notice = `<p>
					<span
			  class="pill yellow">${sprintf(_n('A company with the name %2$s already exists. Would you like to edit it instead?', 'We found %1$d companies with similar names to %2$s.', items.length, 'groundhogg-companies'), items.length, bold(items.length === 1 ? items[0].data.name : newName))}
					<a href="${items.length === 1 ? items[0].admin : adminPageURL('gh_companies', { s: newName })}">${items.length > 1 ? sprintf(__('View %d similar companies'), items.length) : sprintf(__('Edit %s'), items[0].data.name)}</a></span>
				</p>`

                $('#maybe-match').html(notice)
              } else {
                $('#maybe-match').html('')
              }
            })

          })

          $('#company-website[name="domain"]').on('change', e => {

            let newDomain = e.target.value
            let cleanDomain = newDomain.replace( 'https://', '' ).replace( 'http://', '' ).replace('www.', '')

            if (!newDomain) return

            CompaniesStore.fetchItems({
              where: [
                [ 'domain', 'RLIKE', cleanDomain ]
              ]
            }).then(items => {
              if (items.length) {

                // language=HTML
                let notice = `<p>
					<span
			  class="pill yellow">${sprintf(_n('A company with the domain %2$s already exists. Would you like to edit it instead?', 'We found %1$d companies with similar domains to %2$s.', items.length, 'groundhogg-companies'), items.length, bold(items.length === 1 ? items[0].data.domain : newDomain))}
					<a href="${items.length === 1 ? items[0].admin : adminPageURL('gh_companies', { s: cleanDomain })}">${items.length > 1 ? sprintf(__('View %d similar companies'), items.length) : sprintf(__('Edit %s'), items[0].data.name)}</a></span>
				</p>`

                $('#maybe-match').html(notice)
              } else {
                $('#maybe-match').html('')
              }
            })

          })

          $('#save-changes').on('click', e => {

            commitChanges().then((c) => {
              dialog({
                message: __('Company created!')
              })

              window.location.href = c.admin

            })

          })

          $('#cancel-changes').on('click', e => {
            close()
          })

        }
      })
    })
  })

})(jQuery)