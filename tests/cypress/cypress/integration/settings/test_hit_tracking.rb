require './bootstrap.rb'

feature 'Hit Tracking', () => {
  before :each do
    cy.auth();
    page = HitTracking.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Hit Tracking page', () => {
    page.all_there?.should == true
  }

  it('validates the suspend threshold field', () => {
    is_numeric_error = 'This field must contain only numeric characters.'

    // Ajax testing
    page.dynamic_tracking_disabling.set 'three'
    page.dynamic_tracking_disabling.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.dynamic_tracking_disabling, is_numeric_error)
    should_have_form_errors(page)

    // Clean up after Ajax testing
    page.dynamic_tracking_disabling.set '3'
    page.dynamic_tracking_disabling.trigger 'blur'
    page.wait_for_error_message_count(0)

    // Form Validation
    page.dynamic_tracking_disabling.set 'three'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.should have_text 'Attention: Settings not saved'
    should_have_error_text(page.dynamic_tracking_disabling, is_numeric_error)
  }

  it('saves settings on page load', () => {
    enable_online_user_tracking = ee_config(item: 'enable_online_user_tracking')
    enable_hit_tracking = ee_config(item: 'enable_hit_tracking')
    enable_entry_view_tracking = ee_config(item: 'enable_entry_view_tracking')

    page.enable_online_user_tracking_toggle.click()
    page.enable_hit_tracking_toggle.click()
    page.enable_entry_view_tracking_toggle.click()
    page.dynamic_tracking_disabling.set '360'
    page.submit

    cy.hasNoErrors()
    page.should_not have_text 'Attention: Settings not saved'
    page.enable_online_user_tracking.value.should_not == enable_online_user_tracking
    page.enable_hit_tracking.value.should_not == enable_hit_tracking
    page.enable_entry_view_tracking.value.should_not == enable_entry_view_tracking
    page.dynamic_tracking_disabling.value.should == '360'
  }
}
