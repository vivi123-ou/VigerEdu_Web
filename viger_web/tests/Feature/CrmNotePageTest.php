<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmNotePageTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $me;

    private User $colleague;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'manager', 'full_name' => 'Tôi Đây']);
        $this->colleague = User::factory()->create(['active' => true, 'role' => 'USER', 'crm_role' => 'manager']);
        $this->lead = Lead::factory()->create(['full_name' => 'Phụ Huynh X']);
    }

    public function test_lists_only_manual_notes_not_system_logs(): void
    {
        $this->lead->log('Gọi tư vấn Ausbildung', 'call', $this->me->id);

        $res = $this->actingAs($this->me)->get(self::CRM.'/notes')->assertOk()
            ->assertSee('Gọi tư vấn Ausbildung')
            ->assertDontSee('<p class="note-content">CRM tiếp nhận', false);   // dòng hệ thống khi tạo lead

        $this->assertSame(1, $res->viewData('counts')['all']);
    }

    public function test_search_in_content_highlights_match(): void
    {
        $this->lead->log('Phụ huynh hỏi về visa du học', 'note', $this->me->id);
        $this->lead->log('Hẹn gọi lại tuần sau', 'note', $this->me->id);

        $this->actingAs($this->me)->get(self::CRM.'/notes?q=visa')
            ->assertSee('<mark>visa</mark>', false)
            ->assertDontSee('<p class="note-content">Hẹn gọi lại tuần sau', false);
    }

    public function test_filter_type_and_author(): void
    {
        $this->lead->log('Nội dung tôi viết', 'note', $this->me->id);
        $this->lead->log('Cuộc gọi đồng nghiệp', 'call', $this->colleague->id);

        $this->actingAs($this->me);
        $this->get(self::CRM.'/notes?type=call')->assertSee('Cuộc gọi đồng nghiệp')->assertDontSee('<p class="note-content">Nội dung tôi viết', false);
        $this->get(self::CRM.'/notes?author='.$this->me->id)->assertSee('Nội dung tôi viết')->assertDontSee('<p class="note-content">Cuộc gọi đồng nghiệp', false);
    }

    public function test_quick_add_note(): void
    {
        $this->actingAs($this->me)->post(self::CRM.'/notes', [
            'lead_id' => $this->lead->id, 'type' => 'meeting', 'content' => 'Gặp phụ huynh tại văn phòng',
        ])->assertRedirect(self::CRM.'/notes')->assertSessionHas('success');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->lead->id, 'type' => 'meeting', 'user_id' => $this->me->id, 'content' => 'Gặp phụ huynh tại văn phòng',
        ]);

        $this->post(self::CRM.'/notes', ['lead_id' => $this->lead->id, 'type' => 'system', 'content' => 'x'])->assertSessionHasErrors('type');
        $this->post(self::CRM.'/notes', ['type' => 'note', 'content' => 'x'])->assertSessionHasErrors('lead_id');
    }

    public function test_author_can_edit_and_delete_own_note(): void
    {
        $note = $this->lead->log('Nội dung cũ', 'note', $this->me->id);

        $this->actingAs($this->me)->from(self::CRM.'/notes')
            ->put(self::CRM."/notes/{$note->id}", ['type' => 'call', 'content' => 'Nội dung mới'])
            ->assertRedirect(self::CRM.'/notes');

        $this->assertSame('Nội dung mới', $note->fresh()->content);
        $this->assertSame('call', $note->fresh()->type);

        $this->delete(self::CRM."/notes/{$note->id}")->assertRedirect();
        $this->assertModelMissing($note);
    }

    public function test_cannot_edit_others_note_or_system_log_but_admin_can(): void
    {
        $theirs = $this->lead->log('Của đồng nghiệp', 'note', $this->colleague->id);
        $system = LeadActivity::where('type', 'system')->firstOrFail();

        $this->actingAs($this->me);
        $this->put(self::CRM."/notes/{$theirs->id}", ['type' => 'note', 'content' => 'hack'])->assertForbidden();
        $this->delete(self::CRM."/notes/{$theirs->id}")->assertForbidden();
        $this->delete(self::CRM."/notes/{$system->id}")->assertForbidden();
        $this->get(self::CRM.'/notes')->assertDontSee(route('crm.notes.destroy', $theirs), false);

        $admin = User::factory()->create(['active' => true, 'role' => 'ADMIN']);
        $this->actingAs($admin)->delete(self::CRM."/notes/{$theirs->id}")->assertRedirect();
        $this->assertModelMissing($theirs);

        // Dòng hệ thống: kể cả admin cũng không xoá được
        $this->delete(self::CRM."/notes/{$system->id}")->assertForbidden();
    }
}
