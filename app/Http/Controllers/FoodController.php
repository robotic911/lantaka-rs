<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Food;

class FoodController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|string|in:rice,set_viand,sidedish,drinks,desserts,other_viand,snacks',
            'price' => 'required|numeric|min:0',
            'status' => 'required|string|in:available,unavailable',
        ]);

        // Map HTML form names to your Database column names!
        Food::create([
            'food_name' => $request->name,
            'food_category' => $request->type,
            'food_price' => $request->price,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Food added successfully!');
    }

    public function showFoodOptions(Request $request)
    {
        // Notice we changed groupBy to 'food_category'
        $foods = Food::where('status', 'available')
                     ->get()
                     ->groupBy('food_category'); 

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
            'food_name' => 'required|string|max:255',
            'status' => 'required|in:available,unavailable',
            'type' => 'required|in:breakfast,snack,lunch,dinner',
            'food_price' => 'required|numeric|min:0',
        ]);

        $food = Food::findOrFail($id);

        $food->update([
            'food_name' => $request->food_name,
            'food_category' => $request->type,
            'food_price' => $request->food_price,
            'status' => $request->status,
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
        $foods = Food::where('status', 'available')
            ->orderBy('food_category')
            ->orderBy('food_name')
            ->get()
            ->groupBy(function ($food) {
                return strtolower($food->food_category);
            })
            ->map(function ($categoryFoods) {
                return $categoryFoods->map(function ($food) {
                    return [
                        'food_id' => $food->food_id,
                        'food_name' => $food->food_name,
                        'food_category' => strtolower($food->food_category),
                        'food_price' => $food->food_price,
                        'status' => $food->status,
                    ];
                })->values();
            });
    
        return response()->json($foods);
    }
    

}
