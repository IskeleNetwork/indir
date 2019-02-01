<?php

namespace Indir\Base;

use GuzzleHttp\Client as HttpClient;
use Indir\Exception\RegexNotMatched;

abstract class Hoster
{
    protected $client;

    public function __construct($url, HttpClient $httpClient = null)
    {
        // todo(0xbkt): should continue using strings for $url or use a uri library?
        // todo(0xbkt): should we clean the $url or is it caller's responsibility?

        // decorating the $url. unless the method is declared by
        // the child, original $url will be returned.
        $url = static::onURL($url);

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

    protected function onURL($url)
    {
        return $url;
    }

    abstract protected function onMatch($matches);
    abstract protected function onGenerate($file, $account): Link;

    abstract public function probe(): File;
}
