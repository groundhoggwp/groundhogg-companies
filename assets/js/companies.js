(($) => {

  const { company } = GroundhoggCompanies

  console.log('here')

  Groundhogg.noteEditor('#notes-here', {
    object_id: company.ID,
    object_type: 'company',
    title: '',
  })

  $(document).on('click', '#notes-here button', e => e.preventDefault() )

})(jQuery)