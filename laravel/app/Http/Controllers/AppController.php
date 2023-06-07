<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{
  private $env_text = "";
  private $git_repo = "https://github.com/billdevmaster/booking_demo";
  
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
    chdir(env('NEW_APP_DIR'));
    exec("git clone " . $this->git_repo);
    rename('booking_demo', $request->input('app_data')['APP_NAME']);
    // copy('PATTERN/index.php', $Username.'/index.php');
    var_dump($request->input('app_data'));
  }
}