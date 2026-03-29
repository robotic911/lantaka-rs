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
            'Food_Name'     => 'required|string|max:50',
            'Food_Category' => 'required|string|in:rice,set_viand,sidedish,drinks,desserts,other_viand,snacks',
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
            'Food_Name'     => 'required|string|max:255',
            'Food_Status'   => 'required|in:available,unavailable',
            'Food_Category' => 'required|string|in:rice,set_viand,sidedish,drinks,desserts,other_viand,snacks',
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
        $request->validate([
            'Food_Set_Name'      => 'required|string|max:255',
            'Food_Set_Price'     => 'required|numeric|min:0',
            'Food_Set_Purpose'   => 'required|string|max:50',
            'Food_Set_Meal_Time' => 'required|string|in:breakfast,am_snack,lunch,pm_snack,dinner',
            'Food_Set_Status'    => 'required|string|in:available,unavailable',
        ]);

        FoodSet::create($request->only([
            'Food_Set_Name', 'Food_Set_Price',
            'Food_Set_Purpose', 'Food_Set_Meal_Time', 'Food_Set_Status',
        ]));

        return back()->with('success', 'Food set added successfully.');
    }

    public function updateFoodSet(Request $request, $id)
    {
        $request->validate([
            'Food_Set_Name'      => 'required|string|max:255',
            'Food_Set_Price'     => 'required|numeric|min:0',
            'Food_Set_Purpose'   => 'required|string|max:50',
            'Food_Set_Meal_Time' => 'required|string|in:breakfast,am_snack,lunch,pm_snack,dinner',
            'Food_Set_Status'    => 'required|string|in:available,unavailable',
        ]);

        FoodSet::findOrFail($id)->update($request->only([
            'Food_Set_Name', 'Food_Set_Price',
            'Food_Set_Purpose', 'Food_Set_Meal_Time', 'Food_Set_Status',
        ]));

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
        $foods    = Food::orderBy('Food_Category')->orderBy('Food_Name')->get();
        $foodSets = FoodSet::orderBy('Food_Set_Meal_Time')->orderBy('Food_Set_Name')->get();

        return view('employee.food', compact('foods', 'foodSets'));
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

    /** Food sets grouped by meal time */
    public function getFoodSetsAjax()
    {
        $sets = FoodSet::where('Food_Set_Status', 'available')
            ->orderBy('Food_Set_Meal_Time')
            ->orderBy('Food_Set_Name')
            ->get()
            ->groupBy(fn($s) => strtolower($s->Food_Set_Meal_Time))
            ->map(fn($group) => $group->map(fn($s) => [
                'Food_Set_ID'       => $s->Food_Set_ID,
                'Food_Set_Name'     => $s->Food_Set_Name,
                'Food_Set_Price'    => $s->Food_Set_Price,
                'Food_Set_Purpose'  => $s->Food_Set_Purpose,
                'Food_Set_Meal_Time'=> $s->Food_Set_Meal_Time,
            ])->values());

        return response()->json($sets);
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
