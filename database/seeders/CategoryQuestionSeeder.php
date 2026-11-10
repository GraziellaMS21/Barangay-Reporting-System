<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
class CategoryQuestionSeeder extends Seeder {
    public function run(): void {
        $schemas = [
            'drainage' => ['sla'=>72,'q'=>[
                ['key'=>'water_level','label'=>'How high is the water?','type'=>'select','options'=>['Ankle','Knee','Waist','Above waist'],'required'=>true],
                ['key'=>'blocked_drain','label'=>'Is the drain blocked?','type'=>'boolean','required'=>true],
            ]],
            'garbage' => ['sla'=>48,'q'=>[
                ['key'=>'garbage_type','label'=>'Type of garbage','type'=>'select','options'=>['Household','Construction','Hazardous'],'required'=>true],
                ['key'=>'volume','label'=>'Approximate volume','type'=>'text','required'=>false],
            ]],
            'road-damage' => ['sla'=>168,'q'=>[
                ['key'=>'damage_type','label'=>'Type of damage','type'=>'select','options'=>['Pothole','Crack','Sinkhole'],'required'=>true],
                ['key'=>'size','label'=>'Size','type'=>'text','required'=>false],
            ]],
            'streetlight' => ['sla'=>120,'q'=>[
                ['key'=>'not_working','label'=>'Is it not working?','type'=>'boolean','required'=>true],
                ['key'=>'pole_number','label'=>'Pole number','type'=>'text','required'=>false],
            ]],
            'flooding' => ['sla'=>24,'q'=>[
                ['key'=>'water_depth','label'=>'Water depth','type'=>'select','options'=>['Ankle','Knee','Waist','Above waist'],'required'=>true],
                ['key'=>'area_affected','label'=>'Area affected','type'=>'textarea','required'=>true],
            ]],
            'other' => ['sla'=>96,'q'=>[
                ['key'=>'details','label'=>'Additional details','type'=>'textarea','required'=>false],
            ]],
        ];
        foreach ($schemas as $slug => $data) {
            $cat = Category::where('slug',$slug)->first();
            if ($cat) {
                $cat->update(['question_schema'=>$data['q'],'sla_hours'=>$data['sla']]);
            }
        }
    }
}
