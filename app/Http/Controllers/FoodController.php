<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Food;
use App\Models\FoodSet;

class FoodController extends Controller
{
    /* ══════════════════════════════════════════════════════════
     |  INDIVIDUAL FOOD CRUD
     ╚═════════════════════════════════════════════════════════ */

    public function store(Request $request)
    {
        $request->validate([
            'Food_Name'     => 'required|string|max:50|unique:Food,Food_Name',
            'Food_Category' => 'required|string|in:rice,viand,sidedish,drinks,desserts,fruits,snacks',
            'Food_Price'    => 'required|numeric|min:0',
            'Food_Status'   => 'required|string|in:available,unavailable',
        ]);

        Food::create([
            'Food_Name'     => $request->Food_Name,
            'Food_Category' => $request->Food_Category,
            'Food_Price'    => $request->Food_Price,
            'Food_Status'   => $request->Food_Status,
        ]);

        return redirect()->back()->with('success', 'Food item added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Food_Name'     => 'required|string|max:255|unique:Food,Food_Name,'.$id.',Food_ID',
            'Food_Status'   => 'required|in:available,unavailable',
            'Food_Category' => 'required|string|in:rice,viand,sidedish,drinks,desserts,fruits,snacks',
            'Food_Price'    => 'required|numeric|min:0',
        ]);

        $food = Food::findOrFail($id);
        $food->update([
            'Food_Name'     => $request->Food_Name,
            'Food_Category' => $request->Food_Category,
            'Food_Price'    => $request->Food_Price,
            'Food_Status'   => $request->Food_Status,
        ]);

        return back()->with('success', 'Food item updated successfully.');
    }

    public function destroy($id)
    {
        Food::findOrFail($id)->delete();
        return back()->with('success', 'Food item deleted successfully.');
    }

    /* ══════════════════════════════════════════════════════════
     |  FOOD SET CRUD
     ╚═════════════════════════════════════════════════════════ */

    public function storeFoodSet(Request $request)
    {
        $validPurposes  = 'all,retreat,recollection,meeting,seminar,birthday,lecture,wedding,orientation';
        $validMealTimes = 'any,breakfast,am_snack,lunch,pm_snack,dinner';

        $request->validate([
            'Food_Set_Name'        => 'required|string|max:255',
            'Food_Set_Price'       => 'required|numeric|min:0',
            'Food_Set_Meal_Time'   => 'required|array|min:1',
            'Food_Set_Meal_Time.*' => 'string|in:'.$validMealTimes,
            'Food_Set_Purpose'     => 'required|array|min:1',
            'Food_Set_Purpose.*'   => 'string|in:'.$validPurposes,
            'Food_Set_Status'      => 'required|string|in:available,unavailable',
            'food_ids'             => 'nullable|array',
            'food_ids.*'           => 'integer|exists:Food,Food_ID',
        ]);

        FoodSet::create([
            'Food_Set_Name'      => $request->Food_Set_Name,
            'Food_Set_Price'     => $request->Food_Set_Price,
            'Food_Set_Meal_Time' => $request->Food_Set_Meal_Time,
            'Food_Set_Purpose'   => $request->Food_Set_Purpose,
            'Food_Set_Status'    => $request->Food_Set_Status,
            'Food_Set_Food_IDs'  => $request->input('food_ids', []) ?: [],
        ]);

        return back()->with('success', 'Food set added successfully.');
    }

    public function updateFoodSet(Request $request, $id)
    {
        $validPurposes  = 'all,retreat,recollection,meeting,seminar,birthday,lecture,wedding,orientation';
        $validMealTimes = 'any,breakfast,am_snack,lunch,pm_snack,dinner';

        $request->validate([
            'Food_Set_Name'        => 'required|string|max:255',
            'Food_Set_Price'       => 'required|numeric|min:0',
            'Food_Set_Meal_Time'   => 'required|array|min:1',
            'Food_Set_Meal_Time.*' => 'string|in:'.$validMealTimes,
            'Food_Set_Purpose'     => 'required|array|min:1',
            'Food_Set_Purpose.*'   => 'string|in:'.$validPurposes,
            'Food_Set_Status'      => 'required|string|in:available,unavailable',
            'food_ids'             => 'nullable|array',
            'food_ids.*'           => 'integer|exists:Food,Food_ID',
        ]);

        $set = FoodSet::findOrFail($id);
        $set->update([
            'Food_Set_Name'      => $request->Food_Set_Name,
            'Food_Set_Price'     => $request->Food_Set_Price,
            'Food_Set_Meal_Time' => $request->Food_Set_Meal_Time,
            'Food_Set_Purpose'   => $request->Food_Set_Purpose,
            'Food_Set_Status'    => $request->Food_Set_Status,
            'Food_Set_Food_IDs'  => $request->input('food_ids', []) ?: [],
        ]);

        return back()->with('success', 'Food set updated successfully.');
    }

    public function destroyFoodSet($id)
    {
        FoodSet::findOrFail($id)->delete();
        return back()->with('success', 'Food set deleted successfully.');
    }

    /* ══════════════════════════════════════════════════════════
     |  DEDICATED FOOD MANAGEMENT PAGE (admin/staff)
     ╚═════════════════════════════════════════════════════════ */

    public function showFoodManagementPage()
    {
        $perPage = 10;

        $categoryFilter = request('category');

        $foods = Food::when($categoryFilter, fn ($q) => $q->where('Food_Category', $categoryFilter))
                     ->orderBy('Food_Category')
                     ->orderBy('Food_Name')
                     ->paginate($perPage, ['*'], 'food_page')
                     ->appends(request()->except('food_page'));

        $foodSets = FoodSet::orderBy('Food_Set_Name')
                        ->paginate($perPage, ['*'], 'set_page')
                        ->appends(request()->except('set_page'));

        // All foods for the checklist in add/edit set modals
        $allFoods = Food::orderBy('Food_Category')->orderBy('Food_Name')->get();

        return view('employee.food', compact('foods', 'foodSets', 'allFoods'));
    }

    /* ══════════════════════════════════════════════════════════
     |  AJAX — client & employee booking flows
     ╚═════════════════════════════════════════════════════════ */

    /** Individual foods grouped by category */
    public function getFoodsAjax()
    {
        $foods = Food::where('Food_Status', 'available')
            ->orderBy('Food_Category')
            ->orderBy('Food_Name')
            ->get()
            ->groupBy(fn($f) => strtolower($f->Food_Category))
            ->map(fn($group) => $group->map(fn($f) => [
                'Food_ID'       => $f->Food_ID,
                'Food_Name'     => $f->Food_Name,
                'Food_Category' => strtolower($f->Food_Category),
                'Food_Price'    => $f->Food_Price,
                'Food_Status'   => $f->Food_Status,
            ])->values());

        return response()->json($foods);
    }

    /**
     * Food sets grouped by meal time.
     *
     * Since Food_Set_Meal_Time is now a JSON array (e.g. ['breakfast','lunch']),
     * each set is fanned out into every meal-time bucket it belongs to.
     * Optional ?purpose= query param filters by reservation purpose.
     */
    public function getFoodSetsAjax()
    {
        $purpose = request('purpose', '');

        $sets = FoodSet::where('Food_Set_Status', 'available')
            ->orderBy('Food_Set_Name')
            ->get();

        // Optional purpose filter
        if ($purpose !== '') {
            $sets = $sets->filter(function ($s) use ($purpose) {
                $purposes = $s->Food_Set_Purpose ?? [];
                return in_array('all', $purposes, true)
                    || in_array($purpose, $purposes, true);
            });
        }

        // Bulk-load all foods referenced by any set in one query
        $allFoodIds = $sets->flatMap(fn($s) => $s->Food_Set_Food_IDs ?? [])
                           ->unique()->values()->all();

        $foodsById = count($allFoodIds)
            ? Food::whereIn('Food_ID', $allFoodIds)->get()->keyBy('Food_ID')
            : collect();

        // Fan each set out into one bucket per meal-time
        $grouped = [];
        foreach ($sets as $s) {
            $mealTimes = $s->Food_Set_Meal_Time ?? [];
            if (empty($mealTimes)) {
                continue;
            }

            $setRow = [
                'Food_Set_ID'        => $s->Food_Set_ID,
                'Food_Set_Name'      => $s->Food_Set_Name,
                'Food_Set_Price'     => $s->Food_Set_Price,
                'Food_Set_Purpose'   => $s->Food_Set_Purpose   ?? [],
                'Food_Set_Meal_Time' => $s->Food_Set_Meal_Time ?? [],
                'foods'              => collect($s->Food_Set_Food_IDs ?? [])
                    ->map(fn($id) => isset($foodsById[$id]) ? [
                        'Food_ID'       => $foodsById[$id]->Food_ID,
                        'Food_Name'     => $foodsById[$id]->Food_Name,
                        'Food_Category' => $foodsById[$id]->Food_Category,
                        'Food_Price'    => $foodsById[$id]->Food_Price,
                    ] : null)
                    ->filter()
                    ->values()
                    ->all(),
            ];

            foreach ($mealTimes as $mt) {
                $grouped[strtolower($mt)][] = $setRow;
            }
        }

        // Convert inner arrays to indexed collections for consistent JSON
        $result = collect($grouped)->map(fn($bucket) => array_values($bucket));

        return response()->json($result);
    }

    /* ── legacy helpers (kept for backward compatibility) ── */

    public function showFoodOptions(Request $request)
    {
        $foods = Food::where('Food_Status', 'available')->get()->groupBy('Food_Category');
        return view('client.food_option', ['foods' => $foods]);
    }

    public function showEmployeeFood(Request $request)
    {
        $foods = Food::all();
        return view('employee_food', ['foods' => $foods]);
    }
}
