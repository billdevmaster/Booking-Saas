<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
    
    chdir(env('NEW_APP_DIR'));
    exec("git clone " . $this->git_user . $this->git_repo_name);
    rename($this->git_repo_name, $request->input('app_data')['folder_name']);
    chdir($request->input('app_data')['folder_name']);

    // create .env file
    $envfile = fopen(".env", "w");
    
    $env_text_arr = explode(PHP_EOL, $this->env_text);
    for($i = 0; $i < count($env_text_arr); $i++) {
      $env_arr = explode("=", $env_text_arr[$i]);
      if (trim($env_arr[0]) == "APP_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $request->input('app_data')['APP_NAME'];
      } else if (trim($env_arr[0]) == "APP_URL") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $this->new_app_base_url . $request->input('app_data')['folder_name'];
      } else if (trim($env_arr[0]) == "DB_DATABASE") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $request->input('app_data')['DB_DATABASE'];
      } else if (trim($env_arr[0]) == "DB_USERNAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $request->input('app_data')['DB_USERNAME'];
      } else if (trim($env_arr[0]) == "DB_PASSWORD") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $request->input('app_data')['DB_PASSWORD'];
      } else if (trim($env_arr[0]) == "MAIL_PASSWORD") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . env('MAIL_SMTP_PWD');
      } else if (trim($env_arr[0]) == "MAIL_FROM_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $request->input('app_data')['APP_NAME'];
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
    // database migrate
    migrate_database($request->input('app_data')['DB_DATABASE'], $request->input('app_data')['DB_USERNAME'], $request->input('app_data')['DB_PASSWORD']);

    return response()->json( ['status' => 'success'] );
  }

  private function migrate_database($database, $database_user, $database_pwd) {
    $filename = 'demo.sql';
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
    // Read in entire file
    $lines = file($filename);
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
}