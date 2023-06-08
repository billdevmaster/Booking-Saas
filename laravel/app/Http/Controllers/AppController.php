<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Apps;

class AppController extends Controller
{
  private $env_text = '
    APP_NAME=
    APP_ENV=local
    APP_KEY=
    APP_DEBUG=true
    APP_URL=
    
    LOG_CHANNEL=stack
    LOG_LEVEL=debug
    
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=
    DB_USERNAME=
    DB_PASSWORD=
    
    BROADCAST_DRIVER=log
    CACHE_DRIVER=file
    FILESYSTEM_DRIVER=local
    QUEUE_CONNECTION=sync
    SESSION_DRIVER=file
    SESSION_LIFETIME=120
    
    MEMCACHED_HOST=127.0.0.1
    
    REDIS_HOST=127.0.0.1
    REDIS_PASSWORD=null
    REDIS_PORT=6379
    
    MAIL_DRIVER=smtp
    MAIL_HOST=smtp.gmail.com
    MAIL_PORT=587
    MAIL_USERNAME=bookidee22@gmail.com
    MAIL_PASSWORD=
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS=bookidee22@gmail.com
    MAIL_FROM_NAME=
    
    AWS_ACCESS_KEY_ID=
    AWS_SECRET_ACCESS_KEY=
    AWS_DEFAULT_REGION=us-east-1
    AWS_BUCKET=
    AWS_USE_PATH_STYLE_ENDPOINT=false
    
    PUSHER_APP_ID=
    PUSHER_APP_KEY=
    PUSHER_APP_SECRET=
    PUSHER_APP_CLUSTER=mt1
    
    MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
    MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
  ';
  private $new_app_base_url = "https://bookid.ee/";
  private $git_user = "https://github.com/billdevmaster/";
  private $git_repo_name = "booking_demo";
  
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
    $filename = "demo.sql";
    $lines = file($filename);

    // check valid inputs
    $ret = $this->check_validation($request->input('app_data'));
    if (!$ret) {
      return response()->json([
        'status' => 'error',
        'message' => 'the url or name is already registered'
      ], 503);
    }
    
    // install project
    $ret = $this->install_project($request->input('app_data'));
    if (!$ret) {
      return response()->json([
        'status' => 'error',
        'message' => 'folder name is already exist'
      ], 503);
    }

    // database migrate
    $this->migrate_database($request->input('app_data')['DB_DATABASE'], $request->input('app_data')['DB_USERNAME'], $request->input('app_data')['DB_PASSWORD'], $lines);

    // save apps
    $this->save_app($request->input('app_data'));

    return response()->json( ['status' => 'success'] );
  }

  private function check_validation($app_data) {
    // check if name is exist
    $app = Apps::where("name", $app_data['APP_NAME'])->orwhere("url", $this->new_app_base_url . $app_data['folder_name'])->first();
    if ($app != null) {
      return false;
    } else {
      return true;
    }
  }

  private function migrate_database($database, $database_user, $database_pwd, $lines) {
    // MySQL host
    $mysql_host = 'localhost';
    // MySQL username
    $mysql_username = $database_user;
    // MySQL password
    $mysql_password = $database_pwd;
    // Database name
    $mysql_database = $database;

    // Connect to MySQL server
    $con = mysqli_connect($mysql_host, $mysql_username, $mysql_password) or die('Error connecting to MySQL server: ' . mysql_error());
    // Select database
    mysqli_select_db($con, $mysql_database) or die('Error selecting MySQL database: ' . mysql_error());

    // Temporary variable, used to store current query
    $templine = '';
    // Loop through each line
    foreach ($lines as $line)
    {
      // Skip it if it's a comment
      if (substr($line, 0, 2) == '--' || $line == '')
          continue;

      // Add this line to the current segment
      $templine .= $line;
      // If it has a semicolon at the end, it's the end of the query
      if (substr(trim($line), -1, 1) == ';')
      {
          // Perform the query
          mysqli_query($con, $templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysql_error() . '<br /><br />');
          // Reset temp variable to empty
          $templine = '';
      }
    }
  }

  private function install_project($app_data) {
    chdir(env('NEW_APP_DIR'));
    // check if there is already dir.
    if (is_dir($app_data['folder_name'])){
      return false;
    }
    exec("git clone " . $this->git_user . $this->git_repo_name);
    rename($this->git_repo_name, $app_data['folder_name']);
    chdir($app_data['folder_name']);

    // create .env file
    $envfile = fopen(".env", "w");
    
    $env_text_arr = explode(PHP_EOL, $this->env_text);
    for($i = 0; $i < count($env_text_arr); $i++) {
      $env_arr = explode("=", $env_text_arr[$i]);
      if (trim($env_arr[0]) == "APP_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $app_data['APP_NAME'];
      } else if (trim($env_arr[0]) == "APP_URL") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $this->new_app_base_url . $app_data['folder_name'];
      } else if (trim($env_arr[0]) == "DB_DATABASE") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $app_data['DB_DATABASE'];
      } else if (trim($env_arr[0]) == "DB_USERNAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $app_data['DB_USERNAME'];
      } else if (trim($env_arr[0]) == "DB_PASSWORD") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $app_data['DB_PASSWORD'];
      } else if (trim($env_arr[0]) == "MAIL_PASSWORD") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . env('MAIL_SMTP_PWD');
      } else if (trim($env_arr[0]) == "MAIL_FROM_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $app_data['APP_NAME'];
      } else if (trim($env_arr[0]) == "APP_KEY") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . env('NEW_APP_KEY');
      } else {
        $env_text_arr[$i] = trim($env_text_arr[$i]);
      }
    }
    $this->env_text = implode(PHP_EOL, $env_text_arr);
    fwrite($envfile, $this->env_text);

    // composer install
    exec("composer install");
    return true;
  }

  private function save_app($app_data) {
    $app = new Apps();
    $app->name = $app_data['APP_NAME'];
    $app->url = $this->new_app_base_url . $app_data['folder_name'];
    $app->DB_DATABASE = $app_data['DB_DATABASE'];
    $app->DB_USERNAME = $app_data['DB_USERNAME'];
    $app->DB_PASSWORD = $app_data['DB_PASSWORD'];
    $app->save();
  }
}