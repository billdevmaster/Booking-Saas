<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
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

  public function create(Request $request)
  {
    chdir('../../../');
    exec("git clone https://github.com/billdevmaster/my-history");
    rename('my-history', $request->input('app_data')['APP_NAME']);
    var_dump($request->input('app_data'));
  }
}