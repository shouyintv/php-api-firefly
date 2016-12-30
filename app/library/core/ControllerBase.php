<?php
/**
 * ControllerBase
 *
 * @author : Cyw
 * @email  : rose2099.c@gmail.com
 * @created: 16/12/30 下午8:22
 * @logs   :
 *
 */
namespace Core;

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    protected $uid = null;

    /**
     * ControllerBase constructor.
     */
    public function onConstruct()
    {
        //TODO checkSign
    }

    /**
     * success
     *
     * @param array $data
     * @param int $code
     * @param string $message
     */
    protected function success($data = [], $code = 0, $message = '')
    {
        $strRtn = FireReturn::makeJson($code, $message, $data);
        echo $strRtn;
        exit;
    }

    /**
     * error
     *
     * @param int $code
     * @param string $message
     * @param array $data
     */
    protected function error($code = -99, $message = '', $data = [])
    {
        $strRtn = FireReturn::makeJson($code, $message, $data);
        echo $strRtn;
        exit;
    }

    /**
     * 确认登录
     */
    public function checkLogin()
    {
        if (!$this->isLogin()) {
            return $this->error('-1', '请登录后再操作');
        }
    }

    /**
     * 确认Public
     */
    public function checkPublic()
    {
        $toid = $this->request->get('toid', 'int');
        $token = $this->request->get('token', 'string');

        if (Token::check($toid, $token)) {
            $this->uid = $toid;

            return true;
        } else {
            if ($this->isLogin()) {
                return true;
            }
        }
    }

    /**
     * 检测登录状态
     */
    public function isLogin()
    {
        // TODO check session
        return true;
    }

    /**
     * 获取用户信息
     *
     * @param        $uid
     * @param string $field
     *
     * @return array
     */
    public function getUser($uid, $field = '', $all = false)
    {
        if (is_array($uid) && !empty($uid)) {
            $uid = current($uid);
        }

        // TODO getUser

        return $user = [];
    }
}
