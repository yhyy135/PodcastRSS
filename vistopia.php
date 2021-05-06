<?php
require 'vendor/autoload.php';

// Define separator by server type.
define("IS_WIN", DIRECTORY_SEPARATOR == '\\');
if (IS_WIN) {
    define("BREAK_STRING", "\r\n");
} else {
    define("BREAK_STRING", "\n");
}

new Vistopia();

class Vistopia
{
    public $channel_url;
    public $channel_id;
    public $content_id;
    public $title;
    public $subtitle;
    public $author;
    public $channel_image;
    public $articleList;
    public $articleCount;
    public $fileName;

    // The url to the directory of site.
    public $domain = "https://exaple.com/";

    public $token = '';
    public $show_note_flag = false;
    public $timezone = 'Asia/Shanghai';

    /**
     * Initializer
     */
    public function __construct()
    {
        //Get options.
        $opts = getopt('i:t::', ['shownote::']);
        if (!isset($opts['i']) || empty($opts['i'])) {
            commonLog('Please input channel url!', true);
        }
        $this->channel_url = $opts['i'];

        if (str_contains($this->channel_url, 'https://shop.vistopia.com.cn/detail?id=')) {
            $channel_id = str_replace('https://shop.vistopia.com.cn/detail?id=', '', $this->channel_url);
            $this->channel_id = $channel_id;
        }
        $this->token = isset($opts['t']) ? $opts['t'] : '';
        $this->show_note_flag = isset($opts['shownote']) ? true : false;
        date_default_timezone_set($this->timezone);

        $this->handle();
    }

    /**
     * 执行函数
     */
    public function handle()
    {
        $this->getShowInfo();
        $this->getCategory();
        $this->generateRss();

        commonLog('Youtube rss generation is done!!!');
        commonLog('The RSS Link is ' . $this->domain . $this->fileName, true);
    }

    /**
     * Get detail info of the show.
     * @return bool
     */
    protected function getShowInfo() {
        $url = "https://api.vistopia.com.cn/api/v1/content/content-show/" . $this->channel_id;
        $url = !empty($this->token) ? $url . "?api_token=" . $this->token : $url;
        $response = getRequest($url);

        $response = json_decode($response, true);
        if (empty($response['data'])) {
            commonLog('Get show info failed. Please try again.', true);
        }

        $data = $response['data'];
        $this->content_id    = $data['content_id'];
        $this->title         = $data['title'];
        $this->subtitle      = $data['subtitle'];
        $this->author        = $data['author'];
        $this->channel_image = $data['background_img'];

        return true;
    }

    /**
     * Get article list of the show.
     * @return bool
     */
    public function getCategory()
    {
        if (!empty($this->token)) {
            $url = 'https://api.vistopia.com.cn/api/v1/content/article_list?api_token=' . $this->token . '&content_id=' . $this->content_id . '&api_token=' . $this->token . '&count=1001';
        } else {
            $url = 'https://api.vistopia.com.cn/api/v1/content/article_list?&count=1001&content_id=' . $this->content_id;
        }
        $response = getRequest($url);

        $response = json_decode($response, true);
        if (empty($response['data'])) {
            commonLog('Get category failed. Please try again.', true);
        }
        $this->articleList = $response['data']['article_list'];
        $this->articleCount = $response['data']['article_count'];

        return true;
    }

    /**
     * Generate RSS file.
     * @return bool
     */
    protected function generateRss() {
        if (empty($this->articleList)) {
            commonLog('Article list is empty.', true);
        }

        $feedIo = \FeedIo\Factory::create()->getFeedIo();
        // build the feed
        $feed = new FeedIo\Feed;

        // add namespaces
        $feed->addNS('dc', 'http://purl.org/dc/elements/1.1/');
        $feed->addNS('sy', 'http://purl.org/rss/1.0/modules/syndication/');
        $feed->addNS('admin', 'http://webns.net/mvcb/');
        $feed->addNS('atom', 'http://www.w3.org/2005/Atom/');
        $feed->addNS('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $feed->addNS('content', 'http://purl.org/rss/1.0/modules/content/');
        $feed->addNS('googleplay', 'http://www.google.com/schemas/play-podcasts/1.0');
        $feed->addNS('itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $feed->addNS('fireside', 'http://fireside.fm/modules/rss/fireside');

        $channel = $feed->newElement();
        $channel->setName('copyright');
        $channel->setValue('Copyright 看理想 @看理想vistopia');
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('language');
        $channel->setValue('zh-cn');
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('link');
        $channel->setValue($this->channel_url);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('title');
        $channel->setValue($this->title);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:author');
        $channel->setValue($this->author);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:subtitle');
        $channel->setValue($this->subtitle);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:summary');
        $channel->setValue($this->title . $this->subtitle);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('description');
        $channel->setValue($this->title . $this->subtitle);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:image');
        $channel->setAttribute('href', $this->channel_image);
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:category');
        $channel->setAttribute('text', 'Society &amp; Culture');
        $feed->addElement($channel);

        $channel = $feed->newElement();
        $channel->setName('itunes:explicit');
        $channel->setValue('no');
        $feed->addElement($channel);

        $i = 1;
        $contentArr = [];
        foreach ($this->articleList as $datum) {
            $content = !empty($datum['content_url']) ? '阅读原文：'.$datum['content_url'] : '';

            $item = new \FeedIo\Feed\Item;
            $item->setTitle($datum['title']);
            $item->setSummary($content);
            $item->setContent($content);
            $item->setLink($datum['share_url']);

            $element = $item->newElement();
            $element->setName('itunes:subtitle');
            $element->setValue($this->subtitle);
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('itunes:image');
            $element->setAttribute('href', $this->channel_image);
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('enclosure');
            $element->setAttribute('url', $datum['media_key_full_url']);
            $element->setAttribute('type', 'audio/mp3');
            $element->setAttribute('length', $datum['media_size']*1024);
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('guid');
            $element->setAttribute('isPermaLink', 'false');
            $element->setValue($datum['media_key_full_url']);
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('itunes:explicit');
            $element->setValue('no');
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('itunes:episodeType');
            $element->setValue('full');
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('itunes:duration');
            $element->setValue($datum['duration_str']);
            $item->addElement($element);


            $description = $content;
            if ($this->show_note_flag) {
                commonLog("Getting content of show[$i/$this->articleCount]: " . $datum['title']);
                sleep(3);

                $content_url = 'https://api.vistopia.com.cn/api/v1/reader/section-detail?api_token=&article_id=' . $datum['article_id'];
                $response = getRequest($content_url);
                $response = json_decode($response, true);
                if (empty($response['data'])) {
                    commonLog('Get category failed. Please try again.', true);
                }
                $descriptionHtml = $response['data']['part'][0]['content'] ?? '';
                $description = htmlspecialchars(strip_tags($descriptionHtml));

                $uuid = 'content_' . $datum['article_id'];
                $contentArr[$uuid] = '<![CDATA[' . $descriptionHtml . ']]>';

                $element = $item->newElement();
                $element->setName('content:encoded');
                $element->setValue($uuid);
                $item->addElement($element);

                $element = $item->newElement();
                $element->setName('itunes:summary');
                $element->setValue($uuid);
                $item->addElement($element);

                $updateDate = $response['data']['part'][0]['update_date'] ?? '';
                $updateDate = str_replace('.', '-', $updateDate);
                $pubTime = !empty($updateDate) ? strtotime($updateDate) : time();
                $dateTime = new \DateTime;
                $item->setLastModified($dateTime->setTimestamp($pubTime));

                unset($dom, $xpath, $elems);
            }

            $element = $item->newElement();
            $element->setName('description');
            $element->setValue($description);
            $item->addElement($element);

            $element = $item->newElement();
            $element->setName('itunes:order');
            $element->setValue($i);
            $item->addElement($element);
            $i++;

            $feed->add($item);
        }

        $atomString = $feedIo->toRss($feed);

        $atomString = !empty($contentArr) ? str_replace(array_keys($contentArr), $contentArr, $atomString) : $atomString;

        $this->fileName = "vistopia-$this->content_id.xml";

        file_put_contents('./'.$this->fileName, $atomString);

        return true;
    }
}

/**
 * Print method for debug.
 */
function p()
{
    $args=func_get_args();  //获取多个参数
    if (count($args)<1) {
        echo("<font color='red'>必须为p()函数提供参数!");
        return;
    }
    echo '<div style="width:100%;text-align:left"><pre>';
    //多个参数循环输出
    foreach ($args as $arg) {
        if (is_array($arg)) {
            print_r($arg);
            echo '<br>';
        } elseif (is_string($arg)) {
            echo $arg.'<br>';
        } else {
            var_dump($arg);
            echo '<br>';
        }
    }
    echo '</pre></div>';
    die;
}

/**
 * Output the log info.
 * @param string $message
 * @param false $flag
 */
function commonLog($message = '', $flag = false)
{
    $niceMsg = "[" . date("Y-m-d H:i:s") . "] " . $message . BREAK_STRING;
    echo $niceMsg;
    if ($flag) {
        exit;
    }
}

/**
 * Get curl request result.
 * @param string $url
 * @param array $header
 * @return bool|string
 */
function getRequest($url = '', $header = []) {
    if (empty($url)) {
        return false;
    }

    $response = curl_request($url, $header);
    $cnt = 0;
    while ($cnt < 3 && $response === false) {
        $response = curl_request($url, $header);
        sleep(1);
        $cnt++;
    }

    return $response;
}

/**
 * CURL request method.
 * @param $request_url
 * @param array $header
 * @return bool|string
 */
function curl_request($request_url, $header = []) {
    $ch     = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request_url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36");

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
