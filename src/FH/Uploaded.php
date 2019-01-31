<?php

namespace Indir\FH;

use Indir\Base\File;
use Indir\Base\Hoster;
use Indir\Base\Link;
use Indir\Exception\Account\Dead as AccountDead;
use Indir\Exception\Account\NotPremium as AccountNotPremium;
use Indir\Exception\Link\Dead as LinkDead;

class Uploaded extends Hoster
{
    const PATTERN = '@^https?://(?:www\.)?uploaded\.(?:to|net)/file/(?<fileId>[\w]+)?@si';
    const API_KEY = 'lhF2IeeprweDfu9ccWlxXVVypA5nA3EL';

    protected function onMatch($matches)
    {
        $this->fileId = $matches['fileId'];
    }

    protected function onGenerate($file, $account): Link
    {
        $response = $this->client->post('http://api.uploaded.net/api/user/login', [
            'form_params' => [
                'name' => $account->user,
                'pass' => $account->pass,
                'app' => 'JDownloader',
            ],
        ]);

        $json = json_decode((string) $response->getBody());

        if (@$json->err != null) {
            throw new AccountDead();
        }

        $accessToken = $json->access_token;

        $response = $this->client->get('http://api.uploaded.net/api/user/jdownloader', [
            'query' => [
                'access_token' => $accessToken,
            ],
        ]);

        $json = json_decode((string) $response->getBody());

        if ($json->account_type != 'premium' || !$json->download_available) {
            throw new AccountNotPremium();
        }

        $response = $this->client->post('http://api.uploaded.net/api/download/jdownloader', [
            'form_params' => [
                'auth' => $this->fileId,
                'access_token' => $accessToken,
            ],
        ]);

        $json = json_decode((string) $response->getBody());

        $link = new Link();
        $link->url = $json->link;
        // todo(0xbkt): or should we depend on argument $file?
        {
            $link->name = $json->name;
            $link->size = $json->size;
        }

        return $link;
    }

    public function probe(): File
    {
        $response = $this->client->post('http://api.uploaded.net/api/filemultiple', [
            'form_params' => [
                'apikey' => self::API_KEY,
                'id_0' => $this->fileId,
            ],
        ]);

        list($status, , $size, , $name) = str_getcsv((string) $response->getBody());

        if ($status != 'online') {
            throw new LinkDead();
        }

        $file = new File();
        $file->name = $name;
        $file->size = $size;

        return $file;
    }
}
