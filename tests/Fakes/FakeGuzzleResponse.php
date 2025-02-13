<?php

namespace Makeable\CloudImages\Tests\Fakes;

class FakeGuzzleResponse
{
    /**
     * @var FakeGuzzleClient
     */
    public $client;

    /**
     * @var array
     */
    public $request;

    /**
     * FakeGuzzleClient constructor.
     *
     * @param  $client
     * @param  $request
     */
    public function __construct($client, $request)
    {
        $this->client = $client;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return json_encode([
            'url' => 'https://localhost/somehash',
        ]);
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->client->status;
    }
}
