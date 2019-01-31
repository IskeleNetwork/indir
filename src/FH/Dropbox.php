<?php

namespace Indir\FH;

use function GuzzleHttp\Psr7\parse_header;
use Indir\Base\File;
use Indir\Base\Hoster;
use Indir\Base\Link;
use Indir\Exception\Link\Dead as LinkDead;

class Dropbox extends Hoster
{
    const PATTERN = '@^https?://(?:www\.)?dropbox\.com/(sh|s)/([^\s]+)$@is';

    protected function onMatch($matches)
    {
        $this->filePath = "{$matches[1]}/dl/{$matches[2]}";
    }

    protected function onGenerate($file, $account): Link
    {
        $resp = $this->client->head("https://www.dropbox.com/{$this->filePath}", [
            'allow_redirects' => [
                'strict' => true,
                'track_redirects' => true,
            ],
            'http_errors' => false,
        ]);

        if ($resp->getStatusCode() != 200) {
            throw new LinkDead();
        }

        $redirectHistory = $resp->getHeader('X-Guzzle-Redirect-History');

        $attachment = parse_header(
            $resp->getHeader('Content-Disposition')
        );

        $link = new Link();
        $link->url = end($redirectHistory);
        $link->name = $attachment[0]['filename'];
        $link->size = $resp->getHeaderLine('Content-Length');

        return $link;
    }

    public function probe(): File
    {
        // simulating a normal generation.
        $link = self::onGenerate(null, null);

        $file = new File();
        $file->name = $link->name;
        $file->size = $link->size;

        return $file;
    }
}
