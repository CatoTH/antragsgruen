<?php

namespace app\components\wordpress;

class WordpressCompatibility
{
    const SETTINGS_PAGE_HANDLE = 'motions_settings_handle';

    public static $SETTING_PAGE_ROUTES = [
        'admin/' => WordpressCompatibility::SETTINGS_PAGE_HANDLE,
    ];

    /** @var Application */
    public static $app;

    /**
     * @param Application $app
     */
    public static function setApp($app)
    {
        static::$app = $app;
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    public static function isRelevantUri($uri)
    {
        $uriComponents = parse_url($uri);

        return (mb_stripos($uriComponents['path'], ANTRAGSGRUEN_WP_PATH) === 0);
    }

    /**
     */
    public static function registerGlobalComponents()
    {
        add_action('widgets_init', function () {
            register_widget(Sidebar::class);
        });
    }

    /**
     * @param string $defaultRoute
     */
    public static function renderAdminPage($defaultRoute)
    {
        /** @var Request $request */
        $request = \Yii::$app->request;
        $request->setAdminDefaultRoute($defaultRoute);

        try {
            static::$app->run();

            $data = WordpressLayoutData::getInstance();
            foreach ($data->jsFiles as $name => $file) {
                wp_enqueue_script('motions-' . $name, $file, ['jquery-core'], ANTRAGSGRUEN_WP_VERSION,
                    true);
            }
            foreach ($data->cssFiles as $name => $file) {
                wp_enqueue_style('motions-layout', $file);
            }

            echo $data->content;

            if ($data->onLoadJs) {
                foreach ($data->onLoadJs as $onLoadJs) {
                    echo '<script>jQuery(function() {' . $onLoadJs . '});</script>';
                }
            }
        } catch (\Exception $e) {
            echo esc_html($e->getMessage());
        }
    }

    /**
     */
    public static function registerAdminComponents()
    {
        if ( ! is_admin()) {
            return;
        }

        add_action('admin_menu', function () {
            add_menu_page(
                'Motions Administration',
                'Motions',
                /* $capability */
                'manage_options',
                /* $menu_slug  */
                self::SETTINGS_PAGE_HANDLE,
                function () {
                    WordpressCompatibility::renderAdminPage('admin/index');
                },
                WP_PLUGIN_URL . '/motions/web/favicon-16x16.png'
            );
        });
    }

    /**
     */
    public static function runFrontendApp()
    {
        if (is_admin()) {
            return;
        }
        if ( ! WordpressCompatibility::isRelevantUri($_SERVER['REQUEST_URI'])) {
            return;
        }

        add_action('template_redirect', function () {
            global $wp_query;

            // Reset the post
            static::reset_post(array(
                'ID'          => 0,
                'is_404'      => true,
                'post_status' => 'publish',
            ));

            status_header(200);
            $wp_query->is_page     = true;
            $wp_query->is_singular = true;
            $wp_query->is_404      = false;
        }, 10);

        add_action('init', function () {
            try {

                static::$app->run();

                $data = WordpressLayoutData::getInstance();
                foreach ($data->jsFiles as $name => $file) {
                    wp_enqueue_script('motions-' . $name, $file, ['jquery-core'], ANTRAGSGRUEN_WP_VERSION, true);
                }
                foreach ($data->cssFiles as $name => $file) {
                    wp_enqueue_style('motions-layout', $file);
                }
            } catch (\Exception $e) {
                add_filter('the_content', function () use ($e) {
                    return $e->getMessage();
                }, 10);
            }
        });

        add_filter('the_content', function () {
            $wpdata  = WordpressLayoutData::getInstance();
            $content = $wpdata->content;
            if ($wpdata->onLoadJs) {
                foreach ($wpdata->onLoadJs as $onLoadJs) {
                    $content .= '<script>jQuery(function() {' . $onLoadJs . '});</script>';
                }
            }

            return $content;
        }, 10);
    }

    /**
     * Populate various WordPress globals with dummy data to prevent errors.
     * Inspired by BudyPress
     *
     * @param array $args Array of optional arguments. Arguments parallel the properties
     *                    of {@link WP_Post}; see that class for more details.
     */
    public static function reset_post($args = array())
    {
        global $wp_query, $post;

        // Switch defaults if post is set
        if (isset($wp_query->post)) {
            $dummy = wp_parse_args($args, array(
                'ID'                    => $wp_query->post->ID,
                'post_status'           => $wp_query->post->post_status,
                'post_author'           => $wp_query->post->post_author,
                'post_parent'           => $wp_query->post->post_parent,
                'post_type'             => $wp_query->post->post_type,
                'post_date'             => $wp_query->post->post_date,
                'post_date_gmt'         => $wp_query->post->post_date_gmt,
                'post_modified'         => $wp_query->post->post_modified,
                'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
                'post_content'          => $wp_query->post->post_content,
                'post_title'            => $wp_query->post->post_title,
                'post_excerpt'          => $wp_query->post->post_excerpt,
                'post_content_filtered' => $wp_query->post->post_content_filtered,
                'post_mime_type'        => $wp_query->post->post_mime_type,
                'post_password'         => $wp_query->post->post_password,
                'post_name'             => $wp_query->post->post_name,
                'guid'                  => $wp_query->post->guid,
                'menu_order'            => $wp_query->post->menu_order,
                'pinged'                => $wp_query->post->pinged,
                'to_ping'               => $wp_query->post->to_ping,
                'ping_status'           => $wp_query->post->ping_status,
                'comment_status'        => $wp_query->post->comment_status,
                'comment_count'         => $wp_query->post->comment_count,
                'filter'                => $wp_query->post->filter,

                'is_404'     => false,
                'is_page'    => false,
                'is_single'  => false,
                'is_archive' => false,
                'is_tax'     => false,
            ));
        } else {
            $dummy = wp_parse_args($args, array(
                'ID'                    => -9999,
                'post_status'           => 'public',
                'post_author'           => 0,
                'post_parent'           => 0,
                'post_type'             => 'page',
                'post_date'             => 0,
                'post_date_gmt'         => 0,
                'post_modified'         => 0,
                'post_modified_gmt'     => 0,
                'post_content'          => '',
                'post_title'            => '',
                'post_excerpt'          => '',
                'post_content_filtered' => '',
                'post_mime_type'        => '',
                'post_password'         => '',
                'post_name'             => '',
                'guid'                  => '',
                'menu_order'            => 0,
                'pinged'                => '',
                'to_ping'               => '',
                'ping_status'           => '',
                'comment_status'        => 'closed',
                'comment_count'         => 0,
                'filter'                => 'raw',

                'is_404'     => false,
                'is_page'    => false,
                'is_single'  => false,
                'is_archive' => false,
                'is_tax'     => false,
            ));
        }

        // Bail if dummy post is empty
        if (empty($dummy)) {
            return;
        }

        // Set the $post global
        $post = new \WP_Post((object)$dummy);

        // Copy the new post global into the main $wp_query
        $wp_query->post  = $post;
        $wp_query->posts = array($post);

        // Prevent comments form from appearing
        $wp_query->post_count = 1;
        $wp_query->is_404     = $dummy['is_404'];
        $wp_query->is_page    = $dummy['is_page'];
        $wp_query->is_single  = $dummy['is_single'];
        $wp_query->is_archive = $dummy['is_archive'];
        $wp_query->is_tax     = $dummy['is_tax'];

        // Clean up the dummy post
        unset($dummy);

        /**
         * Force the header back to 200 status if not a deliberate 404
         *
         * @see https://bbpress.trac.wordpress.org/ticket/1973
         */
        if ( ! $wp_query->is_404()) {
            status_header(200);
        }

        // If we are resetting a post, we are in theme compat
        //bp_set_theme_compat_active( true );
    }
}