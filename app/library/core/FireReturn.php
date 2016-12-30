<?php
namespace Core;
/**
 * FireReturn
 *
 * @author : Cyw
 * @email  : rose2099.c@gmail.com
 * @created: 16/12/30 下午8:11
 * @logs   :
 *
 */
class FireReturn
{
    private static $responseData = array(
        'code'    => 0,
        'message' => '',
        'data'    => [],
    );

    public static function makeJson($code, $message, $data)
    {
        $temp = self::$responseData;
        $temp['code'] = (int)$code;
        $temp['message'] = (string)$message;

        if (!empty($data)) {
            $temp['data'] = (object)$data;
        }

        return json_encode($temp, JSON_UNESCAPED_UNICODE);
    }
}
