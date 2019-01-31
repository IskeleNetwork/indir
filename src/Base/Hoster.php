<?php

namespace Indir\Base;

use GuzzleHttp\Client as HttpClient;
use Indir\Exception\RegexNotMatched;

abstract class Hoster
{
    protected $client;

    public function __construct($url, HttpClient $httpClient = null)
    {
        // todo(0xbkt): should we clean the $url or is it caller's responsibility?

        if (!preg_match(static::PATTERN, $url, $matches)) {
            throw new RegexNotMatched();
        }

        $this->client = $httpClient ? clone $httpClient : new HttpClient();

        static::onMatch($matches);
    }

    public function generate(Account $account = null): Link
    {
        // todo(0xbkt): should we do something with this?
        $file = static::probe();

        return static::onGenerate($file, $account);
    }

    abstract protected function onMatch($matches);
    abstract protected function onGenerate($file, $account): Link;

    abstract public function probe(): File;
}
