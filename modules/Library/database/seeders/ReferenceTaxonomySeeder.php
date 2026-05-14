<?php

declare(strict_types=1);

namespace Modules\Library\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;

class ReferenceTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $regulators = [
            ['code' => 'CBN',    'name' => 'Central Bank of Nigeria',                     'country' => 'NGA', 'website_url' => 'https://www.cbn.gov.ng'],
            ['code' => 'NDIC',   'name' => 'Nigeria Deposit Insurance Corporation',        'country' => 'NGA', 'website_url' => 'https://www.ndic.org.ng'],
            ['code' => 'NFIU',   'name' => 'Nigerian Financial Intelligence Unit',         'country' => 'NGA', 'website_url' => 'https://www.nfiu.gov.ng'],
            ['code' => 'SEC',    'name' => 'Securities and Exchange Commission Nigeria',   'country' => 'NGA', 'website_url' => 'https://sec.gov.ng'],
            ['code' => 'NDPC',   'name' => 'Nigeria Data Protection Commission',          'country' => 'NGA', 'website_url' => 'https://ndpc.gov.ng'],
            ['code' => 'FIRS',   'name' => 'Federal Inland Revenue Service',              'country' => 'NGA', 'website_url' => 'https://www.firs.gov.ng'],
            ['code' => 'NAICOM', 'name' => 'National Insurance Commission',               'country' => 'NGA', 'website_url' => 'https://www.naicom.gov.ng'],
            ['code' => 'PenCom', 'name' => 'National Pension Commission',                 'country' => 'NGA', 'website_url' => 'https://www.pencom.gov.ng'],
        ];

        foreach ($regulators as $regulator) {
            Regulator::updateOrCreate(['code' => $regulator['code']], $regulator);
        }

        $instrumentTypes = ['Act', 'Regulation', 'Guideline', 'Circular', 'Framework', 'Policy'];
        foreach ($instrumentTypes as $type) {
            InstrumentType::updateOrCreate(['name' => $type], ['name' => $type]);
        }

        $areasOfFocus = [
            'AML/CFT',
            'Conduct',
            'Capital Adequacy',
            'Consumer Protection',
            'Data Protection',
            'Cybersecurity',
            'Corporate Governance',
            'Operational Risk',
        ];
        foreach ($areasOfFocus as $area) {
            AreaOfFocus::updateOrCreate(['name' => $area], ['name' => $area]);
        }

        $natures = ['Statutory', 'Regulatory', 'Industry Standard', 'Internal'];
        foreach ($natures as $nature) {
            Nature::updateOrCreate(['name' => $nature], ['name' => $nature]);
        }

        $statuses = ['Active', 'Repealed', 'Superseded', 'Exposure Draft'];
        foreach ($statuses as $status) {
            Status::updateOrCreate(['name' => $status], ['name' => $status]);
        }

        $riskRatings = [
            ['name' => 'High',   'color' => '#DD6B20'],
            ['name' => 'Medium', 'color' => '#D4AF37'],
            ['name' => 'Low',    'color' => '#2D7D46'],
        ];
        foreach ($riskRatings as $rating) {
            RiskRating::updateOrCreate(['name' => $rating['name']], $rating);
        }
    }
}
