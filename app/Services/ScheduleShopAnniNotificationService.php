<?php

namespace App\Services;

use App\FirebaseToken;
use App\Shop;
use App\User;
use Carbon\Carbon;

class ScheduleShopAnniNotificationService {

    public function sendShopAnniNotification() {
        $shops = $this->getShopAnniversary();
        foreach($shops as $shop) {
            $notificationMsg = "ဒီနေ့ " . Carbon::now()->format('d-m-Y') . " ရက်နေ့တွင်တော့ " . $shop->name . " နှင့် ကျွန်တော်တို့  လက်တွဲပူးပေါင်းခဲ့သည်မှာ (" . Carbon::parse($shop->opening_day)->diffInYears(Carbon::now())  . ") နှစ်တိုင်တိုင်ရှိခဲ့ပြီဖြစ်ပြီး ဤ (" . Carbon::parse($shop->opening_day)->diffInYears(Carbon::now()) .") နှစ်တာကာလအတွင်း " . $shop->name .  " ဆိုင်၏ လုပ်ငန်းကြီးပွားတိုးတတ်စေမှုအတွက် အကျိုးတူပူးပေါင်းခွင့်ရရှိခဲ့သည့်အတွက် ဂုဏ်ယူဝမ်းမြောက်ကျေနပ်မိပါသည်ခင်ဗျာ....";           
            $notificationService = new NotificationService("Anniversary", $notificationMsg, "customer_birthday", 1);
            $userIds = User::where('shop_id', $shop->id)->pluck('id');
            $firebase_tokens = FirebaseToken::whereIn('user_id', $userIds)->orderBy('id', 'DESC')->pluck('firebase_token');
            $notificationService->sendScheduleNotiForCustomerBirthdayAndShopAnni($firebase_tokens);
        }
    }

    public function getShopAnniversary() {
        $shops = \App\Shop::with('warehouse')->whereMonth('opening_day', \Carbon\Carbon::now()->month)
            ->whereDay('opening_day', \Carbon\Carbon::now()->day)->get();
        return $shops;
    }


}