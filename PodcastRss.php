<?php

require __DIR__ . '/vendor/autoload.php';

use App\Vistopia;
use Symfony\Component\Console\Application;

new PodcastRss();

class PodcastRss
{
    /**
     * app constructor.
     */
    public function __construct()
    {
        $application = new Application();

        $application->add(new Vistopia());

        $application->run();
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
    $niceMsg = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
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
