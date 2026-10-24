<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Installation;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds for Areas and Installations.
     * Idempotent using updateOrCreate on unique 'code'.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Site Alpha',
                'code' => 'ALPHA',
                'installations' => [
                    ['name' => 'Alpha Plant 1', 'code' => 'ALP-01'],
                    ['name' => 'Alpha Storage Yard', 'code' => 'ALP-02'],
                    ['name' => 'Alpha Maintenance Bay', 'code' => 'ALP-03'],
                ],
            ],
            [
                'name' => 'Site Beta',
                'code' => 'BETA',
                'installations' => [
                    ['name' => 'Beta Compressor Station', 'code' => 'BET-01'],
                    ['name' => 'Beta Pipeline Terminal', 'code' => 'BET-02'],
                ],
            ],
            [
                'name' => 'Site Gamma',
                'code' => 'GAMMA',
                'installations' => [
                    ['name' => 'Gamma Substation', 'code' => 'GAM-01'],
                    ['name' => 'Gamma Water Works', 'code' => 'GAM-02'],
                ],
            ],
            [
                'name' => 'Headquarters',
                'code' => 'HQ',
                'installations' => [
                    ['name' => 'HQ Engineering Lab', 'code' => 'HQ-01'],
                    ['name' => 'HQ Central Depot', 'code' => 'HQ-02'],
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
