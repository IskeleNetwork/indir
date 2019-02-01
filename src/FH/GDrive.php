<?php

namespace Indir\FH;

use function GuzzleHttp\Psr7\parse_header;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Indir\Base\File;
use Indir\Base\Hoster;
use Indir\Base\Link;
use Indir\Exception\Link\Dead as LinkDead;

class GDrive extends Hoster
{
    const PATTERN = '@^https?://(?:drive|docs)\.google\.com/(?:file/d/([\w-]+)|uc\?id=([\w-]+)$)@is';

    protected function onURL($url)
    {
        $query = $url->getQuery();

        parse_str($query, $output);

        // leaving only `id` query param
        $newQuery = array_filter($output, function ($key) {
            return $key == 'id';
        }, ARRAY_FILTER_USE_KEY);

        return $url->withQuery(\http_build_query($newQuery));
    }

    protected function onMatch($matches)
    {
        @$this->filePath = $matches[1] . $matches[2];
    }

    protected function onGenerate($file, $account): Link
    {
        $jar = new CookieJar();
        $req = new HttpRequest('GET', 'https://drive.google.com/uc?export=download&id=' . $this->filePath);

        doContend:
        $resp = $this->client->send($req, [
            'allow_redirects' => [
                'track_redirects' => true,
            ],
            'stream' => true,
            'cookies' => $jar,
            'headers' => [
                'Range' => 'bytes=0-',
            ],
        ]);

        $link = new Link();

        $statusCode = $resp->getStatusCode();
        switch ($statusCode) {
            case 404:
                throw new LinkDead();
            case 206:
                $redirectHistory = $resp->getHeader('X-Guzzle-Redirect-History');

                $attachment = parse_header(
                    $resp->getHeader('Content-Disposition')
                );

                $link->url = end($redirectHistory);
                $link->name = $attachment[0]['filename'];
                $link->size = explode('/', $resp->getHeaderLine('Content-Range'))[1];

                break;
            case 200:
                $currentUri = $req->getUri();

                $warningCookie = array_filter($jar->toArray(), function ($cookie) {
                    return strpos($cookie['Name'], 'download_warning') === 0;
                })[0];

                $newUri = $currentUri->withQuery(
                    $currentUri->getQuery() . '&confirm=' . $warningCookie['Value']
                );

                $req = $req->withUri($newUri);

                goto doContend;
            default:
                throw new \RuntimeException(
                    sprintf('unexpected status code returned: %s', $statusCode)
                );
        }

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
