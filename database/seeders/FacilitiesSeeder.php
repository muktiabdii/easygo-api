<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Facility;

class FacilitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $facilities = [
            ['name' => 'Jalur Kursi Roda', 'logo' => 'jalur_kursi_roda.png'],
            ['name' => 'Pintu Otomatis', 'logo' => 'pintu_otomatis.png'],
            ['name' => 'Parkir Disabilitas', 'logo' => 'parkir_disabilitas.png'],
            ['name' => 'Toilet Disabilitas', 'logo' => 'toilet_disabilitas.png'],
            ['name' => 'Lift Braille & Suara', 'logo' => 'lift_braille_suara.png'],
            ['name' => 'Interpreter Isyarat', 'logo' => 'interpreter_isyarat.png'],
            ['name' => 'Menu Braille', 'logo' => 'menu_braille.png'],
            ['name' => 'Jalur Guiding Block', 'logo' => 'jalur_guiding_block.png'],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }
    }
}