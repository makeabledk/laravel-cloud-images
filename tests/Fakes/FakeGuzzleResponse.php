<?php

namespace Makeable\CloudImages\Tests\Fakes;

class FakeGuzzleResponse
{
    /**
     * @var
     */
    public $status;

    /**
     * FakeGuzzleClient constructor.
     * @param $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return json_encode([
            'url' => 'http://localhost/somehash',
        ]);
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->status;
    }
}
