<?php

namespace App\Utils;

use App\Models\TgUser;
use Illuminate\Support\Facades\Cache;

class UserSession
{

    protected $keysArrKey;
    public function __construct()
    {
        $this->keysArrKey = 'user_activity_keys_arr';
    }
    public function getUserFromUpdate($update)
    {
        $msg = [];
        if (isset($update['callback_query'])) {
            $msg = $update['callback_query'];
        } else if (isset($update['message'])) {
            $msg = $update['message'];
        }
        $user = isset($msg['from']) ? $msg['from'] : null;
        return $user;
    }

    public function setUser($update)
    {
        $tgUser = $this->getUserFromUpdate($update);

        if (!$tgUser) return false;

        return $this->fromCache($tgUser);
    }


    private function fromCache($tgUser)
    {
        $id = $tgUser['id'];
        $key = 'user_' . $id;
        return Cache::remember($key, 1000, function () use ($tgUser) {
            return $this->getTgUserFromDb($tgUser);
        });
    }

    public function getTgUserFromDb($user)
    {
        $tgId = $user['id'];

        $insertData = $user;
        unset($insertData['id']);

        $user = TgUser::firstOrCreate(
            ['tg_id' =>  $tgId],
            $insertData
        );

        $shouldUpdate = false;
        if ($user->first_name !== $insertData['first_name']) {
            $shouldUpdate = true;
            $user->first_name = $insertData['first_name'];
        }
        if ($user->last_name !== $insertData['last_name']) {
            $shouldUpdate = true;
            $user->last_name = $insertData['last_name'];
        }
        if ($user->username !== $insertData['username']) {
            $shouldUpdate = true;
            $user->username = $insertData['username'];
        }

        if ($shouldUpdate) {
            dump('shouldUpdate');
            $user->save();
        }

        return $user;
    }



    public function getDbUserByTgUser($tgUser)
    {
        return $this->fromCache($tgUser);
    }


    public function saveActivity($activity)
    {
        $id = $activity['tguser_id'];
        $key = 'user_activity_' . $id;
        $activitiesArr = Cache::get($key, []);

        $time = now()->timestamp;
        $activity['created_at'] =  $time;
        $activity['updated_at'] =  $time;
        $activitiesArr[$time] = $activity;
        Cache::forever($key, $activitiesArr);



        $activitiesKeys = Cache::get($this->keysArrKey, []);
        if (!isset($activitiesKeys[$key])) {
            $activitiesKeys[$key] = $key;
            Cache::forever($this->keysArrKey, $activitiesKeys);
        }
    }

    public function getCacheAactivities()
    {
        $activities = [];
        $activitiesKeys = Cache::pull($this->keysArrKey, []);
        foreach ($activitiesKeys as $key) {
            $userActivities = Cache::pull($key, []);
            $activities[$key] = $userActivities;
        }
        return $activities;
    }
}
