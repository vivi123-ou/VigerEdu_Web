<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerSortOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeBanner(string $title, ?int $order): Banner
    {
        return Banner::create(['title' => ['vi' => $title], 'image' => 'banners/x.jpg', 'sort_order' => $order]);
    }

    /** @return array<string, int> tiêu đề => thứ tự */
    private function orders(): array
    {
        return Banner::orderBy('sort_order')->get()
            ->mapWithKeys(fn (Banner $banner) => [$banner->getTranslation('title', 'vi') => $banner->sort_order])
            ->all();
    }

    private function seedFour(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $i => $title) {
            $this->makeBanner($title, $i + 1);
        }
    }

    public function test_creating_with_taken_order_inserts_and_shifts_others(): void
    {
        $this->seedFour();

        $this->makeBanner('Mới', 4);

        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3, 'Mới' => 4, 'D' => 5], $this->orders());
    }

    public function test_creating_without_order_goes_to_end(): void
    {
        $this->seedFour();

        $this->makeBanner('Mới', null);

        $this->assertSame(5, Banner::where('title->vi', 'Mới')->value('sort_order'));
    }

    public function test_creating_with_free_order_does_not_shift(): void
    {
        $this->seedFour();

        $this->makeBanner('Mới', 9);

        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'Mới' => 9], $this->orders());
    }

    public function test_moving_up_and_down_keeps_orders_unique(): void
    {
        $this->seedFour();

        Banner::where('title->vi', 'D')->first()->update(['sort_order' => 2]);
        $this->assertSame(['A' => 1, 'D' => 2, 'B' => 3, 'C' => 4], $this->orders());

        Banner::where('title->vi', 'A')->first()->update(['sort_order' => 3]);
        $this->assertSame(['D' => 1, 'B' => 2, 'A' => 3, 'C' => 4], $this->orders());
    }

    public function test_deleting_closes_the_gap(): void
    {
        $this->seedFour();

        Banner::where('title->vi', 'B')->first()->delete();

        $this->assertSame(['A' => 1, 'C' => 2, 'D' => 3], $this->orders());
    }
}
