<?php

namespace App\Services;

use App\FirebaseToken;
use App\User;
use Carbon\Carbon;

class ScheduleCustomerBirthdayNotificationService {

    public function sendCustomerBirthdayNotification() {
        $userIds = $this->getCustomerBirthdayShop();
        $notificationMsg = "ပျော်ရွှင်စရာမွေးနေ့လေးဖြစ်ပါစေ။ အိပ်မက်တွေ အကောင်အထည်ဖော်နိုင်ပြီး ဘဝခရီးလမ်းမှာ အောင်မြင်မှုတွေရရှိပြီး  ချစ်ရသောသူတွေနဲ့ အေးအေးချမ်းချမ်း ပျော်ပျော်ရွှင်ရွှင်ဖြတ်သန်းသွားနိုင်ပါစေလို့ NTR Auto Parts မိသားစုမှဆုမွန်ကောင်းတောင်း‌ပေးလိုက်ပါတယ်.....";
        $notificationService = new NotificationService("Happy Birthday", $notificationMsg, "customer_birthday", 1);
        $firebase_tokens = FirebaseToken::whereIn('user_id', $userIds)->orderBy('id', 'DESC')->pluck('firebase_token');
        $notificationService->sendScheduleNotiForCustomerBirthdayAndShopAnni($firebase_tokens);
    }

    public function getCustomerBirthdayShop() {
        $users = User::with('ShopRelation')->whereMonth('date_of_birth', Carbon::now()->month)
            ->whereDay('date_of_birth', Carbon::now()->day)->where('shop_id', '!=', null)
            ->where('active_status', 1)
            ->where('date_of_birth', '!=', null)->pluck('id');
        return $users;
    }

}