<?php
if (!class_exists('jobsearch_candidate_pdf_resume_template_twelve')) {

    class jobsearch_candidate_pdf_resume_template_twelve
    {
        public function __construct()
        {
            add_action('init', array($this, 'jobsearch_single_candidate_resume_export_callback'));
            add_action('wp_footer', array($this, 'jobsearch_single_candidate_resume_form'), 10);
            add_action('admin_footer', array($this, 'jobsearch_single_candidate_resume_form'), 10);
        }

        public function jobsearch_single_candidate_resume_form()
        {
            global $jobsearch_plugin_options, $sitepress;
            $flag = false;
            $page_id = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
            $page_id = jobsearch__get_post_id($page_id, 'page');
            $lang_code = '';
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $lang_code = $sitepress->get_current_language();
            }
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $page_id = icl_object_id($page_id, 'page', false, $lang_code);
            }
            if (is_page($page_id)) {
                $flag = true;
            }
            if (is_admin()) {
                $flag = true;

            }
            if ($flag == false) {
                return;
            }
            ?>
            <form id="pdf_cand_generate_form_templt_twelve" method="post" enctype="multipart/form-data"
                  style="display: none">
                <input type="text" name="jobsearch_single_pdf_cand_id_templt_twelve" value="">
                <input type="submit" class="btn btn-default" name="pdf_cand_generate_form_submit_templt_twelve"
                       value="Generate PDF">
            </form>
            <script type="text/javascript">
                jQuery(document).on('click', '.jobsearch-get-cand-id-templt-twelve', function () {
                    var _this = jQuery(this), _template = _this.attr('data-template'), _loader_html,
                        _template_class = _this.attr('data-class'), _cand_id = jQuery(this).attr('data-cand-id');
                    _loader_html = '<div class="jobsearch-candidate-pdf-locked pdf-loader"><a href="javascript:void(0)" class="fa fa-refresh fa-spin"></a></div>';
                    jQuery(document).find('.' + _template_class).after(_loader_html);
                    jQuery(".jobsearch-candidate-pdf-list").find("figcaption").remove();

                    var request = jQuery.ajax({
                        url: jobsearch_plugin_vars.ajax_url,
                        method: "POST",
                        data: {
                            template_name: _template,
                            action: 'jobsearch_user_pdf_type_save',
                        },
                        dataType: "json"
                    });
                    request.done(function (response) {
                        if (typeof response.res !== 'undefined' && response.res == true) {
                            jQuery(document).find('.' + _template_class).after('<figcaption>' + jobsearch_export_vars.active + '</figcaption>');
                            jQuery(document).find(".pdf-loader").remove();
                            //
                            jQuery("input[name=jobsearch_single_pdf_cand_id_templt_twelve]").val(_cand_id);
                            jQuery("input[name=pdf_cand_generate_form_submit_templt_twelve]").trigger('click')
                        }
                    });
                    request.fail(function (jqXHR, textStatus) {
                        console.info(textStatus);
                    });
                });
            </script>
        <?php }

        public function jobsearch_single_candidate_resume_export_callback()
        {
            global $jobsearch_resume_export, $jobsearch_plugin_options;
            if (isset($_POST['pdf_cand_generate_form_submit_templt_twelve'])) {

                $candidate_id = $_POST['jobsearch_single_pdf_cand_id_templt_twelve'];
                $stylesheet = file_get_contents($jobsearch_resume_export->jobsearch_resume_export_get_path('css/jobsearch-mpdf-style-template-twelve.css'));

                $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
                $fontDirs = $defaultConfig['fontDir'];

                $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
                $fontData = $defaultFontConfig['fontdata'];
                if(is_rtl()){
                    $mpdf = new \Mpdf\Mpdf([
                        'mode' => '+aCJK',
                        "autoScriptToLang" => true,
                        "autoLangToFont" => true,
                        'format' => 'A4',
                        'border' => '2px solid #000',
                        'margin_left' => 0,
                        'margin_right' => 0,
                        'margin_top' => 0,
                        'margin_bottom' => 0,
                        'mirrorMargins' => true,
                        'tempDir' => __DIR__ . '/upload',
                        'fontDir' => array_merge($fontDirs, [
                            __DIR__ . '/fonts'
                        ]),
                        'fontdata' => $fontData + [
                                "dejavusans" => [
                                    'R' => 'DejaVuSans.ttf',
                                    'B' => 'DejaVuSans-Bold.ttf',
                                    'useOTL' => 0xFF,
                                    'useKashida' => 75,
                                ],
                                "jobsearch" => [
                                    'R' => "icomoon.ttf",
                                ],
                                "careerfy" => [
                                    'R' => "careerfy.ttf",
                                ],
                            ],
                        'default_font' => 'dejavusans'
                    ]);
                }

                else {
                    $mpdf = new \Mpdf\Mpdf([
                        'mode' => '+aCJK',
                        "autoScriptToLang" => true,
                        "autoLangToFont" => true,
                        'format' => 'A4',
                        'margin_left' => 0,
                        'margin_right' => 0,
                        'margin_top' => 0,
                        'margin_bottom' => 0,
                        'border' => '2px solid #000',
                        'mirrorMargins' => true,
                        'tempDir' => __DIR__ . '/upload',
                        'fontDir' => array_merge($fontDirs, [
                            __DIR__ . '/fonts'
                        ]),
                        'fontdata' => $fontData + [
                                "montserrat" => [
                                    'R' => "Montserrat-Regular.ttf",
                                    'B' => "Montserrat-Bold.ttf",

                                ],
                                "jobsearch" => [
                                    'R' => "icomoon.ttf",
                                ],
                                "careerfy" => [
                                    'R' => "careerfy.ttf",
                                ],
                            ],
                        'default_font' => 'montserrat'
                    ]);
                }
                $mpdf->defaultheaderline = 0;
                $mpdf->defaultfooterline = 0;

                $user_id = jobsearch_get_candidate_user_id($candidate_id);
                $user_obj = get_user_by('ID', $user_id);
                $user_displayname = isset($user_obj->display_name) ? $user_obj->display_name : '';
                $user_displayname = apply_filters('jobsearch_user_display_name', $user_displayname, $user_obj);
                $candidate_obj = get_post($candidate_id);
                $candidate_content = $candidate_obj->post_content;
                $candidate_content = apply_filters('the_content', $candidate_content);

                $user_website = isset($user_obj->user_url) ? $user_obj->user_url : '';
                $user_email = isset($user_obj->user_email) ? $user_obj->user_email : '';
                //
                $jobsearch_candidate_jobtitle = get_post_meta($candidate_id, 'jobsearch_field_candidate_jobtitle', true);
                $candidate_company_str = '';
                if ($jobsearch_candidate_jobtitle != '') {
                    $candidate_company_str .= $jobsearch_candidate_jobtitle;
                }
                $cand_det_full_address_switch = true;

                $locations_view_type = isset($jobsearch_plugin_options['cand_det_loc_listing']) ? $jobsearch_plugin_options['cand_det_loc_listing'] : '';
                $loc_view_country = $loc_view_state = $loc_view_city = false;
                if (!empty($locations_view_type)) {
                    if (is_array($locations_view_type) && in_array('country', $locations_view_type)) {
                        $loc_view_country = true;
                    }

                    if (is_array($locations_view_type) && in_array('state', $locations_view_type)) {
                        $loc_view_state = true;
                    }
                    if (is_array($locations_view_type) && in_array('city', $locations_view_type)) {
                        $loc_view_city = true;
                    }
                }
                $candidate_address = get_post_meta($candidate_id, 'jobsearch_field_location_address', true);
                if (function_exists('jobsearch_post_city_contry_txtstr')) {
                    $candidate_address = jobsearch_post_city_contry_txtstr($candidate_id, $loc_view_country, $loc_view_state, $loc_view_city, $cand_det_full_address_switch);
                }
                // Extra Fields
                $user_def_avatar_url = jobsearch_candidate_img_url_comn($candidate_id);
                $profile_image = $user_def_avatar_url;
                $user_id = jobsearch_get_candidate_user_id($candidate_id);
                $user_obj = get_user_by('ID', $user_id);

                $cand_email = $user_obj->user_email;
                $user_firstname = isset($user_obj->first_name) ? $user_obj->first_name : '';
                $user_last_name = isset($user_obj->last_name) ? $user_obj->last_name : '';
                $user_displayname = isset($user_obj->display_name) ? $user_obj->display_name : '';
                //
                $user_dob_dd = get_post_meta($candidate_id, 'jobsearch_field_user_dob_dd', true);
                $user_dob_mm = get_post_meta($candidate_id, 'jobsearch_field_user_dob_mm', true);
                $user_dob_yy = get_post_meta($candidate_id, 'jobsearch_field_user_dob_yy', true);

                $user_dob_whole = get_post_meta($candidate_id, 'jobsearch_field_user_dob_whole', true);

                if ($user_dob_whole == '' && $user_dob_dd != '' && $user_dob_mm != '' && $user_dob_yy != '') {
                    $user_dob_whole = $user_dob_dd . '-' . $user_dob_mm . '-' . $user_dob_yy;
                }
                $user_dob_whole = get_post_meta($candidate_id, 'jobsearch_field_user_dob_whole', true);
                if ($user_dob_whole != '') {
                    $user_dob_whole = date_i18n(get_option('date_format'), strtotime($user_dob_whole));
                }
                $phone_number = get_post_meta($candidate_id, 'jobsearch_field_user_phone', true);
                if (function_exists('jobsearch_user_member_phone_number')) {
                    $phone_number = jobsearch_user_member_phone_number($candidate_id);
                }
                ob_start();
                ?>
                <div class="pdf-style13-left-section">

                    <div class="pdf-style13-top-description">
                        <div class="pdf-style13-top-description-title"><?php echo($user_firstname) ?>
                            <span><?php echo($user_last_name) ?></span></div>
                        <div class="pdf-style13-top-description-sub"><?php echo($candidate_company_str) ?></div>
                        <div class="pdf-style13-top-description-pera"><?php echo($candidate_content) ?>
                        </div>
                    </div>

                    <div class="pdf-style13-info-list">
                        <?php if (!empty($phone_number)) { ?>
                            <div class="pdf-style13-info-list-inn">
                                <div class="pdf-style13-info-list-title"><?php echo esc_html__('Phone:', 'jobsearch-resume-export') ?></div>
                                <div class="pdf-style13-info-list-sub"><?php echo($phone_number) ?></div>
                            </div>
                        <?php } ?>
                        <?php if (!empty($candidate_address)) { ?>
                            <div class="pdf-style13-info-list-inn">
                                <div class="pdf-style13-info-list-title"><?php echo esc_html__('Address:', 'jobsearch-resume-export') ?></div>
                                <div class="pdf-style13-info-list-sub"><?php echo($candidate_address) ?></div>
                            </div>
                        <?php } ?>
                        <div class="pdf-style13-info-list-inn">
                            <div class="pdf-style13-info-list-title"><?php echo esc_html__('Email:', 'jobsearch-resume-export') ?></div>
                            <div class="pdf-style13-info-list-sub"><?php echo($cand_email) ?></div>
                        </div>
                        <?php if (!empty($user_website)) { ?>
                            <div class="pdf-style13-info-list-inn">
                                <div class="pdf-style13-info-list-title"><?php echo esc_html__('Website:', 'jobsearch-resume-export') ?></div>
                                <div class="pdf-style13-info-list-sub"><?php echo($user_website) ?></div>
                            </div>
                        <?php } ?>
                    </div>
                    <!--Candidate Experience-->
                    <?php echo self::jobsearch_resume_candidate_experience($candidate_id) ?>
                    <!--Candidate honors & awards-->
                    <?php echo self::jobsearch_resume_candidate_awards($candidate_id) ?>
                    <!--Candidate portfolio-->
                    <?php echo self::jobsearch_resume_cand_portfolio($candidate_id) ?>
                </div>

                <div class="pdf-style13-right-content"
                     style="background-image: url('<?php echo $jobsearch_resume_export->jobsearch_resume_export_get_path('images/template_13/pattren.png') ?>');">
                    <!--Candidate Custom Fields-->
                    <?php echo self::jobsearch_resume_candidate_custom_fields($candidate_id) ?>
                    <!--Candidate Education-->
                    <?php echo self::jobsearch_resume_candidate_education($candidate_id) ?>
                    <!--Candidate Expertise-->
                    <?php echo self::jobsearch_resume_candidate_expertise($candidate_id) ?>
                    <!--Candidate Languages-->
                    <?php echo self::jobsearch_resume_candidate_languages($candidate_id) ?>
                    <!--Candidate Skills-->
                    <?php echo self::jobsearch_resume_candidate_skills($candidate_id) ?>
                </div>
                <?php
                $pdf_html = ob_get_clean();
                $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
                $mpdf->WriteHTML($pdf_html, \Mpdf\HTMLParserMode::HTML_BODY);
                $mpdf->Output($user_firstname . '-' . date('dmy') . "-" . $candidate_id . '.pdf', 'D');
                
//                $file_name = $user_firstname . '-' . date('dmy') . "-" . $candidate_id . '.pdf';
//                
//                header('Content-Description: File Transfer');
//                header("Content-type: application/pdf");
//                header("Content-type: force-download");
//                header('Content-Disposition:attachment; filename="' . $file_name . '"');
//                header('Content-Transfer-Encoding: Binary');
//                header('Expires: 0');
//                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//                header('Pragma: public');
//                header('Content-Length: ' . @filesize($file_name));
//                
//                ob_end_clean();
//                flush();
//                readfile($file_name);
                exit;
            }
        }

        public function jobsearch_candidate_resume_bulk_export_template_twelve($candidate_id)
        {
            global $jobsearch_resume_export, $jobsearch_plugin_options, $jobsearch_pdf_temp_upload_file;
            $stylesheet = file_get_contents($jobsearch_resume_export->jobsearch_resume_export_get_path('css/jobsearch-mpdf-style-template-twelve.css'));
            $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            $mpdf = new \Mpdf\Mpdf([
                'mode' => '+aCJK',
                "autoScriptToLang" => true,
                "autoLangToFont" => true,
                'format' => 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'border' => '2px solid #000',
                'mirrorMargins' => true,
                'tempDir' => __DIR__ . '/upload',
                'fontDir' => array_merge($fontDirs, [
                    __DIR__ . '/fonts'
                ]),
                'fontdata' => $fontData + [
                        "montserrat" => [
                            'R' => "Montserrat-Regular.ttf",
                            'B' => "Montserrat-Bold.ttf",

                        ],
                        "jobsearch" => [
                            'R' => "icomoon.ttf",
                        ],
                        "careerfy" => [
                            'R' => "careerfy.ttf",
                        ],
                    ],
                'default_font' => 'montserrat'
            ]);

            $mpdf->defaultheaderline = 0;
            $mpdf->defaultfooterline = 0;

            $user_id = jobsearch_get_candidate_user_id($candidate_id);
            $user_obj = get_user_by('ID', $user_id);
            $user_displayname = isset($user_obj->display_name) ? $user_obj->display_name : '';
            $user_displayname = apply_filters('jobsearch_user_display_name', $user_displayname, $user_obj);
            $candidate_obj = get_post($candidate_id);
            $candidate_content = $candidate_obj->post_content;
            $candidate_content = apply_filters('the_content', $candidate_content);

            $user_website = isset($user_obj->user_url) ? $user_obj->user_url : '';
            $cand_email = isset($user_obj->user_email) ? $user_obj->user_email : '';
            //
            $user_dob_whole = get_post_meta($candidate_id, 'jobsearch_field_user_dob_whole', true);
            if ($user_dob_whole != '') {
                    $user_dob_whole = date_i18n(get_option('date_format'), strtotime($user_dob_whole));
                }
            $jobsearch_candidate_jobtitle = get_post_meta($candidate_id, 'jobsearch_field_candidate_jobtitle', true);
            $candidate_company_str = '';
            if ($jobsearch_candidate_jobtitle != '') {
                $candidate_company_str .= $jobsearch_candidate_jobtitle;
            }
            $cand_det_full_address_switch = true;

            $locations_view_type = isset($jobsearch_plugin_options['cand_det_loc_listing']) ? $jobsearch_plugin_options['cand_det_loc_listing'] : '';
            $loc_view_country = $loc_view_state = $loc_view_city = false;
            if (!empty($locations_view_type)) {
                if (is_array($locations_view_type) && in_array('country', $locations_view_type)) {
                    $loc_view_country = true;
                }

                if (is_array($locations_view_type) && in_array('state', $locations_view_type)) {
                    $loc_view_state = true;
                }
                if (is_array($locations_view_type) && in_array('city', $locations_view_type)) {
                    $loc_view_city = true;
                }
            }
            $candidate_address = get_post_meta($candidate_id, 'jobsearch_field_location_address', true);
            if (function_exists('jobsearch_post_city_contry_txtstr')) {
                $candidate_address = jobsearch_post_city_contry_txtstr($candidate_id, $loc_view_country, $loc_view_state, $loc_view_city, $cand_det_full_address_switch);
            }
            // Extra Fields
            $user_def_avatar_url = jobsearch_candidate_img_url_comn($candidate_id);
            $profile_image = $user_def_avatar_url;

            $user_firstname = isset($user_obj->first_name) ? $user_obj->first_name : '';
            $user_last_name = isset($user_obj->last_name) ? $user_obj->last_name : '';
            $user_displayname = isset($user_obj->display_name) ? $user_obj->display_name : '';
            //
            $phone_number = get_post_meta($candidate_id, 'jobsearch_field_user_phone', true);
            if (function_exists('jobsearch_user_member_phone_number')) {
                $phone_number = jobsearch_user_member_phone_number($candidate_id);
            }
            ob_start();
            ?>
            <div class="pdf-style13-left-section">

                <div class="pdf-style13-top-description">
                    <div class="pdf-style13-top-description-title"><?php echo($user_firstname) ?>
                        <span><?php echo($user_last_name) ?></span></div>
                    <div class="pdf-style13-top-description-sub"><?php echo($candidate_company_str) ?></div>
                    <div class="pdf-style13-top-description-pera"><?php echo($candidate_content) ?>
                    </div>
                </div>

                <div class="pdf-style13-info-list">
                    <?php if (!empty($phone_number)) { ?>
                        <div class="pdf-style13-info-list-inn">
                            <div class="pdf-style13-info-list-title"><?php echo esc_html__('Phone:', 'jobsearch-resume-export') ?></div>
                            <div class="pdf-style13-info-list-sub"><?php echo($phone_number) ?></div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($candidate_address)) { ?>
                        <div class="pdf-style13-info-list-inn">
                            <div class="pdf-style13-info-list-title"><?php echo esc_html__('Address:', 'jobsearch-resume-export') ?></div>
                            <div class="pdf-style13-info-list-sub"><?php echo($candidate_address) ?></div>
                        </div>
                    <?php } ?>
                    <div class="pdf-style13-info-list-inn">
                        <div class="pdf-style13-info-list-title"><?php echo esc_html__('Email:', 'jobsearch-resume-export') ?></div>
                        <div class="pdf-style13-info-list-sub"><?php echo($cand_email) ?></div>
                    </div>
                    <?php if (!empty($user_website)) { ?>
                        <div class="pdf-style13-info-list-inn">
                            <div class="pdf-style13-info-list-title"><?php echo esc_html__('Website:', 'jobsearch-resume-export') ?></div>
                            <div class="pdf-style13-info-list-sub"><?php echo($user_website) ?></div>
                        </div>
                    <?php } ?>
                </div>
                <!--Candidate Experience-->
                <?php echo self::jobsearch_resume_candidate_experience($candidate_id) ?>
                <!--Candidate honors & awards-->
                <?php echo self::jobsearch_resume_candidate_awards($candidate_id) ?>
                <!--Candidate portfolio-->
                <?php echo self::jobsearch_resume_cand_portfolio($candidate_id) ?>
            </div>
            <div class="pdf-style13-right-content"
                 style="background-image: url('<?php echo $jobsearch_resume_export->jobsearch_resume_export_get_path('images/template_13/pattren.png') ?>');">
                <!--Candidate Custom Fields-->
                <?php echo self::jobsearch_resume_candidate_custom_fields($candidate_id) ?>
                <!--Candidate Education-->
                <?php echo self::jobsearch_resume_candidate_education($candidate_id) ?>
                <!--Candidate Expertise-->
                <?php echo self::jobsearch_resume_candidate_expertise($candidate_id) ?>
                <!--Candidate Languages-->
                <?php echo self::jobsearch_resume_candidate_languages($candidate_id) ?>
                <!--Candidate Skills-->
                <?php echo self::jobsearch_resume_candidate_skills($candidate_id) ?>
            </div>
            <?php
            if (file_exists(JOBSEARCH_RESUME_PDF_TEMP_DIR_PATH)) {
                $location = JOBSEARCH_RESUME_PDF_TEMP_DIR_PATH;
            } else {
                $jobsearch_pdf_temp_upload_file = true;
                add_filter('upload_dir', 'jobsearch_resume_export_files_upload_dir', 10, 1);
                $wp_upload_dir = wp_upload_dir();
                $location = $wp_upload_dir['path'] . "/";
                remove_filter('upload_dir', 'jobsearch_resume_export_files_upload_dir', 10, 1);
                $jobsearch_pdf_temp_upload_file = false;
            }
            $pdf_html = ob_get_clean();
            $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($pdf_html, \Mpdf\HTMLParserMode::HTML_BODY);
            $mpdf->Output($location . $user_firstname . '-' . date('dmy') . "-" . $candidate_id . '.pdf', 'F');
        }

        public static function jobsearch_resume_candidate_custom_fields($candidate_id)
        {
            global $sitepress;
            $custom_all_fields = get_option('jobsearch_custom_field_candidate');
            $lang_code = '';
            if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
                $lang_code = $sitepress->get_current_language();
            }
            if (!empty($custom_all_fields)) { ?>
                <div class="pdf-style13-section-heading"><?php echo esc_html__('About Me', 'jobsearch-resume-export'); ?></div>
                <div class="pdf-style13-bar-services">

                <?php
                $fields_data = [];
                foreach ($custom_all_fields as $info) {
                    $field_name = isset($info['name']) ? $info['name'] : '';
                    $field_label = isset($info['label']) ? stripslashes($info['label']) : '';
                    $type = isset($info['type']) ? $info['type'] : '';
                    $icon = isset($info['icon']) ? $info['icon'] : '';
                    $field_for_non_reg_user = isset($info['non_reg_user']) ? $info['non_reg_user'] : '';
                    $field_put_val = get_post_meta($candidate_id, $field_name, true);
                    $icon_type = strpos($icon, 'careerfy') !== false ? 'careerfy' : 'jobsearch';
                    
                    $field_vtype = isset($info['non_reg_user']) ? $info['non_reg_user'] : '';
                    if ($field_vtype == 'admin_view_only') {
                        continue;
                    }

                    if ($type == 'heading' && $field_for_non_reg_user != 'admin_view_only') { ?>
                        </div>
                        <div class="pdf-style13-section-heading"><?php echo($field_label); ?></div>
                        <div class="pdf-style13-bar-services">

                    <?php } else if ($type == 'checkbox' && $field_for_non_reg_user != 'admin_view_only') {
                        $drop_down_arr = array();
                        $cut_field_flag = 0;
                        foreach ($info['options']['value'] as $key => $cus_field_options_value) {
                            $drop_down_arr[$cus_field_options_value] = (apply_filters('wpml_translate_single_string', $info['options']['label'][$cut_field_flag], 'Custom Fields', 'Checkbox Option Label - ' . $info['options']['label'][$cut_field_flag], $lang_code));
                            $cut_field_flag++;
                        }

                        if (is_array($field_put_val) && !empty($field_put_val)) {
                            $field_put_valarr = array();
                            foreach ($field_put_val as $fil_putval) {
                                if (isset($drop_down_arr[$fil_putval]) && $drop_down_arr[$fil_putval] != '') {
                                    $field_put_valarr[] = $drop_down_arr[$fil_putval];
                                }
                            }
                            $field_put_val = implode(', ', $field_put_valarr);
                        } else {
                            if (isset($drop_down_arr[$field_put_val]) && $drop_down_arr[$field_put_val] != '') {
                                $field_put_val = $drop_down_arr[$field_put_val];
                            }
                        }
                        $fields_data[] = array(
                            'icon' => jobsearch_get_font_code($icon),
                            'label' => $field_label,
                            'value' => $field_put_val,
                            'icon_type' => $icon_type,
                        );

                    } else if (!empty($field_name)) {
                        $field_name = $type == 'upload_file' ? 'jobsearch_cfupfiles_' . $field_name : $field_name;
                        $field_value = get_post_meta($candidate_id, $field_name, true);
                        $icon_type = strpos($icon, 'careerfy') !== false ? 'careerfy' : 'jobsearch';
                        if ($type == 'upload_file' && $field_for_non_reg_user != 'admin_view_only') {
                            if (is_array($field_value) && count($field_value) > 0) { ?>
                                <div class="pdf-stylefield-bar-servicesfull-list">
                                    <?php if (!empty($icon)) { ?>
                                        <div class="pdf-style13-bar-icon">
                                            <div style="font-family: <?php echo($icon_type) ?>;"><?php echo jobsearch_get_font_code($icon) ?></div>
                                        </div>
                                    <?php } ?>
                                    <br><br>
                                    <div class="pdf-stylefield-bar-servicesfull-text">
                                        <div class="pdf-style13-bar-services-title"><?php echo($field_label) ?></div>
                                    </div>
                                    <?php
                                    if (is_array($field_value) && !empty($field_value)) {
                                        foreach ($field_value as $val) {
                                            //$img_path = str_replace(get_site_url(), ABSPATH, $val);
                                            echo ($val);
                                        }
                                    } else {
                                        echo ($field_value);
                                    } ?>
                                </div>
                            <?php } ?>
                        <?php } else { ?>

                            <div class="pdf-style13-bar-services-list">
                                <div class="pdf-style13-bar-icon">
                                    <div style="font-family: <?php echo($icon_type) ?>"><?php echo jobsearch_get_font_code($icon) ?></div>
                                </div>
                                <div class="pdf-style13-bar-services-text">
                                    <div class="pdf-style13-bar-services-title"><?php echo($field_label) ?></div>
                                    <?php
                                    if (isset($info['options']['value'])) {
                                        $field_put_val = jobsearch_custom_fields_options_slugtolabel($field_value, $info);
                                        ?>
                                        <div class="pdf-style13-bar-services-sub"><?php echo ($field_put_val) ?></div>
                                        <?php
                                    } else {
                                        if (is_array($field_value) && count($field_value) > 0) {
                                            foreach ($field_value as $val) { ?>
                                                <div class="pdf-style13-bar-services-sub"><?php echo($val) ?></div>
                                            <?php }
                                        } else {
                                            $field_value = $type == 'date' ? date_i18n($info['date-format'], $field_value) : $field_value;
                                            if (strpos($field_value, 'http') !== false) {
                                                $field_value = '<a href="' . $field_value . '">' . $field_value . '</a>';
                                            }
                                            ?>
                                            <div class="pdf-style13-bar-services-sub"><?php echo($field_value) ?></div>
                                        <?php }
                                    }
                                    ?>

                                </div>
                            </div>
                        <?php } ?>
                    <?php }
                } ?>
                <?php
                if (count($fields_data) > 0) {
                    foreach ($fields_data as $fields) { ?>
                        <div class="pdf-style13-bar-services-list">
                            <?php if (!empty($fields['icon'])) { ?>
                                <div class="pdf-style13-bar-icon">
                                    <div style="font-family: <?php echo($fields['icon_type']) ?>"><?php echo($fields['icon']) ?></div>
                                </div>
                            <?php } ?>
                            <div class="pdf-style13-bar-services-text">
                                <div class="pdf-style13-bar-services-title"><?php echo($fields['label']) ?></div>
                                <div class="pdf-style13-bar-services-sub"><?php echo($fields['value']) ?></div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
                </div>
            <?php }
        }

        public function jobsearch_resume_candidate_experience($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_title', true);
            $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_experience_description', true);
            $experience_start_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_start_date', true);
            $experience_end_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_end_date', true);
            $experience_prsnt_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_experience_date_prsnt', true);
            $experience_company_field_list = get_post_meta($candidate_id, 'jobsearch_field_experience_company', true);
            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>
                <div class="pdf-style13-left-heading"><?php echo esc_html__('Experience', 'jobsearch-resume-export'); ?></div>
                <div class="pdf-style13-experience-list">
                    <?php
                    $exfield_counter = 0;
                    $x = 1;
                    foreach ($exfield_list as $exfield) {
                        $exfield_val = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                        $experience_start_datefield_val = isset($experience_start_datefield_list[$exfield_counter]) ? $experience_start_datefield_list[$exfield_counter] : '';
                        $experience_end_datefield_val = isset($experience_end_datefield_list[$exfield_counter]) ? $experience_end_datefield_list[$exfield_counter] : '';
                        $experience_prsnt_datefield_val = isset($experience_prsnt_datefield_list[$exfield_counter]) ? $experience_prsnt_datefield_list[$exfield_counter] : '';
                        $experience_end_companyfield_val = isset($experience_company_field_list[$exfield_counter]) ? $experience_company_field_list[$exfield_counter] : '';
                        
                        $exfield_val = apply_filters('the_content', $exfield_val);
                        ?>
                        <div class="pdf-style13-experience-list-inn">
                            <?php if ($experience_prsnt_datefield_val == 'on') { ?>
                                <div class="pdf-style13-experience-list-date"><?php echo ($experience_start_datefield_val != '' ? jobsearch_get_date_year_only($experience_start_datefield_val) : '') . (' - ') . esc_html__('Present', 'jobsearch-resume-export') ?></div>
                            <?php } else { ?>
                                <div class="pdf-style13-experience-list-date"><?php echo ($experience_start_datefield_val != '' ? jobsearch_get_date_year_only($experience_start_datefield_val) : '') . ($experience_end_datefield_val != '' ? ' - ' . jobsearch_get_date_year_only($experience_end_datefield_val) : '') ?></div>
                            <?php } ?>
                            <div class="pdf-style13-experience-list-title"><?php echo jobsearch_esc_html($exfield) ?></div>
                            <div class="pdf-style13-experience-list-sub"><?php echo jobsearch_esc_html($experience_end_companyfield_val) ?></div>
                            <div class="pdf-style13-experience-list-pera"><?php echo jobsearch_esc_wp_editor($exfield_val) ?></div>
                        </div>
                        <?php
                        $exfield_counter++;
                        $x++;
                    } ?>
                </div>
            <?php }
        }

        public static function jobsearch_resume_candidate_education($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_education_title', true);
            $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_education_description', true);
            $education_academyfield_list = get_post_meta($candidate_id, 'jobsearch_field_education_academy', true);
            $education_yearfield_list = get_post_meta($candidate_id, 'jobsearch_field_education_year', true);
            $education_start_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_start_date', true);
            $education_end_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_end_date', true);
            $education_prsnt_datefield_list = get_post_meta($candidate_id, 'jobsearch_field_education_date_prsnt', true);
            $edu_start_metaexist = metadata_exists('post', $candidate_id, 'jobsearch_field_education_start_date');

            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>

                <div class="pdf-style13-section-heading"><?php echo esc_html__('Education', 'jobsearch-resume-export'); ?></div>
                <div class="pdf-style13-experience-list pdf-style13-white">
                    <?php
                    $exfield_counter = 0;
                    $x = 1;
                    $length = count($exfield_list);
                    foreach ($exfield_list as $exfield) {
                        $exfield_val = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                        $education_academyfield_val = isset($education_academyfield_list[$exfield_counter]) ? $education_academyfield_list[$exfield_counter] : '';
                        $education_yearfield_val = isset($education_yearfield_list[$exfield_counter]) ? $education_yearfield_list[$exfield_counter] : '';
                        $education_start_datefield_val = isset($education_start_datefield_list[$exfield_counter]) ? $education_start_datefield_list[$exfield_counter] : '';
                        $education_end_datefield_val = isset($education_end_datefield_list[$exfield_counter]) ? $education_end_datefield_list[$exfield_counter] : '';
                        $education_prsnt_datefield_val = isset($education_prsnt_datefield_list[$exfield_counter]) ? $education_prsnt_datefield_list[$exfield_counter] : '';
                        ?>

                        <div class="pdf-style13-experience-list-inn">
                            <?php
                            if ($edu_start_metaexist) {
                                if ($education_prsnt_datefield_val == 'on') { ?>
                                    <div class="pdf-style13-experience-list-date">
                                        &nbsp;<?php echo ($education_start_datefield_val != '' ? jobsearch_get_date_year_only($education_start_datefield_val) : '') . (' - ') . esc_html__('Present', 'wp-jobsearch') ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="pdf-style13-experience-list-date">
                                        &nbsp;<?php echo ($education_start_datefield_val != '' ? jobsearch_get_date_year_only($education_start_datefield_val) : '') . ($education_end_datefield_val != '' ? ' - ' . jobsearch_get_date_year_only($education_end_datefield_val) : '') ?>
                                    </div>
                                    <?php
                                }
                            } else { ?>
                                <div class="pdf-style13-experience-list-date"><?php echo($education_yearfield_val) ?></div>
                            <?php } ?>
                            <div class="pdf-style13-experience-list-title"><?php echo($exfield) ?></div>
                            <div class="pdf-style13-experience-list-pera"><?php echo($exfield_val) ?></div>
                        </div>

                        <?php $exfield_counter++;
                        $x++;
                    } ?>
                </div>
            <?php }
        }

        public static function jobsearch_resume_candidate_awards($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_award_title', true);
            $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_award_description', true);
            $award_yearfield_list = get_post_meta($candidate_id, 'jobsearch_field_award_year', true);
            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>

                <div class="pdf-style13-left-heading"><?php echo esc_html__('Honors & Awards', 'jobsearch-resume-export'); ?></div>
                <div class="pdf-style13-experience-list">

                    <?php
                    $exfield_counter = 0;
                    $x = 1;
                    foreach ($exfield_list as $exfield) {
                        $exfield_val = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                        $award_yearfield_val = isset($award_yearfield_list[$exfield_counter]) ? $award_yearfield_list[$exfield_counter] : '';
                        ?>
                        <div class="pdf-style13-experience-list-inn">
                            <div class="pdf-style13-experience-list-date"><?php echo jobsearch_esc_html($award_yearfield_val) ?></div>
                            <div class="pdf-style13-experience-list-title"><?php echo($exfield) ?></div>
                            <div class="pdf-style13-experience-list-pera"><?php echo jobsearch_esc_html($exfield_val) ?></div>
                        </div>
                        <?php $exfield_counter++;
                        $x++;
                    } ?>
                </div>
            <?php }
        }

        public static function jobsearch_resume_candidate_expertise($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_skill_title', true);
            $skill_percentagefield_list = get_post_meta($candidate_id, 'jobsearch_field_skill_percentage', true);
            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>
                <div class="pdf-style13-section-heading"><?php echo esc_html__('Expertise', 'jobsearch-resume-export') ?></div>
                <div class="pdf-style13-skills-wrap">
                    <?php
                    $exfield_counter = 0;
                    $x = 1;
                    foreach ($exfield_list as $exfield) {
                        $skill_percentagefield_val = isset($skill_percentagefield_list[$exfield_counter]) ? absint($skill_percentagefield_list[$exfield_counter]) : '';
                        $skill_percentagefield_val = $skill_percentagefield_val > 100 ? 100 : $skill_percentagefield_val;
                        ?>
                        <div class="pdf-style13-skills">
                            <div class="pdf-style13-skills-left"><?php echo($exfield) ?></div>
                            <div class="pdf-style13-skills-line">
                                <div class="pdf-style13-skills-inn1"></div>
                                <div class="pdf-style13-skills-inn2"
                                     style="width: <?php echo($skill_percentagefield_val) ?>%;"></div>
                            </div>
                        </div>
                        <?php $exfield_counter++;
                        $x++;
                    } ?>
                </div>

            <?php }
        }

        public static function jobsearch_resume_candidate_languages($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_title', true);
            $lang_percentagefield_list = get_post_meta($candidate_id, 'jobsearch_field_lang_percentage', true);
            $lang_level_list = get_post_meta($candidate_id, 'jobsearch_field_lang_level', true);

            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>
                <div class="pdf-style13-section-heading"><?php echo esc_html__('Languages', 'jobsearch-resume-export') ?></div>
                <div class="pdf-style13-skills-wrap">
                    <?php
                    $exfield_counter = 0;
                    foreach ($exfield_list as $exfield) {

                        $lang_percentagefield_val = isset($lang_percentagefield_list[$exfield_counter]) ? absint($lang_percentagefield_list[$exfield_counter]) : '';
                        $lang_percentagefield_val = $lang_percentagefield_val > 100 ? 100 : $lang_percentagefield_val;
                        $lang_level_val = isset($lang_level_list[$exfield_counter]) ? ($lang_level_list[$exfield_counter]) : '';

                        $lang_level_str = esc_html__('Beginner', 'wp-jobsearch');
                        if ($lang_level_val == 'proficient') {
                            $lang_level_str = esc_html__('Proficient', 'wp-jobsearch');
                        } else if ($lang_level_val == 'intermediate') {
                            $lang_level_str = esc_html__('Intermediate', 'wp-jobsearch');
                        }
                        ?>
                        <div class="pdf-style13-skills">
                            <div class="pdf-style13-skills-left">
                                <strong><?php echo($exfield) ?></strong> <?php echo($lang_level_str) ?></div>
                            <div class="pdf-style13-skills-line">
                                <div class="pdf-style13-skills-inn1"></div>
                                <div class="pdf-style13-skills-inn2"
                                     style="width: <?php echo($lang_percentagefield_val) ?>%;"></div>
                            </div>
                        </div>
                        <?php
                        $exfield_counter++;
                    } ?>
                </div>
            <?php }
        }

        public static function jobsearch_resume_cand_portfolio($candidate_id)
        {
            $exfield_list = get_post_meta($candidate_id, 'jobsearch_field_portfolio_title', true);
            $exfield_list_val = get_post_meta($candidate_id, 'jobsearch_field_portfolio_image', true);
            $exfield_portfolio_url = get_post_meta($candidate_id, 'jobsearch_field_portfolio_url', true);
            $exfield_portfolio_vurl = get_post_meta($candidate_id, 'jobsearch_field_portfolio_vurl', true);

            if (is_array($exfield_list) && sizeof($exfield_list) > 0) { ?>
                <div class="pdf-style13-left-heading"><?php echo esc_html__('Portfolio', 'jobsearch-resume-export'); ?></div>
                <div class="pdf-style13-skills-wrap">
                    <?php
                    $exfield_counter = 0;
                    foreach ($exfield_list as $exfield) {
                        $portfolio_img = isset($exfield_list_val[$exfield_counter]) ? $exfield_list_val[$exfield_counter] : '';
                        $portfolio_url = isset($exfield_portfolio_url[$exfield_counter]) ? $exfield_portfolio_url[$exfield_counter] : '';
                        $portfolio_vurl = isset($exfield_portfolio_vurl[$exfield_counter]) ? $exfield_portfolio_vurl[$exfield_counter] : '';
                        $file_path = jobsearch_get_cand_portimg_path($candidate_id, $portfolio_img);
                        ?>
                        <div class="jobsearch-pdf-porfolio-img">
                            <?php if (!empty($file_path)) { ?>
                                <img src="<?php echo($file_path) ?>">
                            <?php } ?>
                            <br>
                            <div class="jobsearch-pdf-porfolio-link">
                                <?php if (!empty($exfield)) {
                                    echo $exfield . "<br>";
                                } ?>
                                <?php if (!empty($portfolio_url)) { ?>
                                    <?php echo esc_html__('Portfolio URL: ', 'jobsearch-resume-export'); ?><br>
                                    <a href="<?php echo($portfolio_url) ?>"><?php echo($portfolio_url); ?></a><br>
                                <?php } ?>
                                <?php if (!empty($portfolio_vurl)) { ?>
                                    <?php echo esc_html__('Video URL: ', 'jobsearch-resume-export'); ?><br>
                                    <a href="<?php echo($portfolio_vurl) ?>"><?php echo($portfolio_vurl); ?></a><br>
                                <?php } ?>
                            </div>
                        </div>
                        <?php
                        $exfield_counter++;
                    }
                    ?>
                </div>
            <?php }
        }

        public static function jobsearch_resume_candidate_skills($candidate_id)
        {
            $skills_list = jobsearch_resume_export_job_get_all_skills($candidate_id, '', '', '', '', '<div class="cndt-skills-inner"><div class="cndt-skills-list-item">', '</div></div>', 'candidate');
            $skills_list = apply_filters('jobsearch_cand_detail_skills_list_html', $skills_list, $candidate_id);

            if (!empty($skills_list)) { ?>
                <div class="pdf-style13-section-heading"><?php echo esc_html__('Skills', 'jobsearch-resume-export'); ?></div>
                <div class="cndt-skills">
                    <?php if ($skills_list != '') { ?>
                        <?php echo($skills_list); ?>
                    <?php } ?>
                </div>
            <?php }
        }
    }
}
global $jobsearch_resume_pdf_template_twelve;
$jobsearch_resume_pdf_template_twelve = new jobsearch_candidate_pdf_resume_template_twelve();