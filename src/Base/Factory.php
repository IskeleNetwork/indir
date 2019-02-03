<?php

namespace Indir\Base;

use Indir\Exception\Link\NotSupported as LinkNotSupported;
use Indir\Exception\RegexNotMatched;
use Indir\FH;

class Factory
{
    private static $map = [
        FH\Uploaded::class,
        FH\Dropbox::class,
        FH\GDrive::class,
    ];

    public static function produce($url): Hoster
    {
        foreach (self::$map as $hoster) {
            try {
                return new $hoster($url);
            } catch (RegexNotMatched $e) {
                // if a regexp match attempt fails, proceed to the next.
                continue;
            } catch (\Exception $e) {
                // falling back.
                throw $e;
            }
        }

        // that we couldn't return until this point
        // means $url is not supported.
        throw new LinkNotSupported();
    }
}
