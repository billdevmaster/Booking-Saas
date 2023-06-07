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
      } else {
        $env_text_arr[$i] = trim($env_text_arr[$i]);
      }
    }
    $this->env_text = implode(PHP_EOL, $env_text_arr);
    fwrite($envfile, $this->env_text);

    // database migration
    exec("composer install");
    exec("php artisan migration");
    exec("php artisan key:generate");
    exec("php artisan db:seed");
    return response()->json( ['status' => 'success'] );
    // copy('PATTERN/index.php', $Username.'/index.php');
    // var_dump($request->input('app_data'));
  }
}