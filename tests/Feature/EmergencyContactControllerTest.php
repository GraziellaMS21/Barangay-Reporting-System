<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EmergencyContact;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_open_emergency_contact_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.settings.emergency-contacts.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_contacts_with_audit_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $contact = [
            'name' => 'National Emergency Hotline',
            'number' => '911',
            'type' => 'emergency',
            'is_active' => '1',
            'sort_order' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('admin.settings.emergency-contacts.store'), $contact)
            ->assertRedirect(route('admin.settings.emergency-contacts.index'))
            ->assertSessionHas('success', 'Emergency contact added.');

        $createdContact = EmergencyContact::query()->where('name', $contact['name'])->firstOrFail();
        $this->assertDatabaseHas('emergency_contacts', [
            'id' => $createdContact->id,
            'number' => '911',
            'type' => 'emergency',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $createdAudit = AuditLog::query()->where('action', 'created')->firstOrFail();
        $this->assertSame($admin->id, $createdAudit->user_id);
        $this->assertSame('911', $createdAudit->new_values['number']);

        $this->put(route('admin.settings.emergency-contacts.update', $createdContact), [
            'name' => 'Calarian Hotline',
            'number' => '09991234567',
            'type' => 'barangay',
            'is_active' => '0',
            'sort_order' => '2',
        ])->assertRedirect(route('admin.settings.emergency-contacts.index'))
            ->assertSessionHas('success', 'Emergency contact updated.');

        $updatedContact = $createdContact->fresh();
        $this->assertSame('Calarian Hotline', $updatedContact->name);
        $this->assertFalse($updatedContact->is_active);
        $updatedAudit = AuditLog::query()->where('action', 'updated')->firstOrFail();
        $this->assertSame('National Emergency Hotline', $updatedAudit->old_values['name']);
        $this->assertSame('Calarian Hotline', $updatedAudit->new_values['name']);

        $this->delete(route('admin.settings.emergency-contacts.destroy', $updatedContact))
            ->assertRedirect(route('admin.settings.emergency-contacts.index'))
            ->assertSessionHas('success', 'Emergency contact deleted.');

        $this->assertDatabaseMissing('emergency_contacts', ['id' => $createdContact->id]);
        $deletedAudit = AuditLog::query()->where('action', 'deleted')->firstOrFail();
        $this->assertSame('Calarian Hotline', $deletedAudit->old_values['name']);
        $this->assertSame($admin->id, $deletedAudit->user_id);
    }

    public function test_resident_form_and_success_page_show_only_active_contacts_in_sort_order(): void
    {
        EmergencyContact::create([
            'name' => 'Later Hotline',
            'number' => '333',
            'type' => 'health',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        EmergencyContact::create([
            'name' => 'Hidden Hotline',
            'number' => '222',
            'type' => 'police',
            'is_active' => false,
            'sort_order' => 0,
        ]);
        EmergencyContact::create([
            'name' => 'First Hotline',
            'number' => '111',
            'type' => 'emergency',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $report = Report::factory()->create();

        $this->get(route('reports.create'))
            ->assertOk()
            ->assertSeeInOrder(['First Hotline', '111', 'Later Hotline', '333'])
            ->assertDontSee('Hidden Hotline');

        $this->get(route('reports.success', $report->tracking_code))
            ->assertOk()
            ->assertSeeInOrder(['First Hotline', '111', 'Later Hotline', '333'])
            ->assertDontSee('Hidden Hotline');
    }

    public function test_contact_validation_rejects_an_unsupported_type(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.emergency-contacts.store'), [
                'name' => 'Bad contact',
                'number' => '911',
                'type' => 'other',
                'is_active' => '1',
                'sort_order' => '0',
            ])
            ->assertSessionHasErrors([
                'type' => 'Choose a valid emergency contact type.',
            ]);

        $this->assertDatabaseCount('emergency_contacts', 0);
    }
}
