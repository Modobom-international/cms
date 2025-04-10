<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

trait LogsModelActivity
{
    protected static function bootLogsModelActivity()
    {
        static::retrieved(function ($model) {
            if (property_exists($model, 'logRetrieved') && $model->logRetrieved) {
                app(ActivityLogger::class)->log(
                    action: 'retrieve_' . $model->getTable(),
                    details: [
                        'id' => $model->id,
                        'attributes' => array_intersect_key(
                            $model->getAttributes(),
                            array_flip($model->loggableAttributes ?? array_keys($model->getAttributes()))
                        ),
                    ],
                    description: "Lấy thông tin từ bảng {$model->getTable()} với ID: {$model->id}",
                    userId: Auth::id()
                );
            }
        });

        static::created(function ($model) {
            app(ActivityLogger::class)->log(
                action: 'create_' . $model->getTable(),
                details: [
                    'id' => $model->id,
                    'attributes' => array_intersect_key(
                        $model->getAttributes(),
                        array_flip($model->loggableAttributes ?? array_keys($model->getAttributes()))
                    ),
                ],
                description: "Tạo bản ghi mới ở bảng {$model->getTable()} với ID: {$model->id}",
                userId: Auth::id()
            );
        });

        static::updated(function ($model) {
            app(ActivityLogger::class)->log(
                action: 'update_' . $model->getTable(),
                details: [
                    'id' => $model->id,
                    'changes' => $model->getChanges(),
                    'original' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                ],
                description: "Cập nhật bản ghi ở bảng {$model->getTable()} với ID: {$model->id}",
                userId: Auth::id()
            );
        });

        static::deleted(function ($model) {
            app(ActivityLogger::class)->log(
                action: 'delete_' . $model->getTable(),
                details: [
                    'id' => $model->id,
                    'attributes' => array_intersect_key(
                        $model->getAttributes(),
                        array_flip($model->loggableAttributes ?? array_keys($model->getAttributes()))
                    ),
                ],
                description: "Xóa bản ghi ở bảng {$model->getTable()} với ID: {$model->id}",
                userId: Auth::id()
            );
        });
    }
}
