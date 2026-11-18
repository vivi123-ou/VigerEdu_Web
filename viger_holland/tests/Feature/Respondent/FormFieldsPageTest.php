<?php

namespace Tests\Feature\Respondent;

use App\Models\User;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Respondent\Filament\Pages\FormFields;
use App\Modules\Respondent\Models\FormField;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H6 – "Form khách điền thông tin": sửa ô, thêm ô mới hiện ngay trên form khách, ô Email luôn bắt buộc, ô lõi không xóa được.
 */
class FormFieldsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    public function test_admin_edits_and_adds_fields_that_show_on_the_customer_form(): void
    {
        $this->get(FormFields::getUrl())->assertOk()->assertSee('Điện thoại / Zalo của phụ huynh')->assertSee('parent_phone');

        $school = FormField::query()->where('key', 'truong')->firstOrFail();
        Livewire::test(FormFields::class)
            ->callTableAction('edit', $school, ['label' => 'Trường đang học', 'type' => 'text', 'is_required' => false, 'is_visible' => true, 'to_crm' => true])
            ->assertHasNoTableActionErrors();

        Livewire::test(FormFields::class)
            ->callTableAction('create', data: ['label' => 'Học trường chuyên?', 'type' => 'radio', 'is_required' => false, 'is_visible' => true, 'to_crm' => true,
                'options' => [['value' => 'co', 'label' => 'Có'], ['value' => 'khong', 'label' => 'Không']]])
            ->assertHasNoTableActionErrors();
        $new = FormField::query()->where('label', 'Học trường chuyên?')->firstOrFail();
        $this->assertSame('hoc_truong_chuyen', $new->key);
        $this->assertFalse($new->is_system);
        $this->assertNull($new->crm_key);

        $session = app(AssessmentService::class)->start(null, null);
        $session->update(['status' => 'answered']);
        $this->withSession(['holland.test' => $session->uuid])->get(route('report.request'))
            ->assertSee('Trường đang học')->assertSee('Học trường chuyên?')->assertSee('value="khong"', false);
    }

    public function test_email_stays_required_and_core_fields_cannot_be_deleted(): void
    {
        $email = FormField::query()->where('key', 'email')->firstOrFail();
        Livewire::test(FormFields::class)
            ->callTableAction('edit', $email, ['label' => 'Email', 'type' => 'email', 'is_required' => false, 'is_visible' => false, 'to_crm' => true]);
        $this->assertTrue($email->fresh()->is_required);
        $this->assertTrue($email->fresh()->is_visible);

        Livewire::test(FormFields::class)->assertTableActionHidden('delete', $email);

        $extra = FormField::query()->create(['key' => 'ghi_chu', 'label' => 'Ghi chú', 'type' => 'text', 'is_visible' => true, 'sort_order' => 30]);
        Livewire::test(FormFields::class)->callTableAction('delete', $extra);
        $this->assertSoftDeleted($extra);
        Livewire::test(FormFields::class, ['tab' => 'thung-rac'])->callTableAction('restore', $extra);
        $this->assertNotSoftDeleted($extra);
    }

    public function test_staff_and_counselor_cannot_open_the_page(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_COUNSELOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(FormFields::getUrl())->assertNotFound();
        }
    }
}
