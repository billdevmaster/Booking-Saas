<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe;
use App\Models\AppSubscription;
use App\Models\Apps;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function get_subscriptions(Request $request) {
        $prices = \Stripe\Price::all(['limit' => 100,'expand' => ['data.product'], 'active' => true]);
        $subscriptions = [];
        foreach($prices->data as $price) {
            if ($price['product']['active']) {
                array_push($subscriptions, $price);
            }
        }
        return response()->json($subscriptions);
    }

    public function create_checkout_session(Request $request) {
        $YOUR_DOMAIN = $request->input('domain');
        $app = Apps::where("url", $request->input('domain'))->first();
        $app_subscription = AppSubscription::where('app_id', $app->id)->whereNull('deleted_at')->first();
        if ($app_subscription != null) {
            return response()->redirectTo($request->input('domain') . '/admin/subscribe?error=App is Subscribed Already');
        }
        
        try {
            $checkout_session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                  'price' => $request->input('price_id'),
                  'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => env('APP_URL') . '/api/subscribe/success?session_id={CHECKOUT_SESSION_ID}&app_url=' . $YOUR_DOMAIN,
                'cancel_url' => $YOUR_DOMAIN . '/admin/subscribe',
                'subscription_data' => [
                    'trial_period_days' => 1,
                ],
            ]);
            return response()->redirectTo($checkout_session->url);
            // var_dump($checkout_session->url);
            // exit();
            // header("HTTP/1.1 303 See Other");
            // header("Location: " . $checkout_session->url);
        } catch (Error $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function success(Request $request) {
        try {
            $session = \Stripe\Checkout\Session::retrieve($request->input('session_id'));
            $subscriptionId = $session->subscription;
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $app = Apps::where("url", $request->input('app_url'))->first();
            $app_subscription = AppSubscription::where('subscription_id', $subscription->id)->whereNull('deleted_at')->first();
            if ($app_subscription != null) {
                return response()->redirectTo($request->input('app_url') . '/admin/subscribe');
            }
            $app_subscription = AppSubscription::where('app_id', $app->id)->whereNull('deleted_at')->first();
            if ($app_subscription != null) {
                return response()->redirectTo($request->input('app_url') . '/admin/subscribe');
            }
            $app_subscription = new AppSubscription();
            $app_subscription->app_id = $app->id;
            $app_subscription->subscription_id = $subscriptionId;
            $app_subscription->customer_id = $subscription->customer;
            $app_subscription->product_id = $subscription->plan->product;
            $app_subscription->price_id = $subscription->plan->id;
            $app_subscription->status = $subscription->status;
            $app_subscription->save();
            return response()->redirectTo($request->input('app_url') . '/admin/subscribe');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle any API errors
            echo 'Error: ' . $e->getMessage();
        }
    }

    public function create_portal_session(Request $request) {
        try {
            $return_url = $request->input('app_url') . '/admin/subscribe';
          
            // Authenticate your user.
            $session = \Stripe\BillingPortal\Session::create([
              'customer' => $request->input('customer_id'),
              'return_url' => $return_url,
            ]);
            return response()->redirectTo($session->url);
          } catch (Error $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
          }
    }

    public function webhook(Request $request) {
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = 'whsec_5eac192ba9218a65415ea5fd73d50c3badf2255ba78e555372924adcb978fe17';

        $payload = @file_get_contents('php://input');
        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(
              json_decode($payload, true)
            );
          } catch(\UnexpectedValueException $e) {
            // Invalid payload
            echo '⚠️  Webhook error while parsing basic request.';
            http_response_code(400);
            exit();
          } catch(\Stripe\Exception\SignatureVerificationException $e) {
              // Invalid signature
              http_response_code(400);
              exit();
          }
          // Handle the event
          switch ($event->type) {
            case 'customer.subscription.trial_will_end':
              $subscription = $event->data->object; // contains a \Stripe\Subscription
              // Then define and call a method to handle the trial ending.
              $this->handleSubscriptionUpdated($subscription);
              break;
            case 'customer.subscription.paused':
                $subscription = $event->data->object; // contains a \Stripe\Subscription
                // Then define and call a method to handle the subscription being created.
                $this->handleSubscriptionUpdated($subscription);
                break;
            case 'customer.subscription.resumed':
                $subscription = $event->data->object; // contains a \Stripe\Subscription
                // Then define and call a method to handle the subscription being created.
                $this->handleSubscriptionUpdated($subscription);
                break;
            case 'customer.subscription.created':
              $subscription = $event->data->object; // contains a \Stripe\Subscription
              // Then define and call a method to handle the subscription being created.
              $this->handleSubscriptionUpdated($subscription);
              break;
            case 'customer.subscription.deleted':
              $subscription = $event->data->object; // contains a \Stripe\Subscription
              // Then define and call a method to handle the subscription being deleted.
              $this->handleSubscriptionDeleted($subscription);
              break;
            case 'customer.subscription.updated':
              $subscription = $event->data->object; // contains a \Stripe\Subscription
              // Then define and call a method to handle the subscription being updated.
              $newfile = fopen("tset.json", 'w');
              fwrite($newfile, json_encode($subscription));
              fclose($newfile);
              $this->handleSubscriptionUpdated($subscription);
              break;
            default:
              // Unexpected event type
              echo 'Received unknown event type';
          }
    }

    private function handleSubscriptionUpdated($subscription) {
        $app_subscription = AppSubscription::where("subscription_id", $subscription->id)->whereNull('deleted_at')->first();
        if ($app_subscription) {
            $app_subscription->customer_id = $subscription->customer;
            $app_subscription->product_id = $subscription->plan->product;
            $app_subscription->price_id = $subscription->plan->id;
            $app_subscription->status = $subscription->status;
            $app_subscription->save();
        }
    }

    private function handleSubscriptionDeleted($subscription) {
        $app_subscription = AppSubscription::where("subscription_id", $subscription->id)->first();
        if ($app_subscription) {
            $app_subscription->deleted_at = date("Y-m-d H:i:s");
            $app_subscription->save();
        }
    }
}
