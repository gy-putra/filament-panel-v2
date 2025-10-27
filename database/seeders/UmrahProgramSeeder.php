<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UmrahProgram;

class UmrahProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'program_code' => 'UMRH-REG',
                'program_name' => 'Umrah Regular',
            ],
            [
                'program_code' => 'UMRH-PLUS',
                'program_name' => 'Umrah Plus Dubai',
            ],
            [
                'program_code' => 'UMRH-VIP',
                'program_name' => 'Umrah VIP Premium',
            ],
            [
                'program_code' => 'UMRH-RAMADAN',
                'program_name' => 'Umrah Ramadan Special',
            ],
            [
                'program_code' => 'UMRH-FAMILY',
                'program_name' => 'Umrah Family Package',
            ],
        ];

        foreach ($programs as $program) {
            UmrahProgram::create($program);
        }
    }
}
