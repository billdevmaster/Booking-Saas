<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plans;

class PlansController extends Controller
{
    /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth:api');
  }

  public function get_plans() {
    $plans = Plans::whereNull('deleted_at')->get();
    return response()->json( compact('plans') );
  }

  public function create(Request $request) {
    $plan = Plans::find($request->input('id'));
    if ($plan == null) {
        $plan = new Plans();
    }
    $plan->name = $request->input('plan_data')['name'];
    $plan->description = $request->input('plan_data')['description'];
    $plan->duration = $request->input('plan_data')['duration'];
    $plan->price = $request->input('plan_data')['price'];
    $plan->save();
    return response()->json( ['status' => 'success'] );
  }

  public function get_plan(Request $request) {
    $plan = Plans::find($request->input('id'));
    return response()->json( compact('plan') );
  }
}
