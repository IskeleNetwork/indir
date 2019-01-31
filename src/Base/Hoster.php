<?php

namespace Indir\Base;

use Indir\Exception\RegexNotMatched;

abstract class Hoster
{
    public function __construct($url)
    {
        if (!preg_match(static::PATTERN, $url, $matches)) {
            throw new RegexNotMatched();
        }

        static::onMatch($matches);
    }

    public function generate(Account $account): Link
    {
        // todo(0xbkt): should we do something with this?
        $file = static::probe();

        return static::onGenerate($file, $account);
    }

    abstract protected function onMatch($matches);
    abstract protected function onGenerate($file, $account): Link;

    abstract public function probe(): File;
}
