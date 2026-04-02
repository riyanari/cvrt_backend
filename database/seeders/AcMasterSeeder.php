<?php

namespace Database\Seeders;

use App\Models\AcBrand;
use App\Models\AcCapacity;
use App\Models\AcCatalog;
use App\Models\AcType;
use Illuminate\Database\Seeder;

class AcMasterSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'DAIKIN',
            'PANASONIC',
            'SAMSUNG',
            'LG',
            'SHARP',
            'GREE',
            'MITSUBISHI ELECTRIC',
            'MITSUBISHI HEAVY',
            'TOSHIBA',
            'AQUA',
            'POLYTRON',
            'AUX',
            'MIDEA',
            'HISENSE',
            'CHANGHONG',
            'DENPOO',
            'TCL',
            'HAIER',
            'CARRIER',
            'YORK',
            'SANYO',
            'ELECTROLUX',
            'MODENA',
            'GEA',
            'FUJITSU',
        ];

        $types = [
            'SPLIT',
            'CASSETTE',
            'FLOOR STANDING',
            'CEILING DUCT',
            'WINDOW',
            'PORTABLE',
            'VRV/VRF',
            'MULTI SPLIT',
            'WALL MOUNTED',
            'CEILING SUSPENDED',
        ];

        $capacities = [
            '0,5 PK',
            '3/4 PK',
            '1 PK',
            '1,5 PK',
            '2 PK',
            '2,5 PK',
            '3 PK',
            '4 PK',
            '5 PK',
            '6 PK',
            '8 PK',
            '10 PK',
        ];

        foreach ($brands as $name) {
            AcBrand::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        foreach ($types as $name) {
            AcType::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        foreach ($capacities as $name) {
            AcCapacity::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        // lookup helper
        $brand = fn(string $name) => AcBrand::where('name', $name)->firstOrFail();
        $type = fn(string $name) => AcType::where('name', $name)->firstOrFail();
        $capacity = fn(string $name) => AcCapacity::where('name', $name)->firstOrFail();

        $rows = [
            // =========================
            // DAIKIN
            // =========================
            ['DAIKIN', 'SPLIT', '0,5 PK', 'FTV15AXV14'],
            ['DAIKIN', 'SPLIT', '3/4 PK', 'FTV20AXV14'],
            ['DAIKIN', 'SPLIT', '1 PK', 'FTV25AXV14'],
            ['DAIKIN', 'SPLIT', '1,5 PK', 'FTV35AXV14'],
            ['DAIKIN', 'SPLIT', '1 PK', 'FTNE25MV14'],
            ['DAIKIN', 'SPLIT', '1,5 PK', 'FTNE35MV14'],
            ['DAIKIN', 'SPLIT', '1 PK', 'FTC25NV14'],
            ['DAIKIN', 'SPLIT', '1,5 PK', 'FTC35NV14'],
            ['DAIKIN', 'SPLIT', '1 PK', 'FTKF25UVM4'],
            ['DAIKIN', 'SPLIT', '1,5 PK', 'FTKF35UVM4'],
            ['DAIKIN', 'SPLIT', '1 PK', 'FTKM25UVM4'],
            ['DAIKIN', 'SPLIT', '1,5 PK', 'FTKM35UVM4'],
            ['DAIKIN', 'CASSETTE', '1,5 PK', 'FCFC35DVM'],
            ['DAIKIN', 'CASSETTE', '2 PK', 'FCFC50DVM'],
            ['DAIKIN', 'FLOOR STANDING', '3 PK', 'FVGR71NV1'],
            ['DAIKIN', 'CEILING DUCT', '4 PK', 'FDMQ100PVM4'],

            // =========================
            // PANASONIC
            // =========================
            ['PANASONIC', 'SPLIT', '0,5 PK', 'CS-YN5WKJ'],
            ['PANASONIC', 'SPLIT', '3/4 PK', 'CS-YN7WKJ'],
            ['PANASONIC', 'SPLIT', '1 PK', 'CS-YN9WKJ'],
            ['PANASONIC', 'SPLIT', '1 PK', 'CS-PN9WKJ'],
            ['PANASONIC', 'SPLIT', '1,5 PK', 'CS-PN12WKJ'],
            ['PANASONIC', 'SPLIT', '1 PK', 'CS-XPU9VKJ'],
            ['PANASONIC', 'SPLIT', '1,5 PK', 'CS-XPU12VKJ'],
            ['PANASONIC', 'SPLIT', '1 PK', 'CS-U9WKJ'],
            ['PANASONIC', 'SPLIT', '1,5 PK', 'CS-U12WKJ'],
            ['PANASONIC', 'SPLIT', '2,5 PK', 'CS-PV24RB4P'],
            ['PANASONIC', 'CASSETTE', '2 PK', 'S-1821PU3E'],
            ['PANASONIC', 'CASSETTE', '3 PK', 'S-2430PU3E'],
            ['PANASONIC', 'CEILING DUCT', '4 PK', 'S-3448PF3H'],

            // =========================
            // SAMSUNG
            // =========================
            ['SAMSUNG', 'SPLIT', '0,5 PK', 'AR05TGHQASINSE'],
            ['SAMSUNG', 'SPLIT', '3/4 PK', 'AR09TGHQASINSE'],
            ['SAMSUNG', 'SPLIT', '1,5 PK', 'AR12TGHQASINSE'],
            ['SAMSUNG', 'SPLIT', '2 PK', 'AR18TYHYEWKNSV'],
            ['SAMSUNG', 'SPLIT', '2,5 PK', 'AR24TYHYEWKNSV'],
            ['SAMSUNG', 'SPLIT', '1 PK', 'AR10TYGZEWKNSE'],
            ['SAMSUNG', 'SPLIT', '1,5 PK', 'AR13TYGZEWKNSE'],
            ['SAMSUNG', 'CASSETTE', '2 PK', 'AC071TN4PKC'],
            ['SAMSUNG', 'CASSETTE', '3 PK', 'AC100TN4PKC'],
            ['SAMSUNG', 'CEILING DUCT', '4 PK', 'AC120TNMDKC'],

            // =========================
            // LG
            // =========================
            ['LG', 'SPLIT', '0,5 PK', 'T05EV5'],
            ['LG', 'SPLIT', '1 PK', 'T09EV5'],
            ['LG', 'SPLIT', '1,5 PK', 'T12EV5'],
            ['LG', 'SPLIT', '0,5 PK', 'H05TN4'],
            ['LG', 'SPLIT', '1 PK', 'H09TN4'],
            ['LG', 'FLOOR STANDING', '2 PK', 'APNQ18GS1K1'],
            ['LG', 'CASSETTE', '2 PK', 'ATNQ18GPLA4'],
            ['LG', 'CEILING DUCT', '3 PK', 'ABNQ24GM1T6'],

            // =========================
            // SHARP
            // =========================
            ['SHARP', 'SPLIT', '0,5 PK', 'AH-A5UCY'],
            ['SHARP', 'SPLIT', '3/4 PK', 'AH-A7UCY'],
            ['SHARP', 'SPLIT', '1 PK', 'AH-A9UCY'],
            ['SHARP', 'SPLIT', '1 PK', 'AH-X9BEY'],
            ['SHARP', 'SPLIT', '1,5 PK', 'AH-X12BEY'],
            ['SHARP', 'SPLIT', '2 PK', 'AH-X18BEY'],

            // =========================
            // GREE
            // =========================
            ['GREE', 'SPLIT', '0,5 PK', 'GWC-05MOO5'],
            ['GREE', 'SPLIT', '1 PK', 'GWC-09MOO5'],
            ['GREE', 'SPLIT', '1,5 PK', 'GWC-12MOO5'],
            ['GREE', 'FLOOR STANDING', '2,5 PK', 'GFH-24K3FI'],
            ['GREE', 'CASSETTE', '2 PK', 'GKH-18K3BI'],
            ['GREE', 'CEILING DUCT', '3 PK', 'GUD71PHS'],

            // =========================
            // MITSUBISHI ELECTRIC
            // =========================
            ['MITSUBISHI ELECTRIC', 'SPLIT', '1 PK', 'MS-JP25VF'],
            ['MITSUBISHI ELECTRIC', 'SPLIT', '1,5 PK', 'MS-JP35VF'],
            ['MITSUBISHI ELECTRIC', 'SPLIT', '0,5 PK', 'MSY-GN13VF'],
            ['MITSUBISHI ELECTRIC', 'SPLIT', '1 PK', 'MSY-GN18VF'],
            ['MITSUBISHI ELECTRIC', 'CASSETTE', '2 PK', 'PLA-RP50BA'],

            // =========================
            // MITSUBISHI HEAVY
            // =========================
            ['MITSUBISHI HEAVY', 'SPLIT', '1 PK', 'SRK09CRS-S'],
            ['MITSUBISHI HEAVY', 'SPLIT', '1,5 PK', 'SRK13CRS-S'],
            ['MITSUBISHI HEAVY', 'SPLIT', '2 PK', 'SRK18CRS-S'],
            ['MITSUBISHI HEAVY', 'CASSETTE', '2,5 PK', 'FDT71KXE6F'],

            // =========================
            // TOSHIBA
            // =========================
            ['TOSHIBA', 'SPLIT', '0,5 PK', 'RAS-05N3KCVG'],
            ['TOSHIBA', 'SPLIT', '1 PK', 'RAS-10N3KCVG'],
            ['TOSHIBA', 'SPLIT', '1,5 PK', 'RAS-13N3KCVG'],
            ['TOSHIBA', 'CASSETTE', '2 PK', 'RAV-GM561ATP-E'],

            // =========================
            // AQUA
            // =========================
            ['AQUA', 'SPLIT', '0,5 PK', 'AQA-KCR5ANQ'],
            ['AQUA', 'SPLIT', '1 PK', 'AQA-KCR9ANQ'],
            ['AQUA', 'SPLIT', '1,5 PK', 'AQA-KCR12ANQ'],
            ['AQUA', 'SPLIT', '2 PK', 'AQA-KCR18ANQ'],

            // =========================
            // POLYTRON
            // =========================
            ['POLYTRON', 'SPLIT', '0,5 PK', 'PAC 05VXM'],
            ['POLYTRON', 'SPLIT', '1 PK', 'PAC 09VXM'],
            ['POLYTRON', 'SPLIT', '1,5 PK', 'PAC 12VXM'],
            ['POLYTRON', 'SPLIT', '2 PK', 'PAC 18VXM'],

            // =========================
            // AUX
            // =========================
            ['AUX', 'SPLIT', '0,5 PK', 'ASW-05A4'],
            ['AUX', 'SPLIT', '1 PK', 'ASW-09A4'],
            ['AUX', 'SPLIT', '1,5 PK', 'ASW-12A4'],
            ['AUX', 'SPLIT', '2 PK', 'ASW-18A4'],

            // =========================
            // MIDEA
            // =========================
            ['MIDEA', 'SPLIT', '0,5 PK', 'MSAF-05CRN2'],
            ['MIDEA', 'SPLIT', '1 PK', 'MSAF-09CRN2'],
            ['MIDEA', 'SPLIT', '1,5 PK', 'MSAF-12CRN2'],
            ['MIDEA', 'SPLIT', '2 PK', 'MSAF-18CRN2'],
            ['MIDEA', 'PORTABLE', '1 PK', 'MPPH-09CRN7'],

            // =========================
            // HISENSE
            // =========================
            ['HISENSE', 'SPLIT', '1 PK', 'AS-09CR4'],
            ['HISENSE', 'SPLIT', '1,5 PK', 'AS-12CR4'],
            ['HISENSE', 'SPLIT', '2 PK', 'AS-18CR4'],

            // =========================
            // CHANGHONG
            // =========================
            ['CHANGHONG', 'SPLIT', '0,5 PK', 'CHOL-05L'],
            ['CHANGHONG', 'SPLIT', '1 PK', 'CHOL-09L'],
            ['CHANGHONG', 'SPLIT', '1,5 PK', 'CHOL-12L'],

            // =========================
            // DENPOO
            // =========================
            ['DENPOO', 'SPLIT', '0,5 PK', 'DDS-05A'],
            ['DENPOO', 'SPLIT', '1 PK', 'DDS-09A'],
            ['DENPOO', 'SPLIT', '1,5 PK', 'DDS-12A'],

            // =========================
            // TCL
            // =========================
            ['TCL', 'SPLIT', '0,5 PK', 'TAC-05CSA'],
            ['TCL', 'SPLIT', '1 PK', 'TAC-09CSA'],
            ['TCL', 'SPLIT', '1,5 PK', 'TAC-12CSA'],

            // =========================
            // HAIER
            // =========================
            ['HAIER', 'SPLIT', '0,5 PK', 'HSU-05VQ'],
            ['HAIER', 'SPLIT', '1 PK', 'HSU-09VQ'],
            ['HAIER', 'SPLIT', '1,5 PK', 'HSU-12VQ'],

            // =========================
            // CARRIER
            // =========================
            ['CARRIER', 'SPLIT', '1 PK', '42KCE009'],
            ['CARRIER', 'SPLIT', '1,5 PK', '42KCE012'],
            ['CARRIER', 'CASSETTE', '2 PK', '40QSF018'],
            ['CARRIER', 'CEILING DUCT', '3 PK', '42DTV025'],

            // =========================
            // YORK
            // =========================
            ['YORK', 'SPLIT', '1 PK', 'YWM09J'],
            ['YORK', 'SPLIT', '1,5 PK', 'YWM12J'],
            ['YORK', 'CASSETTE', '2 PK', 'YCC18FS'],

            // =========================
            // SANYO
            // =========================
            ['SANYO', 'SPLIT', '0,5 PK', 'SAP-KC5AH'],
            ['SANYO', 'SPLIT', '1 PK', 'SAP-KC9AH'],
            ['SANYO', 'SPLIT', '1,5 PK', 'SAP-KC12AH'],

            // =========================
            // ELECTROLUX
            // =========================
            ['ELECTROLUX', 'SPLIT', '0,5 PK', 'ES05CRV'],
            ['ELECTROLUX', 'SPLIT', '1 PK', 'ES09CRV'],
            ['ELECTROLUX', 'SPLIT', '1,5 PK', 'ES12CRV'],

            // =========================
            // MODENA
            // =========================
            ['MODENA', 'SPLIT', '1 PK', 'SAS09'],
            ['MODENA', 'SPLIT', '1,5 PK', 'SAS12'],
            ['MODENA', 'SPLIT', '2 PK', 'SAS18'],

            // =========================
            // GEA
            // =========================
            ['GEA', 'SPLIT', '0,5 PK', 'GAC-05'],
            ['GEA', 'SPLIT', '1 PK', 'GAC-09'],
            ['GEA', 'SPLIT', '1,5 PK', 'GAC-12'],

            // =========================
            // FUJITSU
            // =========================
            ['FUJITSU', 'SPLIT', '1 PK', 'ASAG09'],
            ['FUJITSU', 'SPLIT', '1,5 PK', 'ASAG12'],
            ['FUJITSU', 'CASSETTE', '2 PK', 'AUYG18LVLB'],
        ];

        foreach ($rows as [$brandName, $typeName, $capacityName, $series]) {
            $brandModel = $brand($brandName);
            $typeModel = $type($typeName);
            $capacityModel = $capacity($capacityName);

            AcCatalog::firstOrCreate(
                [
                    'brand_id' => $brandModel->id,
                    'type_id' => $typeModel->id,
                    'capacity_id' => $capacityModel->id,
                    'series' => $series,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}