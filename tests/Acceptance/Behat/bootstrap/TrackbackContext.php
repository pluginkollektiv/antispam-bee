<?php

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementNotFoundException;
use PaulGibbs\WordpressBehatExtension\Context\RawWordpressContext;

/**
 * Class PluginContext
 *
 * @package AntispamBee\Tests\Behat
 */

class TrackbackContext extends RawWordpressContext implements Context
{


    /**
     * Initialise context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the context constructor through behat.yml.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @Given I send a trackback with the title :title and the excerpt :excerpt and the url :url and the blog_name :blogName to the post :postId

     */
    public function iSendATrackback($title, $excerpt, $url, $blogName, $postId)
    {
        $trackBackUrl = $this->getMinkParameter('base_url') . '/wp-trackback.php?p=' . $postId;
        $params = [
            'title' => $title,
            'excerpt' => $excerpt,
            'url' => $url,
            'blog_name' => $blogName,
        ];
        $postdata = http_build_query($params);

        $opts = ['http' =>
            [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata,
            ],
        ];

        $context  = stream_context_create($opts);

        $result = file_get_contents($trackBackUrl, false, $context);
        if( ! preg_match( '^<error>0</error>^', $result ) ) {
            throw new \Exception('Error in sending trackback.');
        }
    }

}