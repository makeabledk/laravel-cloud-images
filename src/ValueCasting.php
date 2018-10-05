<?php

namespace Makeable\CloudImages;

trait ValueCasting
{
    /**
     * @return mixed
     */
    abstract function get();

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function toArray()
    {
        return $this->get();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}