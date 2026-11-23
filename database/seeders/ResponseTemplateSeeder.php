<?php

namespace Database\Seeders;

use App\Models\ResponseTemplate;
use Illuminate\Database\Seeder;

class ResponseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'received' => 'Your report (Tracking Code: {{tracking_code}}) has been officially received by Barangay Calarian. Our staff will review the details shortly.',
            'under review' => 'Your report (Tracking Code: {{tracking_code}}) is currently under review by barangay personnel to determine the appropriate response and required resources.',
            'needs more info' => 'Additional information is needed to proceed with your report (Tracking Code: {{tracking_code}}). Please review the requested details so our team can take action.',
            'routed' => 'Your report (Tracking Code: {{tracking_code}}) has been routed to the designated office for field inspection and scheduling.',
            'resolved' => 'Action has been completed for report {{tracking_code}}. If the issue persists, you may follow up with the barangay office referencing this tracking code.',
            'out of scope' => 'Report {{tracking_code}} falls outside the direct jurisdiction of Barangay Calarian. It has been referred or noted for coordination with the concerned agency.',
        ];

        foreach ($templates as $name => $body) {
            ResponseTemplate::firstOrCreate(
                ['name' => $name],
                [
                    'body' => $body,
                    'is_active' => true,
                ]
            );
        }
    }
}
