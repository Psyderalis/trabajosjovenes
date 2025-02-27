<?php

namespace CareerfyElementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

/**
 * @since 1.1.0
 */
class SimpleEmployerListings extends Widget_Base
{

    /**
     * Retrieve the widget name.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'simple-emp-listing';
    }

    /**
     * Retrieve the widget title.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('Simple Employer Listing', 'careerfy-frame');
    }

    /**
     * Retrieve the widget icon.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'fa fa-list-alt';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @since 1.1.0
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['wp-jobsearch'];
    }

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.1.0
     *
     * @access protected
     */


    protected function register_controls()
    {
        $categories = get_terms(array(
            'taxonomy' => 'sector',
            'hide_empty' => false,
        ));

        $cate_array = array(esc_html__("Select Sector", "careerfy-frame") => '');
        if (is_array($categories) && sizeof($categories) > 0) {
            foreach ($categories as $category) {
                $cate_array[$category->name] = $category->slug;
            }
        }

        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Simple Employer Listings Settings', 'careerfy-frame'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'employer_style',
            [
                'label' => __('Style', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'simple',
                'options' => [
                    'simple' => __('Simple Style 1', 'careerfy-frame'),
                    'style3' => __('Simple Style 2', 'careerfy-frame'),
                    'style4' => __('Simple Style 3', 'careerfy-frame'),
                    'style5' => __('Simple Style 4', 'careerfy-frame'),
                    'slider' => __('Slider Style 1', 'careerfy-frame'),
                    'slider2' => __('Slider Style 2', 'careerfy-frame'),
                    'slider3' => __('Slider Style 3', 'careerfy-frame'),

                ],
            ]
        );
        $this->add_control(
            'employer_title',
            [
                'label' => __('Title', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'employer_style' => ['slider2'],
                ],
            ]
        );

        $this->add_control(
            'employer_cat',
            [
                'label' => __('Sector', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'options' => $cate_array,
            ]
        );

        $this->add_control(
            'link_text',
            [
                'label' => __('Link text', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => [
                    'employer_style' => 'slider2',
                ],
            ]
        );

        $this->add_control(
            'link_text_url',
            [
                'label' => __('Link Text URL', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'condition' => [
                    'employer_style' => 'slider2',
                ],
            ]
        );

        $this->add_control(
            'employer_order',
            [
                'label' => __('Order', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'desc',
                'description' => __('Choose list items order.', 'careerfy-frame'),
                'options' => [
                    'desc' => __('DESC', 'careerfy-frame'),
                    'asc' => __('ASC', 'careerfy-frame'),
                ],
            ]
        );

        $this->add_control(
            'employer_orderby',
            [
                'label' => __('Order By', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'date',
                'description' => __('Choose list items orderby.', 'careerfy-frame'),
                'options' => [
                    'date' => __('Date', 'careerfy-frame'),
                    'title' => __('Title', 'careerfy-frame'),
                    'promote_profile' => __('Promote Profile', 'careerfy-frame'),
                    'meta_value_num' => __('Active Jobs', 'careerfy-frame'),
                ],
            ]
        );

        $this->add_control(
            'employer_per_page',
            [
                'label' => __('Number of Items', 'careerfy-frame'),
                'type' => Controls_Manager::TEXT,
                'default' => '20',
                'description' => __('Set number that how many employers you want to show.', 'careerfy-frame'),
            ]
        );

        $this->add_control(
            'employer_load_more',
            [
                'label' => __('Load More Jobs', 'careerfy-frame'),
                'type' => Controls_Manager::SELECT2,
                'default' => 'DESC',
                'description' => __('Choose yes if you want to show more job items.', 'careerfy-frame'),
                'options' => [
                    'yes' => __('Yes', 'careerfy-frame'),
                    'no' => __('No', 'careerfy-frame'),
                ],
                'condition' => [
                    'employer_style' => ['simple'],
                ],
            ]
        );


        $this->end_controls_section();
    }


    public static function load_more_employers_posts($employers_posts, $listin_view = 'simple')
    {
        global $jobsearch_plugin_options, $sitepress, $employer_per_page, $rand_num;
        $sectors_enable_switch = isset($jobsearch_plugin_options['sectors_onoff_switch']) ? $jobsearch_plugin_options['sectors_onoff_switch'] : '';
        $all_location_allow = isset($jobsearch_plugin_options['all_location_allow']) ? $jobsearch_plugin_options['all_location_allow'] : '';
        $employer_types_switch = isset($jobsearch_plugin_options['employer_types_switch']) ? $jobsearch_plugin_options['employer_types_switch'] : '';
        if (!empty($employers_posts)) {
            $half_list_num = 1;
            if (sizeof($employers_posts) > 10) {
                $half_list_num = ceil(sizeof($employers_posts) / 2);
            }
            $emplist_count = 1;

            foreach ($employers_posts as $employer_id) {
                $post_thumbnail_src = self::EmployerThumbNails($employer_id, $listin_view);
                $categories = get_the_terms($employer_id, 'sector');

                $job_search_employer_location = get_post_meta($employer_id, 'jobsearch_field_location_address', true);
                $job_search_employer_featured = get_post_meta($employer_id, 'cusemp_feature_fbckend', true);
                $job_search_employer_sectors = wp_get_post_terms($employer_id, 'sector');
                $employer_sector = !empty($job_search_employer_sectors[0]->name) ? jobsearch_esc_html($job_search_employer_sectors[0]->name) : '';
                $job_search_employer_info = get_post($employer_id);
                $jobsearch_employer_job_count = jobsearch_employer_total_jobs_posted($employer_id);
                if ($jobsearch_employer_job_count == 1) {
                    if ($listin_view == 'slider3' || $listin_view == 'style5') {
                        $jobsearch_employer_job_count_str = esc_html__('POSITION', 'careerfy-frame');
                    } else {
                        $jobsearch_employer_job_count_str = esc_html__('Active Job', 'careerfy-frame');
                    }
                } else {
                    if ($listin_view == 'slider3' || $listin_view == 'style5') {
                        $jobsearch_employer_job_count_str = esc_html__('POSITIONS', 'careerfy-frame');
                    } else {
                        $jobsearch_employer_job_count_str = esc_html__('Active Jobs', 'careerfy-frame');
                    }
                }
                if ($listin_view == 'simple' || $listin_view == 'slider') { ?>
                    <li>
                        <a href="<?php echo get_permalink($employer_id) ?>">
                        <span class="careerfy-categories-classic-logo"> <img src="<?php echo($post_thumbnail_src) ?>" alt=""> </span>
                            <span class="careerfy-categories-classic-title"><?php echo($jobsearch_employer_job_count_str) ?></span>
                            <small>(<?php echo absint($jobsearch_employer_job_count) ?>)</small>
                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <span class="careerfy-jobli-medium3"><i class="fa fa-star"></i></span>
                            <?php } ?>
                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <span class="careerfy-jobli-medium3"><i class="fa fa-star"></i></span>
                            <?php } ?>
                        </a>
                    </li>
                <?php } else if ($listin_view == 'style5') { ?>
                    <li class="col-md-3">
                        <div class="careerfy-seventeen-employers-grid-inner">
                            <figure><a href="<?php echo get_permalink($employer_id) ?>"><img
                                            src="<?php echo($post_thumbnail_src) ?>" alt=""></a></figure>
                            <h2>
                                <a href="<?php echo get_permalink($employer_id) ?>"><?php echo jobsearch_esc_html($job_search_employer_info->post_title) ?></a>
                            </h2>
                            <?php if (!empty($categories)) { ?>
                                <span><?php echo jobsearch_esc_html($categories[0]->name) ?></span>
                            <?php } ?>
                            <small><?php echo absint($jobsearch_employer_job_count) ?><?php echo $jobsearch_employer_job_count_str ?></small>

                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <span class="careerfy-jobli-medium3"><i class="fa fa-star"></i></span>
                            <?php } ?>
                        </div>

                    </li>
                <?php } else if ($listin_view == 'style3') { ?>
                    <li class="col-md-4">
                        <div class="top-companies-list-inner">
                            <figure>
                                <a href="<?php echo get_permalink($employer_id) ?>"><img
                                            src="<?php echo($post_thumbnail_src) ?>" alt=""></a>
                            </figure>
                            <div class="top-companies-list-text">
                                <?php if ($employer_sector != "") { ?>
                                    <span><?php echo jobsearch_esc_html($employer_sector) ?></span>
                                <?php } ?>
                                <h2>
                                    <a href="<?php echo get_permalink($employer_id) ?>"><?php echo jobsearch_esc_html($job_search_employer_info->post_title) ?></a>
                                </h2>
                                <small><?php echo jobsearch_esc_html($job_search_employer_location) ?></small>
                                <a href="<?php echo get_permalink($employer_id) ?>"
                                   class="top-companies-list-text-btn"><?php echo absint($jobsearch_employer_job_count) ?><?php echo($jobsearch_employer_job_count_str) ?></a>
                            </div>
                            <?php
                            if (function_exists('jobsearch_member_promote_profile_iconlab')) {
                                echo jobsearch_member_promote_profile_iconlab($employer_id, 'simple_employer_list_style3');
                            }
                            ?>
                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <span class="careerfy-jobli-medium3"><i class="fa fa-star"></i></span>
                            <?php } ?>
                        </div>
                    </li>

                <?php } else if ($listin_view == 'slider2') { ?>
                    <li>
                        <a href="<?php echo get_permalink($employer_id) ?>">
                            <img src="<?php echo($post_thumbnail_src) ?>" alt="">
                            <span><?php echo $jobsearch_employer_job_count_str ?>
                                <small>(<?php echo($jobsearch_employer_job_count) ?>)</small></span>
                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <small class="careerfy-jobli-medium3"><i class="fa fa-star"></i></small>
                            <?php } ?>
                        </a>
                    </li>

                <?php } else if ($listin_view == 'slider3') { ?>
                    <li class="col-md-3">
                        <figure>
                            <a href="<?php echo get_permalink($employer_id) ?>">
                                <img src="<?php echo($post_thumbnail_src) ?>" alt=""></a>
                            <figcaption>
                                <h2>
                                    <a href="<?php echo get_permalink($employer_id) ?>">
                                        <?php echo jobsearch_esc_html(get_the_title($employer_id)) ?></a>
                                </h2>
                                <span><i class="fa fa-map-marker"></i>
                                    <?php echo jobsearch_esc_html($job_search_employer_location) ?></span>
                                <small>
                                    <?php echo($jobsearch_employer_job_count) ?>
                                    <?php echo($jobsearch_employer_job_count_str) ?></small>
                            </figcaption>
                            <?php if ($job_search_employer_featured == 'on') { ?>
                                <span class="careerfy-jobli-medium3"><i class="fa fa-star"></i></span>
                            <?php } ?>
                        </figure>
                    </li>
                <?php } else { ?>
                    <li><a href="<?php echo get_permalink($employer_id) ?>"><img
                                    src="<?php echo($post_thumbnail_src) ?>" alt=""></a></li>
                <?php }


                if ($half_list_num > 1 && $emplist_count == $half_list_num && $listin_view == 'slider') {
                    echo '</ul>
                    </div>
                </div>
                <div class="careerfy-categories-classic-slider-layer">
                    <div class="careerfy-categories-classic">
                        <ul class="main-empslists-' . $rand_num . '">';
                } else if ($emplist_count % 9 === 0 && $listin_view == 'slider2' && $emplist_count != $employer_per_page) {
                    echo '</ul>
                    </div>
                </div>
                <div class="careerfy-top-employers-slider-layer">
                    <div class="careerfy-top-employers-slider-list">
                        <ul class="main-empslists-' . $rand_num . '">';
                } else if ($emplist_count % 4 == 0 && $listin_view == 'slider3' && $emplist_count != $employer_per_page) {


                    echo '</ul>
                    </div>
                </div>
                <div class="careerfy-sixteen-topcompanies-layer">
                    <div class="careerfy-sixteen-topcompanies-list">
                        <ul class="row main-empslists-' . $rand_num . '">';
                }

                $emplist_count++;
            }
        }
    }

    /**
     * Edit join
     *
     * @param string $join_paged_statement
     * @param WP_Query $wp_query
     * @return string
     */
    public function edit_join($join_paged_statement, $wp_query)
    {
        global $wpdb;
        if (
            !isset($wp_query->query) || $wp_query->is_page || (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] != 'employer')
        ) {
            return $join_paged_statement;
        }

        $join_to_add = "
                LEFT JOIN {$wpdb->prefix}postmeta AS postmeta
                    ON ({$wpdb->prefix}posts.ID = postmeta.post_id
                        AND postmeta.meta_key = 'promote_profile_substime')";

        // Only add if it's not already in there
        if (strpos($join_paged_statement, $join_to_add) === false) {
            $join_paged_statement = $join_paged_statement . $join_to_add;
        }

        return $join_paged_statement;
    }

    /**
     * Edit orderby
     *
     * @param string $orderby_statement
     * @param WP_Query $wp_query
     * @return string
     */
    Private static function EmployerThumbNails($employer_id, $listin_view)
    {
        $post_thumbnail_id = jobsearch_employer_get_profile_image($employer_id);

        if ($listin_view == 'style3' || $listin_view == 'slider2' || $listin_view == 'style5') {
            $img_size = 'style3';
        } else if ($listin_view == 'slider') {
            $img_size = 'thumbnail';
        } else if ($listin_view == 'slider3') {
            $img_size = 'careerfy-service';
        } else {
            $img_size = 'careerfy-emp-msmal';
        }

        $post_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, $img_size);
        $post_thumbnail_src = isset($post_thumbnail_image[0]) && esc_url($post_thumbnail_image[0]) != '' ? $post_thumbnail_image[0] : '';
        return $post_thumbnail_src == '' ? jobsearch_employer_image_placeholder() : $post_thumbnail_src;
    }

    public function edit_orderby($orderby_statement, $wp_query)
    {
        if (!isset($wp_query->query) || $wp_query->is_page || (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] != 'employer')
        ) {
            return $orderby_statement;
        }
        $orderby_statement = "cast(postmeta.meta_value as unsigned) DESC";
        return $orderby_statement;
    }

    protected function render()
    {
        global $jobsearch_plugin_options, $employer_per_page, $rand_num;
        $atts = $this->get_settings_for_display();
        extract(shortcode_atts(array(
            'employer_style' => 'simple',
            'employer_cat' => '',
            'employer_order' => 'DESC',
            'employer_orderby' => 'date',
            'employer_per_page' => '20',
            'employer_load_more' => 'yes',
            'employer_title' => '',
            'link_text' => '',
            'link_text_url' => '',
        ), $atts));

        $rand_num = rand(10000000, 99909999);
        $jobsearch__options = get_option('jobsearch_plugin_options');
        $employer_per_page = isset($employer_per_page) && !empty($employer_per_page) && $employer_per_page > 0 ? $employer_per_page : 20;
        if ($employer_style == 'slider' && $employer_per_page > 0) {
            $employer_per_page = $employer_per_page * 2;
        }

        $element_filter_arr = array();
        $element_filter_arr[] = array(
            'key' => 'jobsearch_field_employer_approved',
            'value' => 'on',
            'compare' => '=',
        );
//        $element_filter_arr[] = array(
//            'key' => 'cusemp_feature_fbckend',
//            'value' => 'on',
//            'compare' => '=',
//        );

        $args = array(
            'posts_per_page' => $employer_per_page,
            'post_type' => 'employer',
            'post_status' => 'publish',
            'order' => $employer_order,
            'orderby' => $employer_orderby,
            'meta_key' => 'jobsearch_field_employer_job_count',
            'fields' => 'ids', // only load ids
            'meta_query' => array(
                $element_filter_arr,
            ),
        );
        if ($employer_cat != '') {
            $args['tax_query'][] = array(
                'taxonomy' => 'sector',
                'field' => 'slug',
                'terms' => $employer_cat
            );
        }

        if ($employer_orderby == 'promote_profile') {
            add_filter('posts_join_paged', array($this, 'edit_join'), 999, 2);
            add_filter('posts_orderby', array($this, 'edit_orderby'), 999, 2);
        }

        $employers_query = new \WP_Query($args);


        $totl_found_jobs = $employers_query->found_posts;
        $employers_posts = $employers_query->posts;

        if ($employer_orderby == 'promote_profile') {
            remove_filter('posts_join_paged', array($this, 'edit_join'), 999, 2);
            remove_filter('posts_orderby', array($this, 'edit_orderby'), 999, 2);
        }

        ob_start();
        if (!empty($employers_posts)) {
            if ($employer_style == 'slider' || $employer_style == 'slider2' || $employer_style == 'slider3') {
                echo '
                <div id="careerfy-slidmaintop-' . ($rand_num) . '" style="position: relative; float: left; width: 100%;">
                <div id="careerfy-slidloder-' . ($rand_num) . '" class="careerfy-slidloder-section"><div class="ball-scale-multiple"><div></div><div></div><div></div></div></div>';

            }
            if ($employer_style == 'slider') {
                echo '<div id="employer-listin-slidr-' . $rand_num . '" class="careerfy-categories-classic-slider">
                  <div class="careerfy-categories-classic-slider-layer">';
            }
            if ($employer_style == 'simple' || $employer_style == 'slider') {
                ?>
                <div class="careerfy-categories-classic">
                    <ul class="main-empslists-<?php echo($rand_num) ?>">
                        <?php
                        self::load_more_employers_posts($employers_posts, $employer_style);
                        ?>
                    </ul>
                </div>
                <?php
            } else if ($employer_style == 'style5') { ?>
                <div class="careerfy-seventeen-employers-grid">
                    <ul class="row">
                        <?php
                        self::load_more_employers_posts($employers_posts, $employer_style);
                        ?>
                    </ul>
                </div>

            <?php } else if ($employer_style == 'style3') { ?>
                <!-- Top Companies List -->
                <div class="top-companies-list">
                    <ul class="row">
                        <?php
                        self::load_more_employers_posts($employers_posts, $employer_style);
                        ?>
                    </ul>
                </div>
                <!-- Top Companies List -->
            <?php } else if ($employer_style == 'slider2') {
                $view_all_btn_class = isset($employer_per_page) && $totl_found_jobs <= 9 ? 'no-slider' : '';
                ?>
                <!-- Premium Section -->
                <div class="careerfy-section-premium-wrap">
                    <?php if (jobsearch_esc_html($employer_title) != '') { ?>
                        <div class="careerfy-section-title-style">
                            <h2><?php echo jobsearch_esc_html($employer_title) ?></h2>
                            <?php if (!empty($link_text)) { ?>
                                <a href="<?php echo $link_text_url ?>"
                                   class="careerfy-section-title-btn <?php echo $view_all_btn_class ?>"><?php echo $link_text ?></a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div id="careerfy-top-employers-<?php echo $rand_num ?>" class="careerfy-top-employers-slider">
                        <div class="careerfy-top-employers-slider-layer">
                            <div class="careerfy-top-employers-slider-list">
                                <ul class="main-empslists-<?php echo($rand_num) ?>">
                                    <?php
                                    self::load_more_employers_posts($employers_posts, $employer_style);
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Section -->
            <?php } else if ($employer_style == 'slider3') { ?>

                <div id="topcompanies-slider-<?php echo $rand_num ?>" class="careerfy-sixteen-topcompanies-slider">
                    <div class="careerfy-sixteen-topcompanies-layer">
                        <div class="careerfy-sixteen-topcompanies-list">
                            <ul class="row main-empslists-<?php echo($rand_num) ?>">
                                <?php self::load_more_employers_posts($employers_posts, $employer_style); ?>
                            </ul>
                        </div>
                    </div>
                </div>

            <?php } else { ?>
                <div class="top-companies-list">
                    <ul>
                        <?php self::load_more_employers_posts($employers_posts, $employer_style); ?>
                    </ul>
                </div>

            <?php }

            if ($employer_style == 'slider') {
                echo '</div></div>';
                ?>

                <script>
                    jQuery(document).ready(function ($) {
                        jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                        jQuery('#main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});
                        jQuery('#employer-listin-slidr-<?php echo($rand_num) ?>').slick({
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            autoplay: true,
                            autoplaySpeed: 5000,
                            infinite: false,
                            dots: false,
                            prevArrow: "<span class='slick-arrow-left'><i class='careerfy-icon careerfy-arrow-right-light'></i></span>",
                            nextArrow: "<span class='slick-arrow-right'><i class='careerfy-icon careerfy-arrow-right-light'></i></span>",
                            responsive: [
                                {
                                    breakpoint: 1024,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1,
                                        infinite: true,
                                    }
                                },
                                {
                                    breakpoint: 800,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                },
                                {
                                    breakpoint: 400,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                }
                            ]
                        });

                        var remSlidrLodrInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-slidloder-<?php echo($rand_num) ?>').remove();
                            clearInterval(remSlidrLodrInt<?php echo($rand_num) ?>);
                        }, 1500);

                        var slidrHightInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                            jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});

                            var slider_act_height_<?php echo($rand_num) ?> = jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').height();

                            var filtr_cname_<?php echo($rand_num) ?> = 'careerfy_topemps_slidr_lheight';
                            var c_date_<?php echo($rand_num) ?> = new Date();
                            c_date_<?php echo($rand_num) ?>.setTime(c_date_<?php echo($rand_num) ?>.getTime() + (60 * 60 * 1000));
                            var c_expires_<?php echo($rand_num) ?> = "; c_expires=" + c_date_<?php echo($rand_num) ?>.toGMTString();
                            document.cookie = filtr_cname_<?php echo($rand_num) ?> + "=" + slider_act_height_<?php echo($rand_num) ?> + c_expires_<?php echo($rand_num) ?> + "; path=/";

                            clearInterval(slidrHightInt<?php echo($rand_num) ?>);
                        }, 1700);
                    });
                    jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'none'});

                    var slider_height_<?php echo($rand_num) ?> = '<?php echo(isset($_COOKIE['careerfy_topemps_slidr_lheight']) && $_COOKIE['careerfy_topemps_slidr_lheight'] != '' ? $_COOKIE['careerfy_topemps_slidr_lheight'] . 'px' : '300px') ?>';
                    jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': slider_height_<?php echo($rand_num) ?>});
                </script>

            <?php } else if ($employer_style == 'slider2') { ?>
                <script type="text/javascript">
                    //*** Function Top Employers Slider
                    jQuery(document).ready(function ($) {
                        jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                        jQuery('#main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});
                        jQuery('#careerfy-top-employers-<?php echo $rand_num ?>').slick({
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            autoplay: true,
                            autoplaySpeed: 5000,
                            infinite: true,
                            dots: false,
                            prevArrow: "<span class='slick-arrow-left'><i class='careerfy-icon careerfy-next'></i></span>",
                            nextArrow: "<span class='slick-arrow-right'><i class='careerfy-icon careerfy-next'></i></span>",
                            responsive: [
                                {
                                    breakpoint: 1024,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1,
                                        infinite: true,
                                    }
                                },
                                {
                                    breakpoint: 800,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                },
                                {
                                    breakpoint: 400,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                }
                            ]
                        });

                        var remSlidrLodrInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-slidloder-<?php echo($rand_num) ?>').remove();
                            clearInterval(remSlidrLodrInt<?php echo($rand_num) ?>);
                        }, 1500);

                        var slidrHightInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-top-employers-<?php echo($rand_num) ?>').find('img').attr('width', '');
                            jQuery('#careerfy-top-employers-<?php echo($rand_num) ?>').find('img').attr('height', '');
                            jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                            jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});

                            var slider_act_height_<?php echo($rand_num) ?> = jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').height();

                            var filtr_cname_<?php echo($rand_num) ?> = 'careerfy_topemps_slidr_lheight';
                            var c_date_<?php echo($rand_num) ?> = new Date();
                            c_date_<?php echo($rand_num) ?>.setTime(c_date_<?php echo($rand_num) ?>.getTime() + (60 * 60 * 1000));
                            var c_expires_<?php echo($rand_num) ?> = "; c_expires=" + c_date_<?php echo($rand_num) ?>.toGMTString();
                            document.cookie = filtr_cname_<?php echo($rand_num) ?> + "=" + slider_act_height_<?php echo($rand_num) ?> + c_expires_<?php echo($rand_num) ?> + "; path=/";
                            clearInterval(slidrHightInt<?php echo($rand_num) ?>);
                        }, 1700);
                    });

                    jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'none'});

                    var slider_height_<?php echo($rand_num) ?> = '<?php echo(isset($_COOKIE['careerfy_topemps_slidr_lheight']) && $_COOKIE['careerfy_topemps_slidr_lheight'] != '' ? $_COOKIE['careerfy_topemps_slidr_lheight'] . 'px' : '300px') ?>';
                    jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': slider_height_<?php echo($rand_num) ?>});
                </script>

            <?php } else if ($employer_style == 'slider3') { ?>

                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                        jQuery('#main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});

                        $('#topcompanies-slider-<?php echo $rand_num ?>').slick({
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            autoplay: true,
                            autoplaySpeed: 3000,
                            infinite: true,
                            dots: true,
                            arrows: false,
                            responsive: [
                                {
                                    breakpoint: 1024,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1,
                                        infinite: true,
                                    }
                                },
                                {
                                    breakpoint: 800,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                },
                                {
                                    breakpoint: 400,
                                    settings: {
                                        slidesToShow: 1,
                                        slidesToScroll: 1
                                    }
                                }
                            ]
                        });
                        var remSlidrLodrInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-slidloder-<?php echo($rand_num) ?>').remove();
                            clearInterval(remSlidrLodrInt<?php echo($rand_num) ?>);
                        }, 1500);

                        var slidrHightInt<?php echo($rand_num) ?> = setInterval(function () {
                            jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': 'auto'});
                            jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'block'});

                            var slider_act_height_<?php echo($rand_num) ?> = jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').height();

                            var filtr_cname_<?php echo($rand_num) ?> = 'careerfy_topemps_slidr_lheight';
                            var c_date_<?php echo($rand_num) ?> = new Date();
                            c_date_<?php echo($rand_num) ?>.setTime(c_date_<?php echo($rand_num) ?>.getTime() + (60 * 60 * 1000));
                            var c_expires_<?php echo($rand_num) ?> = "; c_expires=" + c_date_<?php echo($rand_num) ?>.toGMTString();
                            document.cookie = filtr_cname_<?php echo($rand_num) ?> + "=" + slider_act_height_<?php echo($rand_num) ?> + c_expires_<?php echo($rand_num) ?> + "; path=/";

                            clearInterval(slidrHightInt<?php echo($rand_num) ?>);
                        }, 1700);
                    });

                    jQuery('.main-empslists-<?php echo($rand_num) ?>').css({'display': 'none'});

                    var slider_height_<?php echo($rand_num) ?> = '<?php echo(isset($_COOKIE['careerfy_topemps_slidr_lheight']) && $_COOKIE['careerfy_topemps_slidr_lheight'] != '' ? $_COOKIE['careerfy_topemps_slidr_lheight'] . 'px' : '300px') ?>';
                    jQuery('#careerfy-slidmaintop-<?php echo($rand_num) ?>').css({'height': slider_height_<?php echo($rand_num) ?>});
                </script>

            <?php }
            if ($employer_style == 'slider' || $employer_style == 'slider2' || $employer_style == 'slider3') {
                echo '</div>';
            }
        } else {
            echo '<p>' . esc_html__('No employer found.', 'careerfy-frame') . '</p>';
        }
        //
        if ($employer_load_more == 'yes' && $employer_style != 'slider' && $employer_style != 'style3' && $employer_style != 'style4' && $employer_style != 'slider5' && $employer_style != 'slider2' && $employer_style != 'slider3' && $totl_found_jobs > $employer_per_page) {
            $total_pages = ceil($totl_found_jobs / $employer_per_page);
            ?>
            <div class="careerfy-loadmore-ninebtn"><a href="javascript:void(0);"
                                                      class="lodmore-empslists-<?php echo($rand_num) ?>"
                                                      data-tpages="<?php echo($total_pages) ?>"
                                                      data-gtopage="2"><?php esc_html_e('Load More', 'careerfy-frame') ?></a>
            </div>
            <script>
                jQuery(document).on('click', '.lodmore-empslists-<?php echo($rand_num) ?>', function (e) {
                    e.preventDefault();

                    var _this = jQuery(this),
                        total_pages = _this.attr('data-tpages'),
                        page_num = _this.attr('data-gtopage'),
                        this_html = _this.html(),
                        appender_con = jQuery('#main-empslists-<?php echo($rand_num) ?>'),
                        ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';

                    if (!_this.hasClass('ajax-loadin')) {
                        _this.addClass('ajax-loadin');
                        _this.html(this_html + '<i class="fa fa-refresh fa-spin"></i>');

                        total_pages = parseInt(total_pages);
                        page_num = parseInt(page_num);

                        var request = jQuery.ajax({
                            url: ajax_url,
                            method: "POST",
                            data: {
                                page_num: page_num,
                                employer_cat: '<?php echo($employer_cat) ?>',
                                employer_order: '<?php echo($employer_order) ?>',
                                employer_orderby: '<?php echo($employer_orderby) ?>',
                                employer_per_page: '<?php echo($employer_per_page) ?>',
                                action: 'jobsearch_load_more_insimple_empslistin_con'
                            },
                            dataType: "json"
                        });

                        request.done(function (response) {
                            if ('undefined' !== typeof response.html) {
                                page_num += 1;
                                _this.attr('data-gtopage', page_num);
                                if (page_num > total_pages) {
                                    _this.parent('div').hide();
                                }
                                appender_con.append(response.html);
                            }
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });

                        request.fail(function (jqXHR, textStatus) {
                            _this.html(this_html);
                            _this.removeClass('ajax-loadin');
                        });
                    }
                    return false;

                });
            </script>
            <?php
        }
        $html = ob_get_clean();
        echo $html;
    }


    protected function content_template()
    {

    }
}