<?php

namespace Makeable\CloudImages\Tests\Fakes;

class FakeGuzzleClient
{
    /**
     * @var
     */
    public $status;

    /**
     * FakeGuzzleClient constructor.
     * @param $status
     */
    public function __construct($status = 200)
    {
        $this->status = $status;
    }

    public function request()
    {
        return new FakeGuzzleResponse($this->status);
    }
}