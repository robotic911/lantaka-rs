<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Old purpose key → new purpose key map.
     * Keys not in this map are kept as-is (or dropped if unmappable).
     */
    private array $map = [
        'room_overnight' => 'meeting',   // no direct spiritual match → general meeting
        'room_retreat'   => 'retreat',
        'venue_retreat'  => 'retreat',
        // recollection, seminar, birthday, lecture, wedding, orientation stay as-is
    ];

    public function up(): void
    {
        $sets = DB::table('Food_Set')->get();

        foreach ($sets as $set) {
            $purposes = json_decode($set->Food_Set_Purpose, true);
            if (!is_array($purposes)) {
                continue;
            }

            $updated = [];
            foreach ($purposes as $p) {
                $newKey = $this->map[$p] ?? $p;
                if (!in_array($newKey, $updated, true)) {
                    $updated[] = $newKey;
                }
            }

            if ($updated !== $purposes) {
                DB::table('Food_Set')
                    ->where('Food_Set_ID', $set->Food_Set_ID)
                    ->update(['Food_Set_Purpose' => json_encode($updated)]);
            }
        }
    }

    public function down(): void
    {
        // Reverse map (best-effort — merged keys can't be perfectly reversed)
        $reverseMap = [
            'retreat' => 'room_retreat',
            'meeting' => 'room_overnight',
        ];

        $sets = DB::table('Food_Set')->get();

        foreach ($sets as $set) {
            $purposes = json_decode($set->Food_Set_Purpose, true);
            if (!is_array($purposes)) {
                continue;
            }

            $reverted = [];
            foreach ($purposes as $p) {
                $oldKey = $reverseMap[$p] ?? $p;
                if (!in_array($oldKey, $reverted, true)) {
                    $reverted[] = $oldKey;
                }
            }

            if ($reverted !== $purposes) {
                DB::table('Food_Set')
                    ->where('Food_Set_ID', $set->Food_Set_ID)
                    ->update(['Food_Set_Purpose' => json_encode($reverted)]);
            }
        }
    }
};
