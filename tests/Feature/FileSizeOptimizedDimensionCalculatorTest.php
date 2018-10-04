<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\CloudImageFacade;
use Makeable\CloudImages\Events\CloudImageDeleted;
use Makeable\CloudImages\Exceptions\FailedDeletionException;
use Makeable\CloudImages\FileSizeOptimizedDimensionCalculator;
use Makeable\CloudImages\Tests\Fakes\FakeGuzzleClient;
use Makeable\CloudImages\Tests\TestCase;

class FileSizeOptimizedDimensionCalculatorTest extends TestCase
{
    /** @test **/
    public function it_returns_collection_with_sizes()
    {
        $calculator = new FileSizeOptimizedDimensionCalculator(2000, 1000, $this->mb(2));

        $this->assertEquals($calculator->calculateDimensions(2000, 1000)[0], [2000, 1000]);
    }

    /** @test **/
    public function it_generates_sizes_until_last_image_is_very_small()
    {
        $calculator = new FileSizeOptimizedDimensionCalculator(2000, 1000, $this->mb(2));

        $this->assertTrue(
            $calculator->calculateDimensions(2000, 1000)->count() >
            $calculator->calculateDimensions(1000, 1000)->count()
        );

        $smallest = $calculator->calculateDimensions(1000, 1000)->last();

        $this->assertTrue($this->area(...$smallest) < $this->area(150, 100));
    }

    /**
     * @param $mb
     * @return int
     */
    protected function mb($mb)
    {
        return $mb * 1024 * 1024;
    }

    /**
     * @param $x
     * @param $y
     * @return float|int
     */
    protected function area($x, $y)
    {
        return $x * $y;
    }
}
