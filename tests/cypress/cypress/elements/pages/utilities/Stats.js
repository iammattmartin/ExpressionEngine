import ControlPanel from '../ControlPanel'

class Stats extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/stats';

        this.selectors = Object.assign(this.selectors, {
        	  "heading": '.ee-main .title-bar .title-bar__title',
			  "content_table": 'div.container form table',
			  "rows": 'div.container form table tr',
			  "sources": 'div.container form table tr td:first-child',
			  "counts": 'div.container form table tr td:nth-child(2)',
			  "bulk_action": 'form fieldset.bulk-action-bar select[name="bulk_action"]'
        })
    }

    load(){
	    this.open_dev_menu()
	    this.get('main_menu').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("Statistics")').click()
    }


}
export default Stats;