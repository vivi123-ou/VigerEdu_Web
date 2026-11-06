<?php

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khối nhúng cho web chính (/embed, /embed.js, /api/v1/public/intro) và vai trò marketing (EDITOR).
 */
class EmbedAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_embed_page_only_allows_configured_domains(): void
    {
        $this->get(route('embed'))->assertOk()
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://viger.edu.vn")
            ->assertSee('Làm bài trắc nghiệm')
            ->assertSee('utm_source=web-chinh', false);
    }

    public function test_embed_script_mounts_iframe(): void
    {
        $response = $this->get(route('embed.script'))->assertOk();

        $this->assertStringStartsWith('application/javascript', $response->headers->get('Content-Type'));
        $response->assertSee(json_encode(route('embed')), false)->assertSee('holland-embed-height');
    }

    public function test_public_intro_api_returns_block_and_cors_for_allowed_origin(): void
    {
        $this->getJson(route('api.public.intro'), ['Origin' => 'https://viger.edu.vn'])
            ->assertOk()
            ->assertJsonStructure(['title', 'subtitle', 'image_url', 'button_text', 'start_url', 'stats' => ['total_attempts']])
            ->assertJsonPath('title', 'Trắc nghiệm hướng nghiệp Holland')
            ->assertHeader('Access-Control-Allow-Origin', 'https://viger.edu.vn');

        $this->getJson(route('api.public.intro'), ['Origin' => 'https://la.example'])
            ->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_editor_sees_only_content_sections(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR, 'name' => 'Bạn Marketing']);
        $this->actingAs($editor);

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Trang giới thiệu')->assertSee('Thư viện ảnh')->assertSee('Lượt làm bài')
            ->assertDontSee('Cấu hình chấm điểm')->assertDontSee('Bộ câu hỏi');

        $this->get(route('admin.content.edit', 'intro'))->assertOk();
        $this->get(route('admin.content.edit', 'result-page'))->assertOk();
        $this->get(route('admin.intro-blocks.index'))->assertOk();
        $this->get(route('admin.media.index'))->assertOk();
        $this->get(route('admin.groups.edit'))->assertOk();
        $this->get(route('admin.attempts.index'))->assertOk();

        $this->get(route('admin.question-sets.index'))->assertForbidden();
        $this->get(route('admin.info-questions.index'))->assertForbidden();
        $this->get(route('admin.scoring.edit'))->assertForbidden();
        $this->get(route('admin.excel.index'))->assertForbidden();
        $this->get(route('admin.content.edit', 'appearance'))->assertForbidden();
        $this->get(route('admin.section', 'settings'))->assertForbidden();
    }

    public function test_editor_cannot_revert_question_or_scoring_changes(): void
    {
        $scoring = AdminActivity::record(AdminActivity::ACTION_UPDATE, AdminActivity::SUBJECT_SCORING, null, 'x', ['scoring_config' => null], ['scoring_config' => '{}']);
        $intro = AdminActivity::record(AdminActivity::ACTION_UPDATE, 'settings.intro', null, 'y', ['intro_title' => 'Cũ'], ['intro_title' => 'Mới']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_EDITOR]));

        $this->post(route('admin.history.revert', $scoring))->assertForbidden();
        $this->post(route('admin.history.revert', $intro))->assertRedirect();
        $this->assertSame('Cũ', Setting::where('key', 'intro_title')->value('value'));
    }
}
