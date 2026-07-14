<?php
namespace TypechoPlugin\YetAnotherLike;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$db = \Typecho\Db::get();
$LIKE_FIELD_NAME = "likes";
$cid = \Widget\Archive::alloc()->cid;
$userlist = ",";
$already_exists = $db->fetchRow(
    $db->select()->from("table.fields")->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME)
);
if (!is_null($already_exists)) {
    $userlist = $already_exists["str_value"];
}
$like_count = substr_count($userlist, ",") - 1;

$likerid = "";
$user = \Widget\User::alloc();
if ($user->hasLogin()) {
    $likerid = strval($user->uid);
}
else {
    $likerid = $_SERVER['REMOTE_ADDR'];
}
$liked = strpos($userlist, ",{$likerid},") === false ? "" : " liked";

?>

<style>
.like-bar{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:18px;

    margin:40px auto;
    padding:16px 24px;

    width:fit-content;
    background:var(--card-bg,#fff);
    border-radius:16px;
    box-shadow:0 8px 24px rgba(0,0,0,.08);
}

.like-btn{
    display:flex;
    align-items:center;
    gap:8px;

    padding:10px 20px;

    border:none;
    border-radius:999px;

    background:#ff6b81;
    color:#fff;

    font-size:15px;
    font-weight:600;

    cursor:pointer;
    transition:.25s;
}

.like-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(255,107,129,.35);
}

.like-icon{
    font-size:18px;
    line-height:1;
}

.like-count{
    color:#666;
    font-size:15px;
}

.like-count strong{
    color:#ff6b81;
    font-size:18px;
    margin:0 2px;
}

/* 已点赞状态 */
.like-btn.liked{
    background:#ffffff;
    color:#ff6b81;
    box-shadow:0 6px 16px rgba(255,107,129,.18);
}

.like-btn.liked .like-icon{
    color:#ff4d6d;
    transform:scale(1.1);
}

.like-btn.liked:hover{
    background:#fff5f7;
    box-shadow:0 8px 20px rgba(255,107,129,.28);
}

</style>

<div class="like-bar" data-yet-another-like data-cid="<?= htmlspecialchars((string) $cid, ENT_QUOTES, 'UTF-8') ?>">
  <button type="button" class="like-btn<?= $liked ?>">
    <span class="like-icon">❤</span>
    <span>点赞</span>
  </button>

  <span class="like-count">
    已获 <strong class="like-count-number"><?= $like_count ?></strong> 个赞
  </span>
</div>

<script>
    (() => {
        const state = window.TypechoPluginYetAnotherLike || {};
        state.endpoint = <?= json_encode(
            \Widget\Security::alloc()->getIndex('/action/like'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;
        window.TypechoPluginYetAnotherLike = state;

        if (state.clickHandler) {
            return;
        }

        state.clickHandler = async (event) => {
            const button = event.target.closest('.like-bar[data-yet-another-like] .like-btn');
            if (!button || button.disabled) {
                return;
            }

            const bar = button.closest('.like-bar[data-yet-another-like]');
            const count = bar.querySelector('.like-count-number');
            const action = button.classList.contains('liked') ? 'cancel' : 'up';
            const params = new URLSearchParams({cid: bar.dataset.cid});
            params.set(action, '1');
            button.disabled = true;

            try {
                const response = await fetch(state.endpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: params.toString()
                });
                const text = (await response.text()).trim();

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                if (text === '! please login') {
                    alert('请登录再点赞文章！');
                    return;
                }
                if (text === '! already liked') {
                    alert('您已经点过赞了！');
                    return;
                }
                if (text === '! you have not liked') {
                    alert('您还没有点过赞，无法取消');
                    return;
                }
                if (!/^\d+$/.test(text)) {
                    throw new Error('服务器返回了无效的点赞数');
                }

                count.textContent = text;
                button.classList.toggle('liked', action === 'up');
            }
            catch (error) {
                console.error('YetAnotherLike:', error);
                alert('点赞请求失败，请稍后重试');
            }
            finally {
                button.disabled = false;
            }
        };

        document.addEventListener('click', state.clickHandler);
    })();

</script>
