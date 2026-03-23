<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Food;

class FoodController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'Food_Name' => 'required|string|max:50',
            'Food_Category' => 'required|string|in:rice,set_viand,sidedish,drinks,desserts,other_viand,snacks',
            'Food_Price' => 'required|numeric|min:0',
            'Food_Status' => 'required|string|in:available,unavailable',
        ]);

        // Map HTML form names to your Database column names!
        Food::create([
            'Food_Name' => $request->Food_Name,
            'Food_Category' => $request->Food_Category,
            'Food_Price' => $request->Food_Price,
            'Food_Status' => $request->Food_Status,
        ]);

        return redirect()->back()->with('success', 'Food added successfully!');
    }

    public function showFoodOptions(Request $request)
    {
        // Group by PascalCase column 'Food_Category'
        $foods = Food::where('Food_Status', 'available')
                     ->get()
                     ->groupBy('Food_Category');

        return view('client_food_options', [
            'foods' => $foods,
        ]);
    }

    public function showEmployeeFood(Request $request)
    {
        $foods = Food::all();
        return view('employee_food', [
            'foods' => $foods,
        ]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'Food_Name' => 'required|string|max:255',
            'Food_Status' => 'required|in:available,unavailable',
            'Food_Category' => 'required|string|in:rice,set_viand,sidedish,drinks,desserts,other_viand,snacks',
            'Food_Price' => 'required|numeric|min:0',
        ]);

        $food = Food::findOrFail($id);

        $food->update([
            'Food_Name' => $request->Food_Name,
            'Food_Category' => $request->Food_Category,
            'Food_Price' => $request->Food_Price,
            'Food_Status' => $request->Food_Status,
        ]);

        return back()->with('success', 'Food updated successfully.');
    }

    public function destroy($id)
    {
        $food = Food::findOrFail($id);

        $food->delete();

        return back()->with('success','Food deleted successfully');
}


    public function getFoodsAjax()
    {
        $foods = Food::where('Food_Status', 'available')
            ->orderBy('Food_Category')
            ->orderBy('Food_Name')
            ->get()
            ->groupBy(function ($food) {
                return strtolower($food->Food_Category);
            })
            ->map(function ($categoryFoods) {
                return $categoryFoods->map(function ($food) {
                    return [
                        'Food_ID'       => $food->Food_ID,
                        'Food_Name'     => $food->Food_Name,
                        'Food_Category' => strtolower($food->Food_Category),
                        'Food_Price'    => $food->Food_Price,
                        'Food_Status'   => $food->Food_Status,
                    ];
                })->values();
            });

        return response()->json($foods);
    }


}
