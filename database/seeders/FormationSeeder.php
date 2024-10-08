<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('formations')->insert([

            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 1,
                'item_id' => 15,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 1,
                'item_id' => 13,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 1,
                'item_id' => 8,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 1,
                'item_id' => 9,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 1,
                'item_id' => 35,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 1,
                'item_id' => 23,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 1,
                'item_id' => 18,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 1,
                'item_id' => 8,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 2,
                'item_id' => 15,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 2,
                'item_id' => 13,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 2,
                'item_id' => 8,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 2,
                'item_id' => 9,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 2,
                'item_id' => 35,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 2,
                'item_id' => 23,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 2,
                'item_id' => 18,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 2,
                'item_id' => 8,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 3,
                'item_id' => 15,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 3,
                'item_id' => 13,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(12),
                'champion_id' => 12,
                'compo_id' => 3,
                'item_id' => 8,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 3,
                'item_id' => 9,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 3,
                'item_id' => 35,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(4),
                'champion_id' => 24,
                'compo_id' => 3,
                'item_id' => 23,
                'star' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 3,
                'item_id' => 18,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slot_table' => json_encode(16),
                'champion_id' => 8,
                'compo_id' => 3,
                'item_id' => 8,
                'star' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
