<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\ReferralAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralAttachment>
 */
class ReferralAttachmentFactory extends Factory
{
    protected $model = ReferralAttachment::class;

    public function definition(): array
    {
        return [
            'referral_id' => Referral::factory(),
            'label' => 'Case Endorsement Document',
            'path' => 'referral-attachments/test-document.pdf',
            'uploaded_by' => User::factory(),
        ];
    }
}
