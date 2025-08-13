<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    //
    protected SubscriptionService $subscriptionServices;
    public function __construct(SubscriptionService $subscriptionServices)
    {
        $this->subscriptionServices = $subscriptionServices;
    }

    public function add_subscription(Request $request){
        return $this->subscriptionServices->add_subscription($request);
    }
}
