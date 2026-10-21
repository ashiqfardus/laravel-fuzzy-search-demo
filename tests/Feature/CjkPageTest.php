<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The CJK page finds what README section 11 says each example finds. */
class CjkPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_each_readme_example_finds_its_product(): void
    {
        $expected = [
            '東京'     => ['東京タワー 模型', '京都 抹茶 セット'],
            '烤鸭'     => ['北京烤鸭 调味料'],
            'ヘッドホン' => ['ソニー ヘッドホン'],
            '김치'     => ['서울 김치 세트'],
            'tokyo'    => ['東京タワー 模型'],
        ];

        foreach ($expected as $query => $names) {
            $page = $this->get('/search/i18n?q=' . urlencode($query))->assertOk();
            foreach ($names as $name) {
                $page->assertSeeText($name);
            }
        }
    }

    public function test_the_page_opens_on_its_default_example(): void
    {
        $this->get('/search/i18n')->assertOk()->assertSeeText('東京タワー 模型');
    }
}
