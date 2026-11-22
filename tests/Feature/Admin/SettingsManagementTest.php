<?php

namespace Tests\Feature\Admin;

use App\Models\ResponseTemplate;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_view_and_update_general_settings(): void
    {
        $response = $this->get(route('admin.settings.general.edit'));
        $response->assertStatus(200);
        $response->assertSee('General Settings');

        $updateResponse = $this->post(route('admin.settings.general.update'), [
            'barangay_name' => 'Barangay Calarian West',
            'city' => 'Zamboanga City',
            'province' => 'Zamboanga del Sur',
            'region' => 'Region IX',
            'hall_address' => 'Pettit Barracks Hall Rd',
            'contact_number' => '0917-999-8877',
            'email' => 'contact@calarian-west.gov.ph',
            'cases_enabled' => 1,
            'agencies_registration_open' => 1,
            'default_format' => 'pdf',
            'paper_size' => 'a4',
        ]);

        $updateResponse->assertRedirect(route('admin.settings.general.edit'));
        $this->assertSame('Barangay Calarian West', setting('barangay.name'));
        $this->assertSame('pdf', setting('reports.default_format'));
        $this->assertSame('a4', setting('reports.paper_size'));
    }

    public function test_admin_can_view_and_update_numbering_schemes(): void
    {
        $response = $this->get(route('admin.numbering.edit'));
        $response->assertStatus(200);
        $response->assertSee('Document Numbering Schemes');

        $updateResponse = $this->post(route('admin.numbering.update'), [
            'referral_format' => 'REF-{YEAR}-{SEQ:5}',
            'case_format' => '{MM}-{SEQ:4}-{YEAR}',
            'blotter_format' => 'BLOT-{YEAR}-{SEQ:3}',
            'summons_format' => 'SUM-{YEAR}-{SEQ:4}',
        ]);

        $updateResponse->assertRedirect(route('admin.numbering.edit'));
        $this->assertSame('REF-{YEAR}-{SEQ:5}', setting('numbering.referral.format'));
        $this->assertSame('{MM}-{SEQ:4}-{YEAR}', setting('numbering.case.format'));
    }

    public function test_numbering_scheme_validation_rejects_missing_sequence_token(): void
    {
        $response = $this->post(route('admin.numbering.update'), [
            'referral_format' => 'REF-{YEAR}',
            'case_format' => '{MM}-{SEQ:4}-{YEAR}',
            'blotter_format' => 'BLOT-{YEAR}-{SEQ:3}',
            'summons_format' => 'SUM-{YEAR}-{SEQ:4}',
        ]);

        $response->assertSessionHasErrors(['referral_format']);
    }

    public function test_admin_can_manage_calendar_holidays(): void
    {
        $response = $this->get(route('admin.calendar.index'));
        $response->assertStatus(200);
        $response->assertSee('Official Calendar Holidays');

        $addResponse = $this->post(route('admin.calendar.store'), [
            'date' => '2026-12-30',
            'label' => 'Rizal Day',
        ]);

        $addResponse->assertRedirect(route('admin.calendar.index'));

        $holidays = (array) setting('calendar.holidays', []);
        $found = collect($holidays)->contains(fn ($h) => is_array($h) && ($h['date'] ?? '') === '2026-12-30');
        $this->assertTrue($found);

        $deleteResponse = $this->delete(route('admin.calendar.destroy', ['date' => '2026-12-30']));
        $deleteResponse->assertRedirect(route('admin.calendar.index'));

        $holidaysAfter = (array) setting('calendar.holidays', []);
        $foundAfter = collect($holidaysAfter)->contains(fn ($h) => is_array($h) && ($h['date'] ?? '') === '2026-12-30');
        $this->assertFalse($foundAfter);
    }

    public function test_admin_can_view_and_confirm_pending_items(): void
    {
        $setting = Setting::create([
            'key' => 'test.pending_item',
            'value' => 'Test Placeholder Value',
            'group' => 'general',
            'label' => 'Test Placeholder',
            'is_confirmed' => false,
        ]);

        $response = $this->get(route('admin.pending.index'));
        $response->assertStatus(200);
        $response->assertSee('Pending Confirmations Register');
        $response->assertSee('Test Placeholder Value');

        $confirmResponse = $this->post(route('admin.pending.confirm'), [
            'type' => 'setting',
            'id' => $setting->id,
        ]);

        $confirmResponse->assertRedirect(route('admin.pending.index'));
        $this->assertTrue((bool) $setting->fresh()->is_confirmed);

        $exportResponse = $this->get(route('admin.pending.export'));
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_manage_response_templates(): void
    {
        $response = $this->get(route('admin.templates.index'));
        $response->assertStatus(200);
        $response->assertSee('Response Templates');

        $createResponse = $this->post(route('admin.templates.store'), [
            'title' => 'First Summons Notice',
            'kind' => 'hearing_notice',
            'body' => 'Please appear before the Punong Barangay on the scheduled hearing date.',
            'is_active' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.templates.index'));
        $template = ResponseTemplate::where('title', 'First Summons Notice')->first();
        $this->assertNotNull($template);
        $this->assertSame('hearing_notice', $template->kind);

        $jsonResponse = $this->get(route('admin.templates.json', ['kind' => 'hearing_notice']));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonFragment(['title' => 'First Summons Notice']);

        $deleteResponse = $this->delete(route('admin.templates.destroy', $template));
        $deleteResponse->assertRedirect(route('admin.templates.index'));
        $this->assertNull(ResponseTemplate::find($template->id));
    }
}
