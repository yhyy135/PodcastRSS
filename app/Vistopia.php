<?php

namespace App;

use DateTime;
use FeedIo\Factory;
use FeedIo\Feed;
use FeedIo\Feed\Item;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Vistopia extends BaseCommand
{
    public $token;

    public $channel_url;
    public $channel_code;
    public $content_id;
    public $title;
    public $subtitle;
    public $author;
    public $channel_image;
    public $articleList;
    public $articleCount;
    public $fileName;

    protected function configure(): void
    {
        $this->setName('generate:vistopia')
             ->setDescription('This is a RSS generator for Vistopia.')
             ->setHelp('This command generate a RSS file in rss folder by input params such as api token of Vistopia. The show notes detail is optional.');

        $this->addOption('url', 'i',  InputOption::VALUE_REQUIRED, 'The url of the show detail page.');
        $this->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'The token of vistopia account.', '');
        $this->addOption('shownote', 's', InputOption::VALUE_OPTIONAL, 'The identifier of generating RSS include show notes information.', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Get options.
        $this->channel_url = $input->getOption('url');

        if (str_contains($this->channel_url, 'https://shop.vistopia.com.cn/detail?id=')) {
            $channel_code         = str_replace('https://shop.vistopia.com.cn/detail?id=', '', $this->channel_url);
            $this->channel_code = $channel_code;
        }

        $this->token = !empty($input->getOption('token')) ? $input->getOption('token') : $this->config['vistopia_token'];
        $this->show_note_flag = $input->getOption('shownote') === false ? false : true;

        date_default_timezone_set($this->timezone);

        $this->getShowInfo();
        $this->getCategory();
        $this->generateRss();

        commonLog('Youtube rss generation is done!!!');
        commonLog('The RSS Link is ' . $this->domain . $this->fileName, true);

        return Command::SUCCESS;
    }

    /**
     * Get detail info of the show.
     * @return bool
     */
    protected function getShowInfo(): bool
    {
        $url = "https://api.vistopia.com.cn/api/v1/content/content-show/" . $this->channel_code;
        $url = !empty($this->token) ? $url . "&api_token=" . $this->token : $url;
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
        $this->channel_url   = preg_replace('#(.+id=)\w+#m', '$1', $this->channel_url) . $this->content_id;


        return true;
    }

    /**
     * Get article list of the show.
     * @return bool
     */
    public function getCategory(): bool
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
    protected function generateRss(): bool
    {
        if (empty($this->articleList)) {
            commonLog('Article list is empty.', true);
        }

        $this->fileName = "vistopia-$this->content_id.xml";

        $feedIo = Factory::create()->getFeedIo();
        // build the feed
        $feed = new Feed;

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
        $channel->setName('atom:link');
        $channel->setAttribute('href', $this->domain . '/' . $this->fileName);
        $channel->setAttribute('type', 'application/rss+xml');
        $channel->setAttribute('rel', 'self');
        $feed->addElement($channel);

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
        $channel->setValue($this->domain . '/' . $this->fileName);
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
        $pubDateArr = [];
        foreach ($this->articleList as $datum) {
            $content = !empty($datum['content_url']) ? '阅读原文：'.$datum['content_url'] : '';

            $item = new Item;
            $item->setTitle($datum['title']);
            $item->setSummary($content);
            $item->setContent($content);

            $itemUrl = preg_replace('#(.+article_id=)\w+#m', '$1', $datum['share_url']) . $datum['article_id'];
            $item->setLink($itemUrl);

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

                $content_url = 'https://api.vistopia.com.cn/api/v1/reader/section-detail?api_token=' . $this->token . '&article_id=' . $datum['article_id'];
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
                $pubTime = !isset($pubDateArr[$updateDate]) ? $pubTime : $pubDateArr[$updateDate] + 1; // Avoid the same pubTime for item.
                $pubDateArr[$updateDate] = $pubTime;
                $dateTime = new DateTime;
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

        $feed->setLastModified(new DateTime);
        $atomString = $feedIo->toRss($feed);

        $atomString = !empty($contentArr) ? str_replace(array_keys($contentArr), $contentArr, $atomString) : $atomString;

        file_put_contents('./'.$this->fileName, $atomString);

        return true;
    }
}