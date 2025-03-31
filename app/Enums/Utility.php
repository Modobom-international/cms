<?php

namespace App\Enums;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

final class Utility
{
    public function saveImageUser($input)
    {
        if ($input) {
            $status = Storage::disk('public-image-user')->put($input['profile_photo_path']->getClientOriginalName(), $input['profile_photo_path']->get());
            return $status;
        }
    }

    public function paginate($items, $perPage = 15, $path = null, $pageName = 'page', $page = null, $options = [])
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $options = ['path' => $path];

        $paginatedItems = $items->forPage($page, $perPage)->values()->toArray();

        return new LengthAwarePaginator($paginatedItems, $items->count(), $perPage, $page, $options);
    }

    public function getCurrentVNTime($timezone = 'Asia/Ho_Chi_Minh', $format = 'Y-m-d H:i:s')
    {
        return (new \DateTime())->setTimezone(new \DateTimeZone($timezone))->format($format);
    }

    public function http($url, $params)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: curl',
        ]);
        $response = curl_exec($ch);
        return json_decode($response);
    }

    public function covertDateTimeToMongoBSONDateGMT7($date)
    {
        return new \MongoDB\BSON\UTCDateTime(((new \DateTime($date))->getTimestamp() + (7 * 3600)) * 1000);
    }
}
