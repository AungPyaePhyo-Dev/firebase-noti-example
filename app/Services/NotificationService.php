<?php

namespace App\Services;

use Log;
use App\User;
use Exception;
use App\Warehouse;
use App\FirebaseToken;
use LaravelFCM\Facades\FCM;
use App\LogisticFirebaseToken;
use App\OrderNotificationHistory;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use App\FcmNotifications\Messages\CustomPayloadNotificationBuilder;

class NotificationService {

    public $title, $body, $category,
        $partId = null, $brandId = null,
        $itemId = null, $badges = 1,
        $imageUrl = null,$orderId= null,
        $order_status=null,$clickAction = null;

    public function __construct( $title, $body, $category, $badges) {
        $this->title = $title;
        $this->body = $body;
        $this->category = $category;
        $this->badges = $badges;
    }

    public function sendGeneralNotification($firebase_token)
    {
        if($this->imageUrl) {
            $this->imageUrl = url('/') . "/ntr_images/notification/" .  $this->imageUrl;
        }

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setImage($this->imageUrl)
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'order_id' => $this->orderId,
            'order_status' => $this->order_status,
            'title' => $this->title,
            'body' => $this->body,
            'firebase_token' => "-",
            'image_url' => $this->imageUrl,
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
            'type' => $this->category,
            'id' => $this->itemId,
            'item_id' => $this->itemId,
            'badges' => $this->badges,
            'brand_id' => $this->brandId,
            'part_id' => $this->partId,
            'sound' => "notisound1.mp3"
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        try {
            $downstreamResponse = FCM::sendTo($firebase_token, $option, $notification, $data);
            OrderNotificationHistory::create([
                'order_id' => $this->orderId,
                'title' => $this->title,
                'notification_message' => $this->body,
                'number_of_token_success' => $downstreamResponse->numberSuccess(),
                'number_of_token_failed' => $downstreamResponse->numberFailure(),
            ]);

            $this->deleteTokens($downstreamResponse->tokensToDelete());

        } catch (Exception $e) {
            // dd($e->getMessage());
            OrderNotificationHistory::create([
                'order_id' => $this->orderId,
                'title' => $this->title,
                'notification_message' => $this->body,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function sendNotiForLogisticReport($firebase_token)
    {

        if($this->imageUrl) {
            $this->imageUrl = url('/') . "/ntr_images/notification/" .  $this->imageUrl;
        }

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setImage($this->imageUrl)
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'order_id' => $this->orderId,
            'badges' => $this->badges,
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $downstreamResponse = FCM::sendTo($firebase_token, $option, $notification);
    }

    function getDeliveryFirebaseTokenByWarehouseId($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        $tokens = [];
        if($warehouse) {
            $deliIds = $warehouse->logisticWorkers()->pluck('delivery_man.id');
            $tokens = LogisticFirebaseToken::whereIn('user_id', $deliIds)->pluck('firebase_token')->toArray();
        }
        return $tokens;
    }

    function getStockTransferFirebaseTokenByWarehouseId($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        $tokens = [];
        if($warehouse) {
            $deliIds = $warehouse->logisticWorkers()->role('stock')->pluck('delivery_man.id');
            $tokens = LogisticFirebaseToken::whereIn('user_id', $deliIds)->pluck('firebase_token')->toArray();
        }
        return $tokens;
    }

    private function deleteTokens($tokensToDelete)
    {
        FirebaseToken::whereIn('firebase_token', $tokensToDelete)->delete();
        LogisticFirebaseToken::whereIn('firebase_token', $tokensToDelete)->delete();
    }

    public function sendNotiToShopForCashReceipt($shopId)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'title' => $this->title,
            'body' => $this->body,
            'firebase_token' => "-",
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $firebase_tokens = $this->getTokenByShopId($shopId);

        try {
            if($firebase_tokens) {
                $downstreamResponse = FCM::sendTo($firebase_tokens->toArray(), $option, $notification, $data);
                OrderNotificationHistory::create([
                    'title' => $this->title,
                    'notification_message' => $this->body,
                    'number_of_token_success' => $downstreamResponse->numberSuccess(),
                    'number_of_token_failed' => $downstreamResponse->numberFailure(),
                ]);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTokenByShopId($shopId)
    {
        $userIds = User::where('shop_id', $shopId)->pluck('id');
        $tokens = FirebaseToken::whereIn('user_id', $userIds)->orderBy('id', 'DESC')->pluck('firebase_token');
        return $tokens;
    }

    public function sendAdsNotification($firebase_token)
    {
        if($this->imageUrl) {
            $this->imageUrl = url('/') . "/ntr_images/notification/" .  $this->imageUrl;
        }

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setImage($this->imageUrl)
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'order_id' => $this->orderId,
            'order_status' => $this->order_status,
            'title' => $this->title,
            'body' => $this->body,
            'firebase_token' => "-",
            'image_url' => $this->imageUrl,
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
            'type' => $this->category,
            'id' => $this->itemId,
            'item_id' => $this->itemId,
            'badges' => $this->badges,
            'brand_id' => $this->brandId,
            'part_id' => $this->partId,
            'sound' => "notisound1.mp3"
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        try {
            $downstreamResponse = FCM::sendTo($firebase_token, $option, $notification, $data);
            $this->deleteTokens($downstreamResponse->tokensToDelete());
        } catch (Exception $e) {
            OrderNotificationHistory::create([
                'order_id' => $this->orderId,
                'title' => $this->title,
                'notification_message' => $this->body,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    //
    // Schedule Notifcation for Credit Limit
    //
    public function sendScheduleNotiForCreditLimit($tokens)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'title' => $this->title,
            'body' => $this->body,
            'firebase_token' => "-",
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        try {
            if($tokens) {
                $downstreamResponse = FCM::sendTo($tokens->toArray(), $option, $notification, $data);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    //
    // Schedule Notifcation for Customer Birthday And Shop Anniversary
    //
    public function sendScheduleNotiForCustomerBirthdayAndShopAnni($tokens)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(86400); // one day in second

        $notificationBuilder = new CustomPayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->body)
            ->setSound('notisound1')
            ->setClickAction($this->clickAction);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'title' => $this->title,
            'body' => $this->body,
            'firebase_token' => "-",
            'click_action' => $this->clickAction?$this->clickAction:'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        try {
            if($tokens) {
                $downstreamResponse = FCM::sendTo($tokens->toArray(), $option, $notification, $data);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
