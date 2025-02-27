<?php

class JobsearchXingLogin {

    private $xing_details;
    private $redirect_url;

    /**
     * JobsearchXingLogin constructor.
     */
    public function __construct() {

        add_shortcode('jobsearch_xing_login', array($this, 'display_login_button'));
        add_action('wp_ajax_jobsearch_xing_response_data', array($this, 'jobsearch_xing_response_data_callback'));
        add_action('wp_ajax_nopriv_jobsearch_xing_response_data', array($this, 'jobsearch_xing_response_data_callback'));
        
        add_action('jobsearch_do_apply_job_xing', array($this, 'do_apply_job_with_xing'), 10, 1);
        
        add_action('jobsearch_apply_job_with_xing', array($this, 'apply_job_with_xing'), 10, 1);

        add_action('wp_ajax_jobsearch_applying_job_with_xing', array($this, 'applying_job_with_xing'));
        add_action('wp_ajax_nopriv_jobsearch_applying_job_with_xing', array($this, 'applying_job_with_xing'));
    }

    public function jobsearch_xing_response_data_callback() {
        global $jobsearch_plugin_options;
        $candidate_auto_approve = isset($jobsearch_plugin_options['candidate_auto_approve']) ? $jobsearch_plugin_options['candidate_auto_approve'] : '';

        $json_arr = array();

        $user_data = isset($_POST['user_data']) && !empty($_POST['user_data']) ? $_POST['user_data'] : '';

        $user_dashboard_page = isset($jobsearch_plugin_options['user-dashboard-template-page']) ? $jobsearch_plugin_options['user-dashboard-template-page'] : '';
        $user_dashboard_page = isset($user_dashboard_page) && !empty($user_dashboard_page) ? jobsearch__get_post_id($user_dashboard_page, 'page') : 0;
        $real_redirect_url = $user_dashboard_page > 0 ? get_permalink($user_dashboard_page) : home_url('/');

        $json_arr['redirect_url'] = $real_redirect_url; // redirect url after login
        $json_arr['status'] = true;

        $this->xing_details = $user_data; // data format in json
        $xing_user = json_decode(stripslashes($user_data), true);

        $user_image = $xing_user['photo_urls']['maxi_thumb'];

        $job_title = $xing_user['professional_experience']['primary_company']['title'];

        // check a user is already reistered or not

        $user_id = isset($xing_user['id']) ? $xing_user['id'] : '';
        $wp_users = get_users(array(
            'meta_key' => 'jobsearch_xing_id',
            'meta_value' => $user_id,
            'number' => 1,
            'count_total' => false,
            'fields' => 'id',
        ));
        if (empty($wp_users[0])) { // execute if user is not registered
            // create user for non-registerd
            $user_id = isset($xing_user['id']) ? $xing_user['id'] : '';
            $first_name = isset($xing_user['first_name']) ? $xing_user['first_name'] : '';
            $last_name = isset($xing_user['last_name']) ? $xing_user['last_name'] : '';
            $email = isset($xing_user['active_email']) ? $xing_user['active_email'] : '';

            $_social_user_obj = get_user_by('email', $email);
            if (is_object($_social_user_obj) && isset($_social_user_obj->ID)) {
                update_user_meta($_social_user_obj->ID, 'jobsearch_xing_id', $user_id);
            }
            if ($first_name != '' && $last_name != '') {
                $name = $first_name . '_' . $last_name;
                $name = str_replace(array(' '), array('_'), $name);
                $username = sanitize_user(str_replace(' ', '_', strtolower($name)));
            } else {
                $username = $email;
            }
            if (username_exists($username)) {
                $username .= '_' . rand(10000, 99999);
            }
            // Creating our user
            $user_pass = wp_generate_password();
            $new_user = wp_create_user($username, $user_pass, $email);
            if (is_wp_error($new_user)) {
                $json_arr['status'] = false;
                $json_arr['msg'] = $new_user->get_error_message();
            } else {
                
                $user_candidate_id = jobsearch_get_user_candidate_id($new_user);
                // user role
                $user_role = 'jobsearch_candidate';
                wp_update_user(array('ID' => $new_user, 'role' => $user_role));
                update_user_meta($new_user, 'first_name', $first_name);
                update_user_meta($new_user, 'jobsearch_xing_id', $user_id);
                $candidate_id = get_user_meta($new_user, 'jobsearch_candidate_id', true);
                update_post_meta($candidate_id, 'jobsearch_field_candidate_jobtitle', $job_title);
                
                if ($candidate_auto_approve == 'on' || $candidate_auto_approve == 'email') {
                    update_post_meta($user_candidate_id, 'jobsearch_field_candidate_approved', 'on');
                }
                
                $c_user = get_user_by('ID', $new_user);
                do_action('jobsearch_new_user_register', $c_user, $user_pass);

                wp_set_auth_cookie($new_user);
            }
        } else {
            wp_set_auth_cookie($wp_users[0]);
        }

        echo json_encode($json_arr);
        wp_die();
    }

    public function display_login_button() {

        global $jobsearch_plugin_options;
        $rand_id = rand(10000000, 99999999);
        $admin_ajax_url = admin_url('admin-ajax.php');
        $jobsearch_xing_consumer_key = isset($jobsearch_plugin_options['jobsearch_xing_consumer_key']) ? $jobsearch_plugin_options['jobsearch_xing_consumer_key'] : '';
        
        ob_start();
        if (!empty($jobsearch_xing_consumer_key)) {
            echo '<script type="xing/login">{"consumer_key": "' . $jobsearch_xing_consumer_key . '","size": "xlarge"}</script>';
        }
        $Button_Data = ob_get_clean();
        ?>
        <li id="jobsearch-xing-wrapper" style="position: relative;">
            <script>
                jQuery(document).ready(function () {
                    var setCusCss<?php echo ($rand_id); ?> = setInterval(function () {
                        //
                        var $iframe<?php echo ($rand_id); ?> = jQuery('#xing-dynam-btn<?php echo ($rand_id); ?>').find('iframe');
                        $iframe<?php echo ($rand_id); ?>.attr('id', 'xing_frame_<?php echo ($rand_id); ?>');
                        $iframe<?php echo ($rand_id); ?>.attr('name', 'xing_frame_<?php echo ($rand_id); ?>');
                        $iframe<?php echo ($rand_id); ?>.css({'width': '100%', 'opacity': '0'});
                        $iframe<?php echo ($rand_id); ?>.attr('width', '100%');

                        clearInterval(setCusCss<?php echo ($rand_id); ?>);
                    }, 2000);
                });
                function onXingAuthLogin(xing_response) {
                    if (xing_response.error && xing_response.user != null) {
                        alert(xing_response.error);
                    } else {
                        if (xing_response.user != 'undefined' && xing_response.user != null) {
                            var userStr = JSON.stringify(xing_response.user);
                            var dataString = 'action=jobsearch_xing_response_data&user_data=' + userStr + '';
                            var ajax_url = '<?php echo ($admin_ajax_url); ?>';
                            jQuery.ajax({
                                type: 'POST',
                                url: ajax_url,
                                dataType: "json",
                                data: dataString,
                                success: function (response) {
                                    if (response.status !== 'undefined' && response.status == true) {
                                        window.location.href = response.redirect_url;
                                    } else {
                                        alert(response.msg);
                                    }
                                }
                            });
                        }
                    }
                }
            </script>
            <div id="xing-dynam-btn<?php echo ($rand_id); ?>" class="xing-dynamic-btn" style="position: absolute; width: 96%; z-index: 999;"><?php echo ($Button_Data); ?></div>
            <script>
                (function (d) {
                    var js, id = 'lwx';
                    if (d.getElementById(id))
                        return;
                    js = d.createElement('script');
                    js.id = id;
                    js.src = "https://www.xing-share.com/plugins/login.js";
                    d.getElementsByTagName('head')[0].appendChild(js)
                }(document));
            </script>

            <a id="jobsearch-xing-login" class="jobsearch-xing-bg" href="javascript:void(0);"><i class="fa fa-xing"></i><?php echo __('Login with Xing', 'wp-jobsearch') ?></a>
        </li>
        <?php
    }
    
    public function do_apply_job_with_xing($user_id)
    {

        global $jobsearch_plugin_options;

        $candidate_auto_approve = isset($jobsearch_plugin_options['candidate_auto_approve']) ? $jobsearch_plugin_options['candidate_auto_approve'] : '';

        $user_is_candidate = jobsearch_user_is_candidate($user_id);

        $apply_job_cond = true;
        if ($candidate_auto_approve != 'on') {
            $apply_job_cond = false;
            if ($user_is_candidate) {
                $candidate_id = jobsearch_get_user_candidate_id($user_id);
                $candidate_status = get_post_meta($candidate_id, 'jobsearch_field_candidate_approved', true);
                if ($candidate_status == 'on') {
                    $apply_job_cond = true;
                }
            }
        }
        if ($candidate_auto_approve == 'email') {
            $apply_job_cond = true;
        }

        if (isset($_COOKIE['jobsearch_apply_xing_jobid']) && $_COOKIE['jobsearch_apply_xing_jobid'] > 0) {
            $job_id = $_COOKIE['jobsearch_apply_xing_jobid'];

            //
            if ($user_is_candidate && $apply_job_cond) {
                $candidate_id = jobsearch_get_user_candidate_id($user_id);

                jobsearch_create_user_meta_list($job_id, 'jobsearch-user-jobs-applied-list', $user_id);
                $job_applicants_list = get_post_meta($job_id, 'jobsearch_job_applicants_list', true);
                if ($job_applicants_list != '') {
                    $job_applicants_list = explode(',', $job_applicants_list);
                    if (!in_array($candidate_id, $job_applicants_list)) {
                        $job_applicants_list[] = $candidate_id;
                    }
                    $job_applicants_list = implode(',', $job_applicants_list);
                } else {
                    $job_applicants_list = $candidate_id;
                }
                update_post_meta($job_id, 'jobsearch_job_applicants_list', $job_applicants_list);
                $c_user = get_user_by('ID', $user_id);
                do_action('jobsearch_job_applied_to_employer', $c_user, $job_id);
                do_action('jobsearch_job_applied_to_candidate', $c_user, $job_id);
            }

            unset($_COOKIE['jobsearch_apply_xing_jobid']);
            setcookie('jobsearch_apply_xing_jobid', null, -1, '/');
        }
    }

    public function applying_job_with_xing()
    {
        global $jobsearch_plugin_options;

        $candidate_auto_approve = isset($jobsearch_plugin_options['candidate_auto_approve']) ? $jobsearch_plugin_options['candidate_auto_approve'] : '';

        $job_id = isset($_POST['job_id']) ? $_POST['job_id'] : '';
        if ($job_id > 0 && get_post_type($job_id) == 'job') {

            setcookie('jobsearch_apply_xing_jobid', $job_id, time() + (180), "/");
            if ($candidate_auto_approve == 'on') {
                $real_redirect_url = get_permalink($job_id);
                setcookie('xing_redirect_url', $real_redirect_url, time() + (360), "/");
            }

            echo json_encode(array('redirect_url' => admin_url('admin-ajax.php?action=jobsearch_xing')));
            die;
        } else {
            echo json_encode(array('msg' => esc_html__('There is some problem.', 'wp-jobsearch')));
            die;
        }
    }

    public function apply_job_with_xing($args = array())
    {
        global $jobsearch_plugin_options;
        
        $rand_id = rand(10000000, 99999999);
        $admin_ajax_url = admin_url('admin-ajax.php');
        
        $jobsearch_xing_consumer_key = isset($jobsearch_plugin_options['jobsearch_xing_consumer_key']) ? $jobsearch_plugin_options['jobsearch_xing_consumer_key'] : '';
        ob_start();
        if (!empty($jobsearch_xing_consumer_key)) {
            echo '<script type="xing/login">{"consumer_key": "' . $jobsearch_xing_consumer_key . '","size": "xlarge"}</script>';
        }
        $Button_Data = ob_get_clean();
        
        $xing_login = isset($jobsearch_plugin_options['xing-social-login']) ? $jobsearch_plugin_options['xing-social-login'] : '';
        if ($jobsearch_xing_consumer_key != '' && $xing_login == 'on') {
            $job_id = isset($args['job_id']) ? $args['job_id'] : '';
            $classes = isset($args['classes']) && !empty($args['classes']) ? $args['classes'] : 'jobsearch-applyjob-xing-btn';

            $label = isset($args['label']) ? $args['label'] : '';
            $view = isset($args['view']) ? $args['view'] : '';

            ob_start();
            ?>
            <script>
                jQuery(document).ready(function () {
                    var setCusCss<?php echo ($rand_id); ?> = setInterval(function () {
                        //
                        var $iframe<?php echo ($rand_id); ?> = jQuery('#xing-dynam-btn<?php echo ($rand_id); ?>').find('iframe');
                        $iframe<?php echo ($rand_id); ?>.attr('id', 'xing_frame_<?php echo ($rand_id); ?>');
                        $iframe<?php echo ($rand_id); ?>.attr('name', 'xing_frame_<?php echo ($rand_id); ?>');
                        $iframe<?php echo ($rand_id); ?>.css({'width': '100%', 'opacity': '0'});
                        $iframe<?php echo ($rand_id); ?>.attr('width', '100%');

                        clearInterval(setCusCss<?php echo ($rand_id); ?>);
                    }, 2000);
                });
                function onXingAuthLogin(xing_response) {
                    if (xing_response.error && xing_response.user != null) {
                        alert(xing_response.error);
                    } else {
                        if (xing_response.user != 'undefined' && xing_response.user != null) {
                            var userStr = JSON.stringify(xing_response.user);
                            var dataString = 'action=jobsearch_xing_response_data&user_data=' + userStr + '';
                            var ajax_url = '<?php echo ($admin_ajax_url); ?>';
                            jQuery.ajax({
                                type: 'POST',
                                url: ajax_url,
                                dataType: "json",
                                data: dataString,
                                success: function (response) {
                                    if (response.status !== 'undefined' && response.status == true) {
                                        window.location.href = response.redirect_url;
                                    } else {
                                        alert(response.msg);
                                    }
                                }
                            });
                        }
                    }
                }
            </script>
            <div id="xing-dynam-btn<?php echo ($rand_id); ?>" class="xing-dynamic-btn" style="position: absolute; width: 96%; z-index: 999;"><?php echo ($Button_Data); ?></div>
            <script>
                (function (d) {
                    var js, id = 'lwx';
                    if (d.getElementById(id))
                        return;
                    js = d.createElement('script');
                    js.id = id;
                    js.src = "https://www.xing-share.com/plugins/login.js";
                    d.getElementsByTagName('head')[0].appendChild(js)
                }(document));
            </script>
            <?php
            $xing_script = ob_get_clean();
            
            if ($view == 'job2') { ?>
                <a href="javascript:void(0);" class="<?php echo($classes); ?>"
                   data-id="<?php echo($job_id) ?>"><?php echo($label); ?><?php echo ($xing_script) ?></a>
            <?php } elseif ($view == 'job3') { ?>
                <li><a href="javascript:void(0);" class="<?php echo($classes); ?>" data-id="<?php echo($job_id) ?>"></a><?php echo ($xing_script) ?>
                </li>
            <?php } elseif ($view == 'job4') { ?>
                <a href="javascript:void(0);" class="<?php echo($classes); ?>"
                   data-id="<?php echo($job_id) ?>"><i
                            class="fa fa-xing"></i> <?php esc_html_e('Apply with Xing', 'wp-jobsearch') ?><?php echo ($xing_script) ?>
                </a>
            <?php } elseif ($view == 'job5') { ?>
                <a href="javascript:void(0);" class="<?php echo($classes); ?>"
                   data-id="<?php echo($job_id) ?>"><i
                            class="fa fa-xing"></i><?php echo($label); ?></a><?php echo ($xing_script) ?>
            <?php } elseif ($view == 'job6') { ?>
                <li><a href="javascript:void(0);" class="<?php echo($classes); ?>"
                       data-id="<?php echo($job_id) ?>"><i
                                class="fa fa-xing"></i><?php echo($label); ?></a><?php echo ($xing_script) ?></li>
            <?php } else { ?>
                <li><a href="javascript:void(0);" class="<?php echo($classes); ?>" data-id="<?php echo($job_id) ?>"><i
                                class="fa fa-xing"></i> <?php esc_html_e('Xing', 'wp-jobsearch') ?>
                    </a><?php echo ($xing_script) ?></li>
                <?php
            }
        }
    }

}

/*
 * Starts social login
 */
new JobsearchXingLogin();
