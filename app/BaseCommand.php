<?php

namespace App;

use Symfony\Component\Console\Command\Command;

class BaseCommand extends Command
{
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
    }
}