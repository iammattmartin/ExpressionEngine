<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;

/**
 * Columns Filter
 */
class Columns extends Filter {

	public $view_id = null;
	public $channel_id = null;

	public function __construct(array $columns = array(), $channel = null, $view_id = null)
	{
		$this->name = 'columns';
		$this->label = lang('columns_filter');
		$this->options = $columns;
		$this->view_id = $view_id;

		if (! empty($channel))
		{
			$this->channel_id = $channel->channel_id;
		}

		$this->default_value = json_encode(['entry_id', 'title', 'entry_date', 'author', 'status']);
	}

	// get columns from view
	public function value()
	{
		$value = '';

		//if we had channel switched and no saved view, make sure to fallback to default
		if (ee()->input->post('filter_by_channel')!='') {
			$value = parent::value();
		}

		$query = ee('Model')->get('EntryManagerView')
			->filter('member_id', ee()->session->userdata('member_id'))
			->filter('channel_id', (int) ee()->input->get_post('filter_by_channel'));
		$view = $query->first();

		if (!empty($view)) {
			$value = $view->columns;
		}

		if (empty($value)) {
			$value = $this->default_value;
		}

		return $value;
	}

	/**
	 * @see Filter::render
	 */
	public function render(ViewFactory $view, URL $url)
	{
		//selected options go first in chosen order
		$options = [];
		$selected = json_decode($this->value());
		foreach ($selected as $key) {
			$options[$key] = $this->options[$key];
			unset($this->options[$key]);
		}
		$options = array_merge($options, $this->options);
		$filter = array(
			'label'			=> $this->label,
			'value'			=> '',
			'available_columns' => $options,
			'selected_columns' => json_decode($this->value())
		);

		return $view->make('_shared/filters/columns')->render($filter);
	}

}

// EOF
