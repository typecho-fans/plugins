<?php

namespace TypechoPlugin\YetAnotherLike;

use Typecho\Widget;
use Typecho\Widget\Request as WidgetRequest;
use Typecho\Widget\Response as WidgetResponse;
use Widget\ActionInterface;
use Widget\Options;
use Widget\Security;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class Like_Action extends Widget implements ActionInterface {
    private $db;

    public function __construct(WidgetRequest $request, WidgetResponse $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = \Typecho\Db::get();
    }

    public function action()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatus(405);
            return;
        }

        Security::alloc()->protect();

        if ($this->request->is('up')) {
            $this->up();
            return;
        }
        if ($this->request->is('cancel')) {
            $this->cancel();
            return;
        }

        $this->response->setStatus(404);
    }

    public function up() {
        $like_field_is_null = true;
        $LIKE_FIELD_NAME = "likes";
        $cid = $this->request->get("cid");
        if(is_null($cid)) {
            echo "! no cid parameter";
            return;
        }

        $likerid = "";
        // 先看用户是否登录，如果登录就不管IP了
        $user = \Widget\User::alloc();
        if ($user->hasLogin()) {
            $likerid = strval($user->uid);
        }
        else {
            // 仅登录用户判断
            if (Options::alloc()->plugin('YetAnotherLike')->login == "1") {
                echo "! please login";
                return;
            }
            $likerid = self::getClientIp();
        }

        // 把点赞用户加入field
        $userlist = ",";
        $already_exists = $this->db->fetchRow(
            $this->db->select()->from("table.fields")->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME)
        );
        if (!is_null($already_exists)) {
            $like_field_is_null = false;
            $userlist = $already_exists["str_value"];
        }
        if (strpos($userlist, ",{$likerid},") !== false) {
            echo "! already liked";
            return;
        }
        $userlist = $userlist . $likerid . ",";
        if ($like_field_is_null) {
            $stmt = $this->db->insert("table.fields")->rows([
                'cid' => $cid,
                'name' => $LIKE_FIELD_NAME,
                'type' => 'str',
                'str_value' => $userlist,
            ]);
            $this->db->query($stmt);
        }
        else {
            $stmt = $this->db->update("table.fields")->rows([
                'str_value' => $userlist,
            ])->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME);
            $this->db->query($stmt);
        }

        $liker_count = substr_count($userlist, ",") - 1;
        echo $liker_count;
    }

    public function cancel() {
        $LIKE_FIELD_NAME = "likes";
        $cid = $this->request->get("cid");
        if(is_null($cid)) {
            echo "! no cid parameter";
            return;
        }

        $likerid = "";
        $user = \Widget\User::alloc();
        if ($user->hasLogin()) {
            $likerid = strval($user->uid);
        }
        else {
            $likerid = self::getClientIp();
        }

        $userlist_row = $this->db->fetchRow(
            $this->db->select()->from("table.fields")->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME)
        );
        if (is_null($userlist_row)) {
            echo "! you have not liked";
            return;
        }
        $userlist = $userlist_row["str_value"];
        if (strpos($userlist, ",{$likerid},") === false) {
            echo "! you have not liked";
            return;
        }
        # 此时确定已经点赞，删除点赞者列表中的likerid部分
        $userlist = str_replace(",{$likerid},", ',', $userlist);
        $stmt = $this->db->update("table.fields")->rows([
            'str_value' => $userlist
        ])->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME);
        $this->db->query($stmt);

        $liker_count = substr_count($userlist, ",") - 1;
        echo $liker_count;
    }

    private static function getClientIp() {
        return $_SERVER['REMOTE_ADDR'];
    }




}
