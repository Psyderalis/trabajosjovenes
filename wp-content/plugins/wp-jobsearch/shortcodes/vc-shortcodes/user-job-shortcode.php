<?php

add_action('jobsearch_translate_job_with_wpml_btn', 'jobsearch_translate_job_with_wpml_btn', 10, 1);
function jobsearch_translate_job_with_wpml_btn($job_id = 0)
{
    global $jobsearch_plugin_options, $sitepress;

    if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') && $job_id > 0) {
        $page_id = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
        $page_id = jobsearch__get_post_id($page_id, 'page');

        $current_lang = $sitepress->get_current_language();
        $languages = icl_get_languages('skip_missing=0&orderby=code');
        if (is_array($languages) && sizeof($languages) > 0) {
            $real_job_id = $job_id;
            $job_id = jobsearch_wpml_is_original($job_id, 'post_job');
            $job_id = isset($job_id['original_ID']) && $job_id['original_ID'] > 0 ? $job_id['original_ID'] : $real_job_id;
            foreach ($languages as $lang_code => $language) {
                $sitepress->switch_lang($lang_code);
                $page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page'); //get_permalink($page_id);
                $page_url = apply_filters('wpml_permalink', $page_url, $lang_code);
                $icl_post_id = icl_object_id($job_id, 'candidate', false, $lang_code);
                $sitepress->switch_lang($current_lang);

                if ($icl_post_id <= 0) { ?>
                    <a class="other-lang-translate-post"
                       href="<?php echo add_query_arg(array('tab' => 'user-job', 'job_id' => $job_id, 'action' => 'update', 'lang' => $lang_code), $page_url) ?>"><?php printf(esc_html__('Translate in %s', 'wp-jobsearch'), (isset($language['translated_name']) ? $language['translated_name'] : '')) ?></a>
                <?php }
            }
        }
    }
}

function jobsearch_jobpost_editr_mce_button()
{
    global $jobsearch_plugin_options;
    $caned_resps_switch = isset($jobsearch_plugin_options['caned_resp_switch']) ? $jobsearch_plugin_options['caned_resp_switch'] : '';

    if (!is_admin() && $caned_resps_switch == 'on') {
        $args = array(
            'post_type' => 'jobdesctemp',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'order' => 'DESC',
            'orderby' => 'ID',
        );
        $temps_query = new WP_Query($args);
        $found_temps = $temps_query->found_posts;
        wp_reset_postdata();

        if ($found_temps > 0) {
            add_filter('mce_external_plugins', 'jobsearch_jobpost_add_tinymce_plugin');
            add_filter('mce_buttons', 'jobsearch_jobpost_editr_regr_mce_button');
        }
    }
}

add_action('init', 'jobsearch_jobpost_editr_mce_button');

// register new button in the editor
function jobsearch_jobpost_editr_regr_mce_button($buttons)
{
    array_push($buttons, 'caned_resp_button');
    return $buttons;
}


// declare a script for the new button
// the script will insert the shortcode on the click event
function jobsearch_jobpost_add_tinymce_plugin($plugin_array)
{
    $plugin_array['caned_resp_button'] = jobsearch_plugin_get_url('js/wdm-mce-button.js');
    return $plugin_array;
}

//

add_shortcode('jobsearch_user_job', 'jobsearch_user_job_shortcode');

function jobsearch_user_job_shortcode($atts)
{
    global $jobsearch_plugin_options, $job_userreg_withmail, $job_form_errs, $package_form_errs, $jobsearch_currencies_list, $sitepress, $wpdb, $jobsearch_gdapi_allocation, $in_jobpost_form_sh;
    $all_locations_type = isset($jobsearch_plugin_options['all_locations_type']) ? $jobsearch_plugin_options['all_locations_type'] : '';
    $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
    $page_id = jobsearch__get_post_id($user_dashboard_page, 'page');

    $in_jobpost_form_sh = true;

    extract(shortcode_atts(array(
        'title' => '',
    ), $atts));

    wp_enqueue_script('jobsearch-user-job-posting');
    
    $negotiable_salary = isset($jobsearch_plugin_options['negotiable_salary']) ? $jobsearch_plugin_options['negotiable_salary'] : '';
    
    ob_start();
    if (($page_id == '0' || !is_page($page_id)) && $all_locations_type == 'api') {
        $jobsearch_gdapi_allocation->load_locations_js(true, false);
    }
    $user_id = get_current_user_id();
    $user_id = apply_filters('jobsearch_job_postinsh_top_user_id', $user_id);

    $is_candidate = false;
    $is_employer = false;
    if (is_user_logged_in()) {
        $candidate_id = jobsearch_get_user_candidate_id($user_id);
        $is_candidate = $candidate_id > 0 ? true : false;

        if (jobsearch_user_isemp_member($user_id)) {
            $employer_id = jobsearch_user_isemp_member($user_id);
            $user_id = jobsearch_get_employer_user_id($employer_id);
        } else {
            $employer_id = jobsearch_get_user_employer_id($user_id);
        }
        $is_employer = $employer_id > 0 ? true : false;
    }

    $lang_code = '';
    if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
        $lang_code = $sitepress->get_current_language();
    }

    $job_submit_title = isset($jobsearch_plugin_options['job-submit-title']) ? $jobsearch_plugin_options['job-submit-title'] : '';
    $job_submit_title = apply_filters('wpml_translate_single_string', $job_submit_title, 'JobSearch Options', 'Job Submit Title - ' . $job_submit_title, $lang_code);
    $job_submit_msg = isset($jobsearch_plugin_options['job-submit-msge']) ? $jobsearch_plugin_options['job-submit-msge'] : '';
    $job_submit_msg = apply_filters('wpml_translate_single_string', $job_submit_msg, 'JobSearch Options', 'Job Submit Message - ' . $job_submit_msg, $lang_code);
    $job_submit_img = isset($jobsearch_plugin_options['job-submit-img']) ? $jobsearch_plugin_options['job-submit-img'] : '';

    $job_submit_img_url = isset($job_submit_img['url']) ? $job_submit_img['url'] : '';

    $free_jobs_allow = isset($jobsearch_plugin_options['free-jobs-allow']) ? $jobsearch_plugin_options['free-jobs-allow'] : '';

    $page_id = $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
    $page_id = $user_dashboard_page = jobsearch__get_post_id($user_dashboard_page, 'page');
    $page_url = jobsearch_wpml_lang_page_permalink($page_id, 'page'); //get_permalink($page_id);

    $job_salary_types = isset($jobsearch_plugin_options['job-salary-types']) ? $jobsearch_plugin_options['job-salary-types'] : '';
    $job_salary_types = !empty($job_salary_types) ? $job_salary_types : array();

    if ($negotiable_salary == 'on') {
        $job_salary_types['negotiable'] = esc_html__('Negotiable', 'wp-jobsearch');
    }

    $is_updating = false;
    $job_id = 0;
    $job_content = '';
    //

    $caned_resps_switch = isset($jobsearch_plugin_options['caned_resp_switch']) ? $jobsearch_plugin_options['caned_resp_switch'] : '';
    $caned_resp_deftempid = isset($jobsearch_plugin_options['caned_resp_def_tempid']) ? $jobsearch_plugin_options['caned_resp_def_tempid'] : '';
    if ($caned_resps_switch == 'on' && $caned_resp_deftempid > 0 && get_post_type($caned_resp_deftempid) == 'jobdesctemp') {
        $jbdesc_tmppost_obj = get_post($caned_resp_deftempid);
        $job_content = isset($jbdesc_tmppost_obj->post_content) ? $jbdesc_tmppost_obj->post_content : '';
        $job_content = apply_filters('the_content', $job_content);
    }
    //
    $job_sector = '';
    $job_type = '';
    $_job_filled = '';
    $_job_salary_currency = '';
    $_job_salary_pos = '';
    $_job_salary_deci = '';
    $_job_salary_sep = '';

    if (isset($_GET['job_id']) && $_GET['job_id'] > 0 && jobsearch_is_employer_job($_GET['job_id'])) {
        $real_job_id = $job_id = $_GET['job_id'];

        if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher')) {
            $job_id = jobsearch_wpml_is_original($job_id, 'post_job');
            $job_id = isset($job_id['original_ID']) && $job_id['original_ID'] > 0 ? $job_id['original_ID'] : $real_job_id;
        }

        $is_updating = true;
        $tr_lang_hap = true;

        if (isset($_GET['step']) && $_GET['step'] == 'confirm') {
            $tr_lang_hap = false;
        }
        $tr_lang_hap = apply_filters('jobsearch_allowflag_translate_job_with_wpml', $tr_lang_hap);
        if (function_exists('icl_object_id') && function_exists('wpml_init_language_switcher') && $tr_lang_hap === true) {
            $current_lang = $sitepress->get_current_language();

            $found_job = icl_object_id($job_id, 'job', false, $current_lang);

            if ($found_job <= 0) {
                $tr_job_obj = get_post($job_id);
                $tr_job_content = $tr_job_obj->post_content;
                $tr_job_content = apply_filters('the_content', $tr_job_content);
                $def_trid = $sitepress->get_element_trid($job_id);
                $ru_args = array(
                    'post_title' => get_the_title($job_id),
                    'post_content' => $tr_job_content,
                    'post_status' => 'publish',
                    'post_type' => 'job'
                );

                $ru_post_id = wp_insert_post($ru_args);
                $sitepress->set_element_language_details($ru_post_id, 'post_job', $def_trid, $current_lang);

                // employer assign
                $g_job_posted_by = get_post_meta($job_id, 'jobsearch_field_course_posted_by', true);
                if ($g_job_posted_by > 0) {
                    $g_job_posted_by = icl_object_id($g_job_posted_by, 'employer', true, $current_lang);
                    update_post_meta($ru_post_id, 'jobsearch_field_job_posted_by', $g_job_posted_by);
                }

                $sector_terms = wp_get_post_terms($job_id, 'sector');
                if (!empty($sector_terms)) {
                    $set_to_terms = array();
                    foreach ($sector_terms as $sector_term) {
                        $tr_tax_id = icl_object_id($sector_term->term_id, 'sector', true, $current_lang);
                        $set_to_terms[] = $sector_term->term_id;
                    }
                    wp_set_post_terms($ru_post_id, $set_to_terms, 'sector', false);
                }

                $type_terms = wp_get_post_terms($job_id, 'jobtype');
                if (!empty($type_terms)) {
                    $set_to_terms = array();
                    foreach ($type_terms as $type_term) {
                        $tr_tax_id = icl_object_id($type_term->term_id, 'jobtype', true, $current_lang);
                        $set_to_terms[] = $type_term->term_id;
                    }
                    wp_set_post_terms($ru_post_id, $set_to_terms, 'jobtype', false);
                }

                $skill_terms = wp_get_post_terms($job_id, 'skill');
                if (!empty($skill_terms)) {
                    $set_to_terms = array();
                    foreach ($skill_terms as $skill_term) {
                        $tr_tax_id = icl_object_id($skill_term->term_id, 'skill', true, $current_lang);
                        $set_to_terms[] = $skill_term->term_id;
                    }
                    wp_set_post_terms($ru_post_id, $set_to_terms, 'skill', false);
                }

                // location
                $loc1_val = get_post_meta($job_id, 'jobsearch_field_location_location1', true);

                if ($loc1_val != '') {
                    $loc_term_obj = get_term_by('slug', $loc1_val, 'job-location');
                    if (is_object($loc_term_obj) && isset($loc_term_obj->term_id)) {
                        $loc_term_id = $loc_term_obj->term_id;
                        $tr_tax_id = icl_object_id($loc_term_id, 'job-location', true, $current_lang);
                        $tr_loc_term_obj = get_term_by('id', $tr_tax_id, 'job-location');
                        update_post_meta($ru_post_id, 'jobsearch_field_location_location1', $tr_loc_term_obj->slug);
                    }
                }

                $loc1_val = get_post_meta($job_id, 'jobsearch_field_location_location2', true);

                if ($loc1_val != '') {
                    $loc_term_obj = get_term_by('slug', $loc1_val, 'job-location');
                    if (is_object($loc_term_obj) && isset($loc_term_obj->term_id)) {
                        $loc_term_id = $loc_term_obj->term_id;
                        $tr_tax_id = icl_object_id($loc_term_id, 'job-location', true, $current_lang);
                        $tr_loc_term_obj = get_term_by('id', $tr_tax_id, 'job-location');
                        update_post_meta($ru_post_id, 'jobsearch_field_location_location2', $tr_loc_term_obj->slug);
                    }
                }
                $loc1_val = get_post_meta($job_id, 'jobsearch_field_location_location3', true);
                if ($loc1_val != '') {
                    $loc_term_obj = get_term_by('slug', $loc1_val, 'job-location');
                    if (is_object($loc_term_obj) && isset($loc_term_obj->term_id)) {
                        $loc_term_id = $loc_term_obj->term_id;
                        $tr_tax_id = icl_object_id($loc_term_id, 'job-location', true, $current_lang);
                        $tr_loc_term_obj = get_term_by('id', $tr_tax_id, 'job-location');
                        update_post_meta($ru_post_id, 'jobsearch_field_location_location3', $tr_loc_term_obj->slug);
                    }
                }
                $loc1_val = get_post_meta($job_id, 'jobsearch_field_location_location4', true);
                if ($loc1_val != '') {
                    $loc_term_obj = get_term_by('slug', $loc1_val, 'job-location');
                    if (is_object($loc_term_obj) && isset($loc_term_obj->term_id)) {
                        $loc_term_id = $loc_term_obj->term_id;
                        $tr_tax_id = icl_object_id($loc_term_id, 'job-location', true, $current_lang);
                        $tr_loc_term_obj = get_term_by('id', $tr_tax_id, 'job-location');
                        update_post_meta($ru_post_id, 'jobsearch_field_location_location4', $tr_loc_term_obj->slug);
                    }
                }

                $application_deadline = get_post_meta($job_id, 'jobsearch_field_job_application_deadline_date', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_application_deadline_date', $application_deadline);

                $_job_filled = get_post_meta($job_id, 'jobsearch_field_job_filled', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_filled', $_job_filled);

                $_job_salary_type = get_post_meta($job_id, 'jobsearch_field_job_salary_type', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary_type', $_job_salary_type);

                $_job_apply_type = get_post_meta($job_id, 'jobsearch_field_job_apply_type', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_apply_type', $_job_apply_type);
                $_job_apply_url = get_post_meta($job_id, 'jobsearch_field_job_apply_url', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_apply_url', $_job_apply_url);
                $_job_apply_email = get_post_meta($job_id, 'jobsearch_field_job_apply_email', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_apply_email', $_job_apply_email);

                $_job_salary = get_post_meta($job_id, 'jobsearch_field_job_salary', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary', $_job_salary);
                $_job_max_salary = get_post_meta($job_id, 'jobsearch_field_job_max_salary', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_max_salary', $_job_max_salary);

                $_job_salary_currency = get_post_meta($job_id, 'jobsearch_field_job_salary_currency', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary_currency', $_job_salary_currency);
                $_job_salary_pos = get_post_meta($job_id, 'jobsearch_field_job_salary_pos', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary_pos', $_job_salary_pos);
                $_job_salary_deci = get_post_meta($job_id, 'jobsearch_field_job_salary_deci', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary_deci', $_job_salary_deci);
                $_job_salary_sep = get_post_meta($job_id, 'jobsearch_field_job_salary_sep', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_salary_sep', $_job_salary_sep);
                //
                do_action('jobsearch_dashboard_pass_values_to_duplicate_post', $job_id, $ru_post_id, 'job');
                //
                $tr_gal_ids_arr = get_post_meta($job_id, 'jobsearch_field_job_attachment_files', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_attachment_files', $tr_gal_ids_arr);
                //
                $tr_location_adres = get_post_meta($job_id, 'jobsearch_field_location_address', true);
                if ($tr_location_adres != '') {
                    update_post_meta($ru_post_id, 'jobsearch_field_location_address', $tr_location_adres);
                }
                $tr_location_lat = get_post_meta($job_id, 'jobsearch_field_location_lat', true);
                if ($tr_location_lat != '') {
                    update_post_meta($ru_post_id, 'jobsearch_field_location_lat', $tr_location_lat);
                }
                $tr_location_lng = get_post_meta($job_id, 'jobsearch_field_location_lng', true);
                if ($tr_location_lng != '') {
                    update_post_meta($ru_post_id, 'jobsearch_field_location_lng', $tr_location_lng);
                }
                $tr_location_zoom = get_post_meta($job_id, 'jobsearch_field_location_zoom', true);
                if ($tr_location_zoom != '') {
                    update_post_meta($ru_post_id, 'jobsearch_field_location_zoom', $tr_location_zoom);
                }
                $tr_location_hieght = get_post_meta($job_id, 'jobsearch_field_map_height', true);
                if ($tr_location_hieght != '') {
                    update_post_meta($ru_post_id, 'jobsearch_field_map_height', $tr_location_hieght);
                }
            } else {
                $ru_post_id = $found_job;
            }

            if ($ru_post_id > 0 && $ru_post_id != $job_id) {

                $job_tr_expiry = get_post_meta($job_id, 'jobsearch_field_job_expiry_date', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_expiry_date', $job_tr_expiry);

                $job_tr_posted = get_post_meta($job_id, 'jobsearch_field_job_publish_date', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_publish_date', $job_tr_posted);

                $job_tr_status = get_post_meta($job_id, 'jobsearch_field_job_status', true);
                update_post_meta($ru_post_id, 'jobsearch_field_job_status', $job_tr_status);
            }
            $job_id = icl_object_id($job_id, 'job', true, $current_lang);
        }

        $post_obj = get_post($job_id);
        $job_content = isset($post_obj->post_content) ? $post_obj->post_content : '';

        $sectors = wp_get_post_terms($job_id, 'sector');
        $job_sector = isset($sectors[0]->term_id) ? $sectors[0]->term_id : '';

        $jobtypes = wp_get_post_terms($job_id, 'jobtype');
        $job_type = isset($jobtypes[0]->term_id) ? $jobtypes[0]->term_id : '';

        $application_deadline = get_post_meta($job_id, 'jobsearch_field_job_application_deadline_date', true);
        if ($application_deadline != '') {
            $application_deadline = date('d-m-Y', $application_deadline);
        }

        $_job_filled = get_post_meta($job_id, 'jobsearch_field_job_filled', true);

        $_job_featured = get_post_meta($job_id, 'jobsearch_field_job_featured', true);

        $_job_apply_type = get_post_meta($job_id, 'jobsearch_field_job_apply_type', true);
        $_job_apply_url = get_post_meta($job_id, 'jobsearch_field_job_apply_url', true);
        $_job_apply_email = get_post_meta($job_id, 'jobsearch_field_job_apply_email', true);

        $_job_salary_type = get_post_meta($job_id, 'jobsearch_field_job_salary_type', true);
        $_job_salary = get_post_meta($job_id, 'jobsearch_field_job_salary', true);
        $_job_max_salary = get_post_meta($job_id, 'jobsearch_field_job_max_salary', true);

        $_job_salary_currency = get_post_meta($job_id, 'jobsearch_field_job_salary_currency', true);
        $_job_salary_pos = get_post_meta($job_id, 'jobsearch_field_job_salary_pos', true);
        $_job_salary_deci = get_post_meta($job_id, 'jobsearch_field_job_salary_deci', true);
        $_job_salary_sep = get_post_meta($job_id, 'jobsearch_field_job_salary_sep', true);
    }
    ?>
    <div class="jobsearch-employer-dasboard jobsearch-typo-wrap">

        <?php
        $post_job_without_reg = isset($jobsearch_plugin_options['job-post-wout-reg']) ? $jobsearch_plugin_options['job-post-wout-reg'] : '';
        if (!is_user_logged_in() && $post_job_without_reg != 'on') {
            $restrict_img = isset($jobsearch_plugin_options['job_post_restrict_img']) ? $jobsearch_plugin_options['job_post_restrict_img'] : '';
            $restrict_img_url = isset($restrict_img['url']) ? $restrict_img['url'] : '';

            $op_emp_register_allow = isset($jobsearch_plugin_options['login_employer_register']) ? $jobsearch_plugin_options['login_employer_register'] : '';
            ?>
            <div class="restrict-candidate-sec">
                <img src="<?php echo($restrict_img_url) ?>" alt="">
                <h2><?php esc_html_e('The Page is accessible only for Subscribed Employers', 'wp-jobsearch') ?></h2>
                <p><?php esc_html_e('If you are an employer please login to post a new job.', 'wp-jobsearch') ?></p>
                <div class="login-btns">
                    <a href="javascript:void(0);" class="jobsearch-open-signin-tab"><i
                                class="jobsearch-icon jobsearch-user"></i><?php esc_html_e('Login', 'wp-jobsearch') ?>
                    </a>
                    <?php
                    if ($op_emp_register_allow != 'no') {
                        ?>
                        <a href="javascript:void(0);" class="jobsearch-open-register-tab company-register-tab"><i
                                    class="jobsearch-icon jobsearch-plus"></i><?php esc_html_e('Become an Employer', 'wp-jobsearch') ?>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        } else {
            ob_start();
            ?>
            <form autocomplete="off" id="<?php echo apply_filters('jobsearch_job_posting_form_id', 'job-posting-form') ?>"
                  method="post" <?php echo(!is_user_logged_in() ? ' class="user-logform-err"' : '') ?>
                  enctype="multipart/form-data">
                <div class="jobsearch-employer-box-section">

                    <?php
                    if (is_user_logged_in() && !$is_employer) { ?>
                        <p><?php esc_html_e('You are not allowed to post a job. Only an Employer can Post a Job.', 'wp-jobsearch') ?></p>
                    <?php } else { ?>
                        <div class="jobsearch-profile-title">
                            <?php
                            if ($is_updating) { ?>
                                <h2><?php printf(esc_html__('Job "%s"', 'wp-jobsearch'), get_the_title($job_id)) ?></h2>
                                <?php
                            } else { ?>
                                <h2><?php esc_html_e('Post a New Job', 'wp-jobsearch') ?></h2>
                            <?php } ?>
                        </div>
                        <nav class="jobsearch-employer-jobnav">
                            <ul>
                                <li<?php echo(!isset($_GET['step']) || (isset($_GET['step']) && ($_GET['step'] != '')) ? ' class="active"' : '') ?>>
                                    <a href="<?php echo($is_updating && $free_jobs_allow != 'on' ? add_query_arg(array('tab' => 'user-job', 'job_id' => $job_id, 'action' => 'update'), $page_url) : 'javascript:void(0);') ?>">
                                        <i class="jobsearch-icon jobsearch-briefcase-1"></i>
                                        <span><?php esc_html_e('Job Detail', 'wp-jobsearch') ?></span>
                                    </a>
                                </li>
                                <?php
                                echo apply_filters('jobsearch_jobpost_stepslis_after_detail', '');
                                if ($free_jobs_allow != 'on') {
                                    ob_start();
                                    ?>
                                    <li<?php echo($is_updating && isset($_GET['step']) && ($_GET['step'] == 'package' || $_GET['step'] == 'confirm') ? ' class="active"' : '') ?>>
                                        <a href="<?php echo($is_updating ? add_query_arg(array('tab' => 'user-job', 'job_id' => $job_id, 'step' => 'package', 'action' => 'update'), $page_url) : 'javascript:void(0);') ?>">
                                            <i class="jobsearch-icon jobsearch-credit-card"></i>
                                            <span><?php esc_html_e('Package & Payments', 'wp-jobsearch') ?></span>
                                        </a>
                                    </li>
                                    <?php 
                                    $pkgstep_html = ob_get_clean();
                                    echo apply_filters('jobsearch_jobpostin_topsteps_pkgstep_html', $pkgstep_html, $is_updating);
                                }
                                ?>
                                <li<?php echo($is_updating && isset($_GET['step']) && $_GET['step'] == 'confirm' ? ' class="active"' : '') ?>>
                                    <a href="javascript:void(0);">
                                        <i class="jobsearch-icon jobsearch-checked"></i>
                                        <span><?php esc_html_e('Confirmation', 'wp-jobsearch') ?></span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php
                    $job_detail_tab = true;
                    if (isset($_GET['step']) && $_GET['step'] == 'confirm_user_job') {
                    $job_detail_tab = false;
                    $job_confirm_tab = true;

                    $_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : '';
                    ?>
                        <div class="jobsearch-employer-confitmation">
                            <?php
                            if ($_job_id > 0 && get_post_type($_job_id) == 'job') {
                                if ($job_submit_img_url != '') {
                                    ?>
                                    <img src="<?php echo($job_submit_img_url) ?>" alt="">
                                    <?php
                                }
                                ?>
                                <h2><?php echo($job_submit_title) ?></h2>
                                <p><?php esc_html_e('Your job is submitted. Logged in your account to manage this job.', 'wp-jobsearch') ?></p>
                                <div class="clearfix"></div>
                                <a href="javascript:void(0);"
                                   class="jobsearch-open-signin-tab"><?php esc_html_e('Sign In', 'wp-jobsearch') ?></a>
                                <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                    if (isset($_GET['step']) && $_GET['step'] == 'confirm_detail') {
                    $job_detail_tab = false;
                    $job_confirm_tab = true;

                    $_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : '';
                    ?>
                        <div class="jobsearch-employer-confitmation">
                            <?php
                            if ($_job_id > 0 && get_post_type($_job_id) == 'job') {
                                if ($job_submit_img_url != '') {
                                    ?>
                                    <img src="<?php echo($job_submit_img_url) ?>" alt="">
                                    <?php
                                }
                                ?>
                                <h2><?php echo($job_submit_title) ?></h2>
                                <p><?php esc_html_e('Your job is submitted. But you have to verify your email address in order to manage your job. Kindly check your email.', 'wp-jobsearch') ?></p>
                                <div class="clearfix"></div>
                                <a href="javascript:void(0);"
                                   class="jobsearch-open-signin-tab"><?php esc_html_e('Sign In', 'wp-jobsearch') ?></a>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    if ($is_updating && isset($_GET['step']) && $_GET['step'] == 'confirm') {
                        $job_detail_tab = false;
                        $job_confirm_tab = true;
                        ?>
                        <div class="jobsearch-employer-confitmation">
                            <?php
                            if ($job_submit_img_url != '') {
                                ?>
                                <img src="<?php echo($job_submit_img_url) ?>" alt="">
                                <?php
                            }
                            ?>
                            <h2><?php echo apply_filters('jobsearch_job_post_confrm_success_mtitle', $job_submit_title) ?></h2>
                            <p><?php echo apply_filters('jobsearch_job_post_confrm_success_msg', $job_submit_msg) ?></p>
                            <div class="clearfix"></div>
                            <a href="<?php echo add_query_arg(array('tab' => 'manage-jobs'), $page_url) ?>"><?php esc_html_e('Manage Jobs', 'wp-jobsearch') ?></a>
                            <a href="<?php echo get_permalink($job_id) ?>"><?php esc_html_e('View Job', 'wp-jobsearch') ?></a>
                        </div>
                        <?php
                    }
                    if ($free_jobs_allow != 'on' && $is_updating && isset($_GET['step']) && $_GET['step'] == 'package') {
                        $job_detail_tab = false;
                        $job_package_tab = true;

                        $job_is_active = jobsearch_check_job_approved_active($job_id);

                        // errors
                        if (isset($package_form_errs[0]) && $package_form_errs[0] != '') {
                            ?>
                            <div class="jobsearch-alert jobsearch-error-alert">
                                <p><?php echo($package_form_errs[0]) ?></p>
                            </div>
                            <?php
                        }

                        $args = array(
                            'post_type' => 'package',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'fields' => 'ids',
                            'order' => 'ASC',
                            'orderby' => 'title',
                            'meta_query' => array(
                                array(
                                    'key' => 'jobsearch_field_package_type',
                                    'value' => 'feature_job',
                                    'compare' => '=',
                                ),
                            ),
                        );
                        $pkgs_query = new WP_Query($args);

                        $preselect_opts_pckgs = isset($jobsearch_plugin_options['preselect-featjob-pkgs']) ? $jobsearch_plugin_options['preselect-featjob-pkgs'] : '';
                        if (!empty($preselect_opts_pckgs)) {
                            $fpkgs_posts = $preselect_opts_pckgs;
                        } else {
                            $fpkgs_posts = $pkgs_query->posts;
                        }

                        if (!empty($fpkgs_posts)) {
                            ob_start();
                            ?>
                            <div class="jobsearch-employer-payments">
                                <h2><?php esc_html_e('Aditional featured job package', 'wp-jobsearch') ?></h2>
                                <?php ?>
                                <table>
                                    <thead>
                                    <tr>
                                        <th><?php esc_html_e('Select', 'wp-jobsearch') ?></th>
                                        <th><?php esc_html_e('Title', 'wp-jobsearch') ?></th>
                                        <th><?php esc_html_e('Price', 'wp-jobsearch') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($fpkgs_posts as $pkg_id) {
                                        $pkg_rand = rand(10000000, 99999999);
                                        //$pkg_id = get_the_ID();
                                        $pkg_attach_product = get_post_meta($pkg_id, 'jobsearch_package_product', true);

                                        if ($pkg_attach_product != '' && get_page_by_path($pkg_attach_product, 'OBJECT', 'product')) {
                                            $pkg_otype = get_post_meta($pkg_id, 'jobsearch_field_package_type', true);

                                            $pkg_type = get_post_meta($pkg_id, 'jobsearch_field_charges_type', true);
                                            $pkg_price = get_post_meta($pkg_id, 'jobsearch_field_package_price', true);

                                            $job_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_job_expiry_time', true);
                                            $job_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_job_expiry_time_unit', true);

                                            $pkg_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time', true);
                                            $pkg_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time_unit', true);

                                            ob_start();
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="jobsearch-payments-checkbox">
                                                        <input id="pkg-<?php echo absint($pkg_rand) ?>"
                                                               name="job_package_featured"
                                                               value="<?php echo absint($pkg_id) ?>"
                                                               type="checkbox">
                                                        <label for="pkg-<?php echo absint($pkg_rand) ?>"><span></span></label>
                                                    </div>
                                                </td>
                                                <td><span><?php echo get_the_title($pkg_id) ?></span>
                                                    <small><?php esc_html_e('Select this package to make your job Featured.', 'wp-jobsearch') ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($pkg_type == 'paid') {
                                                        echo jobsearch_get_price_format($pkg_price, '', 'package');
                                                    } else {
                                                        esc_html_e('Free', 'wp-jobsearch');
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                            $featpkg_itm_html = ob_get_clean();
                                            echo apply_filters('jobsearch_postjob_featpkg_listitm_after', $featpkg_itm_html, $pkg_id, $job_id);
                                        }
                                    }
                                    wp_reset_postdata();
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                            $featured_pkg_html = ob_get_clean();
                            echo apply_filters('jobsearch_postjob_pkgs_featuredpkg_html', $featured_pkg_html, $job_id);
                        }
                        $args = array(
                            'post_type' => 'shop_order',
                            'posts_per_page' => -1,
                            'post_status' => 'wc-completed',
                            'order' => 'DESC',
                            'orderby' => 'ID',
                            'meta_query' => array(
                                array(
                                    'key' => 'package_type',
                                    'value' => apply_filters('jobsearch_emp_postjob_pkg_job_metakey', array('job', 'featured_jobs', 'emp_allin_one', 'employer_profile')),
                                    'compare' => 'IN',
                                ),
                                array(
                                    'key' => 'package_expiry_timestamp',
                                    'value' => strtotime(current_time('d-m-Y H:i:s')),
                                    'compare' => '>',
                                ),
                                array(
                                    'key' => 'jobsearch_order_user',
                                    'value' => $user_id,
                                    'compare' => '=',
                                ),
                            ),
                        );

                        $pkgs_query = new WP_Query($args);
                        if ($pkgs_query->have_posts()) {

                            ob_start();
                            while ($pkgs_query->have_posts()) : $pkgs_query->the_post();
                                $pkg_rand = rand(10000000, 99999999);
                                $pkg_order_id = get_the_ID();
                                $pkg_order_name = get_post_meta($pkg_order_id, 'package_name', true);

                                $unlimited_pkg = get_post_meta($pkg_order_id, 'unlimited_pkg', true);
                                //
                                $pkg_type = get_post_meta($pkg_order_id, 'package_type', true);

                                $total_jobs = get_post_meta($pkg_order_id, 'num_of_jobs', true);
                                $unlimited_numjobs = get_post_meta($pkg_order_id, 'unlimited_numjobs', true);
                                if ($unlimited_numjobs == 'yes') {
                                    $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                }
                                $total_jobs = apply_filters('jobsearch_emp_dash_pkg_total_jobs_count', $total_jobs, $pkg_order_id);

                                $job_exp_dur = get_post_meta($pkg_order_id, 'job_expiry_time', true);
                                $job_exp_dur_unit = get_post_meta($pkg_order_id, 'job_expiry_time_unit', true);

                                $used_jobs = jobsearch_pckg_order_used_jobs($pkg_order_id);
                                if ($unlimited_numjobs == 'yes') {
                                    $used_jobs = '-';
                                }
                                $used_jobs = apply_filters('jobsearch_emp_dash_pkg_used_jobs_count', $used_jobs, $pkg_order_id);
                                $remaining_jobs = jobsearch_pckg_order_remaining_jobs($pkg_order_id);
                                if ($unlimited_numjobs == 'yes') {
                                    $remaining_jobs = '-';
                                }
                                $remaining_jobs = apply_filters('jobsearch_emp_dash_pkg_remain_jobs_count', $remaining_jobs, $pkg_order_id);

                                $featjob_pkg_clas = '';
                                if ($pkg_type == 'emp_allin_one') {
                                    $total_jobs = get_post_meta($pkg_order_id, 'allin_num_jobs', true);
                                    $unlimited_numjobs = get_post_meta($pkg_order_id, 'unlimited_numjobs', true);
                                    if ($unlimited_numjobs == 'yes') {
                                        $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }
                                    //
                                    $total_fjobs = get_post_meta($pkg_order_id, 'allin_num_fjobs', true);
                                    $unlimited_numfjobs = get_post_meta($pkg_order_id, 'unlimited_numfjobs', true);
                                    if ($unlimited_numfjobs == 'yes') {
                                        $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }

                                    $job_exp_dur = get_post_meta($pkg_order_id, 'allinjob_expiry_time', true);
                                    $job_exp_dur_unit = get_post_meta($pkg_order_id, 'allinjob_expiry_time_unit', true);

                                    $remain_featjobs = jobsearch_allinpckg_order_remaining_fjobs($pkg_order_id);
                                    if ($remain_featjobs > 0) {
                                        $featjob_pkg_clas = 'class="with_feature_jobs"';
                                    }

                                    $used_jobs = jobsearch_allinpckg_order_used_jobs($pkg_order_id);
                                    $remaining_jobs = jobsearch_allinpckg_order_remaining_jobs($pkg_order_id);
                                    if ($unlimited_numjobs == 'yes') {
                                        $used_jobs = '-';
                                        $remaining_jobs = '-';
                                    }
                                    //
                                    $used_fcrds = jobsearch_allinpckg_order_used_fjobs($pkg_order_id);
                                    $remaining_fcrds = $remain_featjobs;
                                    if ($unlimited_numfjobs == 'yes') {
                                        $used_fcrds = '-';
                                        $remaining_fcrds = '-';
                                    }

                                    //
                                    $pkg_expired = jobsearch_allinpckg_order_is_expired($pkg_order_id);
                                    if ($pkg_expired) {
                                        $pkg_expired = jobsearch_allinpckg_order_is_expired($pkg_order_id, 'fjobs');
                                    }
                                    if ($pkg_expired) {
                                        continue;
                                    }
                                } else if ($pkg_type == 'employer_profile') {
                                    $total_jobs = get_post_meta($pkg_order_id, 'emprof_num_jobs', true);
                                    $unlimited_numjobs = get_post_meta($pkg_order_id, 'unlimited_numjobs', true);
                                    if ($unlimited_numjobs == 'yes') {
                                        $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }
                                    //
                                    $total_fjobs = get_post_meta($pkg_order_id, 'emprof_num_fjobs', true);
                                    $unlimited_numfjobs = get_post_meta($pkg_order_id, 'unlimited_numfjobs', true);
                                    if ($unlimited_numfjobs == 'yes') {
                                        $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }

                                    $job_exp_dur = get_post_meta($pkg_order_id, 'emprofjob_expiry_time', true);
                                    $job_exp_dur_unit = get_post_meta($pkg_order_id, 'emprofjob_expiry_time_unit', true);

                                    $remain_featjobs = jobsearch_emprofpckg_order_remaining_fjobs($pkg_order_id);
                                    if ($remain_featjobs > 0) {
                                        $featjob_pkg_clas = 'class="with_feature_jobs"';
                                    }

                                    $used_jobs = jobsearch_emprofpckg_order_used_jobs($pkg_order_id);
                                    $remaining_jobs = jobsearch_emprofpckg_order_remaining_jobs($pkg_order_id);
                                    if ($unlimited_numjobs == 'yes') {
                                        $used_jobs = '-';
                                        $remaining_jobs = '-';
                                    }
                                    //
                                    $used_fcrds = jobsearch_emprofpckg_order_used_fjobs($pkg_order_id);
                                    $remaining_fcrds = $remain_featjobs;
                                    if ($unlimited_numfjobs == 'yes') {
                                        $used_fcrds = '-';
                                        $remaining_fcrds = '-';
                                    }

                                    //
                                    $pkg_expired = jobsearch_emprofpckg_order_is_expired($pkg_order_id);
                                    if ($pkg_expired) {
                                        $pkg_expired = jobsearch_emprofpckg_order_is_expired($pkg_order_id, 'fjobs');
                                    }
                                    if ($pkg_expired) {
                                        continue;
                                    }
                                } else if ($pkg_type == 'featured_jobs') {
                                    $total_jobs = get_post_meta($pkg_order_id, 'num_of_fjobs', true);
                                    $unlimited_numfjobs = get_post_meta($pkg_order_id, 'unlimited_numfjobs', true);
                                    if ($unlimited_numfjobs == 'yes') {
                                        $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }

                                    $total_fjobs = get_post_meta($pkg_order_id, 'feat_job_credits', true);
                                    $unlimited_numfcrds = get_post_meta($pkg_order_id, 'unlimited_fjobcrs', true);
                                    if ($unlimited_numfcrds == 'yes') {
                                        $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                    }

                                    $job_exp_dur = get_post_meta($pkg_order_id, 'fjob_expiry_time', true);
                                    $job_exp_dur_unit = get_post_meta($pkg_order_id, 'fjob_expiry_time_unit', true);

                                    $remain_featjob_credits = jobsearch_pckg_order_remain_featjob_credits($pkg_order_id);
                                    if ($remain_featjob_credits > 0) {
                                        $featjob_pkg_clas = 'class="with_feature_jobs"';
                                    }

                                    $used_jobs = jobsearch_pckg_order_used_fjobs($pkg_order_id);
                                    $remaining_jobs = jobsearch_pckg_order_remaining_fjobs($pkg_order_id);
                                    if ($unlimited_numfjobs == 'yes') {
                                        $used_jobs = '-';
                                        $remaining_jobs = '-';
                                    }

                                    //
                                    $used_fcrds = jobsearch_pckg_order_used_featjob_credits($pkg_order_id);
                                    $remaining_fcrds = $remain_featjob_credits;
                                    if ($unlimited_numfjobs == 'yes') {
                                        $used_fcrds = '-';
                                        $remaining_fcrds = '-';
                                    }

                                    $pkg_expired = jobsearch_fjobs_pckg_order_is_expired($pkg_order_id);
                                    if ($pkg_expired) {
                                        continue;
                                    }
                                } else {
                                    $pkg_expired = apply_filters('jobsearch_emp_postjob_pkg_expiry_check', jobsearch_pckg_order_is_expired($pkg_order_id), $pkg_order_id);
                                    if ($pkg_expired) {
                                        continue;
                                    }
                                }
                                ob_start();
                                ?>
                                <tr>
                                    <td>
                                        <div class="jobsearch-payments-checkbox">
                                            <input id="pkg-<?php echo absint($pkg_rand) ?>" <?php echo($featjob_pkg_clas) ?>
                                                   name="job_subs_package"
                                                   value="<?php echo absint($pkg_order_id) ?>" type="checkbox">
                                            <label for="pkg-<?php echo absint($pkg_rand) ?>"><span></span></label>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        ob_start();
                                        ?>
                                        <span><?php echo($pkg_order_name) ?></span>
                                        <?php
                                        $pkg_name_html = ob_get_clean();
                                        echo apply_filters('jobsearch_emp_dashboard_pkgs_list_pkg_title', $pkg_name_html, $pkg_order_id);
                                        ?>
                                    </td>
                                    <?php
                                    if ($unlimited_pkg == 'yes') {
                                        ?>
                                        <td><?php esc_html_e('Never', 'wp-jobsearch') ?></td>
                                        <?php
                                    } else {
                                        ?>
                                        <td><?php echo absint($job_exp_dur) . ' ' . jobsearch_get_duration_unit_str($job_exp_dur_unit) ?></td>
                                        <?php
                                    }
                                    if ($pkg_type == 'emp_allin_one' || $pkg_type == 'featured_jobs' || $pkg_type == 'employer_profile') {
                                        ?>
                                        <td>
                                            <?php printf(esc_html__('Normal Jobs: %s', 'wp-jobsearch'), $total_jobs) ?>
                                            <br>
                                            <?php printf(esc_html__('Featured Credits: %s', 'wp-jobsearch'), $total_fjobs) ?>
                                        </td>
                                        <td>
                                            <?php printf(esc_html__('Normal Jobs: %s', 'wp-jobsearch'), $used_jobs) ?>
                                            <br>
                                            <?php printf(esc_html__('Featured Credits: %s', 'wp-jobsearch'), $used_fcrds) ?>
                                        </td>
                                        <td>
                                            <?php printf(esc_html__('Normal Jobs: %s', 'wp-jobsearch'), $remaining_jobs) ?>
                                            <br>
                                            <?php printf(esc_html__('Featured Credits: %s', 'wp-jobsearch'), $remaining_fcrds) ?>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td><?php echo($total_jobs) ?></td>
                                        <td><?php echo($used_jobs) ?></td>
                                        <td><?php echo($remaining_jobs) ?></td>
                                        <?php
                                    }
                                    ?>
                                </tr>
                                <?php
                                $buy_exst_item_html = ob_get_clean();
                                $buy_exst_item_html = apply_filters('jobsearch_postjob_pkgs_buyexist_item_html', $buy_exst_item_html, $pkg_order_id, $job_id);
                                echo($buy_exst_item_html);
                            endwhile;
                            wp_reset_postdata();
                            $exict_pkgs_html = ob_get_clean();

                            if ($exict_pkgs_html != '') {
                                ob_start();
                                ?>
                                <div class="jobsearch-employer-payments">
                                    <h2><?php esc_html_e('Select from already purchased package', 'wp-jobsearch') ?></h2>
                                    <table class="alexist-plans-list">
                                        <thead>
                                        <tr>
                                            <th><?php esc_html_e('Select', 'wp-jobsearch') ?></th>
                                            <th><?php esc_html_e('Title', 'wp-jobsearch') ?></th>
                                            <th><?php esc_html_e('Job Expiry', 'wp-jobsearch') ?></th>
                                            <th><?php echo apply_filters('jobsearch_emp_postjob_pkg_job_num_label', esc_html__('Total', 'wp-jobsearch')) ?></th>
                                            <th><?php esc_html_e('Used', 'wp-jobsearch') ?></th>
                                            <th><?php esc_html_e('Remaining', 'wp-jobsearch') ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        echo($exict_pkgs_html);
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="makefeat-job-sec feat-with-already-purp" style="display: none;">
                                    <ul class="jobsearch-row jobsearch-employer-profile-form">
                                        <li class="jobsearch-column-6">
                                            <label><?php esc_html_e('Make this job featured', 'wp-jobsearch') ?></label>
                                            <div class="jobsearch-profile-select">
                                                <select id="jobsearch_job_apply_type" class="selectize-select"
                                                        name="make_job_feature_alredy">
                                                    <option value="no"><?php esc_html_e('No', 'wp-jobsearch') ?></option>
                                                    <option value="yes"><?php esc_html_e('Yes', 'wp-jobsearch') ?></option>
                                                </select>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <?php
                                $alrdybuy_pkg_html = ob_get_clean();
                                echo apply_filters('jobsearch_postjob_pkgs_alrdybuypkg_html', $alrdybuy_pkg_html, $job_id);
                                ob_start();
                                ?>
                                <div class="restrict-candidate-sec">
                                    <div class="jobsearch-box-title">
                                        <span><?php esc_html_e('OR', 'wp-jobsearch') ?></span>
                                    </div>
                                </div>
                                <?php
                                $buy_or_html = ob_get_clean();
                                echo apply_filters('jobsearch_postjob_pkgs_buyor_html', $buy_or_html, $job_id);
                            }
                        }
                        ob_start();
                        ?>
                        <div class="jobsearch-employer-payments">
                            <h2><?php esc_html_e('Buy new package', 'wp-jobsearch') ?></h2>
                            <?php
                            $args = array(
                                'post_type' => 'package',
                                'posts_per_page' => -1,
                                'post_status' => 'publish',
                                'fields' => 'ids',
                                'order' => 'ASC',
                                'orderby' => 'title',
                                'meta_query' => array(
                                    array(
                                        'key' => 'jobsearch_field_package_type',
                                        'value' => apply_filters('jobsearch_emp_postjob_pkg_job_metakey', array('job', 'featured_jobs', 'emp_allin_one', 'employer_profile')),
                                        'compare' => 'IN',
                                    ),
                                ),
                            );
                            $pkgs_query = new WP_Query($args);

                            $preselect_opts_pckgs = isset($jobsearch_plugin_options['preselect-postjob-pkgs']) ? $jobsearch_plugin_options['preselect-postjob-pkgs'] : '';
                            if (!empty($preselect_opts_pckgs)) {
                                $pkgs_qposts = $preselect_opts_pckgs;
                            } else {
                                $pkgs_qposts = $pkgs_query->posts;
                            }

                            if (!empty($pkgs_qposts)) {
                                ?>
                                <table class="buynew-plans-list">
                                    <thead>
                                    <tr>
                                        <th><?php esc_html_e('Select', 'wp-jobsearch') ?></th>
                                        <th><?php esc_html_e('Title', 'wp-jobsearch') ?></th>
                                        <th><?php esc_html_e('Price', 'wp-jobsearch') ?></th>
                                        <th><?php echo apply_filters('jobsearch_emp_postjob_pkg_job_num_label', esc_html__('Total', 'wp-jobsearch')) ?></th>
                                        <th><?php esc_html_e('Job Expiry', 'wp-jobsearch') ?></th>
                                        <th><?php esc_html_e('Package Expiry', 'wp-jobsearch') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($pkgs_qposts as $pkg_id) {
                                        $pkg_rand = rand(10000000, 99999999);
                                        //$pkg_id = get_the_ID();

                                        $pkg_attach_product = get_post_meta($pkg_id, 'jobsearch_package_product', true);

                                        if ($pkg_attach_product != '' && get_page_by_path($pkg_attach_product, 'OBJECT', 'product')) {

                                            $pkg_otype = get_post_meta($pkg_id, 'jobsearch_field_package_type', true);

                                            $unlimited_pkg = get_post_meta($pkg_id, 'jobsearch_field_unlimited_pkg', true);

                                            $pkg_type = get_post_meta($pkg_id, 'jobsearch_field_charges_type', true);
                                            $pkg_price = get_post_meta($pkg_id, 'jobsearch_field_package_price', true);

                                            $unlimited_numjobs = get_post_meta($pkg_id, 'jobsearch_field_unlimited_numjobs', true);
                                            $unlimited_jobexptm = get_post_meta($pkg_id, 'jobsearch_field_unlimited_jobsexp', true);

                                            //
                                            $total_jobs = get_post_meta($pkg_id, 'jobsearch_field_num_of_jobs', true);
                                            if ($unlimited_numjobs == 'on') {
                                                $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                            }
                                            $total_jobs = apply_filters('jobsearch_emp_postjob_pkg_bp_jobs_count', $total_jobs, $pkg_id);

                                            $job_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_job_expiry_time', true);
                                            $job_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_job_expiry_time_unit', true);

                                            //
                                            $is_pckg_subscribed = jobsearch_pckg_is_subscribed($pkg_id);

                                            $featjob_pkg_clas = '';
                                            if ($pkg_otype == 'emp_allin_one') {
                                                $unlimited_numjobs = get_post_meta($pkg_id, 'jobsearch_field_unlim_allinjobs', true);
                                                $unlimited_numfjobs = get_post_meta($pkg_id, 'jobsearch_field_unlim_allinfjobs', true);
                                                $unlimited_jobexptm = get_post_meta($pkg_id, 'jobsearch_field_unlim_allinjobexp', true);

                                                $total_jobs = get_post_meta($pkg_id, 'jobsearch_field_allin_num_jobs', true);
                                                if ($unlimited_numjobs == 'on') {
                                                    $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }
                                                //
                                                $total_fjobs = get_post_meta($pkg_id, 'jobsearch_field_allin_num_fjobs', true);
                                                if ($unlimited_numfjobs == 'on') {
                                                    $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }

                                                $feat_job_credits = get_post_meta($pkg_id, 'jobsearch_field_allin_num_fjobs', true);
                                                if ($feat_job_credits > 0) {
                                                    $featjob_pkg_clas = 'class="with_feature_jobs"';
                                                }

                                                $job_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_allinjob_expiry_time', true);
                                                $job_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_allinjob_expiry_time_unit', true);

                                                //
                                                $is_pckg_subscribed = jobsearch_allinpckg_is_subscribed($pkg_id);
                                            } else if ($pkg_otype == 'employer_profile') {
                                                $unlimited_numjobs = get_post_meta($pkg_id, 'jobsearch_field_unlim_emprofjobs', true);
                                                $unlimited_numfjobs = get_post_meta($pkg_id, 'jobsearch_field_unlim_emproffjobs', true);
                                                $unlimited_jobexptm = get_post_meta($pkg_id, 'jobsearch_field_unlim_emprofjobexp', true);

                                                $total_jobs = get_post_meta($pkg_id, 'jobsearch_field_emprof_num_jobs', true);
                                                if ($unlimited_numjobs == 'on') {
                                                    $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }
                                                //
                                                $total_fjobs = get_post_meta($pkg_id, 'jobsearch_field_emprof_num_fjobs', true);
                                                if ($unlimited_numfjobs == 'on') {
                                                    $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }

                                                $feat_job_credits = get_post_meta($pkg_id, 'jobsearch_field_emprof_num_fjobs', true);
                                                if ($feat_job_credits > 0) {
                                                    $featjob_pkg_clas = 'class="with_feature_jobs"';
                                                }

                                                $job_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_emprofjob_expiry_time', true);
                                                $job_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_emprofjob_expiry_time_unit', true);

                                                //
                                                $is_pckg_subscribed = jobsearch_emprofpckg_is_subscribed($pkg_id);
                                            } else if ($pkg_otype == 'featured_jobs') {
                                                $unlimited_numfjobs = get_post_meta($pkg_id, 'jobsearch_field_unlimited_numfjobs', true);
                                                $unlimited_numfcrds = get_post_meta($pkg_id, 'jobsearch_field_unlimited_fjobscr', true);
                                                $unlimited_jobexptm = get_post_meta($pkg_id, 'jobsearch_field_unlimited_fjobexp', true);

                                                $total_jobs = get_post_meta($pkg_id, 'jobsearch_field_num_of_fjobs', true);
                                                if ($unlimited_numfjobs == 'on') {
                                                    $total_jobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }

                                                $feat_job_credits = get_post_meta($pkg_id, 'jobsearch_field_feat_job_credits', true);
                                                $total_fjobs = $feat_job_credits;
                                                if ($unlimited_numfcrds == 'on') {
                                                    $total_fjobs = esc_html__('Unlimited', 'wp-jobsearch');
                                                }
                                                if ($feat_job_credits > 0) {
                                                    $featjob_pkg_clas = 'class="with_feature_jobs"';
                                                }

                                                $job_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_fjob_expiry_time', true);
                                                $job_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_fjob_expiry_time_unit', true);

                                                //
                                                $is_pckg_subscribed = jobsearch_fjobs_pckg_is_subscribed($pkg_id);
                                            }

                                            $pkg_exp_dur = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time', true);
                                            $pkg_exp_dur_unit = get_post_meta($pkg_id, 'jobsearch_field_package_expiry_time_unit', true);

                                            ob_start();
                                            ?>
                                            <tr id="buy-newpkgitem-<?php echo absint($pkg_rand) ?>"<?php echo($is_pckg_subscribed ? ' class="pkg-disabled"' : '') ?>>
                                                <td>
                                                    <div class="jobsearch-payments-checkbox">
                                                        <input id="pkg-<?php echo absint($pkg_rand) ?>"
                                                               name="job_package_new" <?php echo($featjob_pkg_clas) ?> <?php echo($is_pckg_subscribed ? 'disabled="disabled"' : '') ?>
                                                               value="<?php echo absint($pkg_id) ?>" type="checkbox">
                                                        <label for="pkg-<?php echo absint($pkg_rand) ?>"><span></span></label>
                                                    </div>
                                                    <?php
                                                    if ($is_pckg_subscribed) {
                                                        ?>
                                                        <script>
                                                            jQuery(document).on('click', '#buy-newpkgitem-<?php echo($pkg_rand) ?>', function () {
                                                                jobsearch_modal_popup_open('JobSearchPkgExstErr<?php echo($pkg_rand) ?>');
                                                            });
                                                        </script>
                                                        <?php
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    ob_start();
                                                    ?>
                                                    <span><?php echo get_the_title($pkg_id) ?></span>
                                                    <?php
                                                    $pkg_name_html = ob_get_clean();
                                                    echo apply_filters('jobsearch_emp_dashboard_pkgs_list_pkg_title', $pkg_name_html, $pkg_id, 'package');
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($pkg_type == 'paid') {
                                                        echo jobsearch_get_price_format($pkg_price, '', 'package');
                                                    } else {
                                                        esc_html_e('Free', 'wp-jobsearch');
                                                    }
                                                    ?>
                                                </td>
                                                <?php
                                                if ($pkg_otype == 'emp_allin_one' || $pkg_otype == 'featured_jobs' || $pkg_otype == 'employer_profile') {
                                                    ?>
                                                    <td>
                                                        <?php printf(esc_html__('Normal Jobs: %s', 'wp-jobsearch'), $total_jobs) ?>
                                                        <br>
                                                        <?php printf(esc_html__('Featured Credits: %s', 'wp-jobsearch'), $total_fjobs) ?>
                                                    </td>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <td><?php echo($total_jobs) ?></td>
                                                    <?php
                                                }
                                                if ($unlimited_jobexptm == 'on') {
                                                    ?>
                                                    <td><?php esc_html_e('Never', 'wp-jobsearch') ?></td>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <td><?php echo absint($job_exp_dur) . ' ' . jobsearch_get_duration_unit_str($job_exp_dur_unit) ?></td>
                                                    <?php
                                                }
                                                if ($unlimited_pkg == 'on') {
                                                    ?>
                                                    <td><?php esc_html_e('Never', 'wp-jobsearch') ?></td>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <td><?php echo absint($pkg_exp_dur) . ' ' . jobsearch_get_duration_unit_str($pkg_exp_dur_unit) ?></td>
                                                    <?php
                                                }
                                                if ($is_pckg_subscribed) {
                                                    $popup_args = array('p_pkg_id' => $pkg_id, 'p_rand_id' => $pkg_rand);
                                                    add_action('wp_footer', function () use ($popup_args) {

                                                        extract(shortcode_atts(array(
                                                            'p_pkg_id' => '',
                                                            'p_rand_id' => ''
                                                        ), $popup_args));
                                                        ?>
                                                        <div class="jobsearch-modal fade"
                                                             id="JobSearchPkgExstErr<?php echo($p_rand_id) ?>">
                                                            <div class="modal-inner-area">&nbsp;</div>
                                                            <div class="modal-content-area">
                                                                <div class="modal-box-area">
                                                                    <span class="modal-close"><i
                                                                                class="fa fa-times"></i></span>
                                                                    <div class="jobsearch-user-errer-pop">
                                                                        <p class="conf-msg"
                                                                           style="text-align: left;"><?php esc_html_e('This plan is already active and available for you. You can buy more of this plan once the number of job postings are exhausted or when the plan expires.', 'wp-jobsearch') ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }, 11, 1);
                                                }
                                                ?>
                                            </tr>
                                            <?php
                                            $buy_new_item_html = ob_get_clean();
                                            $buy_new_item_html = apply_filters('jobsearch_postjob_pkgs_buynew_item_html', $buy_new_item_html, $pkg_id, $job_id, 6);
                                            echo($buy_new_item_html);
                                        }
                                    }
                                    wp_reset_postdata();
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            } else {
                                ?>
                                <p><?php esc_html_e('No Package found.', 'wp-jobsearch') ?></p>
                                <?php
                            }
                            ?>
                            <script>
                                jQuery('.alexist-plans-list').find('input[type="radio"]').on('change', function () {
                                    jQuery('.buynew-plans-list').find('input[type="radio"]').prop('checked', false);
                                    jQuery('.buynew-plans-list').find('input[type="radio"]').removeAttr('checked');
                                });
                                jQuery('.buynew-plans-list').find('input[type="radio"]').on('change', function () {
                                    jQuery('.alexist-plans-list').find('input[type="radio"]').prop('checked', false);
                                    jQuery('.alexist-plans-list').find('input[type="radio"]').removeAttr('checked');
                                });
                                jQuery(document).on('change', 'input[name=job_package_featured], input[name=job_package_new]', function () {
                                    var _this_chk = jQuery(this);
                                    var this_val = _this_chk.val();
                                    var this_parent = _this_chk.parents('tr');
                                    if (this_parent.next('tr').hasClass('wc-prodaddon-info')) {
                                        if (_this_chk.is(":checked")) {
                                            jQuery('tr.prodaddon-info-' + this_val).slideDown();
                                        } else {
                                            jQuery('tr.prodaddon-info-' + this_val).slideUp();
                                        }
                                    }
                                });
                            </script>
                        </div>
                        <div class="makefeat-job-sec feat-with-fresh-npkg" style="display: none;">
                            <ul class="jobsearch-row jobsearch-employer-profile-form">
                                <li class="jobsearch-column-6">
                                    <label><?php esc_html_e('Make this job featured', 'wp-jobsearch') ?></label>
                                    <div class="jobsearch-profile-select">
                                        <select id="jobsearch_job_apply_type" class="selectize-select" name="make_job_feature">
                                            <option value="no"><?php esc_html_e('No', 'wp-jobsearch') ?></option>
                                            <option value="yes"><?php esc_html_e('Yes', 'wp-jobsearch') ?></option>
                                        </select>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <?php
                        $buy_new_html = ob_get_clean();
                        $buy_new_html = apply_filters('jobsearch_postjob_pkgs_buynew_html', $buy_new_html, $job_id);
                        echo($buy_new_html);
                    } else {
                        if ($job_detail_tab && !isset($_GET['step'])) {
                            // Canned msgs script add
                            add_action('wp_footer', function() {
                            $jobsearch__options = get_option('jobsearch_plugin_options');

                            $caned_resps_title = isset($jobsearch__options['caned_resp_jobpost_title']) && $jobsearch__options['caned_resp_jobpost_title'] != '' ? $jobsearch__options['caned_resp_jobpost_title'] : __('Canned Messages', 'wp-jobsearch');
                            $caned_resps_switch = isset($jobsearch__options['caned_resp_switch']) ? $jobsearch__options['caned_resp_switch'] : '';
                            if ($caned_resps_switch != 'on') {
                                return false;
                            }

                            $args = array(
                                'post_type' => 'jobdesctemp',
                                'posts_per_page' => 100,
                                'post_status' => 'publish',
                                'fields' => 'ids',
                                'order' => 'DESC',
                                'orderby' => 'ID',
                            );
                            $temps_query = new WP_Query($args);
                            $temps_posts = $temps_query->posts;
                            wp_reset_postdata();
                            ?>
                            <script type="text/javascript">
                                var joblistin_caned_msgstitl = '<?php echo($caned_resps_title) ?>';
                                var joblistin_caned_msgs;
                                <?php
                                if (!empty($temps_posts)) {
                                $to_ret_aray = array();
                                $msgs_count = 0;
                                foreach ($temps_posts as $temps_postid) {
                                    $the_title = get_the_title($temps_postid);
                                    $post_obj = get_post($temps_postid);
                                    $cont_desc = isset($post_obj->post_content) ? $post_obj->post_content : '';
                                    ob_start();
                                    echo apply_filters('the_content', $cont_desc);
                                    $the_desc = ob_get_clean();
                                    if ($the_title != '' && $cont_desc != '') {
                                        $to_ret_aray[] = array('title' => $the_title, 'desc' => $the_desc);
                                    }
                                    $msgs_count++;
                                }
                                if (!empty($to_ret_aray)) {
                                $to_ret_aray = json_encode($to_ret_aray);
                                ?>
                                joblistin_caned_msgs = jQuery.parseJSON('<?php echo addslashes($to_ret_aray) ?>');
                                <?php
                                }
                                }
                                ?>
                            </script>
                            <?php
                        }, 11);
                        //

                        $job_desc_with_media = isset($jobsearch_plugin_options['job_desc_with_media']) ? $jobsearch_plugin_options['job_desc_with_media'] : '';
                        if (isset($job_form_errs['post_errors']) && $job_form_errs['post_errors'] != '') {
                            ?>
                            <div class="jobsearch-alert jobsearch-error-alert">
                                <p><?php echo ($job_form_errs['post_errors']) ?></p>
                            </div>
                            <?php
                        }

                        //
                        $to_deadline_avail = 0;
                        $is_updeadline_flag = false;
                        if ($is_updating) {
                            $job_expiry_days_pkg = 0;
                            $job_attach_pckgs = get_post_meta($job_id, 'attach_packages_array', true);
                            //var_dump($job_attach_pckgs);
                            $job_attach_lastpkg = !empty($job_attach_pckgs) ? end($job_attach_pckgs) : '';
                            if (!empty($job_attach_lastpkg) && isset($job_attach_lastpkg['job_expiry_time']) && $job_attach_lastpkg['job_expiry_time'] > 0) {
                                $job_expiry_days_pkg = $job_attach_lastpkg['job_expiry_time'];
                                $job_expiry_unit_pkg = $job_attach_lastpkg['job_expiry_time_unit'];
                            } else if (!empty($job_attach_lastpkg) && isset($job_attach_lastpkg['fjob_expiry_time']) && $job_attach_lastpkg['fjob_expiry_time'] > 0) {
                                $job_expiry_days_pkg = $job_attach_lastpkg['fjob_expiry_time'];
                                $job_expiry_unit_pkg = $job_attach_lastpkg['fjob_expiry_time_unit'];
                            } else if (!empty($job_attach_lastpkg) && isset($job_attach_lastpkg['allinjob_expiry_time']) && $job_attach_lastpkg['allinjob_expiry_time'] > 0) {
                                $job_expiry_days_pkg = $job_attach_lastpkg['allinjob_expiry_time'];
                                $job_expiry_unit_pkg = $job_attach_lastpkg['allinjob_expiry_time_unit'];
                            }
                            if ($job_expiry_days_pkg > 0) {
                                $is_updeadline_flag = true;
                                $to_deadline_avail = $job_expiry_days_pkg;
                                if ($job_expiry_unit_pkg == 'weeks') {
                                    $to_deadline_avail = $to_deadline_avail * 7;
                                } else if ($job_expiry_unit_pkg == 'months') {
                                    $to_deadline_avail = $to_deadline_avail * 30;
                                } else if ($job_expiry_unit_pkg == 'years') {
                                    $to_deadline_avail = $to_deadline_avail * 365;
                                }
                            }
                        }
                        if (!$is_updeadline_flag) {
                            $job_expiry_days_op = isset($jobsearch_plugin_options['free-job-post-expiry']) && $jobsearch_plugin_options['free-job-post-expiry'] > 0 ? $jobsearch_plugin_options['free-job-post-expiry'] : 0;
                            if ($job_expiry_days_op > 0) {
                                $to_deadline_avail = $job_expiry_days_op;
                            }
                        }
                        ?>
                            <script>
                                jQuery(document).ready(function () {
                                    var todayDate = new Date().getDate();
                                    jQuery('#jobsearch_job_application_deadline').datetimepicker({
                                        timepicker: true,
                                        format: 'd-m-Y',
                                        <?php
                                        if ($to_deadline_avail > 0) {
                                        ?>
                                        minDate: new Date(new Date().setDate(todayDate)),
                                        maxDate: new Date(new Date().setDate(todayDate + <?php echo($to_deadline_avail) ?>)),
                                        scrollInput: false,
                                        scrollMonth: false,
                                        <?php
                                        }
                                        ?>
                                    });
                                });
                            </script>
                            <ul class="jobsearch-row jobsearch-employer-profile-form">
                                <?php
                                $job_title_val = isset($_POST['job_title']) ? $_POST['job_title'] : '';
                                if ($is_updating) {
                                    $job_title_val = jobsearch_esc_html(get_the_title($job_id));
                                } else {
                                    if ($job_content == '') {
                                        $job_content = isset($_POST['job_detail']) ? $_POST['job_detail'] : '';
                                    }
                                    
                                    $_job_salary_type = isset($_POST['job_salary_type']) ? $_POST['job_salary_type'] : '';
                                    $_job_salary = isset($_POST['job_salary']) ? $_POST['job_salary'] : '';
                                    $_job_max_salary = isset($_POST['job_max_salary']) ? $_POST['job_max_salary'] : '';
                                    
                                    $_job_apply_email = isset($_POST['job_apply_email']) ? $_POST['job_apply_email'] : '';
                                }
                                ob_start();
                                ?>
                                <li class="jobsearch-column-12">
                                    <?php echo jobsearch_add_field_label_tooltip(esc_html__('Job Title *', 'wp-jobsearch'), 'emp_dashb_job_title_tooltip') ?>
                                    <input id="ad-posting-title"
                                           class="jobsearch-req-field" value="<?php echo jobsearch_esc_html($job_title_val) ?>" 
                                           name="job_title" type="text"
                                           placeholder="<?php esc_html_e('Example: php developer', 'wp-jobsearch') ?>">
                                    <span class="field-error"></span>
                                </li>
                                <?php
                                $jobtitle_html = ob_get_clean();
                                echo apply_filters('jobsearch_empdash_job_posting_jobtitle_html', $jobtitle_html, $job_id);
                                ob_start();
                                ?>
                                <li class="jobsearch-column-12">
                                    <?php echo jobsearch_add_field_label_tooltip(esc_html__('Job Description *', 'wp-jobsearch'), 'emp_dashb_job_desc_tooltip') ?>
                                    <?php
                                    $settings = array(
                                        'media_buttons' => ($job_desc_with_media == 'on' && is_user_logged_in() ? true : false),
                                        'editor_class' => 'jobsearch-req-field',
                                        'quicktags' => array('buttons' => 'strong,em,del,ul,ol,li,close'),
                                        'tinymce' => array(
                                            'toolbar1' => 'bold,bullist,numlist,italic,underline,alignleft,aligncenter,alignright,separator,link,unlink,undo,redo,caned_resp_button',
                                            'toolbar2' => '',
                                            'toolbar3' => '',
                                        ),
                                    );
                                    $job_content = jobsearch_esc_wp_editor($job_content);
                                    wp_editor($job_content, 'job_detail', $settings);
                                    ?>
                                    <span class="field-error"></span>
                                </li>
                                <?php
                                $jobdesc_html = ob_get_clean();
                                echo apply_filters('jobsearch_empdash_job_posting_desceditor_html', $jobdesc_html, $job_content, $job_id);
                                //
                                if (!is_user_logged_in()) {
                                    $signup_username_allow = isset($jobsearch_plugin_options['signup_username_allow']) ? $jobsearch_plugin_options['signup_username_allow'] : '';
                                    $email_con_class = 'jobsearch-column-6';
                                    if ($signup_username_allow == 'on') {
                                        $email_con_class = 'jobsearch-column-6';
                                    }
                                    ?>
                                    <li class="<?php echo($email_con_class) ?>">
                                        <label><?php esc_html_e('Email Address *', 'wp-jobsearch') ?></label>
                                        <input type="text" name="reg_user_email" class="jobsearch-req-field">
                                        <span class="field-error"></span>
                                    </li>
                                    <?php
                                    if ($signup_username_allow == 'on') {
                                        ?>
                                        <li class="jobsearch-column-6">
                                            <label><?php esc_html_e('Username *', 'wp-jobsearch') ?></label>
                                            <input type="text" name="reg_user_uname" class="jobsearch-req-field">
                                            <span class="field-error"></span>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li class="jobsearch-column-6">
                                        <label><?php esc_html_e('Company Name', 'wp-jobsearch') ?></label>
                                        <input type="text" name="pt_user_organization">
                                    </li>
                                    <?php
                                }

                                $sectors_enable_switch = isset($jobsearch_plugin_options['sectors_onoff_switch']) ? $jobsearch_plugin_options['sectors_onoff_switch'] : '';

                                $job_apply_deadline_sw = isset($jobsearch_plugin_options['job_appliction_deadline']) ? $jobsearch_plugin_options['job_appliction_deadline'] : '';

                                //
                                $fil_col_class = 'jobsearch-column-4';
                                $fill_field = false;
                                if ($is_updating) {
                                    $job_status = get_post_meta($job_id, 'jobsearch_field_job_status', true);
                                    $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
                                    $job_applicants_list = jobsearch_is_post_ids_array($job_applicants_list, 'candidate');

                                    if ($job_status == 'approved') {
                                        if ($job_apply_deadline_sw != 'off') {
                                            $fil_col_class = 'jobsearch-column-6';
                                        }
                                        $fill_field = true;
                                    }
                                } else if ($job_apply_deadline_sw == 'off') {
                                    $fil_col_class = 'jobsearch-column-6';
                                }
                                if ($job_apply_deadline_sw != 'off') {
                                    ob_start();
                                    ?>
                                    <li class="<?php echo($fil_col_class) ?>">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Application Deadline *', 'wp-jobsearch'), 'emp_dashb_application_deadline_tooltip') ?>
                                        <input type="text" id="jobsearch_job_application_deadline"
                                               class="job-aplication-deadline<?php echo($job_apply_deadline_sw == 'on_req' ? ' jobsearch-req-field' : '') ?>"
                                               name="application_deadline" <?php echo($is_updating ? 'value="' . jobsearch_esc_html($application_deadline) . '"' : '') ?>>
                                    </li>
                                    <?php
                                    $dedline_html = ob_get_clean();
                                    echo apply_filters('jobsearch_jobpost_deadline_field_html', $dedline_html, $job_id, $fil_col_class);
                                }
                                $job_allow_filled = isset($jobsearch_plugin_options['job_allow_filled']) ? $jobsearch_plugin_options['job_allow_filled'] : '';
                                if ($fill_field && $job_allow_filled == 'on') { ?>
                                    <li class="<?php echo($fil_col_class) ?>">
                                        <label><?php esc_html_e('Filled Job', 'wp-jobsearch') ?></label>
                                        <div class="jobsearch-profile-select">
                                            <select name="job_filled" class="selectize-select">
                                                <option value="off" <?php echo($_job_filled == 'off' ? 'selected="selected"' : '') ?>><?php esc_html_e('No', 'wp-jobsearch') ?></option>
                                                <option value="on" <?php echo($_job_filled == 'on' ? 'selected="selected"' : '') ?>><?php esc_html_e('Yes', 'wp-jobsearch') ?></option>
                                            </select>
                                        </div>
                                    </li>
                                <?php }
                                $free_feature_jobs = isset($jobsearch_plugin_options['free_feature_jobs']) ? $jobsearch_plugin_options['free_feature_jobs'] : '';
                                $feature_job_days = isset($jobsearch_plugin_options['free_feature_job_days']) ? absint($jobsearch_plugin_options['free_feature_job_days']) : '';
                                if ($is_updating && $job_status == 'approved' && $free_jobs_allow == 'on' && $free_feature_jobs == 'on' && $feature_job_days > 0) {
                                    ?>
                                    <li class="<?php echo($fil_col_class) ?>">
                                        <label><?php esc_html_e('Featured Job', 'wp-jobsearch') ?></label>
                                        <div class="jobsearch-profile-select">
                                            <select name="jobsearch_field_job_featured" class="selectize-select">
                                                <option value="off" <?php echo($_job_featured == 'off' ? 'selected="selected"' : '') ?>><?php esc_html_e('No', 'wp-jobsearch') ?></option>
                                                <option value="on" <?php echo($_job_featured == 'on' ? 'selected="selected"' : '') ?>><?php esc_html_e('Yes', 'wp-jobsearch') ?></option>
                                            </select>
                                        </div>
                                    </li>
                                    <?php
                                }

                                $sector_selct_method = isset($jobsearch_plugin_options['job_sector_selct_method']) ? $jobsearch_plugin_options['job_sector_selct_method'] : '';
                                if ($sectors_enable_switch == 'on') {
                                    ob_start();
                                    if ($sector_selct_method == 'multi' || $sector_selct_method == 'multi_req') {
                                        $selct_sector_title = esc_html__('Job Sector', 'wp-jobsearch');
                                        $selct_sector_class = 'sector-select-multif';
                                        if ($sector_selct_method == 'multi_req') {
                                            $selct_sector_title = esc_html__('Job Sector *', 'wp-jobsearch');
                                            $selct_sector_class = 'jobsearch-req-field multiselect-req';
                                        }

                                        $jobsector_args = array(
                                            'orderby' => 'name',
                                            'order' => 'ASC',
                                            'fields' => 'all',
                                            'slug' => '',
                                            'hide_empty' => false,
                                        );
                                        $all_sectors = get_terms('sector', $jobsector_args);
                                        //
                                        $job_sectors = wp_get_post_terms($job_id, 'sector');

                                        $saved_sectors = array();
                                        if (!empty($job_sectors)) {
                                            foreach ($job_sectors as $jobsector_obj) {
                                                $saved_sectors[] = $jobsector_obj->term_id;
                                            }
                                        }
                                        ?>
                                        <li class="<?php echo($fil_col_class) ?>">
                                            <?php echo jobsearch_add_field_label_tooltip($selct_sector_title, 'emp_dashb_job_sector_tooltip') ?>
                                            <div class="jobsearch-profile-select">
                                                <?php
                                                if (!empty($all_sectors)) {
                                                    ?>
                                                    <select id="job-sector-multi" name="job_sector[]"
                                                            class="<?php echo($selct_sector_class) ?>" multiple=""
                                                            placeholder="<?php esc_html_e('Select Job Sectors', 'wp-jobsearch') ?>">
                                                        <?php
                                                        echo jobsearch_sector_terms_hierarchical(0, $all_sectors, '', 0, 0, $saved_sectors, 'ids');
                                                        ?>
                                                    </select>
                                                    <?php
                                                }
                                                add_action('wp_footer', function() {
                                                    global $jobsearch_plugin_options;
                                                    $max_secselct_job = isset($jobsearch_plugin_options['max_sector_selct_jobpost']) ? $jobsearch_plugin_options['max_sector_selct_jobpost'] : '';
                                                    $max_secselct_job = $max_secselct_job > 0 ? $max_secselct_job : 5;
                                                    ?>
                                                    <script>
                                                        jQuery(document).ready(function() {
                                                            jQuery('#job-sector-multi').selectize({
                                                                //allowEmptyOption: true,
                                                                plugins: ['remove_button'],
                                                                maxItems: <?php echo ($max_secselct_job) ?>
                                                            });
                                                        });
                                                    </script>
                                                    <?php
                                                }, 100);
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                    } else {
                                        $selct_sector_title = esc_html__('Job Sector', 'wp-jobsearch');
                                        $selct_sector_class = 'selectize-select';
                                        if ($sector_selct_method == 'single_req') {
                                            $selct_sector_title = esc_html__('Job Sector *', 'wp-jobsearch');
                                            $selct_sector_class = 'jobsearch-req-field selectize-select';
                                        }
                                        ?>
                                        <li class="<?php echo($fil_col_class) ?>">
                                            <?php echo jobsearch_add_field_label_tooltip($selct_sector_title, 'emp_dashb_job_sector_tooltip') ?>
                                            <div class="jobsearch-profile-select">
                                                <?php
                                                $sector_args = array(
                                                    'show_option_all' => esc_html__('Select Sector', 'wp-jobsearch'),
                                                    'show_option_none' => '',
                                                    'class' => $selct_sector_class,
                                                    'option_none_value' => '',
                                                    'orderby' => 'title',
                                                    'order' => 'ASC',
                                                    'show_count' => 0,
                                                    'hide_empty' => 0,
                                                    'echo' => 0,
                                                    'selected' => $job_sector,
                                                    'hierarchical' => 1,
                                                    'id' => 'job-sector',
                                                    'name' => 'job_sector',
                                                    'depth' => 0,
                                                    'taxonomy' => 'sector',
                                                    'hide_if_empty' => false,
                                                    'value_field' => 'term_id',
                                                );
                                                $sector_sel_html = wp_dropdown_categories($sector_args);

                                                echo apply_filters('jobsearch_posting_job_sector_select', $sector_sel_html, $job_id);
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    $sector_whole_html = ob_get_clean();
                                    echo apply_filters('jobsearch_jobpostin_sector_select_html', $sector_whole_html, $job_id, $fil_col_class);
                                }
                                $job_types_switch = isset($jobsearch_plugin_options['job_types_switch']) ? $jobsearch_plugin_options['job_types_switch'] : '';
                                if ($job_types_switch != 'off') {
                                    $job_types_selection = isset($jobsearch_plugin_options['job_types_selection']) ? $jobsearch_plugin_options['job_types_selection'] : '';

                                    ob_start();

                                    if ($job_types_selection == 'multi') {
                                        $selct_type_title = esc_html__('Job Type', 'wp-jobsearch');
                                        $selct_type_class = 'selectize-select';
                                        if ($job_types_switch == 'on_req') {
                                            $selct_type_title = esc_html__('Job Type *', 'wp-jobsearch');
                                            $selct_type_class = 'jobsearch-req-field multiselect-req selectize-select';
                                        }
                                        $jobtype_args = array(
                                            'orderby' => 'name',
                                            'order' => 'ASC',
                                            'fields' => 'all',
                                            'slug' => '',
                                            'hide_empty' => false,
                                        );
                                        $all_types = get_terms('jobtype', $jobtype_args);

                                        $job_types = wp_get_post_terms($job_id, 'jobtype');

                                        $saved_types = array();
                                        if (!empty($job_types)) {
                                            foreach ($job_types as $jobtype_obj) {
                                                $saved_types[] = $jobtype_obj->term_id;
                                            }
                                        }
                                        ob_start();
                                        ?>
                                        <li class="<?php echo($fil_col_class) ?>">
                                            <?php echo jobsearch_add_field_label_tooltip($selct_type_title, 'emp_dashb_job_type_tooltip') ?>
                                            <div class="jobsearch-profile-select">
                                                <?php
                                                if (!empty($all_types)) {
                                                    ?>
                                                    <select id="job-type-multi" name="job_type[]"
                                                            class="<?php echo($selct_type_class) ?>" multiple=""
                                                            placeholder="<?php esc_html_e('Select Job Types', 'wp-jobsearch') ?>">
                                                        <?php
                                                        foreach ($all_types as $type_obj) {
                                                            $term_id = $type_obj->term_id;
                                                            //var_dump($term_id);
                                                            ?>
                                                            <option value="<?php echo (string)($term_id) ?>" <?php echo(in_array($term_id, $saved_types) ? ' selected' : '') ?>><?php echo($type_obj->name) ?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                    } else {
                                        $selct_type_title = esc_html__('Job Type', 'wp-jobsearch');
                                        $selct_type_class = 'selectize-select';
                                        if ($job_types_switch == 'on_req') {
                                            $selct_type_title = esc_html__('Job Type *', 'wp-jobsearch');
                                            $selct_type_class = 'jobsearch-req-field selectize-select';
                                        }
                                        ?>
                                        <li class="<?php echo($fil_col_class) ?>">
                                        <?php echo jobsearch_add_field_label_tooltip($selct_type_title, 'emp_dashb_job_type_tooltip') ?>
                                            <div class="jobsearch-profile-select">
                                                <?php
                                                $sector_args = array(
                                                    'show_option_all' => esc_html__('Select Type', 'wp-jobsearch'),
                                                    'show_option_none' => '',
                                                    'class' => $selct_type_class,
                                                    'option_none_value' => '',
                                                    'orderby' => 'title',
                                                    'order' => 'ASC',
                                                    'show_count' => 0,
                                                    'hide_empty' => 0,
                                                    'echo' => 0,
                                                    'selected' => $job_type,
                                                    'hierarchical' => 1,
                                                    'id' => 'job-type',
                                                    'name' => 'job_type',
                                                    'depth' => 0,
                                                    'taxonomy' => 'jobtype',
                                                    'hide_if_empty' => false,
                                                    'value_field' => 'term_id',
                                                );
                                                $sector_sel_html = wp_dropdown_categories($sector_args);

                                                echo($sector_sel_html);
                                                ?>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    $type_whole_html = ob_get_clean();
                                    echo apply_filters('jobsearch_jobpostin_jobtype_select_html', $type_whole_html, $job_id, $fil_col_class);
                                }
                                echo apply_filters('jobsearch_jobpostin_jobtype_after_html', '', $job_id, $fil_col_class);
                                // for urgent job
                                if (isset($employer_id) && $employer_id > 0) {
                                    $att_urgent_pckg = get_post_meta($employer_id, 'att_urgent_pkg_orderid', true);

                                    if ($att_urgent_pckg > 0 && !jobsearch_member_urgent_pkg_is_expired($att_urgent_pckg)) {
                                        $_job_is_urgent = get_post_meta($job_id, 'jobsearch_field_urgent_job', true);
                                        ?>
                                        <li class="jobsearch-column-6">
                                            <label><?php esc_html_e('Urgent Job', 'wp-jobsearch') ?></label>
                                            <div class="jobsearch-profile-select">
                                                <select name="jobsearch_field_urgent_job" class="selectize-select">
                                                    <option value="off" <?php echo($_job_is_urgent == 'off' ? 'selected="selected"' : '') ?>><?php esc_html_e('No', 'wp-jobsearch') ?></option>
                                                    <option value="on" <?php echo($_job_is_urgent == 'on' ? 'selected="selected"' : '') ?>><?php esc_html_e('Yes', 'wp-jobsearch') ?></option>
                                                </select>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                }
                                //
                                $job_skills_switch = isset($jobsearch_plugin_options['job-skill-switch']) ? $jobsearch_plugin_options['job-skill-switch'] : '';
                                $job_max_skills_allow = isset($jobsearch_plugin_options['job_max_skills']) && $jobsearch_plugin_options['job_max_skills'] > 0 ? $jobsearch_plugin_options['job_max_skills'] : 5;
                                $job_sugg_skills_allow = isset($jobsearch_plugin_options['job_sugg_skills']) && $jobsearch_plugin_options['job_sugg_skills'] > 0 ? $jobsearch_plugin_options['job_sugg_skills'] : 0;

                                if ($job_skills_switch == 'on') {
                                    ob_start();
                                    ?>
                                    <li class="jobsearch-column-12">
                                        <?php
                                        wp_enqueue_script('jobsearch-tag-it');
                                        $job_saved_skills = array();
                                        if ($is_updating) {
                                            $job_saved_skills = wp_get_post_terms($job_id, 'skill');
                                        }
                                        ?>
                                        <div class="jobseach-skills-con">
                                            <?php
                                            add_action('wp_footer', function () use ($job_max_skills_allow) {
                                                ?>
                                                <script type="text/javascript">
                                                    jQuery(document).ready(function () {
                                                        jQuery('#job-skills').tagit({
                                                            allowSpaces: true,
                                                            tagLimit: '<?php echo($job_max_skills_allow) ?>',
                                                            placeholderText: '<?php esc_html_e('Add Skills', 'wp-jobsearch') ?>',
                                                            fieldName: 'get_job_skills[]',
                                                            onTagLimitExceeded: function (event, ui) {
                                                                jQuery(".tagit-new input").val("");
                                                                alert('<?php printf(esc_html__('Only %s skills allowed.', 'wp-jobsearch'), $job_max_skills_allow) ?>');
                                                            }
                                                        });
                                                    });
                                                    jQuery(document).on('focus', '.tagit-new input', function () {
                                                        var _this = jQuery(this);
                                                        _this.parents('.jobseach-skills-con').find('.suggested-skills-con').slideDown();
                                                    });
                                                    jQuery(document).on('click', 'body', function (evt) {
                                                        var target = evt.target;
                                                        var this_box = jQuery('.jobseach-skills-con');
                                                        if (!this_box.is(evt.target) && this_box.has(evt.target).length === 0) {
                                                            this_box.find('.suggested-skills-con').slideUp();
                                                        }
                                                    });

                                                    function jobsearch_add_skill_tolist(the_tag) {
                                                        jQuery("#job-skills").tagit("createTag", the_tag);
                                                        return false;
                                                    }
                                                </script>
                                                <?php
                                            }, 30)
                                            ?>
                                            <?php echo jobsearch_add_field_label_tooltip(esc_html__('Required Skills', 'wp-jobsearch'), 'emp_dashb_required_skills_tooltip') ?>
                                            <ul id="job-skills" class="jobseach-job-skills">
                                                <?php
                                                if (!empty($job_saved_skills)) {
                                                    foreach ($job_saved_skills as $job_saved_skill) {
                                                        ?>
                                                        <li><?php echo jobsearch_esc_html($job_saved_skill->name) ?></li>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </ul>
                                            <?php
                                            if ($job_sugg_skills_allow > 0) {
                                                $skills_terms = get_terms(array(
                                                    'taxonomy' => 'skill',
                                                    'orderby' => 'count',
                                                    'number' => $job_sugg_skills_allow,
                                                    'hide_empty' => false,
                                                ));
                                                //
                                                $sectr_terms = $wpdb->get_col($wpdb->prepare("SELECT terms.term_id FROM $wpdb->terms AS terms"
                                                    . " LEFT JOIN $wpdb->term_taxonomy AS term_tax ON(terms.term_id = term_tax.term_id) "
                                                    . " WHERE term_tax.taxonomy=%s"
                                                    . " ORDER BY terms.term_id DESC", 'sector'));
                                                if (!empty($sectr_terms) && !is_wp_error($sectr_terms)) {
                                                    $ismulti_secs = false;
                                                    if ($sector_selct_method == 'multi' || $sector_selct_method == 'multi_req') {
                                                        //
                                                        $job_sectors = wp_get_post_terms($job_id, 'sector');

                                                        $saved_sectors = array();
                                                        if (!empty($job_sectors)) {
                                                            foreach ($job_sectors as $jobsector_obj) {
                                                                $saved_sectors[] = $jobsector_obj->term_id;
                                                            }
                                                        }
                                                        $ismulti_secs = true;
                                                    }
                                                    ob_start();
                                                    ?>
                                                    <div class="suggested-skills-con">
                                                        <label><?php esc_html_e('Suggested Skills', 'wp-jobsearch') ?></label>
                                                        <?php
                                                        foreach ($sectr_terms as $sectr_termid) {
                                                            $sector_jmeta = get_term_meta($sectr_termid, 'careerfy_frame_cat_fields', true);
                                                            $sector_skills = isset($sector_jmeta['skills']) ? $sector_jmeta['skills'] : '';
                                                            if (!empty($sector_skills)) {
                                                                $show_sec_skills = false;
                                                                if ($ismulti_secs) {
                                                                    $show_sec_skills = in_array($sectr_termid, $saved_sectors) ? true : false;
                                                                } else {
                                                                    $show_sec_skills = $job_sector == $sectr_termid ? true : false;
                                                                }
                                                                ?>
                                                                <ul class="suggested-skills suggested-skills-sector-<?php echo($sectr_termid) ?>" style="display: <?php echo ($show_sec_skills ? 'block' : 'none') ?>;">
                                                                    <?php
                                                                    $sector_skills_count = 1;
                                                                    foreach ($sector_skills as $sector_skill_sid) {
                                                                        $skill_term_obj = get_term_by('id', $sector_skill_sid, 'skill');
                                                                        $skill_trmobj_name = $skill_term_obj->name;
                                                                        $skill_trmobj_name = str_replace("'", "\'", $skill_trmobj_name);
                                                                        ?>
                                                                        <li class="skills-cloud"
                                                                            onclick="return jobsearch_add_skill_tolist('<?php echo($skill_trmobj_name) ?>');"><?php echo jobsearch_esc_html($skill_term_obj->name) ?></li>
                                                                        <?php
                                                                        if ($sector_skills_count >= $job_sugg_skills_allow) {
                                                                            break;
                                                                        }
                                                                        $sector_skills_count++;
                                                                    }
                                                                    ?>
                                                                </ul>
                                                                <?php
                                                            }
                                                        }
                                                        ?>
                                                        <ul class="suggested-skills all-suggs-skills">
                                                            <?php
                                                            foreach ($skills_terms as $skill_term) {
                                                                $skill_trm_name = $skill_term->name;
                                                                $skill_trm_name = str_replace("'", "\'", $skill_trm_name);
                                                                ?>
                                                                <li class="skills-cloud" onclick="return jobsearch_add_skill_tolist('<?php echo($skill_trm_name) ?>');"><?php echo($skill_term->name) ?></li>
                                                                <?php
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                    <?php
                                                    $html = ob_get_clean();
                                                    echo apply_filters('jobsearch_post_job_sugg_skills_html', $html, $skills_terms);
                                                }
                                            }
                                            ?>
                                        </div>
                                        <script>
                                            jQuery(document).on('change', '#job-sector', function () {
                                                var sector_id = jQuery(this).val();
                                                var skills_area_con = jQuery('.suggested-skills-con');
                                                skills_area_con.find('.no-sugge-skills').hide();
                                                skills_area_con.find('.all-suggs-skills').hide();
                                                skills_area_con.find('.suggested-skills').hide();
                                                if (skills_area_con.find('.suggested-skills-sector-' + sector_id).length > 0) {
                                                    skills_area_con.find('.suggested-skills-sector-' + sector_id).show();
                                                } else {
                                                    skills_area_con.find('.all-suggs-skills').show();
                                                }
                                            });
                                            jQuery(document).on('change', '#job-sector-multi', function () {
                                                var sector_ids = jQuery(this).val();
                                                var skills_area_con = jQuery('.suggested-skills-con');
                                                skills_area_con.find('.no-sugge-skills').hide();
                                                skills_area_con.find('.all-suggs-skills').hide();
                                                skills_area_con.find('.suggested-skills').hide();
                                                if (sector_ids.length > 0) {
                                                    for (var sec_i = 0; sec_i <= sector_ids.length; sec_i++) {
                                                        var sector_id = sector_ids[sec_i];
                                                        skills_area_con.find('.suggested-skills-sector-' + sector_id).show();
                                                    }
                                                } else {
                                                    skills_area_con.find('.all-suggs-skills').show();
                                                }
                                            });
                                        </script>
                                    </li>
                                    <?php
                                    $skills_html = ob_get_clean();
                                    echo apply_filters('jobsearch_job_postin_skills_html', $skills_html, $job_id);
                                }

                                echo apply_filters('jobsearch_jobpostin_jobskills_after_html', '', $job_id);

                                $job_apply_switch = isset($jobsearch_plugin_options['job-apply-switch']) ? $jobsearch_plugin_options['job-apply-switch'] : 'on';

                                if (isset($job_apply_switch) && $job_apply_switch == 'on') {
                                    $job_extrnal_apply_switch = isset($jobsearch_plugin_options['apply-methods']) ? $jobsearch_plugin_options['apply-methods'] : '';
                                    $internal_flag = false;
                                    $external_flag = false;
                                    $email_flag = false;
                                    if (isset($job_extrnal_apply_switch) && is_array($job_extrnal_apply_switch) && sizeof($job_extrnal_apply_switch) > 0) {
                                        foreach ($job_extrnal_apply_switch as $apply_switch) {
                                            if ($apply_switch == 'internal') {
                                                $internal_flag = true;
                                                $type_hidden_value = 'internal';
                                            }
                                            if ($apply_switch == 'external') {
                                                $external_flag = true;
                                                $type_hidden_value = 'external';
                                            }
                                            if ($apply_switch == 'email') {
                                                $email_flag = true;
                                                $type_hidden_value = 'with_email';
                                            }
                                        }
                                    }
                                    $dropdown_flag = false;
                                    if ($internal_flag && $external_flag && $email_flag) { // in case of all selected
                                        $dropdown_flag = true;
                                    }
                                    if ($internal_flag && $external_flag) { // in case of internal and external
                                        $dropdown_flag = true;
                                    }
                                    if ($internal_flag && $email_flag) {
                                        $dropdown_flag = true;
                                    }
                                    if ($external_flag && $email_flag) {
                                        $dropdown_flag = true;
                                    }
                                if ($dropdown_flag) {
                                    $jobapply_type_req = isset($jobsearch_plugin_options['apply_type_required']) ? $jobsearch_plugin_options['apply_type_required'] : '';
                                    ?>
                                    <li class="jobsearch-column-6">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Job Apply Type' . ($jobapply_type_req == 'on' ? ' *' : ''), 'wp-jobsearch'), 'emp_dashb_job_apply_type_tooltip') ?>
                                        <div class="jobsearch-profile-select">
                                            <select id="jobsearch_job_apply_type"<?php echo($jobapply_type_req == 'on' ? ' required="required"' : '') ?>
                                                    class="selectize-select"
                                                    name="job_apply_type"<?php echo($jobapply_type_req == 'on' ? ' placeholder="' . esc_html__('Select Apply Type', 'wp-jobsearch') . '"' : '') ?>>
                                                <?php
                                                if ($jobapply_type_req == 'on') {
                                                    ?>
                                                    <option value=""><?php esc_html_e('Select Apply Type', 'wp-jobsearch') ?></option>
                                                    <?php
                                                }
                                                if ($internal_flag) {
                                                    ?>
                                                    <option value="internal" <?php echo($is_updating && $_job_apply_type == 'internal' ? 'selected="selected"' : '') ?>><?php esc_html_e('Internal', 'wp-jobsearch') ?></option>
                                                    <?php
                                                }
                                                if ($external_flag) { ?>
                                                    <option value="external" <?php echo($is_updating && $_job_apply_type == 'external' ? 'selected="selected"' : '') ?>><?php esc_html_e('External URL', 'wp-jobsearch') ?></option>
                                                <?php }
                                                if ($email_flag) { ?>
                                                    <option value="with_email" <?php echo($is_updating && $_job_apply_type == 'with_email' ? 'selected="selected"' : '') ?>><?php esc_html_e('By Email', 'wp-jobsearch') ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </li>
                                <?php
                                add_action('wp_footer', function() {
                                ?>
                                    <script>
                                        jQuery('#jobsearch_job_apply_type').on('change', function () {
                                            var extrnal_inp_field = jQuery('input[name=job_apply_url]');
                                            var withemail_inp_field = jQuery('input[name=job_apply_email]');
                                            if (jQuery(this).val() == 'with_email') {
                                                withemail_inp_field.addClass('jobsearch-req-field');
                                                extrnal_inp_field.removeClass('jobsearch-req-field');
                                            } else if (jQuery(this).val() == 'external') {
                                                extrnal_inp_field.addClass('jobsearch-req-field');
                                                withemail_inp_field.removeClass('jobsearch-req-field');
                                            } else {
                                                withemail_inp_field.removeClass('jobsearch-req-field');
                                                extrnal_inp_field.removeClass('jobsearch-req-field');
                                            }
                                        });
                                    </script>
                                <?php
                                }, 35);
                                }
                                if (!$dropdown_flag) {
                                ?>
                                <input type="hidden" name="job_apply_type"
                                       value="<?php echo jobsearch_esc_html($type_hidden_value); ?>"/>
                                <?php
                                }
                                if ($external_flag) {
                                $external_final_flag = false;
                                if ($is_updating && $_job_apply_type == 'external') {
                                    $external_final_flag = true;
                                } elseif (!$is_updating && !$internal_flag) {
                                    $external_final_flag = true;
                                }
                                ?>
                                    <li id="job-apply-external-url" class="jobsearch-column-6"
                                        style="display: <?php echo($external_final_flag ? 'inline-block' : 'none') ?>;">
                                        <label><?php esc_html_e('External URL for Apply Job *', 'wp-jobsearch') ?></label>
                                        <input type="text" name="job_apply_url"
                                               class="external-url-input<?php echo($is_updating && $_job_apply_type == 'external' ? ' jobsearch-req-field' : '') ?>" <?php echo($is_updating ? 'value="' . jobsearch_esc_html($_job_apply_url) . '"' : '') ?>>
                                    </li>
                                <?php
                                }
                                if ($email_flag) {
                                $email_final_flag = false;
                                if ($is_updating && $_job_apply_type == 'with_email') {
                                    $email_final_flag = true;
                                } elseif (!$is_updating && !$internal_flag && !$external_flag) {
                                    $email_final_flag = true;
                                }
                                ?>
                                    <li id="job-apply-by-email" class="jobsearch-column-6"
                                        style="display: <?php echo($email_final_flag ? 'inline-block' : 'none') ?>;">
                                        <label><?php esc_html_e('Job Apply Email *', 'wp-jobsearch') ?></label>
                                        <input type="text" name="job_apply_email"
                                               class="apply-email-input<?php echo($is_updating && $_job_apply_type == 'with_email' ? ' jobsearch-req-field' : '') ?>"
                                            <?php echo ('value="' . jobsearch_esc_html($_job_apply_email) . '"') ?>>
                                    </li>
                                    <?php
                                }
                                }

                                echo apply_filters('jobsearch_sh_addup_job_befor_salary_html', '', $job_id);
                                //
                                ob_start();
                                $salary_onoff_switch = isset($jobsearch_plugin_options['salary_onoff_switch']) ? $jobsearch_plugin_options['salary_onoff_switch'] : '';
                                if ($salary_onoff_switch != 'off') { ?>
                                    <li class="jobsearch-column-12">
                                        <?php echo jobsearch_add_field_label_tooltip(esc_html__('Salary' . ($salary_onoff_switch == 'on_req' ? ' *' : ''), 'wp-jobsearch'), 'emp_dashb_salary_tooltip') ?>
                                        <div class="salary-type">
                                            <div class="jobsearch-profile-select">
                                                <select name="job_salary_type" class="selectize-select">
                                                    <?php
                                                    $slar_type_count = 1;
                                                    foreach ($job_salary_types as $job_salary_tkey => $job_salary_type) {
                                                        $job_salary_type = apply_filters('wpml_translate_single_string', $job_salary_type, 'JobSearch Options', 'Salary Type - ' . $job_salary_type, $lang_code);
                                                        if ($job_salary_tkey === 'negotiable' && $negotiable_salary == 'on') {
                                                            ?>
                                                            <option value="negotiable" <?php echo($is_updating && $_job_salary_type == 'negotiable' ? 'selected="selected"' : '') ?>><?php echo($job_salary_type) ?></option>
                                                            <?php
                                                        } else {
                                                            ?>
                                                            <option value="type_<?php echo($slar_type_count) ?>" <?php echo($is_updating && $_job_salary_type == 'type_' . $slar_type_count ? 'selected="selected"' : '') ?>><?php echo($job_salary_type) ?></option>
                                                            <?php
                                                            $slar_type_count++;
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="salary-input salary-input-fordev<?php echo($salary_onoff_switch == 'on_req' ? ' required-field' : '') ?>"
                                             style="display: <?php echo($_job_salary_type == 'negotiable' && $negotiable_salary == 'on' ? 'none' : 'block') ?>;">
                                            <?php
                                            $_job_currency_sym = isset($jobsearch_currencies_list[$_job_salary_currency]['symbol']) ? $jobsearch_currencies_list[$_job_salary_currency]['symbol'] : jobsearch_get_currency_symbol();
                                            ?>
                                            <div class="min-salary">
                                                <span><?php echo($_job_currency_sym); ?></span>
                                                <input type="text" placeholder="<?php esc_html_e('Min', 'wp-jobsearch') ?>"
                                                       name="job_salary" <?php echo('value="' . jobsearch_esc_html($_job_salary) . '"') ?>>
                                            </div>
                                            <div class="max-salary">
                                                <span><?php echo($_job_currency_sym); ?></span>
                                                <input type="text" placeholder="<?php esc_html_e('Max', 'wp-jobsearch') ?>"
                                                       name="job_max_salary" <?php echo('value="' . jobsearch_esc_html($_job_max_salary) . '"') ?>>
                                            </div>
                                        </div>
                                    </li>
                                    <?php
                                    $job_custom_currency_switch = isset($jobsearch_plugin_options['job_custom_currency']) ? $jobsearch_plugin_options['job_custom_currency'] : '';
                                    if (!empty($jobsearch_currencies_list) && $job_custom_currency_switch == 'on') {

                                        $_job_salary_currency = jobsearch_esc_html($_job_salary_currency);
                                        $_job_salary_pos = jobsearch_esc_html($_job_salary_pos);
                                        $_job_salary_sep = jobsearch_esc_html($_job_salary_sep);
                                        $_job_salary_deci = jobsearch_esc_html($_job_salary_deci);
                                        ?>
                                        <li class="jobsearch-column-12 jobsalary-curency-con"
                                            style="display: <?php echo($_job_salary_type == 'negotiable' && $negotiable_salary == 'on' ? 'none' : 'block') ?>;">
                                            <div class="jobsearch-row">
                                                <div class="jobsearch-column-3">
                                                    <label><?php esc_html_e('Salary Currency', 'wp-jobsearch') ?></label>
                                                    <div class="jobsearch-profile-select">
                                                        <select name="job_salary_currency" class="selectize-select">
                                                            <option value="default"
                                                                    data-cur="<?php echo jobsearch_get_currency_symbol() ?>"><?php esc_html_e('Default', 'wp-jobsearch') ?></option>
                                                            <?php
                                                            foreach ($jobsearch_currencies_list as $cus_currency_key => $cus_currency) {
                                                                $cus_cur_name = isset($cus_currency['name']) ? $cus_currency['name'] : '';
                                                                $cus_cur_symbol = isset($cus_currency['symbol']) ? $cus_currency['symbol'] : '';
                                                                ?>
                                                                <option value="<?php echo($cus_currency_key) ?>"
                                                                        data-cur="<?php echo($cus_cur_symbol) ?>" <?php echo($_job_salary_currency == $cus_currency_key ? 'selected="selected"' : '') ?>><?php echo($cus_cur_name . ' - ' . $cus_cur_symbol) ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="jobsearch-column-3">
                                                    <label><?php esc_html_e('Currency position', 'wp-jobsearch') ?></label>
                                                    <div class="jobsearch-profile-select">
                                                        <select name="job_salary_pos" class="selectize-select">
                                                            <option value="left" <?php echo($_job_salary_pos == 'left' ? 'selected="selected"' : '') ?>><?php esc_html_e('Left', 'wp-jobsearch') ?></option>
                                                            <option value="right" <?php echo($_job_salary_pos == 'right' ? 'selected="selected"' : '') ?>><?php esc_html_e('Right', 'wp-jobsearch') ?></option>
                                                            <option value="left_space" <?php echo($_job_salary_pos == 'left_space' ? 'selected="selected"' : '') ?>><?php esc_html_e('Left with space', 'wp-jobsearch') ?></option>
                                                            <option value="right_space" <?php echo($_job_salary_pos == 'right_space' ? 'selected="selected"' : '') ?>><?php esc_html_e('Right with space', 'wp-jobsearch') ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="jobsearch-column-3">
                                                    <label><?php esc_html_e('Thousand separator', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="job_salary_sep"
                                                           value="<?php echo($_job_salary_sep != '' ? $_job_salary_sep : ',') ?>">
                                                </div>
                                                <div class="jobsearch-column-3">
                                                    <label><?php esc_html_e('Number of decimals', 'wp-jobsearch') ?></label>
                                                    <input type="text" name="job_salary_deci"
                                                           value="<?php echo($_job_salary_deci != '' && $_job_salary_deci > 0 ? $_job_salary_deci : '2') ?>">
                                                </div>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                }
                                $salry_fields_html = ob_get_clean();
                                echo apply_filters('jobsearch_sh_addup_job_salary_fields_html', $salry_fields_html, $job_id);
                                ?>
                            </ul>
                            <?php
                        }
                    }
                        echo apply_filters('jobsearch_jobpost_steps_contnt_after', '');
                    }
                    ?>
                </div>

                <?php
                do_action('jobsearch_jobpost_working_hours_fields', '', $job_id);

                if (!$is_candidate) {
                    if (isset($job_detail_tab) && $job_detail_tab && !isset($_GET['step'])) {
                        echo apply_filters('jobsearch_jobpost_steps_b4r_custom_fields', '', $job_id);
                        do_action('jobsearch_post_job_sh_before_custom_fields', $job_id);
                        do_action('jobsearch_dashboard_custom_fields_load', $job_id, 'job');

                        do_action('jobsearch_post_job_apply_job_questions', $job_id);

                        $job_attachments_switch = isset($jobsearch_plugin_options['job_attachments']) ? $jobsearch_plugin_options['job_attachments'] : '';
                        if ($job_attachments_switch == 'on') { ?>
                            <div class="jobsearch-employer-box-section">
                                <div class="jobsearch-profile-title">
                                    <h2><?php esc_html_e('File Attachments', 'wp-jobsearch') ?></h2>
                                </div>
                                <?php
                                ob_start();
                                ?>
                                <div class="jobsearch-fileUpload">
                                    <span><i class="jobsearch-icon jobsearch-upload"></i> <?php esc_html_e('Upload Files', 'wp-jobsearch') ?></span>
                                    <input id="job_attach_files" name="job_attach_files[]" type="file"
                                           class="upload jobsearch-upload" multiple="multiple"
                                           onchange="jobsearch_job_attach_files_url(event)"/>
                                </div>
                                <div id="attach-files-holder" class="gallery-imgs-holder jobsearch-company-gallery">
                                    <?php
                                    $all_attach_files = get_post_meta($job_id, 'jobsearch_field_job_attachment_files', true);
                                    if (!empty($all_attach_files)) {
                                        ?>
                                        <ul>
                                            <?php
                                            foreach ($all_attach_files as $_attach_file) {
                                                $_attach_id = jobsearch_get_attachment_id_from_url($_attach_file);
                                                $_attach_post = get_post($_attach_id);
                                                $_attach_mime = isset($_attach_post->post_mime_type) ? $_attach_post->post_mime_type : '';
                                                $_attach_guide = isset($_attach_post->guid) ? $_attach_post->guid : '';
                                                $attach_name = basename($_attach_guide);
                                                $file_icon = 'fa fa-file-text-o';
                                                if ($_attach_mime == 'image/png' || $_attach_mime == 'image/jpeg') {
                                                    $file_icon = 'fa fa-file-image-o';
                                                } else if ($_attach_mime == 'application/msword' || $_attach_mime == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                                    $file_icon = 'fa fa-file-word-o';
                                                } else if ($_attach_mime == 'application/vnd.ms-excel' || $_attach_mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                                                    $file_icon = 'fa fa-file-excel-o';
                                                } else if ($_attach_mime == 'application/pdf') {
                                                    $file_icon = 'fa fa-file-pdf-o';
                                                }
                                                ?>
                                                <li class="jobsearch-column-3">
                                                    <a href="javascript:void(0);" class="fa fa-remove el-remove"></a>
                                                    <div class="file-container">
                                                        <a href="<?php echo($_attach_file) ?>"
                                                           oncontextmenu="javascript: return false;"
                                                           onclick="javascript: if ((event.button == 0 && event.ctrlKey)) {return false};"
                                                           download="<?php echo($attach_name) ?>"><i class="<?php echo($file_icon) ?>"></i> <?php echo($attach_name) ?>
                                                        </a>
                                                    </div>
                                                    <input type="hidden" name="jobsearch_field_job_attachment_files[]" value="<?php echo($_attach_file) ?>">
                                                </li>
                                                <?php
                                            }
                                            ?>
                                        </ul>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                                $uplod_html = ob_get_clean();
                                echo apply_filters('jobsearch_dash_postjob_attchuplods_html', $uplod_html, $job_id);
                                ?>
                            </div>
                            <?php
                        }

                        echo apply_filters('jobsearch_sh_add_job_after_file_attachments', '', $job_id);
                        do_action('jobsearch_dashboard_location_map', $job_id, 'job_shortcide');
                        if (!is_user_logged_in()) { ?>
                            <input type="hidden" name="pt_user_role" value="jobsearch_employer">
                        <?php } ?>
                        <input type="hidden" name="user_job_posting" value="1">
                        <?php
                        if ($is_updating) {
                            $republish_jobs_allow = isset($jobsearch_plugin_options['republish_the_job']) ? $jobsearch_plugin_options['republish_the_job'] : '';

                            $job_expiry_date = get_post_meta($job_id, 'jobsearch_field_job_expiry_date', true);
                            $_updtjob_title = esc_html__('Update Job', 'wp-jobsearch');
                            ob_start();
                            ?>
                            <input type="submit" class="jobsearch-employer-profile-submit jobsearch-postjob-btn"
                                   value="<?php echo($_updtjob_title) ?>">
                            <?php
                            $btns_html = ob_get_clean();
                            echo apply_filters('jobsearch_user_dash_jobupdte_btn_html', $btns_html);

                            if ($job_expiry_date == '' || ($job_expiry_date != '' && $job_expiry_date <= current_time('timestamp'))) {
                                if ($republish_jobs_allow == 'on') {
                                    ?>
                                    <input type="submit"
                                           class="jobsearch-employer-profile-submit jobsearch-postjob-btn jobsearch-repblishin-jobtn"
                                           style="margin-left: 10px;"
                                           value="<?php esc_html_e('Republish Job', 'wp-jobsearch') ?>">
                                    <input type="hidden" name="republishin_job" value="0">
                                    <?php
                                }
                            }
                            ob_start();
                            do_action('jobsearch_translate_job_with_wpml_btn', $job_id);
                            $btns_html = ob_get_clean();
                            echo apply_filters('jobsearch_translate_job_with_wpml_btn_html', $btns_html);
                        } else {
                            jobsearch_terms_and_con_link_txt();
                            ob_start();
                            ?>
                            <input type="submit" class="jobsearch-employer-profile-submit jobsearch-postjob-btn"
                                   value="<?php esc_html_e('Post Job', 'wp-jobsearch') ?>">
                            <?php
                            $btns_html = ob_get_clean();
                            echo apply_filters('jobsearch_user_dash_jobpost_btn_html', $btns_html);
                        }
                    }
                    if (isset($job_package_tab)) { ?>
                        <input type="hidden" name="user_job_package_chose" value="1">
                        <input type="submit" class="jobsearch-employer-profile-submit jobsearch-postjob-btn"
                               value="<?php esc_html_e('Update Package', 'wp-jobsearch') ?>">
                        <?php
                    }
                    echo apply_filters('jobsearch_jobpost_steps_submtbtns_after', '');
                } ?>
            </form>
            <?php
            $jobpos_html = ob_get_clean();
            $form_atts = array();
            echo apply_filters('jobsearch_jobpos_sh_wform_html', $jobpos_html, $form_atts);
        }
        ?>
    </div>
    <?php
    $html = ob_get_clean();
    return $html;
}