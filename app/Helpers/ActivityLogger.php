<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogger
{
    /**
     * Ghi log hoạt động vào bảng activity_log
     *
     * @param string $logName
     * @param string $description
     * @param Model|null $subject
     * @param array $properties
     * @param |int|string|null $causer
     *        Có thể là: model user, user id, hoặc 'system'
     * @return Activity
     */
    public static function log(
        string $logName,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        $causer = null
    ): Activity {
        $activity = activity()
            ->useLog($logName);

        if ($subject) {
            $activity->performedOn($subject);
        }

        // Xác định người tạo
        if ($causer === 'system') {
            $activity->causedBy(null);
            $properties['causer_name'] = 'System';
        } elseif (is_numeric($causer)) {
            $userModel = config('auth.providers.users.model');
            $activity->causedBy((new $userModel)->find($causer));
        } elseif ($causer instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            $activity->causedBy($causer);
        } else {
            $activity->causedBy(Auth::user());
        }

        $activity->withProperties(array_merge([
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ], $properties));

        return $activity->log($description);
    }
}
