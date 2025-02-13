<?php

namespace Makeable\CloudImages;

trait ValueCasting
{
    /**
     * @return mixed
     */
    abstract public function get();

    /**
     * @return string
     */
    public function jsonSerialize(): mixed
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
    public function __toString(): string
    {
        return $this->get();
    }
}
