<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Safe to run more than once: existing records are left untouched.
     */
    public function run(): void
    {
        $demo = config('app.demo_user');

        User::firstOrCreate(
            ['email' => $demo['email']],
            ['name' => $demo['name'], 'password' => $demo['password']],
        );

        foreach ($this->items() as $item) {
            Item::firstOrCreate(['code' => $item['code']], $item);
        }
    }

    /**
     * Sample laboratory equipment.
     *
     * @return array<int, array<string, mixed>>
     */
    private function items(): array
    {
        return [
            ['code' => 'LAB-EL-001', 'name' => 'Multimeter Digital Sanwa CD800a', 'category' => 'Elektronika', 'quantity' => 12, 'condition' => 'baik', 'location' => 'Lab Elektronika A-101', 'notes' => null],
            ['code' => 'LAB-EL-002', 'name' => 'Osiloskop Rigol DS1054Z', 'category' => 'Elektronika', 'quantity' => 4, 'condition' => 'baik', 'location' => 'Lab Elektronika A-101', 'notes' => 'Probe cadangan di lemari 2.'],
            ['code' => 'LAB-EL-003', 'name' => 'Solder Station Hakko FX-888D', 'category' => 'Elektronika', 'quantity' => 8, 'condition' => 'rusak_ringan', 'location' => 'Lab Elektronika A-102', 'notes' => 'Dua unit mata solder aus.'],
            ['code' => 'LAB-KM-001', 'name' => 'Raspberry Pi 4 Model B 4GB', 'category' => 'Komputer', 'quantity' => 15, 'condition' => 'baik', 'location' => 'Lab IoT B-201', 'notes' => null],
            ['code' => 'LAB-KM-002', 'name' => 'Arduino Uno R3', 'category' => 'Komputer', 'quantity' => 25, 'condition' => 'baik', 'location' => 'Lab IoT B-201', 'notes' => null],
            ['code' => 'LAB-JR-001', 'name' => 'Router MikroTik hEX RB750Gr3', 'category' => 'Jaringan', 'quantity' => 10, 'condition' => 'baik', 'location' => 'Lab Jaringan B-203', 'notes' => null],
            ['code' => 'LAB-JR-002', 'name' => 'Crimping Tool RJ45', 'category' => 'Jaringan', 'quantity' => 6, 'condition' => 'rusak_berat', 'location' => 'Lab Jaringan B-203', 'notes' => 'Menunggu penggantian.'],
            ['code' => 'LAB-MM-001', 'name' => 'Proyektor Epson EB-X51', 'category' => 'Multimedia', 'quantity' => 3, 'condition' => 'baik', 'location' => 'Ruang Kelas C-301', 'notes' => null],
        ];
    }
}
