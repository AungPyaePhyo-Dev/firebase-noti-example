<?php

namespace App\Services;

use App\FirebaseToken;
use App\User;
use Carbon\Carbon;

class ScheduleCustomerBirthdayNotificationService {

    public function sendCustomerBirthdayNotification() {
        $userIds = $this->getCustomerBirthdayShop();
        $notificationMsg = "ပျော်ရွှင်စရာမွေးနေ့လေးဖြစ်ပါစေ။ ";
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