<?php
global $post, $jobsearch_plugin_options;
$employer_id = $post->ID;

$captcha_switch = isset($jobsearch_plugin_options['captcha_switch']) ? $jobsearch_plugin_options['captcha_switch'] : '';
$jobsearch_sitekey = isset($jobsearch_plugin_options['captcha_sitekey']) ? $jobsearch_plugin_options['captcha_sitekey'] : '';

$all_location_allow = isset($jobsearch_plugin_options['all_location_allow']) ? $jobsearch_plugin_options['all_location_allow'] : '';
$map_switchs_arr = isset($jobsearch_plugin_options['jobsearch-detail-map-switch']) ? $jobsearch_plugin_options['jobsearch-detail-map-switch'] : '';
$job_types_switch = isset($jobsearch_plugin_options['job_types_switch']) ? $jobsearch_plugin_options['job_types_switch'] : '';

$emp_det_full_address_switch = true;
$locations_view_type = isset($jobsearch_plugin_options['emp_det_loc_listing']) ? $jobsearch_plugin_options['emp_det_loc_listing'] : '';

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

$plugin_default_view = isset($jobsearch_plugin_options['jobsearch-default-page-view']) ? $jobsearch_plugin_options['jobsearch-default-page-view'] : 'full';
$plugin_default_view_with_str = '';
if ($plugin_default_view == 'boxed') {

    $plugin_default_view_with_str = isset($jobsearch_plugin_options['jobsearch-boxed-view-width']) && $jobsearch_plugin_options['jobsearch-boxed-view-width'] != '' ? $jobsearch_plugin_options['jobsearch-boxed-view-width'] : '1140px';
    if ($plugin_default_view_with_str != '' && !wp_is_mobile()) {
        $plugin_default_view_with_str = ' style="width:' . $plugin_default_view_with_str . '"';
    }
}
$reviews_switch = isset($jobsearch_plugin_options['reviews_switch']) ? $jobsearch_plugin_options['reviews_switch'] : '';

$employer_views_count = get_post_meta($employer_id, "jobsearch_employer_views_count", true);

//
$user_facebook_url = get_post_meta($employer_id, 'jobsearch_field_user_facebook_url', true);
$user_twitter_url = get_post_meta($employer_id, 'jobsearch_field_user_twitter_url', true);
$user_google_plus_url = get_post_meta($employer_id, 'jobsearch_field_user_google_plus_url', true);
$user_youtube_url = get_post_meta($employer_id, 'jobsearch_field_user_youtube_url', true);
$user_dribbble_url = get_post_meta($employer_id, 'jobsearch_field_user_dribbble_url', true);
$user_linkedin_url = get_post_meta($employer_id, 'jobsearch_field_user_linkedin_url', true);

$membsectors_enable_switch = isset($jobsearch_plugin_options['usersector_onoff_switch']) ? $jobsearch_plugin_options['usersector_onoff_switch'] : '';
$sectors_enable_switch = ($membsectors_enable_switch == 'on_emp' || $membsectors_enable_switch == 'on_both') ? 'on' : '';

$tjobs_posted_switch = isset($jobsearch_plugin_options['empjobs_posted_count']) ? $jobsearch_plugin_options['empjobs_posted_count'] : '';
$totl_views_switch = isset($jobsearch_plugin_options['emptotl_views_count']) ? $jobsearch_plugin_options['emptotl_views_count'] : '';

//
$emp_phone_switch = isset($jobsearch_plugin_options['employer_phone_field']) ? $jobsearch_plugin_options['employer_phone_field'] : '';
$emp_web_switch = isset($jobsearch_plugin_options['employer_web_field']) ? $jobsearch_plugin_options['employer_web_field'] : '';
$emp_foundate_switch = isset($jobsearch_plugin_options['employer_founded_date']) ? $jobsearch_plugin_options['employer_founded_date'] : '';

$user_dob_dd = get_post_meta($employer_id, 'jobsearch_field_user_dob_dd', true);
$user_dob_mm = get_post_meta($employer_id, 'jobsearch_field_user_dob_mm', true);
$user_dob_yy = get_post_meta($employer_id, 'jobsearch_field_user_dob_yy', true);

$found_date_str = '';
if ($user_dob_dd != '' && $user_dob_mm != '' && $user_dob_yy != '') {
    $found_date_str = $user_dob_dd . '-' . $user_dob_mm . '-' . $user_dob_yy;
    $found_date_str = date_i18n(get_option('date_format'), strtotime($found_date_str));
}

$employer_obj = get_post($employer_id);
$employer_content = $employer_obj->post_content;
$employer_content = apply_filters('the_content', $employer_content);

$employer_join_date = isset($employer_obj->post_date) ? $employer_obj->post_date : '';

$employer_address = get_post_meta($employer_id, 'jobsearch_field_location_address', true);

if (function_exists('jobsearch_post_city_contry_txtstr')) {
    $employer_address = jobsearch_post_city_contry_txtstr($employer_id, $loc_view_country, $loc_view_state, $loc_view_city, $emp_det_full_address_switch);
}

$employer_phone = get_post_meta($employer_id, 'jobsearch_field_user_phone', true);

$user_id = jobsearch_get_employer_user_id($employer_id);
$user_obj = get_user_by('ID', $user_id);
$user_displayname = isset($user_obj->display_name) ? $user_obj->display_name : '';
$user_displayname = apply_filters('jobsearch_user_display_name', $user_displayname, $user_obj);

$user_def_avatar_url = get_avatar_url($user_id, array('size' => 140));

$user_avatar_id = get_post_thumbnail_id($employer_id);
if ($user_avatar_id > 0) {
    $user_thumbnail_image = wp_get_attachment_image_src($user_avatar_id, 'thumbnail');
    $user_def_avatar_url = isset($user_thumbnail_image[0]) && esc_url($user_thumbnail_image[0]) != '' ? $user_thumbnail_image[0] : '';
} else {
    $user_def_avatar_url = jobsearch_employer_image_placeholder();
}

$employer_cover_image_src_style_str = '';
if ($employer_id != '') {
    if (class_exists('JobSearchMultiPostThumbnails')) {
        $employer_cover_image_src = JobSearchMultiPostThumbnails::get_post_thumbnail_url('employer', 'cover-image', $employer_id);
        if ($employer_cover_image_src != '') {
            $employer_cover_image_src_style_str = ' style="background:url(' . esc_url($employer_cover_image_src) . ') center/cover no-repeat"';
        }
    }
}
if ($employer_cover_image_src_style_str == '') {
    $emp_def_cvrimg = isset($jobsearch_plugin_options['emp_default_coverimg']['url']) && $jobsearch_plugin_options['emp_default_coverimg']['url'] != '' ? $jobsearch_plugin_options['emp_default_coverimg']['url'] : '';
    $employer_cover_image_src_style_str = ' style="background:url(' . esc_url($emp_def_cvrimg) . ') center/cover no-repeat;"';
}
$subheader_employer_bg_color = isset($jobsearch_plugin_options['careerfy-emp-img-overlay-bg-color']) ? $jobsearch_plugin_options['careerfy-emp-img-overlay-bg-color'] : '';
if (isset($subheader_employer_bg_color['rgba'])) {
    $subheader_bg_color = $subheader_employer_bg_color['rgba'];
}
wp_enqueue_script('isotope-min');
wp_enqueue_style('careerfy-emp-detail-three');
?>

<div class="careerfy-employer-subheader-three">
    <div class="careerfy-employer-subheader-three-transparent-wrap" <?php echo($employer_cover_image_src_style_str); ?>>

        <span class="careerfy-employer-subheader-three-transparent"
              style="background: <?php echo !empty($subheader_bg_color) ? $subheader_bg_color : 'rgb(48, 56, 68, 0.50)' ?>"></span>

    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <figure>
                    <img src="<?php echo($user_def_avatar_url) ?>" alt="">
                    <h1>
                        <?php
                        ob_start();
                        echo($user_displayname);
                        $title_html = ob_get_clean();
                        echo apply_filters('jobsearch_emp_detail_maintitle_html', $title_html, $employer_id, 'view3');
                        if (function_exists('jobsearch_empjobs_urgent_pkg_iconlab')) {
                            echo jobsearch_member_promote_profile_iconlab($employer_id, 'employer_list');
                        }
                        ?>
                    </h1>
                </figure>
                <div class="careerfy-rightcntnt-holder">
                    
                    <div class="careerfy-review-boxcon">
                        <?php
                        //
                        if ($reviews_switch == 'on') {
                            $post_avg_review_args = array(
                                'post_id' => $employer_id,
                                'prefix' => 'careerfy',
                                'total_revs' => 'yes',
                            );
                            do_action('jobsearch_post_avg_rating', $post_avg_review_args);
                            $reviews_switch = isset($jobsearch_plugin_options['reviews_switch']) ? $jobsearch_plugin_options['reviews_switch'] : '';
                            if ($reviews_switch == 'on') {
                                if (is_user_logged_in()) {
                                    wp_enqueue_script('jobsearch-barrating');
                                    wp_enqueue_script('jobsearch-add-review');
                                    ?>
                                    <a href="javascript:void(0);" data-target="add_review_form_sec"
                                       class="careerfy-employer-detail2-toparea-btn jobsearch-go-to-review-form"
                                       data-post_id="<?php echo($employer_id) ?>"><i class="careerfy-icon careerfy-add"></i> <?php esc_html_e('Add a review', 'careerfy') ?>
                                    </a>
                                    <?php
                                } else {
                                    ?>
                                    <a href="javascript:void(0);" class="careerfy-employer-detail2-toparea-btn jobsearch-open-signin-tab">
                                        <i class="careerfy-icon careerfy-add"></i> <?php esc_html_e('Add a review', 'careerfy') ?>
                                    </a>
                                    <?php
                                }
                            }
                        }
                        do_action('jobsearch_empdetail_after_followin_btn', $employer_id, 'view3');
                        ?>
                    </div>
                    <div class="careerfy-rightbtns-con">
                        <?php
                        $follow_btn_args = array(
                            'employer_id' => $employer_id,
                            'before_label' => esc_html__('Follow', 'wp-jobsearch'),
                            'after_label' => esc_html__('Followed', 'wp-jobsearch'),
                            'ext_class' => 'careerfy-employer-detail2-toparea-btn',
                            'view' => 'detail_view3',
                        );
                        do_action('jobsearch_employer_followin_btn', $follow_btn_args);
                        do_action('jobsearch_empdetail_after_following_btn', $employer_id, 'view3');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="careerfy-main-content">

    <div class="careerfy-main-section">
        <div class="container">
            <div class="row">

                <!-- Job Detail Content -->
                <div class="jobsearch-column-8 jobsearch-typo-wrap">
                    <?php
                    $custom_all_fields = get_option('jobsearch_custom_field_employer');
                    ?>
                    <div class="careerfy-jobdetail-content careerfy-employerdetail-content">
                        <?php
                        ob_start();
                        if ($emp_foundate_switch != 'off' && $found_date_str != '') {
                            ?>
                            <li class="careerfy-column-4">
                                <i class="careerfy-icon careerfy-time"></i>
                                <div class="careerfy-services-text"><?php esc_html_e('Founded Date', 'wp-jobsearch') ?>
                                    <small><?php echo ($found_date_str) ?></small>
                                </div>
                            </li>
                            <?php
                        }
                        if ($sectors_enable_switch == 'on') {
                            $sector_str = jobsearch_employer_get_all_sectors($employer_id, '', '', '', '<small>', '</small>');
                            $sector_str = apply_filters('jobsearch_gew_wout_anchr_sector_str_html', $sector_str, $employer_id, '<small>', '</small>');
                            ?>
                            <li class="careerfy-column-4">
                                <i class="careerfy-icon careerfy-folder"></i>
                                <div class="careerfy-services-text"><?php esc_html_e('Sectors', 'careerfy') ?><?php echo wp_kses($sector_str, array('small' => array())) ?></div>
                            </li>
                            <?php
                        }
                        if ($tjobs_posted_switch == 'on') { ?>
                            <li class="careerfy-column-4">
                                <i class="careerfy-icon careerfy-briefcase"></i>
                                <div class="careerfy-services-text"><?php esc_html_e('Posted Jobs', 'careerfy') ?>
                                    <small><?php echo jobsearch_employer_total_jobs_posted($employer_id) ?></small>
                                </div>
                            </li>
                            <?php
                        }
                        if ($totl_views_switch == 'on') {
                            ?>
                            <li class="careerfy-column-4">
                                <i class="careerfy-icon careerfy-view"></i>
                                <div class="careerfy-services-text"><?php esc_html_e('Viewed', 'careerfy') ?>
                                    <small><?php echo($employer_views_count) ?></small>
                                </div>
                            </li>
                            <?php
                        }
                        $extra_cus_fields = ob_get_clean();
                        //
                        $cus_fields = array('content' => '');
                        if (!empty($custom_all_fields)) {
                            $cus_fields = apply_filters('jobsearch_custom_fields_list', 'employer', $employer_id, $cus_fields, '<li class="careerfy-column-4">', '</li>', '', true, true, true, 'careerfy');
                        }
                        if ((isset($cus_fields['content']) && $cus_fields['content'] != '') || $extra_cus_fields != '') {
                            ?>
                            <div class="careerfy-content-title"><h2><?php esc_html_e('Overview', 'careerfy') ?></h2>
                            </div>
                            <div class="careerfy-jobdetail-services">
                                <ul class="careerfy-row">
                                    <?php
                                    echo($extra_cus_fields);

                                    //
                                    echo($cus_fields['content']);
                                    ?>
                                </ul>
                            </div>
                            <?php
                            $ad_args = array(
                                'post_type' => 'employer',
                                'view' => 'view3',
                                'position' => 'b4_desc',
                            );
                            jobsearch_detail_common_ad_code($ad_args);

                            if ($employer_content != '') {
                                ?>
                                <div class="careerfy-content-title">
                                    <h2><?php esc_html_e('Company Description', 'careerfy') ?></h2></div>
                                <div class="jobsearch-description">
                                    <?php echo jobsearch_esc_wp_editor($employer_content) ?>
                                </div>
                                <?php
                            }
                            echo apply_filters('jobsearch_emp_detail_after_company_desc', '', $employer_id);

                            $ad_args = array(
                                'post_type' => 'employer',
                                'view' => 'view3',
                                'position' => 'aftr_desc',
                            );
                            jobsearch_detail_common_ad_code($ad_args);
                            //
                        }
                        ?>
                    </div>
                    <?php
                    $team_experience_show = isset($jobsearch_plugin_options['emp_team_membs_experience']) ? $jobsearch_plugin_options['emp_team_membs_experience'] : '';
                    $team_picture_show = isset($jobsearch_plugin_options['emp_team_membs_img']) ? $jobsearch_plugin_options['emp_team_membs_img'] : '';

                    $exfield_list = (get_post_meta($employer_id, 'jobsearch_field_team_title', true));
                    $exfield_list_val = (get_post_meta($employer_id, 'jobsearch_field_team_description', true));
                    $team_designationfield_list = (get_post_meta($employer_id, 'jobsearch_field_team_designation', true));
                    $team_experiencefield_list = (get_post_meta($employer_id, 'jobsearch_field_team_experience', true));
                    $team_imagefield_list = (get_post_meta($employer_id, 'jobsearch_field_team_image', true));
                    $team_facebookfield_list = (get_post_meta($employer_id, 'jobsearch_field_team_facebook', true));
                    $team_googlefield_list = (get_post_meta($employer_id, 'jobsearch_field_team_google', true));
                    $team_twitterfield_list = (get_post_meta($employer_id, 'jobsearch_field_team_twitter', true));
                    $team_linkedinfield_list = (get_post_meta($employer_id, 'jobsearch_field_team_linkedin', true));
                    if (is_array($exfield_list) && sizeof($exfield_list) > 0) {
                        $total_team = sizeof($exfield_list);
                        $rand_num_ul = rand(1000000, 99999999);
                        ?>
                        <div class="careerfy-employer-wrap-section">
                            <div class="careerfy-content-title careerfy-addmore-space">
                                <h2><?php echo apply_filters('jobsearch_emp_detail_team_hdingtxt', esc_html__('Team Members', 'careerfy')) ?></h2>
                            </div>
                            <div class="careerfy-candidate careerfy-candidate-grid2">
                                <ul class="careerfy-row" id="members-holder-<?php echo absint($rand_num_ul) ?>">
                                    <?php
                                    $exfield_counter = 0;
                                    foreach ($exfield_list as $exfield) {
                                        $rand_num = rand(1000000, 99999999);
                                        $exfield_val = isset($exfield_list_val[$exfield_counter]) ? jobsearch_esc_html($exfield_list_val[$exfield_counter]) : '';
                                        $team_designationfield_val = isset($team_designationfield_list[$exfield_counter]) ? jobsearch_esc_html($team_designationfield_list[$exfield_counter]) : '';
                                        $team_experiencefield_val = isset($team_experiencefield_list[$exfield_counter]) ? jobsearch_esc_html($team_experiencefield_list[$exfield_counter]) : '';
                                        $team_imagefield_val = isset($team_imagefield_list[$exfield_counter]) ? jobsearch_esc_html($team_imagefield_list[$exfield_counter]) : '';
                                        $team_facebookfield_val = isset($team_facebookfield_list[$exfield_counter]) ? jobsearch_esc_html($team_facebookfield_list[$exfield_counter]) : '';
                                        $team_googlefield_val = isset($team_googlefield_list[$exfield_counter]) ? jobsearch_esc_html($team_googlefield_list[$exfield_counter]) : '';
                                        $team_twitterfield_val = isset($team_twitterfield_list[$exfield_counter]) ? jobsearch_esc_html($team_twitterfield_list[$exfield_counter]) : '';
                                        $team_linkedinfield_val = isset($team_linkedinfield_list[$exfield_counter]) ? jobsearch_esc_html($team_linkedinfield_list[$exfield_counter]) : '';

                                        $team_imagefield_imgid = jobsearch_get_attachment_id_from_url($team_imagefield_val);
                                        ?>
                                        <li class="careerfy-column-6">
                                            <script>
                                                jQuery(document).ready(function () {
                                                    jQuery('a[id^="fancybox_notes"]').fancybox({
                                                        'titlePosition': 'inside',
                                                        'transitionIn': 'elastic',
                                                        'transitionOut': 'elastic',
                                                        'width': 400,
                                                        'height': 250,
                                                        'padding': 40,
                                                        'autoSize': false
                                                    });
                                                });
                                            </script>
                                            <figure>
                                                <?php
                                                if ($team_imagefield_imgid > 0 && $team_picture_show == 'on') {
                                                    ?>
                                                    <a id="fancybox_notes<?php echo($rand_num) ?>"
                                                       href="#notes<?php echo($rand_num) ?>"><img
                                                                src="<?php echo($team_imagefield_val) ?>" alt=""></a>
                                                    <?php
                                                }
                                                ?>
                                                <figcaption>
                                                    <h2><a id="fancybox_notes_txt<?php echo($rand_num) ?>"
                                                           href="#notes<?php echo($rand_num) ?>"><?php echo jobsearch_esc_html($exfield) ?></a>
                                                    </h2>
                                                    <span><?php echo($team_designationfield_val) ?></span>
                                                    <?php
                                                    if ($team_experiencefield_val != '' && $team_experience_show == 'on') {
                                                        echo '<span>' . sprintf(esc_html__('Experience: %s', 'careerfy'), $team_experiencefield_val) . '</span>';
                                                    }

                                                    //
                                                    if ($team_facebookfield_val != '' || $team_googlefield_val != '' || $team_twitterfield_val != '' || $team_linkedinfield_val != '') {
                                                        ?>
                                                        <ul class="jobsearch-social-icons">
                                                            <?php
                                                            if ($team_facebookfield_val != '') {
                                                                ?>
                                                                <li><a href="<?php echo($team_facebookfield_val) ?>"
                                                                       data-original-title="facebook"
                                                                       class="jobsearch-icon jobsearch-facebook-logo"></a>
                                                                </li>
                                                                <?php
                                                            }
                                                            if ($team_googlefield_val != '') {
                                                                ?>
                                                                <li><a href="<?php echo($team_googlefield_val) ?>"
                                                                       data-original-title="google-plus"
                                                                       class="jobsearch-icon jobsearch-google-plus-logo-button"></a>
                                                                </li>
                                                                <?php
                                                            }
                                                            if ($team_twitterfield_val != '') {
                                                                ?>
                                                                <li><a href="<?php echo($team_twitterfield_val) ?>"
                                                                       data-original-title="twitter"
                                                                       class="jobsearch-icon jobsearch-twitter-logo"></a>
                                                                </li>
                                                                <?php
                                                            }
                                                            if ($team_linkedinfield_val != '') {
                                                                ?>
                                                                <li><a href="<?php echo($team_linkedinfield_val) ?>"
                                                                       data-original-title="linkedin"
                                                                    ><i class="jobsearch-icon jobsearch-linkedin-button"></i></a>
                                                                </li>
                                                                <?php
                                                            }
                                                            ?>
                                                        </ul>
                                                        <?php
                                                    }
                                                    ?>
                                                </figcaption>
                                            </figure>
                                        </li>
                                        <div id="notes<?php echo($rand_num) ?>"
                                             style="display: none;"><?php echo($exfield_val) ?></div>
                                        <?php
                                        $exfield_counter++;

                                        if ($exfield_counter >= 6) {
                                            break;
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                            <?php
                            $reults_per_page = 3;
                            $total_pages = 1;
                            if ($total_team > 0 && $reults_per_page > 0 && $total_team > $reults_per_page) {
                                $total_pages = ceil($total_team / $reults_per_page);
                                ?>
                                <div class="jobsearch-load-more">
                                    <a data-view="team2" class="load-more-team" href="javascript:void(0);"
                                       data-id="<?php echo($employer_id) ?>" data-pref="jobsearch"
                                       data-rand="<?php echo($rand_num_ul) ?>" data-pages="<?php echo($total_pages) ?>"
                                       data-page="1"><?php esc_html_e('Load More', 'careerfy') ?></a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }

                    $ad_args = array(
                        'post_type' => 'employer',
                        'view' => 'view3',
                        'position' => 'aftr_team',
                    );
                    jobsearch_detail_common_ad_code($ad_args);

                    if ($reviews_switch == 'on') {
                        $post_reviews_args = array(
                            'post_id' => $employer_id,
                            'list_label' => esc_html__('Company Reviews', 'careerfy'),
                        );
                        do_action('jobsearch_post_reviews_list', $post_reviews_args);

                        $review_form_args = array(
                            'post_id' => $employer_id,
                        );
                        do_action('jobsearch_add_review_form', $review_form_args);
                    }
                    //
                    $default_date_time_formate = 'd-m-Y H:i:s';
                    $args = array(
                        'posts_per_page' => 20,
                        'paged' => 1,
                        'post_type' => 'job',
                        'post_status' => 'publish',
                        'meta_key' => $meta_key,
                        'order' => 'DESC',
                        'orderby' => 'ID',
                        'meta_query' => array(
                            array(
                                'key' => 'jobsearch_field_job_expiry_date',
                                'value' => strtotime(current_time($default_date_time_formate, 1)),
                                'compare' => '>=',
                            ),
                            array(
                                'key' => 'jobsearch_field_job_status',
                                'value' => 'approved',
                                'compare' => '=',
                            ),
                            array(
                                'key' => 'jobsearch_field_job_posted_by',
                                'value' => $employer_id,
                                'compare' => '=',
                            ),
                        ),
                    );
                    $args = apply_filters('jobsearch_employer_rel_jobs_query_args', $args);
                    $jobs_query = new WP_Query($args);

                    if ($jobs_query->have_posts()) {
                        $jobsearch_title_limit = isset($jobsearch_plugin_options['related_jobs_title_length']) && $jobsearch_plugin_options['related_jobs_title_length'] > 0 ? $jobsearch_plugin_options['related_jobs_title_length'] : '';
                        ?>
                        <div class="jobsearch-margin-top">
                            <div class="jobsearch-section-title">
                                <h2><?php printf(esc_html__('Active Jobs From %s', 'careerfy'), $user_displayname) ?></h2>
                            </div>

                            <?php
                            ob_start();
                            ?>
                            <div class="jobsearch-job jobsearch-joblisting-classic jobsearch-jobdetail-joblisting">
                                <ul class="jobsearch-row">
                                    <?php
                                    while ($jobs_query->have_posts()) : $jobs_query->the_post();
                                        $job_id = get_the_ID();
                                        $post_thumbnail_id = jobsearch_job_get_profile_image($job_id);

                                        if ($post_thumbnail_id > 0) {
                                            $user_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
                                            $user_def_avatar_url = isset($user_thumbnail_image[0]) && esc_url($user_thumbnail_image[0]) != '' ? $user_thumbnail_image[0] : '';
                                        } else {
                                            $user_def_avatar_url = jobsearch_employer_image_placeholder();
                                        }

                                        $company_name = jobsearch_job_get_company_name($job_id, '@ ');
                                        $jobsearch_job_featured = get_post_meta($job_id, 'jobsearch_field_job_featured', true);
                                        $get_job_location = get_post_meta($job_id, 'jobsearch_field_location_address', true);

                                        $job_city_title = '';
                                        $get_job_city = get_post_meta($job_id, 'jobsearch_field_location_location3', true);
                                        if ($get_job_city == '') {
                                            $get_job_city = get_post_meta($job_id, 'jobsearch_field_location_location2', true);
                                        }
                                        if ($get_job_city == '') {
                                            $get_job_city = get_post_meta($job_id, 'jobsearch_field_location_location1', true);
                                        }

                                        $job_city_tax = $get_job_city != '' ? get_term_by('slug', $get_job_city, 'job-location') : '';
                                        if (is_object($job_city_tax)) {
                                            $job_city_title = $job_city_tax->name;
                                        }

                                        $sector_str = jobsearch_job_get_all_sectors($job_id, '', '', '', '<li><i class="jobsearch-icon jobsearch-filter-tool-black-shape"></i>', '</li>');
                                        $job_type_str = jobsearch_job_get_all_jobtypes($job_id, 'jobsearch-option-btn');
                                        ?>
                                        <li class="jobsearch-column-12">
                                            <div class="jobsearch-joblisting-classic-wrap">
                                                <figure><a href="<?php echo get_permalink($job_id) ?>"><img
                                                                src="<?php echo($user_def_avatar_url) ?>" alt=""></a>
                                                </figure>
                                                <div class="jobsearch-joblisting-text">
                                                    <div class="jobsearch-list-option">
                                                        <h2>
                                                            <a href="<?php echo get_permalink($job_id) ?>"><?php echo esc_html(wp_trim_words(get_the_title($job_id), $jobsearch_title_limit)) ?></a>
                                                            <?php
                                                            if ($jobsearch_job_featured == 'on') {
                                                                ?>
                                                                <span><?php echo esc_html__('Featured', 'careerfy'); ?></span>
                                                                <?php
                                                            }
                                                            ?>
                                                        </h2>
                                                        <ul>
                                                            <?php
                                                            if ($company_name != '') {
                                                                ?>
                                                                <li><?php echo force_balance_tags($company_name); ?></li>
                                                                <?php
                                                            }
                                                            if (!empty($job_city_title) && $all_location_allow == 'on') {
                                                                ?>
                                                                <li>
                                                                    <i class="fa fa-map-marker"></i><?php echo esc_html($job_city_title); ?>
                                                                </li>
                                                                <?php
                                                            }

                                                            if (!empty($sector_str) && $sectors_enable_switch == 'on') {
                                                                echo apply_filters('jobsearch_joblisting_sector_str_html', $sector_str, $job_id, '<li><i class="jobsearch-icon jobsearch-calendar"></i>', '</li>');
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                    <div class="jobsearch-job-userlist">
                                                        <?php
                                                        if ($job_type_str != '' && $job_types_switch != 'off') {
                                                            echo force_balance_tags($job_type_str);
                                                        }
                                                        $book_mark_args = array(
                                                            'job_id' => $job_id,
                                                            'before_icon' => 'fa fa-heart-o',
                                                            'after_icon' => 'fa fa-heart',
                                                            'anchor_class' => 'jobsearch-job-like'
                                                        );
                                                        do_action('jobsearch_job_shortlist_button_frontend', $book_mark_args);
                                                        ?>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php
                                    endwhile;
                                    wp_reset_postdata();
                                    ?>
                                </ul>
                            </div>
                            <?php
                            $activ_jobs_html = ob_get_clean();
                            echo apply_filters('jobsearch_employer_detail_active_jobs_html', $activ_jobs_html, $jobs_query);
                            ?>
                        </div>
                        <?php } ?>
                </div>
                <!-- Job Detail Content -->
                <!-- Job Detail SideBar -->
                <aside class="careerfy-column-4 careerfy-typo-wrap">
                    <?php
                    do_action('jobsearch_employer_detail_side_before_contact_form', array('id' => $employer_id));
                    //
                    ?>
                    <div class="widget widget_your_info">
                        <div class="widget_your_info_wrapas">
                            <ul class="widget_your_info_list">
                                <?php
                                if (!empty($employer_address) && $all_location_allow == 'on') {
                                    ?>
                                    <li><i class="careerfy-color fa fa-map-marker"></i> <?php echo($employer_address) ?>
                                    </li>
                                    <?php
                                }
                                if (isset($user_obj->user_url) && $user_obj->user_url != '' && $emp_web_switch != 'off' && jobsearch_employer_info_div_visible('weburl')) {
                                    $user_url = apply_filters('jobsearch_employer_info_encoding', $user_obj->user_url, 'weburl');
                                    ?>
                                    <li><i class="careerfy-color careerfy-icon careerfy-internet"></i> <a
                                                href="<?php echo esc_url($user_url) ?>"
                                                target="_blank"><?php echo esc_url($user_url) ?></a></li>
                                    <?php
                                }
                                if (isset($user_obj->user_email) && $user_obj->user_email != '' && jobsearch_employer_info_div_visible('email')) {
                                    $user_email = apply_filters('jobsearch_employer_info_encoding', $user_obj->user_email, 'email');
                                    $tr_email = sprintf(__('<a href="mailto: %s">Email: %s</a>', 'careerfy'), $user_email, $user_email);
                                    ?>
                                    <li>
                                        <i class="careerfy-color careerfy-icon careerfy-envelope"></i> <?php echo wp_kses($tr_email, array('a' => array('href' => array(), 'target' => array(), 'title' => array()))) ?>
                                    </li>
                                    <?php
                                }
                                if ($employer_phone != '' && $emp_phone_switch != 'off' && jobsearch_employer_info_div_visible('phone')) {
                                    $user_phone = apply_filters('jobsearch_employer_info_encoding', $employer_phone, 'phone');
                                    ?>
                                    <li>
                                        <i class="careerfy-color careerfy-icon careerfy-technology"></i> <?php printf(esc_html__('Hotline: %s', 'careerfy'), $user_phone) ?>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <?php
                            $emp_alow_fb_smm = isset($jobsearch_plugin_options['emp_alow_fb_smm']) ? $jobsearch_plugin_options['emp_alow_fb_smm'] : '';
                            $emp_alow_twt_smm = isset($jobsearch_plugin_options['emp_alow_twt_smm']) ? $jobsearch_plugin_options['emp_alow_twt_smm'] : '';
                            $emp_alow_gplus_smm = isset($jobsearch_plugin_options['emp_alow_gplus_smm']) ? $jobsearch_plugin_options['emp_alow_gplus_smm'] : '';
                            $emp_alow_linkd_smm = isset($jobsearch_plugin_options['emp_alow_linkd_smm']) ? $jobsearch_plugin_options['emp_alow_linkd_smm'] : '';
                            $emp_alow_dribbb_smm = isset($jobsearch_plugin_options['emp_alow_dribbb_smm']) ? $jobsearch_plugin_options['emp_alow_dribbb_smm'] : '';
                            $employer_social_mlinks = isset($jobsearch_plugin_options['employer_social_mlinks']) ? $jobsearch_plugin_options['employer_social_mlinks'] : '';

                            if (!empty($employer_social_mlinks) || ($emp_alow_fb_smm == 'on' || $emp_alow_twt_smm == 'on' || $emp_alow_gplus_smm == 'on' || $emp_alow_linkd_smm == 'on' || $emp_alow_dribbb_smm == 'on')) {
                                ?>
                                <ul class="widget_your_info_social">
                                    <?php
                                    if ($user_facebook_url != '' && $emp_alow_fb_smm == 'on') {
                                        ?>
                                        <li><a href="<?php echo esc_url($user_facebook_url) ?>" target="_blank"
                                               data-original-title="facebook" class="fa fa-facebook"></a></li>
                                        <?php
                                    }
                                    if ($user_twitter_url != '' && $emp_alow_twt_smm == 'on') {
                                        ?>
                                        <li><a href="<?php echo esc_url($user_twitter_url) ?>" target="_blank"
                                               data-original-title="twitter" class="fa fa-twitter"></a></li>
                                        <?php
                                    }
                                    if ($user_linkedin_url != '' && $emp_alow_linkd_smm == 'on') {
                                        ?>
                                        <li><a href="<?php echo esc_url($user_linkedin_url) ?>" target="_blank"
                                               data-original-title="linkedin" class="fa fa-linkedin"></a></li>
                                        <?php
                                    }
                                    if ($user_dribbble_url != '' && $emp_alow_dribbb_smm == 'on') {
                                        ?>
                                        <li><a href="<?php echo esc_url($user_dribbble_url) ?>" target="_blank"
                                               data-original-title="dribbble" class="fa fa-dribbble"></a></li>
                                        <?php
                                    }
                                    if (!empty($employer_social_mlinks)) {
                                        if (isset($employer_social_mlinks['title']) && is_array($employer_social_mlinks['title'])) {
                                            $field_counter = 0;
                                            foreach ($employer_social_mlinks['title'] as $field_title_val) {
                                                $field_random = rand(10000000, 99999999);
                                                $field_icon_styles = '';
                                                $field_icon = isset($employer_social_mlinks['icon'][$field_counter]) ? $employer_social_mlinks['icon'][$field_counter] : '';
                                                $field_icon_group = isset($employer_social_mlinks['icon_group'][$field_counter]) ? $employer_social_mlinks['icon_group'][$field_counter] : '';
                                                if ($field_icon_group == '') {
                                                    $field_icon_group = 'default';
                                                }
                                                $field_icon_clr = isset($employer_social_mlinks['icon_clr'][$field_counter]) ? $employer_social_mlinks['icon_clr'][$field_counter] : '';
                                                if ($field_icon_clr != '') {
                                                    $field_icon_styles .= ' color: ' . $field_icon_clr . ';';
                                                }
                                                $field_icon_bgclr = isset($employer_social_mlinks['icon_bgclr'][$field_counter]) ? $employer_social_mlinks['icon_bgclr'][$field_counter] : '';
                                                if ($field_icon_bgclr != '') {
                                                    $field_icon_styles .= ' background-color: ' . $field_icon_bgclr . ';';
                                                }
                                                $emp_dynm_social = get_post_meta($employer_id, 'jobsearch_field_dynm_social' . $field_counter, true);
                                                if ($field_title_val != '' && $emp_dynm_social != '') {
                                                    ?>
                                                    <li><a href="<?php echo esc_url($emp_dynm_social) ?>"
                                                           target="_blank" <?php echo($field_icon_styles != '' ? 'style="' . $field_icon_styles . '"' : '') ?>
                                                           class="<?php echo($field_icon) ?>"></a></li>
                                                    <?php
                                                }
                                                $field_counter++;
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                            <?php }
                            $emp_chat_args = array('employer_id' => $employer_id);
                            echo do_action('jobsearch_chat_with_employer', $emp_chat_args);
                            ?>
                        </div>
                    </div>

                    <?php


                    $ad_args = array(
                        'post_type' => 'employer',
                        'view' => 'view3',
                        'position' => 'b4_map',
                    );
                    jobsearch_detail_common_ad_code($ad_args);

                    $map_switch_arr = isset($jobsearch_plugin_options['jobsearch-detail-map-switch']) ? $jobsearch_plugin_options['jobsearch-detail-map-switch'] : '';
                    $employer_map = false;
                    if (isset($map_switch_arr) && is_array($map_switch_arr) && sizeof($map_switch_arr) > 0) {
                        foreach ($map_switch_arr as $map_switch) {
                            if ($map_switch == 'employer') {
                                $employer_map = true;
                            }
                        }
                    }
                    if ($employer_map) {
                        ?>
                        <div class="widget jobsearch_widget_map">
                            <?php
                            jobsearch_google_map_with_directions($employer_id);
                            ?>
                        </div>
                        <?php
                    }

                    $ad_args = array(
                        'post_type' => 'employer',
                        'view' => 'view3',
                        'position' => 'b4_cntct',
                    );
                    jobsearch_detail_common_ad_code($ad_args);

                    $emp_det_contact_form = isset($jobsearch_plugin_options['emp_det_contact_form']) ? $jobsearch_plugin_options['emp_det_contact_form'] : '';
                    if ($emp_det_contact_form != 'off' && isset($user_obj->user_email)) {
                        ob_start();
                        ?>
                        <div class="widget widget_contact_form">
                            <?php
                            $cnt_counter = rand(1000000, 9999999);

                            $cur_user_name = '';
                            $cur_user_email = '';
                            $field_readonly = false;
                            if (is_user_logged_in()) {
                                if ($emp_det_contact_form == 'cand_login') {
                                    $field_readonly = true;
                                }
                                $cur_user_id = get_current_user_id();
                                $cur_user_obj = wp_get_current_user();
                                $cur_user_name = isset($cur_user_obj->display_name) ? $cur_user_obj->display_name : '';
                                $cur_user_email = isset($cur_user_obj->user_email) ? $cur_user_obj->user_email : '';
                                if (jobsearch_user_is_candidate($cur_user_id)) {
                                    $cnt_cand_id = jobsearch_get_user_candidate_id($cur_user_id);
                                    $cur_user_name = get_the_title($cnt_cand_id);
                                }
                            }
                            ?>
                            <div class="careerfy-widget-title"><h2><?php esc_html_e('Contact Form', 'careerfy') ?></h2>
                            </div>
                            <form id="ct-form-<?php echo absint($cnt_counter) ?>"
                                  data-uid="<?php echo absint($user_id) ?>" method="post">
                                <ul>
                                    <li>
                                        <input name="u_name"
                                               placeholder="<?php esc_html_e('Enter Your Name', 'careerfy') ?>"
                                               type="text" <?php echo ($field_readonly ? 'readonly' : '') ?> value="<?php echo ($cur_user_name) ?>">
                                        <i class="jobsearch-icon jobsearch-user"></i>
                                    </li>
                                    <li>
                                        <input name="u_email"
                                               placeholder="<?php esc_html_e('Enter Your Email Address', 'careerfy') ?>"
                                               type="text" <?php echo ($field_readonly ? 'readonly' : '') ?> value="<?php echo ($cur_user_email) ?>">
                                        <i class="jobsearch-icon jobsearch-mail"></i>
                                    </li>
                                    <li>
                                        <input name="u_number"
                                               placeholder="<?php esc_html_e('Enter Your Phone Number', 'careerfy') ?>"
                                               type="text">
                                        <i class="jobsearch-icon jobsearch-technology"></i>
                                    </li>
                                    <li>
                                        <textarea name="u_msg"
                                                  placeholder="<?php esc_html_e('Type Your Message here', 'careerfy') ?>"></textarea>
                                    </li>
                                    <?php
                                    if ($captcha_switch == 'on') {
                                        wp_enqueue_script('jobsearch_google_recaptcha');
                                        ?>
                                        <li>
                                            <script>
                                                var recaptcha_emp3_contact;
                                                var jobsearch_multicap = function () {
                                                    //Render the recaptcha_emp3_contact on the element with ID "recaptcha_emp3_contact"
                                                    recaptcha_emp3_contact = grecaptcha.render('recaptcha_emp3_contact', {
                                                        'sitekey': '<?php echo($jobsearch_sitekey); ?>', //Replace this with your Site key
                                                        'theme': 'light'
                                                    });
                                                };
                                                jQuery(document).ready(function () {
                                                    jQuery('.recaptcha-reload-a').click();
                                                });
                                            </script>
                                            <div class="recaptcha-reload" id="recaptcha_emp3_contact_div">
                                                <?php echo jobsearch_recaptcha('recaptcha_emp3_contact'); ?>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <li>
                                        <?php
                                        jobsearch_terms_and_con_link_txt();
                                        ?>
                                        <input type="submit" class="jobsearch-employer-ct-form"
                                               data-id="<?php echo absint($cnt_counter) ?>"
                                               value="<?php esc_html_e('Send now', 'careerfy') ?>">
                                        <?php
                                        if (!is_user_logged_in() && $emp_det_contact_form != 'on') {
                                            ?>
                                            <a class="jobsearch-open-signin-tab"
                                               style="display: none;"><?php esc_html_e('login', 'careerfy') ?></a>
                                            <?php
                                        }
                                        ?>
                                    </li>
                                </ul>
                                <span class="jobsearch-ct-msg"></span>
                            </form>
                        </div>
                        <?php
                        $emp_cntct_form = ob_get_clean();
                        echo apply_filters('jobsearch_employer_detail_cntct_frm_html', $emp_cntct_form, $employer_id);
                    }

                    $ad_args = array(
                        'post_type' => 'employer',
                        'view' => 'view3',
                        'position' => 'b4_oficpics',
                    );
                    jobsearch_detail_common_ad_code($ad_args);

                    //
                    $compny_gal_allow = isset($jobsearch_plugin_options['allow_compny_galery']) ? $jobsearch_plugin_options['allow_compny_galery'] : '';

                    if ($compny_gal_allow == 'on') {
                        $company_gal_imgs = get_post_meta($employer_id, 'jobsearch_field_company_gallery_imgs', true);

                        $company_gal_videos = get_post_meta($employer_id, 'jobsearch_field_company_gallery_videos', true);
                        $company_gal_descs = get_post_meta($employer_id, 'jobsearch_field_company_gallery_imgs_description', true);
                        $company_gal_titles = get_post_meta($employer_id, 'jobsearch_field_company_gallery_imgs_title', true);

                        if (!empty($company_gal_imgs)) {
                            $_gal_img_counter = 0;

                            foreach ($company_gal_imgs as $company_gal_img) {

                                if ($company_gal_img != '' && !is_numeric($company_gal_img)) {
                                    $company_gal_img = jobsearch_get_attachment_id_from_url($company_gal_img);
                                }

                                if ($company_gal_img > 0) {
                                    $_gal_img_counter++;
                                }
                            }


                            if ($_gal_img_counter > 0) {

                                ?>
                                <div class="widget widget_gallery_three">
                                    <div class="careerfy-widget-title">
                                        <h2><?php echo esc_html__('Office Photos', 'careerfy'); ?></h2></div>
                                    <ul>
                                        <?php
                                        $profile_gal_counter = 1;
                                        $_gal_img_counter = 0;
                                        foreach ($company_gal_imgs as $company_gal_img) {

                                            if ($company_gal_img != '' && absint($company_gal_img) <= 0) {
                                                $company_gal_img = jobsearch_get_attachment_id_from_url($company_gal_img);
                                            }
                                            $gal_thumbnail_image = wp_get_attachment_image_src($company_gal_img, 'large');
                                            $gal_thumb_image_src = isset($gal_thumbnail_image[0]) && esc_url($gal_thumbnail_image[0]) != '' ? $gal_thumbnail_image[0] : '';

                                            $gal_video_url = isset($company_gal_videos[$_gal_img_counter]) && ($company_gal_videos[$_gal_img_counter]) != '' ? $company_gal_videos[$_gal_img_counter] : '';
                                            $gal_img_title = isset($company_gal_titles[$_gal_img_counter]) && ($company_gal_titles[$_gal_img_counter]) != '' ? $company_gal_titles[$_gal_img_counter] : '';
                                            $gal_img_desc = isset($company_gal_descs[$_gal_img_counter]) && ($company_gal_descs[$_gal_img_counter]) != '' ? $company_gal_descs[$_gal_img_counter] : '';

                                            if ($gal_video_url != '') {

                                                if (strpos($gal_video_url, 'watch?v=') !== false) {
                                                    $gal_video_url = str_replace('watch?v=', 'embed/', $gal_video_url);
                                                }

                                                if (strpos($gal_video_url, '?') !== false) {
                                                    $gal_video_url .= '&autoplay=1';
                                                } else {
                                                    $gal_video_url .= '?autoplay=1';
                                                }
                                            }

                                            if ($company_gal_img > 0) {

                                                $gal_full_image = wp_get_attachment_image_src($company_gal_img, 'full');
                                                $gal_full_image_src = isset($gal_full_image[0]) && esc_url($gal_full_image[0]) != '' ? $gal_full_image[0] : '';


                                                ?>
                                                <li>
                                                    <a href="<?php echo($gal_video_url != '' ? $gal_video_url : $gal_full_image_src) ?>"
                                                       title="<?php echo($gal_img_title) ?>"
                                                       data-caption="<?php echo($gal_img_desc) ?>"
                                                       class="<?php echo($gal_video_url != '' ? 'fancybox-video' : 'fancybox-galimg') ?>" <?php echo($gal_video_url != '' ? 'data-fancybox-type="iframe"' : '') ?>
                                                       data-fancybox-group="group">
                                                        <img src="<?php echo($gal_thumb_image_src); ?>" alt=""
                                                             height="109" width="109"/>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            $profile_gal_counter++;
                                            $_gal_img_counter++;
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <?php
                            }
                        }
                    }

                    //
                    echo jobsearch_employer_profile_awards($employer_id);
                    echo jobsearch_employer_profile_affiliations($employer_id);

                    $ad_args = array(
                        'post_type' => 'employer',
                        'view' => 'view3',
                        'position' => 'aftr_oficpics',
                    );
                    jobsearch_detail_common_ad_code($ad_args);
                    ?>
                </aside>

            </div>
        </div>
    </div>
    <!-- Main Section -->

</div>