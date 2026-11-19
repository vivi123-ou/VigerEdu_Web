<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Api\Filament\Pages\ApiClients;
use App\Modules\Api\Models\ApiClient;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Content\Filament\Pages\SeoSettings;
use App\Modules\Respondent\Services\ReportAccessService;
use App\Modules\Respondent\Services\ReportForm;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H7 – API cho chatbot (hợp đồng mục 3): token băm + hash_equals + phạm vi, kết quả không có dữ liệu cá nhân,
 * danh mục; trang cấp / thu hồi token; sao lưu; trang Hiển thị trên Google.
 */
class HollandApiTest extends TestCase
{
    use RefreshDatabase;

    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_results_need_a_valid_active_token_with_the_right_scope(): void
    {
        $session = $this->customer();
        $url = route('api.results.show', $session->uuid);

        $this->getJson($url)->assertUnauthorized();
        $this->getJson($url, ['X-Api-Token' => 'hl_sai'])->assertUnauthorized();

        [$client, $token] = ApiClient::issue('Chatbot', ['results:read']);
        $this->assertNotSame($token, DB::table('api_clients')->value('token_hash'), 'Token lưu dạng băm');
        $this->getJson($url, ['X-Api-Token' => $token])->assertOk();
        $this->assertNotNull($client->fresh()->last_used_at);

        [, $noScope] = ApiClient::issue('Khác', []);
        $this->getJson($url, ['X-Api-Token' => $noScope])->assertForbidden();

        $client->update(['is_active' => false]);
        $this->getJson($url, ['X-Api-Token' => $token])->assertUnauthorized();
    }

    public function test_customer_and_counsellor_views_never_contain_personal_data(): void
    {
        $session = $this->customer();
        [, $token] = ApiClient::issue('Chatbot', ['results:read']);
        $headers = ['X-Api-Token' => $token];

        $customer = $this->getJson(route('api.results.show', $session->uuid), $headers)->assertOk()
            ->assertJsonPath('view', 'khach')->assertJsonPath('code', 'IS')
            ->assertJsonPath('names', ['Nghiên cứu', 'Xã hội'])
            ->assertJsonPath('majors.0.name_vi', 'Tâm lý học');
        $counsellor = $this->getJson(route('api.results.show', [$session->uuid, 'view' => 'tuvan']), $headers)->assertOk()
            ->assertJsonPath('holland_result.code_full', 'ISA')
            ->assertJsonPath('holland_result.relation', 'cach_mot')
            ->assertJsonCount(36, 'answers');

        foreach ([$customer, $counsellor] as $response) {
            foreach (['Nguyễn Minh Anh', 'minhanh@gmail.com', '0912345678', '2009-03-15'] as $private) {
                $this->assertStringNotContainsString($private, $response->getContent());
            }
        }

        $this->getJson(route('api.results.show', '11111111-1111-1111-1111-111111111111'), $headers)->assertNotFound();
        $unverified = app(AssessmentService::class)->start(null, null);
        $this->getJson(route('api.results.show', $unverified->uuid), $headers)->assertNotFound();
    }

    public function test_catalog_lists_types_pairs_third_letters_and_active_majors(): void
    {
        [, $token] = ApiClient::issue('Chatbot', ['results:read']);
        $headers = ['X-Api-Token' => $token];

        $this->getJson(route('api.catalog.show', 'types'), $headers)->assertOk()->assertJsonCount(6, 'items');
        $this->getJson(route('api.catalog.show', 'pairs'), $headers)->assertOk()->assertJsonCount(15, 'items')
            ->assertJsonFragment(['pair' => 'IS']);
        $this->getJson(route('api.catalog.show', 'third'), $headers)->assertOk()->assertJsonCount(6, 'items');
        $majors = $this->getJson(route('api.catalog.show', 'majors'), $headers)->assertOk();
        $this->assertStringNotContainsString('Cần tra', $majors->getContent(), 'Ghi chú nội bộ của ngành chưa kiểm tra không ra API');
        $this->getJson('/api/v1/catalog/users', $headers)->assertNotFound();
    }

    public function test_admin_issues_a_token_shown_once_and_can_revoke_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        Livewire::test(ApiClients::class)
            ->callTableAction('issue', data: ['name' => 'Chatbot', 'scopes' => ['results:read']])
            ->assertNotified();
        $client = ApiClient::query()->sole();
        $this->get(ApiClients::getUrl())->assertOk()->assertSee('Chatbot')->assertSee('…'.$client->token_last4);

        Livewire::test(ApiClients::class)->callTableAction('revoke', $client);
        $this->assertFalse($client->fresh()->is_active);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(ApiClients::getUrl())->assertNotFound();
    }

    public function test_backup_writes_a_compressed_file_and_removes_old_ones(): void
    {
        $db = tempnam(sys_get_temp_dir(), 'hl').'.sqlite';
        copy(__FILE__, $db);
        config(['database.connections.backup_test' => ['driver' => 'sqlite', 'database' => $db], 'database.default' => 'backup_test']);
        $dir = storage_path('app/backups');
        @mkdir($dir, 0700, true);
        // Ngoài 14 ngày: mỗi tuần chỉ giữ bản mới nhất, trong 8 tuần; cũ hơn 8 tuần thì xóa.
        $wednesday = now()->subWeeks(4)->startOfWeek()->addDays(2)->setTime(2, 0);
        $files = [
            'tuan-cu-ban-moi' => [$wednesday, true],
            'tuan-cu-ban-truoc' => [$wednesday->copy()->subDay(), false],
            'qua-8-tuan' => [now()->subWeeks(10), false],
        ];
        foreach ($files as $name => [$time]) {
            file_put_contents($dir."/holland-{$name}.sql.gz", 'cu');
            touch($dir."/holland-{$name}.sql.gz", $time->getTimestamp());
        }

        $this->artisan('holland:backup')->assertSuccessful();

        foreach ($files as $name => [, $kept]) {
            $kept ? $this->assertFileExists($dir."/holland-{$name}.sql.gz") : $this->assertFileDoesNotExist($dir."/holland-{$name}.sql.gz");
        }
        @unlink($dir.'/holland-tuan-cu-ban-moi.sql.gz');
        $latest = collect(glob($dir.'/holland-2*.sql.gz'))->sortDesc()->first();
        $this->assertSame(file_get_contents($db), gzdecode((string) file_get_contents($latest)));
        @unlink($latest);
        config(['database.default' => 'sqlite']);
    }

    public function test_seo_page_saves_title_and_share_image(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->get(SeoSettings::getUrl())->assertOk()->assertSee('Tiêu đề trên Google');

        Livewire::test(SeoSettings::class)->fillForm([
            'seo_home_title' => 'Trắc nghiệm Holland miễn phí – VIGER EDU',
            'seo_home_description' => 'Mô tả mới.',
            'seo_share_image' => 'img/logo.png',
        ])->call('save')->assertHasNoFormErrors();

        $this->get('/')->assertSee('<title>Trắc nghiệm Holland miễn phí – VIGER EDU</title>', false)
            ->assertSee('<meta property="og:image"', false);
    }

    private function customer(): TestSession
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, null);
        $next = array_map(fn () => 0, self::HS01);
        $answers = [];
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => self::HS01[$item->group][$next[$item->group]++], 'ms' => 4000];
        }
        $session = $service->finish($session, $answers);
        app(ReportForm::class)->save($session, ['ho_ten' => 'Nguyễn Minh Anh', 'ngay_sinh' => '2009-03-15', 'email' => 'minhanh@gmail.com', 'sdt_phu_huynh' => '0912345678', 'doi_tuong' => 'HS']);
        app(ReportAccessService::class)->complete($session, null);

        return $session->fresh();
    }
}
