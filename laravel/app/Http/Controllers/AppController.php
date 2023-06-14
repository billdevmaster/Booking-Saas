<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Apps;
use App\Models\Plans;
use App\Models\AppPlans;
use DataTables;
use Stripe\Stripe;
use Stripe\Charge;
use DateTime;
use DateInterval;

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
    var_dump($ret);
    return;
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
    $app_id = $this->save_app($request->input('app_data'), 0);

    // add trial duration for 1months
    $start_date = date("Y-m-d");
    $end_date = $this->get_end_date($start_date, 1, "Month");
    $app_plan = new AppPlans();
    $app_plan->app_id = $app_id;
    $app_plan->plan_id = 0;
    $app_plan->start_date = $start_date;
    $app_plan->end_date = $end_date;
    $app_plan->save();

    return response()->json( ['status' => 'success'] );
  }

  private function check_validation($app_data) {
    // check if name is exist
    $app = Apps::whereNull('deleted_at')
      ->where(function($query1) use($app_data) {
        $query1->where("APP_NAME", $app_data['APP_NAME'])->orwhere("url", $this->new_app_base_url . $app_data['folder_name']);
      })->first();
    
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

  private function update_project($old_app_data, $new_app_data) {
    chdir(env('NEW_APP_DIR'));
    if ($old_app_data->folder_name != $new_app_data['folder_name']) {
      rename($old_app_data->folder_name, $new_app_data['folder_name']);
    }
    chdir($new_app_data['folder_name']);
    $this->env_text = file_get_contents('.env', true);
    $envfile = fopen(".env", "w");
    
    $env_text_arr = explode(PHP_EOL, $this->env_text);
    for($i = 0; $i < count($env_text_arr); $i++) {
      $env_arr = explode("=", $env_text_arr[$i]);
      if (trim($env_arr[0]) == "APP_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $new_app_data['APP_NAME'];
      } else if (trim($env_arr[0]) == "APP_URL") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $this->new_app_base_url . $new_app_data['folder_name'];
      } else if (trim($env_arr[0]) == "DB_DATABASE") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $new_app_data['DB_DATABASE'];
      } else if (trim($env_arr[0]) == "DB_USERNAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $new_app_data['DB_USERNAME'];
      } else if (trim($env_arr[0]) == "DB_PASSWORD") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $new_app_data['DB_PASSWORD'];
      } else if (trim($env_arr[0]) == "MAIL_FROM_NAME") {
        $env_text_arr[$i] = trim($env_arr[0]) . '=' . $new_app_data['APP_NAME'];
      } else {
        $env_text_arr[$i] = trim($env_text_arr[$i]);
      }
    }
    $this->env_text = implode(PHP_EOL, $env_text_arr);
    fwrite($envfile, $this->env_text);
  }

  private function save_app($app_data, $id) {
    $app = Apps::find($id);
    if ($app == null) {
      $app = new Apps();
    }
    $app->APP_NAME = $app_data['APP_NAME'];
    $app->folder_name = $app_data['folder_name'];
    $app->url = $this->new_app_base_url . $app_data['folder_name'];
    $app->DB_DATABASE = $app_data['DB_DATABASE'];
    $app->DB_USERNAME = $app_data['DB_USERNAME'];
    $app->DB_PASSWORD = $app_data['DB_PASSWORD'];
    if ($app_data['user_id']) {
      $app->user_id = $app_data['user_id'];
    }
    $app->save();
    return $app->id;
  }

  public function get_apps(Request $request) {
    $user = auth()->user();
    $roles = explode(",", $user->menuroles);
    if (in_array("admin", $roles)) {
      $apps = Apps::select('apps.id', 'apps.APP_NAME', 'apps.folder_name', 'apps.DB_DATABASE', 'apps.DB_USERNAME')
        ->whereNull('deleted_at')
        ->get();
    } else {
      $apps = Apps::select('apps.id', 'apps.APP_NAME', 'apps.folder_name', 'apps.DB_DATABASE', 'apps.DB_USERNAME')
        ->where("user_id", $user->id)
        ->whereNull('deleted_at')
        ->get();
    }
    return response()->json( compact('apps') );
  }

  public function get_app(Request $request) {
    $app = Apps::find($request->input('id'));
    return response()->json( compact('app') );
  }

  public function update(Request $request) {
    $filename = "demo.sql";
    $lines = file($filename);

    $app = Apps::find($request->input('id'));
    $this->check_validation($request->input('app_data'));
    $this->update_project($app, $request->input('app_data'));
    if ($app->DB_DATABASE != $request->input('app_data')['DB_DATABASE']) {
      $this->migrate_database($request->input('app_data')['DB_DATABASE'], $request->input('app_data')['DB_USERNAME'], $request->input('app_data')['DB_PASSWORD'], $lines);
    }
    $this->save_app($request->input('app_data'), $request->input('id'));
    return response()->json( ['status' => 'success'] );
  }

  public function get_clients(Request $request) {
    $users = DB::table('users')
    ->select('users.id', 'users.email')
    ->where('menuroles', 'user')
    ->whereNull('deleted_at')
    ->get();
    return response()->json( compact('users') );
  }

  public function process_payment(Request $request)
  {
    $stripeSecretKey = env('STRIPE_SECRET');
    Stripe::setApiKey($stripeSecretKey);

    $token = $request->input('token');
    $plan = Plans::find($request->input('plan_id'));
    $amount = $plan->price * $request->input('payMonths');
    try {
      $charge = Charge::create([
          'amount' => $amount * 100, // Replace with your desired amount in cents
          'currency' => 'usd',
          'description' => 'Pay for Booking app',
          'source' => $token['id'],
      ]);
      $today = date('Y-m-d');
      $start_date = $today;
      // get app's allowed period.
      $app_plans = AppPlans::where('end_date', '>=', $today)->first();

      if ($app_plans != null) {
        $start_date = $app_plans->end_date;
      }

      $end_date = $this->get_end_date($start_date, $request->input('payMonths'), $plan->billing_interval);
      $new_app_plan = new AppPlans();
      $new_app_plan->app_id = $request->input('app_id');
      $new_app_plan->plan_id = $request->input('plan_id');
      $new_app_plan->start_date = $start_date;
      $new_app_plan->end_date = $end_date;
      $new_app_plan->status = 'START';
      $new_app_plan->save();

      // Handle the successful payment
      // You can store the payment details in your database or perform any other actions here
      return response()->json([
          'success' => true,
          'message' => 'Payment processed successfully.',
      ]);
    } catch (\Exception $e) {
      // Handle the payment error
      return response()->json([
          'success' => false,
          'message' => 'Payment failed. Please try again.',
          'error' => $e->getMessage(),
      ]);
    }
  }

  private function get_end_date($start_date, $interval, $type) {
    $pass_type = $type == 'Month' ? 'M' : 'Y';
    // create a DateTime object from the start date
    $dt = new DateTime($start_date);

    // add x months to the DateTime object
    $dt->add(new DateInterval('P' . $interval . $pass_type));

    // get the end date in the desired format (e.g. Y-m-d)
    $end_date = $dt->format('Y-m-d');

    // output the end date
    return $end_date;
  }

  public function get_app_end_date(Request $request) {
    $app_id = $request->input('app_id');
    $app_plans = AppPlans::where('app_id', $app_id)->orderBy('created_at', 'DESC')->get();
    if (count($app_plans) == 0) {
      return response()->json(['end_date' => null]);
    } else {
      return response()->json(['end_date' => $app_plans[0]->end_date]);
    }
  }

  public function delete(Request $request) {
    $app = Apps::find($request->input('app_id'));
    $folder_name = $app->folder_name;
    chdir(env('NEW_APP_DIR'));
    if (is_dir($folder_name)) {
      $this->removeDirectory($folder_name);
    }
    $app->deleted_at = date("Y-m-d H:i:s");
    $app->save();
    return response()->json( array('success' => true) );
  }

  private function removeDirectory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir."/".$object) == "dir") {
                    $this->removeDirectory($dir."/".$object);
                } else {
                    unlink($dir."/".$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
}