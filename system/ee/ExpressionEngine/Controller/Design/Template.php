<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design;

use EE_Route;
use ZipArchive;
use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Model\Template\Template as TemplateModel;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 *Design\Template Controller
 */
class Template extends AbstractDesignController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->stdHeader();
    }

    public function create($group_name)
    {
        $errors = null;

        $group = ee('Model')->get('TemplateGroup')
            ->filter('group_name', $group_name)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $group) {
            show_error(sprintf(lang('error_no_template_group'), $group_name));
        }

        if (! in_array($group->group_id, $this->assigned_template_groups) ||
             ! ee('Permission')->can('create_templates_template_group_id_' . $group->getId())) {
            show_error(lang('unauthorized_access'), 403);
        }

        $template = ee('Model')->make('Template');
        $template->site_id = ee()->config->item('site_id');
        $template->TemplateGroup = $group;

        $dup_id = ee()->input->post('template_id');

        // Duplicate a template?
        if (! empty($dup_id)) {
            $master_template = ee('Model')->get('Template', $dup_id)
                ->first();

            $properties = $master_template->getValues();

            unset($properties['template_id']);
            unset($properties['site_id']);
            unset($properties['group_id']);
            unset($properties['hits']);

            $template->set($properties);
        }

        $result = $this->validateTemplate($template);

        if ($result instanceof ValidationResult) {
            $errors = $result;

            if ($result->isValid()) {
                // Unless we are duplicating a template the default is to
                // allow access to everyone
                if (! empty(ee()->input->post('template_id'))) {
                    $template->Roles = $master_template->Roles;
                } else {
                    $template->Roles = ee('Model')->get('Role')->all();
                }

                $template->save();

                $alert = ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('create_template_success'))
                    ->addToBody(sprintf(lang('create_template_success_desc'), $group_name, $template->template_name))
                    ->defer();

                ee()->session->set_flashdata('template_id', $template->template_id);

                if (ee()->input->post('submit') == 'edit') {
                    ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
                } else {
                    ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
                }
            }
        }

        $duplicate_template_options = [
            [
                'label' => lang('do_not_duplicate'),
                'value' => ''
            ]
        ] + $this->getExistingTemplates();

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL', 'design/template/create/' . $group_name),
            'sections' => array(
                array(
                    array(
                        'title' => 'name',
                        'desc' => 'alphadash_desc',
                        'fields' => array(
                            'template_name' => array(
                                'type' => 'text',
                                'required' => true
                            )
                        )
                    ),
                    array(
                        'title' => 'template_type',
                        'fields' => array(
                            'template_type' => array(
                                'type' => 'radio',
                                'choices' => $this->getTemplateTypes(),
                                'value' => null
                            )
                        )
                    ),
                    array(
                        'title' => 'duplicate_existing_template',
                        'desc' => 'duplicate_existing_template_desc',
                        'fields' => array(
                            'template_id' => array(
                                'type' => 'radio',
                                'choices' => $duplicate_template_options,
                                'filter_url' => ee('CP/URL', 'design/template/search-templates')->compile(),
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('templates'))
                                ]
                            )
                        )
                    ),
                )
            ),
            'buttons' => array(
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'finish',
                    'text' => sprintf(lang('btn_save'), lang('template')),
                    'working' => 'btn_saving'
                ),
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'edit',
                    'text' => 'btn_create_and_edit_template',
                    'working' => 'btn_saving'
                ),
            ),
        );

        $this->generateSidebar($group->group_id);
        ee()->view->cp_page_title = lang('create_new_template');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            ee('CP/URL')->make('design/manager/' . $group_name)->compile() => $group->group_name,
            '' => lang('create_new_template')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function edit($template_id)
    {
        $errors = null;

        $template = ee('Model')->get('Template', $template_id)
            ->with('TemplateGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if ($version_id = ee()->input->get('version')) {
            $version = ee('Model')->get('RevisionTracker', $version_id)->first();

            if ($version) {
                $template->template_data = $version->item_data;
            }
        }

        if (! $template) {
            show_error(lang('error_no_template'));
        }

        $group = $template->getTemplateGroup();

        if (! in_array($group->group_id, $this->assigned_template_groups) ||
             ! ee('Permission')->can('edit_templates_template_group_id_' . $group->getId())) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! empty($_POST)) {
            $template_result = $this->validateTemplate($template);
            $route_result = $this->validateTemplateRoute($template);
            $result = $this->combineResults($template_result, $route_result);

            if ($result instanceof ValidationResult) {
                $errors = $result;

                if (AJAX_REQUEST && ($field = ee()->input->post('ee_fv_field'))) {
                    if ($result->hasErrors($field)) {
                        ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
                    } else {
                        ee()->output->send_ajax_response(['success']);
                    }
                    exit;
                }

                if ($result->isValid()) {
                    $template->save();
                    // Save a new revision
                    $template->saveNewTemplateRevision($template);

                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('update_template_success'))
                        ->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name . '/' . $template->template_name))
                        ->defer();

                    if (ee()->input->post('submit') == 'save_and_close') {
                        ee()->session->set_flashdata('template_id', $template->template_id);
                        ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
                    }

                    ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
                }
            }
        }

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL', 'design/template/edit/' . $template_id),
            'tabs' => array(
                'edit' => $this->renderEditPartial($template, $errors),
                'notes' => $this->renderNotesPartial($template, $errors),
            ),
            'buttons' => array(
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save',
                    'shortcut' => 's',
                    'text' => trim(sprintf(lang('btn_save'), '')),
                    'working' => 'btn_saving'
                ),
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_and_close',
                    'text' => 'btn_save_and_close',
                    'working' => 'btn_saving'
                ),
            ),
            'sections' => array(),
        );

        if (ee('Permission')->can('manage_settings_template_group_id_' . $group->getId())) {
            $vars['tabs']['settings'] = $this->renderSettingsPartial($template, $errors);
            $vars['tabs']['access'] = $this->renderAccessPartial($template, $errors);
        }

        if (bool_config_item('save_tmpl_revisions')) {
            $vars['tabs']['revisions'] = $this->renderRevisionsPartial($template, $version_id);
        }

        $view_url = ee()->functions->fetch_site_index();
        $view_url = rtrim($view_url, '/') . '/';

        if ($template->template_type == 'css') {
            $view_url .= QUERY_MARKER . 'css=' . $group->group_name . '/' . $template->template_name;
        } else {
            $view_url .= $group->group_name . (($template->template_name == 'index') ? '' : '/' . $template->template_name);
        }

        $vars['buttons'][] = [
            'text' => 'view_rendered',
            'href' => $view_url,
            'attrs' => 'rel="external"'
        ];

        $vars['view_url'] = $view_url;

        $this->stdHeader();
        $this->loadCodeMirrorAssets();

        ee()->view->header = array(
            'title' => lang('edit_template_title'),
        );

        ee()->view->cp_page_title = $group->group_name . '/' . $template->template_name;
        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            ee('CP/URL')->make('design/manager/' . $group->group_name)->compile() => $group->group_name,
            '' => lang('edit_template_title')
        );

        // Supress browser XSS check that could cause obscure bug after saving
        ee()->output->set_header("X-XSS-Protection: 0");

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Renders the template revisions table for the Revisions tab
     *
     * @param TemplateModel $template A Template entity
     * @param int $version_id ID of template version to mark as selected
     * @return string Table HTML for insertion into Template edit form
     */
    protected function renderRevisionsPartial($template, $version_id = false)
    {
        if (! bool_config_item('save_tmpl_revisions')) {
            return false;
        }

        $table = ee('CP/Table');

        $table->setColumns(
            array(
                'rev_id',
                'rev_date',
                'rev_author',
                'manage' => array(
                    'encode' => false
                )
            )
        );
        $table->setNoResultsText(lang('no_revisions'));

        $data = array();

        $i = $template->Versions->count();

        foreach ($template->Versions->sortBy('item_date')->reverse() as $version) {
            $attrs = array();

            // Last item should be marked as current
            if ($template->Versions->count() == $i) {
                $toolbar = '<span class="st-open">' . lang('current') . '</span>';
            } else {
                $toolbar = ee('View')->make('_shared/toolbar')->render(
                    array(
                        'toolbar_items' => array(
                            'txt-only' => array(
                                'href' => ee('CP/URL')->make('design/template/edit/' . $template->getId(), array('version' => $version->getId())),
                                'title' => lang('view'),
                                'content' => lang('view')
                            ),
                        )
                    )
                );
            }

            // Mark currently-loaded version as selected
            if ((! $version_id && $template->Versions->count() == $i) or $version_id == $version->getId()) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => array(
                    $i,
                    ee()->localize->human_time($version->item_date),
                    ($version->getAuthorName()) ?: lang('author_unknown'),
                    $toolbar
                )
            );
            $i--;
        }

        $table->setData($data);

        return ee('View')->make('_shared/table')->render($table->viewData(''));
    }

    public function settings($template_id)
    {
        $errors = null;

        $template = ee('Model')->get('Template', $template_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $template) {
            show_error(lang('error_no_template'));
        }

        $group = $template->getTemplateGroup();

        if (! in_array($group->group_id, $this->assigned_template_groups) ||
             ! ee('Permission')->can('manage_settings_template_group_id_' . $group->getId())) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! empty($_POST)) {
            $template_result = $this->validateTemplate($template);
            $route_result = $this->validateTemplateRoute($template);
            $result = $this->combineResults($template_result, $route_result);

            if ($result instanceof ValidationResult) {
                $errors = $result;

                if (AJAX_REQUEST && ($field = ee()->input->post('ee_fv_field'))) {
                    if ($result->hasErrors($field)) {
                        ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
                    } else {
                        ee()->output->send_ajax_response(['success']);
                    }
                    exit;
                }

                if ($result->isValid()) {
                    $template->save();

                    if (isset($_POST['save_modal'])) {
                        return array(
                            'messageType' => 'success',
                        );
                    }

                    $alert = ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('update_template_success'))
                        ->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name . '/' . $template->template_name))
                        ->defer();

                    ee()->session->set_flashdata('template_id', $template->template_id);
                    ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
                }
            }
        }

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL', 'design/template/settings/' . $template_id),
            'tabs' => array(
                'settings' => $this->renderSettingsPartial($template, $errors),
                'access' => $this->renderAccessPartial($template, $errors),
            ),
            'sections' => array(),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'cp_page_title' => lang('template_settings_and_access')
        );

        $html = ee()->cp->render('_shared/form', $vars, true);

        if (isset($_POST['save_modal'])) {
            return array(
                'messageType' => 'error',
                'body' => $html
            );
        }

        return $html;
    }

    public function search()
    {
        if (ee()->input->post('bulk_action') == 'remove') {
            if (ee('Permission')->can('delete_templates')) {
                $this->removeTemplates(ee()->input->post('selection'));
                ee()->functions->redirect(ee('CP/URL')->make('design/template/search', ee()->cp->get_url_state()));
            } else {
                show_error(lang('unauthorized_access'), 403);
            }
        } elseif (ee()->input->post('bulk_action') == 'export') {
            $this->exportTemplates(ee()->input->post('selection'));
        }

        $search_terms = ee()->input->get_post('search');

        $return = ee()->input->get_post('return');

        if (! $search_terms) {
            $return = ee('CP/URL')->decodeUrl($return);
        } else {
            $this->stdHeader($return);
        }

        $templates = ee('Model')->get('Template')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('template_data', 'LIKE', '%' . $search_terms . '%');

        $base_url = ee('CP/URL')->make('design/template/search');
        $base_url->setQueryStringVariable('search', $search_terms);

        if (! ee('Permission')->isSuperAdmin()) {
            $assigned_groups = array_keys(ee()->session->userdata['assigned_template_groups']);
            $templates->filter('group_id', 'IN', $assigned_groups);

            if (empty($assigned_groups)) {
                $templates->markAsFutile();
            }
        }

        $this->base_url = $base_url;

        $total = $templates->count();

        $vars = $this->buildTableFromTemplateQueryBuilder($templates, true);

        $vars['show_new_template_button'] = false;
        $vars['show_bulk_delete'] = ee('Permission')->can('delete_templates');

        ee()->view->cp_heading = sprintf(
            lang('search_results_heading'),
            $vars['total'],
            htmlentities($search_terms, ENT_QUOTES, 'UTF-8')
        );

        ee()->javascript->set_global('template_settings_url', ee('CP/URL')->make('design/template/settings/###')->compile());
        ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
                'cp/design/manager'
            ),
        ));

        $this->generateSidebar();
        $this->stdHeader();
        ee()->view->cp_page_title = lang('template_manager');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            '' => lang('search_results')
        );

        ee()->cp->render('design/index', $vars);
    }

    /**
     * Sets a template entity with the POSTed data and validates it, setting
     * an alert if there are any errors.
     *
     * @param TemplateModel $template A Template entity
     * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
     *  or a ValidationResult object.
     */
    private function validateTemplate(TemplateModel $template)
    {
        if (empty($_POST)) {
            return false;
        }

        $template->set($_POST);
        $template->edit_date = ee()->localize->now;
        $template->last_author_id = ee()->session->userdata('member_id');

        $result = $template->validate();

        $field = ee()->input->post('ee_fv_field');

        // The ajaxValidation method looks for the 'ee_fv_field' in the POST
        // data. Then it checks to see if the result object has an error
        // for that field. Then it'll return. Since we may be validating
        // a field on a TemplateRoute model we should check for that
        // befaore outputting an ajax response.
        if (! isset($_POST['save_modal'])
            && isset($field)
            && $template->hasProperty($field)
            && $response = $this->ajaxValidation($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('update_template_error'))
                ->addToBody(lang('update_template_error_desc'))
                ->now();
        } else {
            $access = ee()->input->post('allowed_roles') ?: array();

            $roles = ee('Model')->get('Role', $access)
                ->filter('role_id', '!=', 1)
                ->all();

            if ($roles->count() > 0) {
                $template->Roles = $roles;
            } else {
                // Remove all roles from this template
                $template->Roles = null;
            }
        }

        return $result;
    }

    /**
     * Sets a template route entity with the POSTed data and validates it,
     * setting an alert if there are any errors.
     *
     * @param TemplateModel $template A Template entity
     * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
     *  or a ValidationResult object.
     */
    private function validateTemplateRoute(TemplateModel $template)
    {
        if (! ee()->input->post('route')) {
            // before erasing the route,
            // make sure is was not assigned after template was opened
            if (ee()->input->post('orig_route') !== '' ) {
                $template->TemplateRoute = null;
            }

            return false;
        }

        if (! $template->TemplateRoute) {
            $template->TemplateRoute = ee('Model')->make('TemplateRoute');
        }

        $template->TemplateRoute->set($_POST);
        $result = $template->TemplateRoute->validate();

        if (! isset($_POST['save_modal']) && $response = $this->ajaxValidation($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('update_template_error'))
                ->addToBody(lang('update_template_error_desc'))
                ->now();
        }

        return $result;
    }

    /**
     * Combines the results of two different model validation calls
     *
     * @param bool|ValidationResult $one FALSE (if nothing was submitted) or a
     *   ValidationResult object.
     * @param bool|ValidationResult $two FALSE (if nothing was submitted) or a
     *   ValidationResult object.
     * @return bool|ValidationResult $one FALSE (if nothing was submitted) or a
     *   ValidationResult object.
     */
    private function combineResults($one, $two)
    {
        $result = false;

        if ($one instanceof ValidationResult) {
            $result = $one;

            if ($two instanceof ValidationResult && $two->failed()) {
                foreach ($two->getFailed() as $field => $rules) {
                    foreach ($rules as $rule) {
                        $result->addFailed($field, $rule);
                    }
                }
            }
        } elseif ($two instanceof ValidationResult) {
            $result = $two;
        }

        return $result;
    }

    /**
     * Get template types
     *
     * Returns a list of the standard EE template types to be used in
     * template type selection dropdowns, optionally merged with
     * user-defined template types via the template_types hook.
     *
     * @return array Array of available template types
     */
    private function getTemplateTypes()
    {
        $template_types = array(
            'webpage' => lang('webpage'),
            'feed' => lang('rss'),
            'css' => lang('css_stylesheet'),
            'js' => lang('js'),
            'static' => lang('static'),
            'xml' => lang('xml')
        );

        // -------------------------------------------
        // 'template_types' hook.
        //  - Provide information for custom template types.
        //
        $custom_templates = ee()->extensions->call('template_types', array());
        //
        // -------------------------------------------

        if ($custom_templates != null) {
            // Instead of just merging the arrays, we need to get the
            // template_name value out of the associative array for
            // easy use of the form_dropdown helper
            foreach ($custom_templates as $key => $value) {
                $template_types[$key] = $value['template_name'];
            }
        }

        return $template_types;
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's contents. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderEditPartial(TemplateModel $template, $errors)
    {
        $author = $template->getLastAuthor();

        $section = array(
            array(
                'title' => '',
                'desc' => sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), (empty($author)) ? lang('author_unknown') : $author->screen_name),
                'wide' => true,
                'fields' => array(
                    'template_data' => array(
                        'type' => 'textarea',
                        'attrs' => 'class="template-edit"',
                        'value' => $template->template_data,
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's notes. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderNotesPartial(TemplateModel $template, $errors)
    {
        $section = array(
            array(
                'title' => 'template_notes',
                'desc' => 'template_notes_desc',
                'wide' => true,
                'fields' => array(
                    'template_notes' => array(
                        'type' => 'textarea',
                        'attrs' => 'class="textarea--large"',
                        'value' => $template->template_notes,
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's settings. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderSettingsPartial(TemplateModel $template, $errors)
    {
        $sections = [
            0 => []
        ];
        if (ee('Permission')->isSuperAdmin()) {
            if (ee('Config')->getFile()->getBoolean('allow_php')) {
                $sections[0][] = ee('CP/Alert')->makeInline('permissions-warn')
                    ->asWarning()
                    ->addToBody(lang('php_in_templates_warning'))
                    ->addToBody(
                        sprintf(lang('php_in_templates_warning2'), '<span class="icon--caution" title="exercise caution"></span>'),
                        'caution'
                    )
                    ->cannotClose()
                    ->render();
            } else {
                $sections[0][] = ee('CP/Alert')->makeInline('permissions-warn')
                    ->asWarning()
                    ->addToBody(lang('php_in_templates_warning'))
                    ->addToBody(lang('php_in_templates_config_warning'))
                    ->cannotClose()
                    ->render();
            }
        }
        $sections[0][] = array(
            'title' => 'template_name',
            'desc' => 'alphadash_desc',
            'fields' => array(
                'template_name' => array(
                    'type' => 'text',
                    'value' => $template->template_name,
                    'required' => true
                )
            )
        );
        $sections[0][] = array(
            'title' => 'template_type',
            'fields' => array(
                'template_type' => array(
                    'type' => 'radio',
                    'choices' => $this->getTemplateTypes(),
                    'value' => $template->template_type
                )
            )
        );
        $sections[0][] = array(
            'title' => 'enable_caching',
            'desc' => 'enable_caching_desc',
            'fields' => array(
                'cache' => array(
                    'type' => 'yes_no',
                    'value' => $template->cache
                )
            )
        );
        $sections[0][] = array(
            'title' => 'refresh_interval',
            'desc' => 'refresh_interval_desc',
            'fields' => array(
                'refresh' => array(
                    'type' => 'text',
                    'value' => $template->refresh
                )
            )
        );
        if (ee('Permission')->isSuperAdmin() && ee('Config')->getFile()->getBoolean('allow_php')) {
            $sections[0][] = array(
                'title' => 'enable_php',
                'desc' => 'enable_php_desc',
                'caution' => true,
                'fields' => array(
                    'allow_php' => array(
                        'type' => 'yes_no',
                        'value' => $template->allow_php
                    )
                )
            );
            $sections[0][] = array(
                'title' => 'parse_stage',
                'desc' => 'parse_stage_desc',
                'fields' => array(
                    'php_parse_location' => array(
                        'type' => 'inline_radio',
                        'choices' => array(
                            'i' => 'input',
                            'o' => 'output'
                        ),
                        'value' => $template->php_parse_location
                    )
                )
            );
        }
        $sections[0][] = array(
            'title' => 'hit_counter',
            'desc' => 'hit_counter_desc',
            'fields' => array(
                'hits' => array(
                    'type' => 'text',
                    'value' => $template->hits
                )
            )
        );

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's access settings. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderAccessPartial(TemplateModel $template, $errors)
    {
        $existing_templates = [
            [
                'label' => lang('default_404_option'),
                'value' => ''
            ]
        ] + $this->getExistingTemplates($template->no_auth_bounce);

        // Remove current template from options
        unset($existing_templates[$template->template_id]);

        $roles = ee('Model')->get('Role')
            ->filter('role_id', '!=', 1)
            ->all()
            ->getDictionary('role_id', 'name');

        $sections = array(
            array(
                array(
                    'title' => 'allowed_roles',
                    'desc' => 'allowed_roles_desc',
                    'desc_cont' => 'allowed_roles_super_admin',
                    'fields' => array(
                        'allowed_roles' => array(
                            'type' => 'checkbox',
                            'choices' => $roles,
                            'value' => $template->Roles->pluck('role_id'),
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('roles'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'no_access_redirect',
                    'desc' => 'no_access_redirect_desc',
                    'fields' => array(
                        'no_auth_bounce' => array(
                            'type' => 'radio',
                            'choices' => $existing_templates,
                            'filter_url' => ee('CP/URL', 'design/template/search-templates')->compile(),
                            'value' => $template->no_auth_bounce,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('templates'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'enable_http_authentication',
                    'desc' => 'enable_http_authentication_desc',
                    'fields' => array(
                        'enable_http_auth' => array(
                            'type' => 'yes_no',
                            'value' => $template->enable_http_auth
                        )
                    )
                )
            )
        );

        $route = $template->getTemplateRoute();

        if (! $route) {
            $route = ee('Model')->make('TemplateRoute');
        }

        $sections[0][] = array(
            'title' => 'template_route_override',
            'desc' => 'template_route_override_desc',
            'fields' => array(
                'orig_route' => array(
                    'type' => 'hidden',
                    'value' => $route->route
                ),
                'route' => array(
                    'type' => 'text',
                    'value' => $route->route
                )
            )
        );
        $sections[0][] = array(
            'title' => 'require_all_segments',
            'desc' => 'require_all_segments_desc',
            'fields' => array(
                'route_required' => array(
                    'type' => 'yes_no',
                    'value' => $route->route_required
                )
            )
        );

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    /**
     * Gets a list of all the templates for the current site, grouped by
     * their template group name:
     *   array(
     *     'news' => array(
     *       1 => 'index',
     *       3 => 'about',
     *     )
     *   )
     *
     * @return array An associative array of templates
     */
    private function getExistingTemplates($selected_id = null)
    {
        $search_query = ee('Request')->get('search');

        $templates = ee('Model')->get('Template')
            ->with('TemplateGroup')
            ->with('Site')
            ->order('TemplateGroup.group_name')
            ->order('Template.template_name');

        if ($search_query) {
            $templates = $templates->all()->filter(function ($template) use ($search_query) {
                return strpos(strtolower($template->getPath()), strtolower($search_query)) !== false;
            });
        } else {
            $templates = $templates->limit(100)->all();
        }

        $results = [];
        foreach ($templates as $template) {
            $results[$template->getId()] = [
                'label' => $template->getPath(),
                'instructions' => bool_config_item('multiple_sites_enabled') ? $template->Site->site_label : null
            ];
        }

        if ($selected_id && ! array_key_exists($selected_id, $results) && ! $search_query) {
            $template = ee('Model')->get('Template', $selected_id)
                ->with('TemplateGroup')
                ->with('Site')
                ->first();

            if (!empty($template)) {
                $results[$template->getId()] = [
                    'label' => $template->getPath(),
                    'instructions' => bool_config_item('multiple_sites_enabled') ? $template->Site->site_label : null
                ];
            }
        }

        return $results;
    }

    public function searchTemplates()
    {
        return json_encode($this->getExistingTemplates());
    }
}

// EOF
