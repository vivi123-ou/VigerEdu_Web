<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmStudentListTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
    }

    /** Chỉ xét tên trong bảng */
    private function row(string $name): string
    {
        return 'class="link-strong">'.$name.'</a>';
    }

    public function test_only_leads_with_student_info_are_listed(): void
    {
        Lead::factory()->create(['student_name' => 'Học Sinh A', 'german_level' => 'B1', 'gpa' => 8.5, 'desired_major' => 'Cơ khí', 'holland_i' => 90, 'holland_r' => 80, 'holland_s' => 70]);
        Lead::factory()->create(['full_name' => 'Chỉ Phụ Huynh', 'student_name' => null]);

        $res = $this->actingAs($this->user)->get(self::CRM.'/students')->assertOk()
            ->assertSee($this->row('Học Sinh A'), false)
            ->assertSee('Đức B1')
            ->assertSee('GPA 8.5')
            ->assertSee('Cơ khí');
        $this->assertFalse($res->viewData('students')->contains('full_name', 'Chỉ Phụ Huynh'));

        $this->assertSame(1, $res->viewData('counts')['all']);
    }

    public function test_tabs_by_profile_status(): void
    {
        Lead::factory()->create(['student_name' => 'Có Visa', 'profile_status' => 'Đã có visa']);
        Lead::factory()->create(['student_name' => 'Đang Làm', 'profile_status' => 'Đang chuẩn bị']);
        Lead::factory()->create(['student_name' => 'Chưa Rõ', 'profile_status' => null]);

        $res = $this->actingAs($this->user)->get(self::CRM.'/students');
        $counts = $res->viewData('counts');
        $this->assertSame([3, 1, 1, 1], [$counts['all'], $counts['none'], $counts['Đã có visa'], $counts['Đang chuẩn bị']]);

        $this->get(self::CRM.'/students?tab='.urlencode('Đã có visa'))
            ->assertSee($this->row('Có Visa'), false)
            ->assertDontSee($this->row('Đang Làm'), false);

        $this->get(self::CRM.'/students?tab=none')
            ->assertSee($this->row('Chưa Rõ'), false)
            ->assertDontSee($this->row('Có Visa'), false);
    }

    public function test_filters(): void
    {
        Lead::factory()->create(['student_name' => 'Đức Giỏi', 'german_level' => 'C1', 'program_interest' => 'Đại học', 'holland_a' => 60]);
        Lead::factory()->create(['student_name' => 'Mới Học', 'german_level' => 'A1', 'program_interest' => 'Du học nghề (Ausbildung)', 'student_school' => 'THPT Lê Quý Đôn']);

        $this->actingAs($this->user);

        $this->get(self::CRM.'/students?german=C1')->assertSee($this->row('Đức Giỏi'), false)->assertDontSee($this->row('Mới Học'), false);
        $this->get(self::CRM.'/students?program='.urlencode('Du học nghề (Ausbildung)'))->assertSee($this->row('Mới Học'), false)->assertDontSee($this->row('Đức Giỏi'), false);
        $this->get(self::CRM.'/students?holland=yes')->assertSee($this->row('Đức Giỏi'), false)->assertDontSee($this->row('Mới Học'), false);
        $this->get(self::CRM.'/students?holland=no')->assertSee($this->row('Mới Học'), false)->assertDontSee($this->row('Đức Giỏi'), false);
        $this->get(self::CRM.'/students?q='.urlencode('Lê Quý Đôn'))->assertSee($this->row('Mới Học'), false)->assertDontSee($this->row('Đức Giỏi'), false);
    }

    public function test_invalid_tab_falls_back_to_all(): void
    {
        Lead::factory()->create(['student_name' => 'Ai Đó']);

        $this->actingAs($this->user)->get(self::CRM.'/students?tab=hack')
            ->assertOk()
            ->assertSee($this->row('Ai Đó'), false);
    }
}
