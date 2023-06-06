<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{
  private $env_text = "";
  private $git_repo = "https://github.com/billdevmaster/my-history";
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
    rename('my-history', $request->input('app_data')['APP_NAME']);
    // copy('PATTERN/index.php', $Username.'/index.php');
    var_dump($request->input('app_data'));
  }
}