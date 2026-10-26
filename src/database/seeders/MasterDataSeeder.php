<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Installation;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds for Areas and Installations (Gujarat Industrial Belt).
     * Idempotent using updateOrCreate on unique 'code'.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Vadodara Asset',
                'code' => 'VAD',
                'installations' => [
                    ['name' => 'Vadodara Plant 1', 'code' => 'VAD-01'],
                    ['name' => 'Vadodara Storage Yard', 'code' => 'VAD-02'],
                    ['name' => 'Vadodara Maintenance Bay', 'code' => 'VAD-03'],
                ],
            ],
            [
                'name' => 'Surat Asset',
                'code' => 'SUR',
                'installations' => [
                    ['name' => 'Surat Compressor Station', 'code' => 'SUR-01'],
                    ['name' => 'Surat Pipeline Terminal', 'code' => 'SUR-02'],
                ],
            ],
            [
                'name' => 'Ankleshwar Asset',
                'code' => 'ANK',
                'installations' => [
                    ['name' => 'Ankleshwar Substation', 'code' => 'ANK-01'],
                    ['name' => 'Ankleshwar Water Works', 'code' => 'ANK-02'],
                ],
            ],
            [
                'name' => 'Ahmedabad HQ',
                'code' => 'AHQ',
                'installations' => [
                    ['name' => 'HQ Engineering Lab', 'code' => 'AHQ-01'],
                    ['name' => 'HQ Central Depot', 'code' => 'AHQ-02'],
                ],
            ],
        ];

        foreach ($areas as $areaData) {
            $installations = $areaData['installations'] ?? [];
            unset($areaData['installations']);

            $area = Area::updateOrCreate(
                ['code' => $areaData['code']],
                $areaData
            );

            foreach ($installations as $instData) {
                Installation::updateOrCreate(
                    ['code' => $instData['code']],
                    [
                        'area_id' => $area->id,
                        'name' => $instData['name'],
                    ]
                );
            }
        }
    }
}
