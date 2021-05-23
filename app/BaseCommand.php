<?php

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;

class BaseCommand extends Command
{
    public $config;

    // The url to the directory of site.
    public $domain;

    // The api-token of website.
    public $token;

    public $timezone;

    public $show_note_flag = false;

    /**
     * BaseCommand constructor.
     * @param string $domain
     */
    public function __construct()
    {
        parent::__construct();
        $configFile = dirname(__DIR__) . '/' . 'config.yaml';
        $config = Yaml::parse(file_get_contents($configFile));

        $this->config   = $config ?? [];
        $this->domain   = $config['domain'] ?? 'https://example.com';
        $this->timezone = $config['timezone'] ?? 'Asia/Shanghai';
    }
}