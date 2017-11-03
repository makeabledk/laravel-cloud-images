<?php

namespace Makeable\CloudImages\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\CloudImages\HasImages;

class Product extends Model
{
    use HasImages;
}