<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoryQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $schemas = [
            'drainage' => [
                'sla' => 72,
                'q' => [
                    [
                        'key' => 'water_blocked',
                        'label' => 'Is water blocked or standing now?',
                        'type' => 'select',
                        'options' => ['Yes', 'No', 'Not sure'],
                        'required' => false,
                    ],
                ],
            ],
            'flooding' => [
                'sla' => 24,
                'q' => [
                    [
                        'key' => 'water_present',
                        'label' => 'Is the water still there now?',
                        'type' => 'select',
                        'options' => ['Yes', 'No', 'Not sure'],
                        'required' => false,
                    ],
                    [
                        'key' => 'people_affected',
                        'label' => 'Are homes or people affected?',
                        'type' => 'select',
                        'options' => ['Yes', 'No', 'Not sure'],
                        'required' => false,
                    ],
                ],
            ],
            'garbage' => [
                'sla' => 48,
                'q' => [
                    [
                        'key' => 'garbage_type',
                        'label' => 'What is the garbage situation?',
                        'type' => 'select',
                        'options' => ['Uncollected', 'Dumped pile', 'Overflowing bin', 'Other'],
                        'required' => false,
                    ],
                ],
            ],
            'road-damage' => [
                'sla' => 168,
                'q' => [
                    [
                        'key' => 'damage_type',
                        'label' => 'Type of road damage',
                        'type' => 'select',
                        'options' => ['Pothole', 'Crack', 'Broken edge', 'Other'],
                        'required' => false,
                    ],
                    [
                        'key' => 'blocks_traffic',
                        'label' => 'Does it block vehicles?',
                        'type' => 'select',
                        'options' => ['Yes', 'Partially', 'No', 'Not sure'],
                        'required' => false,
                    ],
                ],
            ],
            'streetlight' => [
                'sla' => 120,
                'q' => [
                    [
                        'key' => 'light_condition',
                        'label' => 'What is the issue with the light?',
                        'type' => 'select',
                        'options' => ['Not working (dark)', 'Flickering', 'Pole damaged', 'Other'],
                        'required' => false,
                    ],
                ],
            ],
            'other' => [
                'sla' => 96,
                'q' => [],
            ],
        ];

        foreach ($schemas as $slug => $data) {
            $cat = Category::where('slug', $slug)->first();
            if ($cat) {
                $updates = [];
                if (empty($cat->question_schema)) {
                    $updates['question_schema'] = $data['q'];
                }
                if ($cat->sla_hours === null) {
                    $updates['sla_hours'] = $data['sla'];
                }
                if (! empty($updates)) {
                    $cat->update($updates);
                }
            }
        }
    }
}
