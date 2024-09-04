<?php

namespace ReadSpeakerHelper;

class App
{
    public static $multisiteLoad = false;
    public static $optionsFrom = null;

    private static $customerId = null;
    private static $readWrapperId = 'readspeaker-read';

    public function __construct()
    {
        //Load json
        add_filter('acf/settings/load_json', function ($paths) {
            $paths[] = READSPEAKERHELPER_PATH . '/acf-exports';
            return $paths;
        });

        //Error notices
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        //Load app
        add_action('init', function () {
            self::$multisiteLoad = apply_filters('ReadSpeakerHelper\multisite_load', false);

            if (is_multisite() && self::$multisiteLoad) {
                self::$optionsFrom = SITE_ID_CURRENT_SITE;
            }

            $options = new \ReadSpeakerHelper\Options();

            if (is_array(self::getOption('options_readspeaker-helper-enable-posttypes'))) {
                $this->init();
            }
        });
    }

    /**
     * Initialize the readspeaker
     * @return void
     */
    public function init()
    {
        self::$customerId = self::getOption('options_readspeaker-helper-customer-id');

        if (self::getOption('options_readspeaker-helper-read-wrapper-id')) {
            self::$readWrapperId = self::getOption('options_readspeaker-helper-read-wrapper-id');
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));

        switch (self::getOption('options_readspeaker-helper-placement')) {
            case 'the_content':
                add_filter('the_content', function ($content) {
                    global $wp_query;
                    $posttypesEnabled = self::getOption('options_readspeaker-helper-enable-posttypes');
                    if (
                        !is_array($posttypesEnabled) ||
                        !in_array(get_post_type(), $posttypesEnabled)
                      ) {
                        return;
                      }

                    do_action('ReadSpeakerHelper/before_the_readspeaker');
                    return $this->getReadSpeakerTag() . '<div id="' . self::$readWrapperId . '">' . $content . '</div>';
                });
                break;
        }
    }

    /**
     * Gets the full readspeaker tag with wrapper
     * @return string
     */
    public function getReadSpeakerTag()
    {
        // Readspeaker tag markup
        $readspeakerTag = '<div class="readspeaker-wrapper">';
        $readspeakerTag .= self::getPlayButton();
        $readspeakerTag .= '</div>';

        return apply_filters('ReadSpeakerHelper/readspeaker_tag', $readspeakerTag);
    }

    /**
     * Get the play button
     * @return string
     */
    public static function getPlayButton()
    {

        $playButton = 
        '<div id="readspeaker_button1" class="rs_skip rsbtn rs_preserve">
            <a rel="nofollow" class="rsbtn_play" title="Lyssna p&aring; sidans text med ReadSpeaker webReader" href="https://app-eu.readspeaker.com/cgi-bin/rsent?customerid=' . self::$customerId . '&amp;lang=' .get_locale(). '&amp;readid=' . self::$readWrapperId . '&amp;url=' . self::currentUrl() . '">
                <span class="rsbtn_left rsimg rspart"><span class="rsbtn_text"><span>Lyssna</span></span></span>
                <span class="rsbtn_right rsimg rsplay rspart"></span>
            </a>
         </div>';

        return apply_filters('ReadSpeakerPlayer/play_button', $playButton);
    }

    /**
     * Enqueue required style
     * @return void
     */
    public function enqueueStyles()
    {
    }

    /**
     * Enqueue required scripts
     * @return void
     */
    public function enqueueScripts()
    {
        $posttypesEnabled = self::getOption('options_readspeaker-helper-enable-posttypes');
        if (!is_array($posttypesEnabled) || !in_array(get_post_type(), $posttypesEnabled)) {
            return;
        }

        /**
         * Enqueue readspeaker script
         */
        wp_register_script(
            'readspeaker',
            '//cdn-eu.readspeaker.com/script/' . self::$customerId . '/webReader/webReader.js?pids=wr',
            array(),
            '1.0.0',
            self::getOption('options_readspeaker-helper-script-footer')
        );
        // Add 'type' and 'id' attributes to the script tag
        wp_script_add_data('readspeaker', 'type', 'text/javascript');
        wp_script_add_data('readspeaker', 'id', 'rs_req_Init');

        wp_enqueue_script('readspeaker');
    }

    public static function currentUrl()
    {
        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }

    public static function getOption($optionName, $default = null)
    {
        $value = false;

        if (self::$optionsFrom) {
            $value = get_blog_option(self::$optionsFrom, $optionName, $default);
        } else {
            $value = get_option($optionName, $default);
        }

        if (is_serialized($value)) {
            return unserialize($value);
        }

        return $value;
    }
}
