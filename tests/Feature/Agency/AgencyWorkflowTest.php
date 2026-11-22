<?php

namespace Tests\Feature\Agency;

use App\Models\AgencyRegistration;
use App\Models\FiscalYear;
use App\Models\Office;
use App\Models\Referral;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_agency_can_register_for_access(): void
    {
        $office = Office::create([
            'name' => 'PNP Station 8',
            'type' => 'external',
            'is_external' => true,
            'is_internal' => false,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('police_id.pdf', 500, 'application/pdf');

        $response = $this->post(route('agency.register.store'), [
            'office_id' => $office->id,
            'agency_name_claimed' => 'PNP Station 8',
            'name' => 'Officer Dela Cruz',
            'position_title' => 'Investigator',
            'email' => 'officer@police.gov.ph',
            'contact_number' => '09123456789',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'id_document' => $file,
            'consent' => '1',
        ]);

        $response->assertRedirect(route('agency.pending'));

        $this->assertDatabaseHas('users', [
            'email' => 'officer@police.gov.ph',
            'role' => 'agency',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('agency_registrations', [
            'agency_name_claimed' => 'PNP Station 8',
            'status' => 'pending',
            'is_office_admin' => true,
        ]);
    }

    public function test_admin_can_approve_agency_registration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $office = Office::create([
            'name' => 'CSWDO Zamboanga',
            'type' => 'external',
            'is_external' => true,
            'is_internal' => false,
            'is_active' => true,
        ]);

        $agencyUser = User::factory()->create(['role' => 'agency', 'is_active' => false]);
        $registration = AgencyRegistration::factory()->create([
            'user_id' => $agencyUser->id,
            'office_id' => $office->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.agencies.approve', $registration), [
            'office_id' => $office->id,
            'is_office_admin' => 1,
        ]);

        $response->assertRedirect(route('admin.agencies.show', $registration));

        $this->assertDatabaseHas('agency_registrations', [
            'id' => $registration->id,
            'status' => 'approved',
            'is_office_admin' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $agencyUser->id,
            'is_active' => true,
        ]);
    }

    public function test_agency_user_sees_only_own_office_referrals(): void
    {
        $officeA = Office::create(['name' => 'PNP Station', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $officeB = Office::create(['name' => 'CSWDO', 'type' => 'external', 'is_external' => true, 'is_active' => true]);

        $agencyUserA = User::factory()->create([
            'role' => 'agency',
            'office_id' => $officeA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::factory()->create([
            'user_id' => $agencyUserA->id,
            'office_id' => $officeA->id,
            'status' => 'approved',
        ]);

        $fy = FiscalYear::first() ?? FiscalYear::factory()->create(['status' => 'active']);
        $report = Report::factory()->create();

        $referralA = Referral::create([
            'report_id' => $report->id,
            'office_id' => $officeA->id,
            'fiscal_year_id' => $fy->id,
            'reference_no' => 'REF-A-001',
            'status' => 'sent',
            'referral_text' => 'Investigation requested',
        ]);

        $referralB = Referral::create([
            'report_id' => $report->id,
            'office_id' => $officeB->id,
            'fiscal_year_id' => $fy->id,
            'reference_no' => 'REF-B-001',
            'status' => 'sent',
            'referral_text' => 'Welfare check',
        ]);

        $response = $this->actingAs($agencyUserA)->get(route('agency.referrals.index'));
        $response->assertStatus(200);
        $response->assertSee('REF-A-001');
        $response->assertDontSee('REF-B-001');

        // Direct show scoping
        $this->actingAs($agencyUserA)->get(route('agency.referrals.show', $referralA))->assertStatus(200);
        $this->actingAs($agencyUserA)->get(route('agency.referrals.show', $referralB))->assertStatus(403);
    }

    public function test_agency_can_acknowledge_and_action_referral(): void
    {
        $office = Office::create(['name' => 'City Engineers', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $agencyUser = User::factory()->create([
            'role' => 'agency',
            'office_id' => $office->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::factory()->create([
            'user_id' => $agencyUser->id,
            'office_id' => $office->id,
            'status' => 'approved',
        ]);

        $fy = FiscalYear::first() ?? FiscalYear::factory()->create(['status' => 'active']);
        $report = Report::factory()->create();

        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'fiscal_year_id' => $fy->id,
            'reference_no' => 'REF-ENG-001',
            'status' => 'sent',
            'referral_text' => 'Road repair matter',
        ]);

        // Acknowledge
        $ackResponse = $this->actingAs($agencyUser)->post(route('agency.referrals.update', $referral), [
            'action_type' => 'acknowledge',
            'agency_reference_no' => 'ENG-CASE-99',
        ]);
        $ackResponse->assertRedirect(route('agency.referrals.show', $referral));
        $this->assertDatabaseHas('referrals', ['id' => $referral->id, 'status' => 'acknowledged', 'agency_reference_no' => 'ENG-CASE-99']);

        // Action taken
        $actionResponse = $this->actingAs($agencyUser)->post(route('agency.referrals.update', $referral), [
            'action_type' => 'action_taken',
            'note' => 'Dispatched engineering inspection team.',
        ]);
        $actionResponse->assertRedirect(route('agency.referrals.show', $referral));
        $this->assertDatabaseHas('referrals', ['id' => $referral->id, 'status' => 'action_taken']);
    }

    public function test_agency_login_redirects_to_agency_portal(): void
    {
        $office = Office::create(['name' => 'CSWDO Zamboanga', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $user = User::factory()->create([
            'email' => 'cswdo@agency.gov.ph',
            'password' => bcrypt('password123'),
            'role' => 'agency',
            'office_id' => $office->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::factory()->create([
            'user_id' => $user->id,
            'office_id' => $office->id,
            'status' => 'approved',
        ]);

        // Even if session has url.intended set to /admin, agency user must redirect to agency portal
        $response = $this->withSession(['url.intended' => 'http://localhost/admin'])->post(route('login'), [
            'email' => 'cswdo@agency.gov.ph',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('agency.referrals.index'));
    }

    public function test_inactive_agency_user_login_shows_pending_message(): void
    {
        $office = Office::create(['name' => 'CSWDO Zamboanga', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $user = User::factory()->create([
            'email' => 'cswdo-pending@agency.gov.ph',
            'password' => bcrypt('password123'),
            'role' => 'agency',
            'office_id' => $office->id,
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'cswdo-pending@agency.gov.ph',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
