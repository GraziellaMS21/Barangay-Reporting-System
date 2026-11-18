<?php

use App\Models\Referral;
use App\Models\ResolutionProof;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // External agencies used by the beyond-authority referral flow.
        Schema::table('offices', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('is_internal');
            $table->string('contact_person')->nullable()->after('contact');
            $table->string('contact_email')->nullable()->after('contact_person');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('address')->nullable()->after('contact_phone');
        });

        // Referral tracking: REF number, sending, follow-up and closure.
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('reference_no')->nullable()->unique()->after('report_id');
            $table->datetime('sent_at')->nullable()->after('referral_text');
            $table->string('sent_via', 30)->nullable()->after('sent_at');
            $table->string('agency_reference_no')->nullable()->after('sent_via');
            $table->datetime('follow_up_at')->nullable()->after('agency_reference_no');
            $table->datetime('closed_at')->nullable()->after('follow_up_at');
            $table->string('outcome', 30)->nullable()->after('closed_at');
            $table->foreignId('prepared_by')->nullable()->after('outcome')->constrained('users')->nullOnDelete();
        });

        Schema::create('referral_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->string('channel', 30);
            $table->datetime('occurred_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('visible_to_resident')->default(true);
            $table->timestamps();
        });

        Schema::create('report_proof_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Legacy referrals used the "pending" status; fold them into the new flow.
        Referral::query()->where('status', 'pending')->get()->each(function (Referral $referral): void {
            $referral->update(['status' => 'prepared']);
        });

        // Keep the legacy resolution_proofs.photo_path column in place, but copy the
        // existing values into the new multi-proof table so they stay visible.
        ResolutionProof::query()
            ->whereNotNull('photo_path')
            ->get()
            ->each(function (ResolutionProof $proof): void {
                \Illuminate\Support\Facades\DB::table('report_proof_photos')->insertOrIgnore([
                    'report_id' => $proof->report_id,
                    'path' => $proof->photo_path,
                    'uploaded_by' => $proof->user_id,
                    'created_at' => $proof->created_at ?? now(),
                    'updated_at' => $proof->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_proof_photos');
        Schema::dropIfExists('referral_updates');

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropColumn([
                'reference_no',
                'sent_at',
                'sent_via',
                'agency_reference_no',
                'follow_up_at',
                'closed_at',
                'outcome',
            ]);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn([
                'is_external',
                'contact_person',
                'contact_email',
                'contact_phone',
                'address',
            ]);
        });
    }
};
