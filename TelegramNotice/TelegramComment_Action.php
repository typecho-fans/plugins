<?php

namespace TypechoPlugin\TelegramNotice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Widget;
use Utils;
use Typecho\Db;

class TelegramComment_Action extends Widget implements \Widget\ActionInterface
{
    public function action()
    {
        $this->execute();
    }

    private function req(string $key, string $default = ''): string
    {
        try {
            $v = $this->request->get($key);
            if ($v !== null && $v !== '') return (string)$v;
        } catch (\Throwable $e) {
            // ignore
        }

        if (isset($_POST[$key]) && $_POST[$key] !== '') return (string)$_POST[$key];
        if (isset($_GET[$key]) && $_GET[$key] !== '') return (string)$_GET[$key];

        return $default;
    }

    public function execute()
    {
        $do = trim($this->req('do', ''));

        if ($do === 'webhookCheck' || $do === 'webhookSet') {
            $this->handleWebhookAjax($do);
            return;
        }

        if ($do === 'webhook') {
            $this->handleTelegramWebhook();
            return;
        }

        if ($do === 'pushPost') {
            $this->handleAdminPushPost();
            return;
        }
        if ($do === 'pushTplSave') {
            $this->handleAdminSavePushTpl();
            return;
        }

        $this->response->setStatus(404);
        $this->response->throwJson(['ok' => false, 'error' => 'not_found']);
    }

    private function handleWebhookAjax(string $do): void
    {
        $token = trim($this->req('botToken', ''));
        $secret = trim($this->req('webhookSecret', ''));

        if ($token === '') {
            $token = trim($this->req('TelegramNotice[botToken]', ''));
        }
        if ($secret === '') {
            $secret = trim($this->req('TelegramNotice[webhookSecret]', ''));
        }

        if ($token === '' || $secret === '') {
            $opt = Utils\Helper::options()->plugin('TelegramNotice');
            if ($token === '') $token = trim((string)($opt->botToken ?? ''));
            if ($secret === '') $secret = (string)($opt->webhookSecret ?? '');
        }

        if ($token === '') {
            $this->response->throwJson([
                'ok' => false,
                'error' => 'botToken_empty',
                'message' => 'Bot Token 为空，无法检测/设置（请在表单中填写后再点按钮，无需保存）',
            ]);
        }

        $siteUrl = (string)Utils\Helper::options()->siteUrl;
        $wantUrl = rtrim(trim($siteUrl), '/') . '/action/telegram-comment?do=webhook' . ($secret !== '' ? ('&secret=' . rawurlencode($secret)) : '');

        
        if ($do === 'webhookCheck') {
            $info = Plugin::tgGetWebhookInfo($token);
            if (!($info['ok'] ?? false)) {
                $this->response->throwJson(['ok' => false, 'error' => 'getWebhookInfo_failed', 'detail' => $info]);
            }
            $cur = (string)($info['result']['url'] ?? '');
            $this->response->throwJson([
                'ok' => true,
                'mode' => 'check',
                'bot' => null,
                'currentUrl' => $cur,
                'wantUrl' => $wantUrl,
                'needSet' => ($cur !== $wantUrl),
            ]);
        }

        // webhookSet
        $beforeInfo = Plugin::tgGetWebhookInfo($token);
        $beforeUrl = ($beforeInfo['ok'] ?? false) ? (string)($beforeInfo['result']['url'] ?? '') : '';

        $set = Plugin::tgSetWebhook($token, $wantUrl);
        if (!($set['ok'] ?? false)) {
            $this->response->throwJson([
                'ok' => false,
                'error' => 'setWebhook_failed',
                'message' => 'setWebhook 失败',
                'detail' => $set,
            ]);
        }

        $afterInfo = Plugin::tgGetWebhookInfo($token);
        $afterUrl = ($afterInfo['ok'] ?? false) ? (string)($afterInfo['result']['url'] ?? '') : '';
        $okMatch = ($afterUrl !== '' && $afterUrl === $wantUrl);

        $this->response->throwJson([
            'ok' => true,
            'mode' => 'set',
            'message' => $okMatch ? '配置成功：Webhook URL 已与期望一致' : '已调用 setWebhook，但检测到 URL 仍未与期望一致（请刷新/稍后重试）',
            'bot' => null,
            'wantUrl' => $wantUrl,
            'beforeUrl' => $beforeUrl,
            'afterUrl' => $afterUrl,
            'matched' => $okMatch,
            'setResult' => $set,
            'afterInfo' => $afterInfo,
        ]);
    }

    private function handleTelegramWebhook(): void
    {
        $do = trim($this->req('do', ''));
        if ($do !== 'webhook') {
            $this->response->setStatus(404);
            $this->response->throwJson(['ok' => false, 'error' => 'not_found']);
        }

        $opt = Utils\Helper::options()->plugin('TelegramNotice');
        $token = trim((string)($opt->botToken ?? ''));
        $secret = (string)($opt->webhookSecret ?? '');
        $emailMapText = (string)($opt->emailChatMap ?? '');
        $alsoSendDefault = trim((string)($opt->alsoSendDefault ?? '1'));
        $defaultChatIdsRaw = (string)($opt->chatId ?? '');

        if ($token === '') {
            $this->response->setStatus(500);
            $this->response->throwJson(['ok' => false, 'error' => 'botToken not configured']);
        }

        $reqSecret = trim($this->req('secret', ''));
        if ($secret !== '' && !hash_equals($secret, $reqSecret)) {
            $this->response->setStatus(403);
            $this->response->throwJson(['ok' => false, 'error' => 'forbidden']);
        }

        $raw = file_get_contents('php://input');
        $update = json_decode($raw, true);
        if (!is_array($update)) {
            $this->response->throwJson(['ok' => true]);
        }

        if (isset($update['message']) && is_array($update['message'])) {
            $msg = $update['message'];

            $replyTo = $msg['reply_to_message'] ?? null;
            $chatId = $msg['chat']['id'] ?? null;
            $text = trim((string)($msg['text'] ?? ''));

            if ($chatId === null || $text === '' || !is_array($replyTo)) {
                $this->response->throwJson(['ok' => true]);
            }

            // 规则：只要当前 chat_id 在绑定表中出现过，就允许（与 alsoSendDefault 无关）
            $boundMail = $this->findEmailByChatId($emailMapText, (string)$chatId);
            if ($boundMail === '') {
                $this->tgSendMessage($token, (string)$chatId, '未绑定邮箱的 Chat ID，禁止通过 Telegram 回复评论。', null);
                $this->response->throwJson(['ok' => true]);
            }

            // reply_to_message 里只保证 text（纯文本），HTML 不会保留
            $replyText = (string)($replyTo['text'] ?? '');
            if (!preg_match('/#TG:(\d+):(\d+):([a-f0-9]{10,12})\b/i', $replyText, $m)) {
                $this->response->throwJson(['ok' => true]);
            }

            $cid = (int)$m[1];
            $parentCoid = (int)$m[2];
            $sig = (string)$m[3];

            $payload = "cid={$cid}&coid={$parentCoid}";
            $expect = Plugin::signCallback($secret, $payload);
            if (!hash_equals($expect, $sig)) {
                $this->tgSendMessage($token, (string)$chatId, '签名校验失败，无法回复。', null);
                $this->response->throwJson(['ok' => true]);
            }

            // 必须原评论已通过
            $db = Db::get();
            $prefix = $db->getPrefix();
            $row = $db->fetchRow(
                $db->select('status', 'cid')
                    ->from($prefix . 'comments')
                    ->where('coid = ? AND cid = ?', $parentCoid, $cid)
                    ->limit(1)
            );
            $status = is_array($row) ? (string)($row['status'] ?? '') : '';
            if ($status !== 'approved') {
                $this->tgSendMessage($token, (string)$chatId, '该评论未通过审核，无法在 Telegram 中回复。', null);
                $this->response->throwJson(['ok' => true]);
            }

            // 写入 Typecho 回复评论（parent=原 coid）
            $author = $this->findUserNameByEmail($boundMail);
            if ($author === '') {
                // 用邮箱前缀
                $author = $this->nameFromEmail($boundMail);
            }

            $mail = $boundMail;

            try {
                $db->query($db->insert($prefix . 'comments')->rows([
                    'cid' => $cid,
                    'created' => time(),
                    'author' => $author,
                    'authorId' => 1,
                    'ownerId' => 1,
                    'mail' => $mail,
                    'url' => '',
                    'ip' => '127.0.0.1', // 127.0.0.1可以改
                    'agent' => 'TelegramReply',
                    'text' => $text,
                    'type' => 'comment',
                    'status' => 'approved',
                    'parent' => $parentCoid,
                ]));

                $this->tgSendMessage($token, (string)$chatId, '已作为回复发布。', null);
            } catch (\Throwable $e) {
                $this->tgSendMessage($token, (string)$chatId, '发布失败：' . $e->getMessage(), null);
            }

            $this->response->throwJson(['ok' => true]);
        }

        if (isset($update['callback_query'])) {
            $cq = $update['callback_query'];
            $data = (string)($cq['data'] ?? '');
            $cbId = (string)($cq['id'] ?? '');
            $chatId = $cq['message']['chat']['id'] ?? null;
            $messageId = $cq['message']['message_id'] ?? null;

            $res = $this->handleCallback($data, $secret);

            if ($chatId !== null && $messageId !== null) {
                if (($res['act'] ?? '') === 'approve') {
                    $origMarkup = $cq['message']['reply_markup'] ?? null;
                    $mergedMarkup = $this->mergeUrlButtons($res['reply_markup'], is_array($origMarkup) ? $origMarkup : null);

                    $this->tgEditReplyMarkup($token, (string)$chatId, (int)$messageId, $mergedMarkup);

                    $this->tgAnswerCallback($token, $cbId, '已标记通过', false);
                } else {
                    $this->tgAnswerCallback($token, $cbId, $res['text'], $res['alert']);
                    $this->tgSendMessage($token, (string)$chatId, $res['reply'], (int)$messageId);
                    $this->tgEditReplyMarkup($token, (string)$chatId, (int)$messageId, ['inline_keyboard' => []]);
                }
            } else {
                $this->tgAnswerCallback($token, $cbId, $res['text'], $res['alert']);
            }

            $this->response->throwJson(['ok' => true]);
        }

        $this->response->throwJson(['ok' => true]);
    }

    /**
     * PHP < 8.0 兼容：str_starts_with
     */
    private static function strStartsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * 根据邮箱查询 Typecho 用户名（优先 screenName，其次 name）。
     * 找不到返回空串。
     */
    private function findUserNameByEmail(string $email): string
    {
        $email = trim((string)$email);
        if ($email === '') return '';

        try {
            $db = Db::get();
            $prefix = $db->getPrefix();

            // users 表字段通常包含：uid, name, screenName, mail
            $row = $db->fetchRow(
                $db->select('screenName', 'name')
                    ->from($prefix . 'users')
                    ->where('mail = ?', $email)
                    ->limit(1)
            );

            if (!is_array($row)) return '';

            $screen = trim((string)($row['screenName'] ?? ''));
            if ($screen !== '') return $screen;

            $name = trim((string)($row['name'] ?? ''));
            return $name;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 从邮箱生成一个兜底昵称：取 @ 前面的部分。
     */
    private function nameFromEmail(string $email): string
    {
        $email = trim((string)$email);
        if ($email === '') return 'Telegram';

        $pos = strpos($email, '@');
        if ($pos === false) return $email;

        $n = trim(substr($email, 0, $pos));
        return $n !== '' ? $n : 'Telegram';
    }
    

    /**
     * 从 emailChatMap 中反查：给定 chat_id，返回绑定邮箱；不存在返回空串。
     * 格式：每行 email=chat_id
     */
    private function findEmailByChatId(string $mapText, string $chatId): string
    {
        $chatId = trim((string)$chatId);
        if ($chatId === '') return '';

        $lines = preg_split('/\r\n|\r|\n/', (string)$mapText) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $this->strStartsWith($line, '#')) continue;

            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $email = trim(substr($line, 0, $pos));
            $cid = trim(substr($line, $pos + 1));

            if ($email !== '' && $cid === $chatId) {
                return $email;
            }
        }
        return '';
    }

    private function handleCallback(string $data, string $secret): array
    {
        $parts = explode(':', $data);
        if (count($parts) !== 4 || $parts[0] !== 'mod') {
            return $this->out('无效操作', true, '操作失败：无效回调数据');
        }

        [$_, $act, $coid, $sig] = $parts;
        $coid = (int)$coid;
        if ($coid <= 0) {
            return $this->out('无效评论', true, '操作失败：coid 无效');
        }

        $payload = "coid={$coid}";
        $expect = Plugin::signCallback($secret, $payload);
        if (!hash_equals($expect, (string)$sig)) {
            return $this->out('签名错误', true, '操作失败：签名校验失败');
        }

        try {
            $db = Db::get();
            $prefix = $db->getPrefix();

            if ($act === 'approve') {
                $db->query($db->update($prefix . 'comments')->rows(['status' => 'approved'])->where('coid = ?', $coid));

                // 删掉“通过”，保留“垃圾/删除”
                $sig2 = Plugin::signCallback($secret, "coid={$coid}");
                $mk = fn(string $a) => "mod:{$a}:{$coid}:{$sig2}";
                $kb = [
                    'inline_keyboard' => [[
                        ['text' => '垃圾', 'callback_data' => $mk('spam')],
                        ['text' => '删除', 'callback_data' => $mk('delete')],
                    ]]
                ];

                return $this->out('已标记通过', false, '', 'approve', $kb);
            }

            if ($act === 'spam') {
                $db->query($db->update($prefix . 'comments')->rows(['status' => 'spam'])->where('coid = ?', $coid));
                return $this->out('已设为垃圾', false, "已将评论 #{$coid} 标记为 垃圾");
            }

            if ($act === 'delete') {
                $db->query($db->delete($prefix . 'comments')->where('coid = ?', $coid));
                return $this->out('已删除', false, "已删除评论 #{$coid}");
            }

            return $this->out('未知操作', true, '操作失败：未知 action');
        } catch (\Throwable $e) {
            return $this->out('异常', true, '操作失败：' . $e->getMessage());
        }
    }

    private function out(string $text, bool $alert, string $reply, string $act = '', array $replyMarkup = ['inline_keyboard' => []]): array
    {
        return ['text' => $text, 'alert' => $alert, 'reply' => $reply, 'act' => $act, 'reply_markup' => $replyMarkup];
    }

    private function tgAnswerCallback(string $token, string $callbackQueryId, string $text, bool $showAlert): void
    {
        if ($callbackQueryId === '') return;
        $this->httpPostForm("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert ? 'true' : 'false',
        ]);
    }

    private function tgSendMessage(string $token, string $chatId, string $text, ?int $replyToMessageId = null): void
    {
        $post = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyToMessageId) {
            $post['reply_to_message_id'] = (string)$replyToMessageId;
            $post['allow_sending_without_reply'] = true;
        }
        $this->httpPostForm("https://api.telegram.org/bot{$token}/sendMessage", $post);
    }

    private function tgEditReplyMarkup(string $token, string $chatId, int $messageId, array $replyMarkup): void
    {
        $this->httpPostForm("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
            'chat_id' => $chatId,
            'message_id' => (string)$messageId,
            'reply_markup' => json_encode($replyMarkup, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function tgEditMessageText(string $token, string $chatId, int $messageId, string $text, ?array $replyMarkup = null): void
    {
        $post = [
            'chat_id' => $chatId,
            'message_id' => (string)$messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup !== null) {
            $post['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        $this->httpPostForm("https://api.telegram.org/bot{$token}/editMessageText", $post);
    }

    private function httpPostForm(string $url, array $post): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($post),
                'timeout' => 10,
            ]
        ]);
        @file_get_contents($url, false, $context);
    }

    /**
     * 将原消息里的 URL 按钮（如“查看评论”）合并回新的 inline_keyboard。
     * 规则：把“全是 url 的行”追加到新键盘末尾，并去重（按 url）。
     */
    private function mergeUrlButtons(array $newMarkup, ?array $origMarkup): array
    {
        $nk = $newMarkup['inline_keyboard'] ?? [];
        if (!is_array($nk)) $nk = [];

        $ok = $origMarkup['inline_keyboard'] ?? [];
        if (!is_array($ok) || !$ok) {
            return ['inline_keyboard' => $nk];
        }

        $seen = [];
        // 记录 newMarkup 里已有的 url，避免重复
        foreach ($nk as $row) {
            if (!is_array($row)) continue;
            foreach ($row as $btn) {
                if (is_array($btn) && isset($btn['url'])) {
                    $seen[(string)$btn['url']] = true;
                }
            }
        }

        foreach ($ok as $row) {
            if (!is_array($row) || !$row) continue;

            // 只合并“URL 行”（行内按钮全部是 url，没有 callback_data）
            $allUrl = true;
            foreach ($row as $btn) {
                if (!is_array($btn) || !isset($btn['url'])) {
                    $allUrl = false;
                    break;
                }
            }
            if (!$allUrl) continue;

            $filteredRow = [];
            foreach ($row as $btn) {
                $url = (string)($btn['url'] ?? '');
                if ($url === '' || isset($seen[$url])) continue;
                $seen[$url] = true;
                $filteredRow[] = $btn;
            }

            if ($filteredRow) {
                $nk[] = $filteredRow;
            }
        }

        return ['inline_keyboard' => $nk];
    }

    private function handleAdminSavePushTpl(): void
    {
        // 后台权限：管理员
        \Typecho\Widget::widget('Widget_User')->pass('administrator');

        // 表单提交字段名可能是 pushTpl 或 TelegramNotice[pushTpl]
        $tpl = (string)$this->req('pushTpl', '');
        if (trim($tpl) === '') {
            $tpl = (string)$this->req('TelegramNotice[pushTpl]', '');
        }
        $tpl = trim($tpl);

        // 保存到插件配置
        $db = Db::get();
        $prefix = $db->getPrefix();
        $options = Utils\Helper::options();

        $pluginName = 'TelegramNotice';
        $row = $db->fetchRow(
            $db->select('value')
                ->from($prefix . 'options')
                ->where('name = ?', 'plugin:' . $pluginName)
                ->limit(1)
        );

        $value = [];
        if (is_array($row) && isset($row['value'])) {
            $value = @unserialize((string)$row['value']);
            if (!is_array($value)) $value = [];
        }
        $value['pushTpl'] = $tpl;

        $db->query(
            $db->update($prefix . 'options')
                ->rows(['value' => serialize($value)])
                ->where('name = ?', 'plugin:' . $pluginName)
        );

        $isAjax = false;
        try {
            $isAjax = strtolower((string)($this->request->getHeader('X-Requested-With') ?? '')) === 'xmlhttprequest';
        } catch (\Throwable $e) {
        }

        if ($isAjax) {
            $this->response->throwJson(['ok' => true, 'message' => '模板已保存']);
        }

        $this->response->redirect($options->adminUrl . 'extending.php?panel=TelegramNotice%2Fpush.php');
    }

    private function handleAdminPushPost(): void
    {
        // 后台权限：管理员
        \Typecho\Widget::widget('Widget_User')->pass('administrator');

        // 兼容：单篇 cid 或批量 cids[]
        $cid = (int)$this->req('cid', '0');
        $cids = [];

        if ($cid > 0) {
            $cids = [$cid];
        } else {
            $raw = $_POST['cids'] ?? [];
            if (is_array($raw)) {
                foreach ($raw as $v) {
                    $id = (int)$v;
                    if ($id > 0) $cids[] = $id;
                }
            }
            $cids = array_values(array_unique($cids));
        }

        if (!$cids) {
            $this->response->setStatus(400);
            $this->response->throwJson(['ok' => false, 'error' => 'cid_invalid']);
        }

        $opt = Utils\Helper::options()->plugin('TelegramNotice');
        $token = trim((string)($opt->botToken ?? ''));
        $pushTpl = (string)($opt->pushTpl ?? '');
        $pushChatIdsRaw = (string)($opt->pushChatId ?? '');

        if ($token === '') {
            $this->response->setStatus(400);
            $this->response->throwJson(['ok' => false, 'error' => 'botToken_empty']);
        }

        $targets = $this->parseChatIds($pushChatIdsRaw);
        if (!$targets) {
            $this->response->setStatus(400);
            $this->response->throwJson(['ok' => false, 'error' => 'pushChatId_empty', 'message' => '未配置文章推送 Chat ID（pushChatId）']);
        }

        // 逐篇推送
        $db = Db::get();
        $prefix = $db->getPrefix();

        foreach ($cids as $cidOne) {
            $post = $db->fetchRow(
                $db->select('cid', 'title', 'created', 'text')
                    ->from($prefix . 'contents')
                    ->where('cid = ?', $cidOne)
                    ->where('type = ?', 'post')
                    ->limit(1)
            );
            if (!is_array($post)) {
                continue;
            }

            $permalink = '';
            try {
                $w = Utils\Helper::widgetById('contents', $cidOne);
                if ($w && $w->have()) {
                    $permalink = (string)$w->permalink;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            $text = $this->renderPostTemplate($pushTpl, $post, $permalink);

            foreach ($targets as $chatId) {
                Plugin::tgApi($token, 'sendMessage', [
                    'chat_id' => (string)$chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ]);
            }
        }

        $this->response->redirect(Utils\Helper::options()->adminUrl . 'extending.php?panel=TelegramNotice%2Fpush.php');
    }

    private function parseChatIds(string $raw): array
    {
        $raw = trim((string)$raw);
        if ($raw === '') return [];
        $parts = preg_split('/[,\n\r]+/', $raw) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn($v) => $v !== ''));
        return array_values(array_unique($parts));
    }

    private function renderPostTemplate(string $tpl, array $post, string $permalink): string
    {
        $tpl = trim((string)$tpl);
        if ($tpl === '') {
            $tpl = "📰 <b>{title}</b>\n\n{excerpt}\n\n<a href=\"{permalink}\">点击阅读</a>";
        }

        $title = (string)($post['title'] ?? '');
        $created = (int)($post['created'] ?? time());
        $body = (string)($post['text'] ?? '');

        $excerpt = $this->makeExcerpt($body, 120);

        $vars = [
            '{title}' => htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{excerpt}' => htmlspecialchars($excerpt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{permalink}' => htmlspecialchars((string)$permalink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{created}' => htmlspecialchars(date('Y-m-d H:i:s', $created), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{cid}' => (string)((int)($post['cid'] ?? 0)),
        ];

        return strtr($tpl, $vars);
    }

    private function makeExcerpt(string $text, int $maxLen): string
    {
        $s = trim(strip_tags($text));
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        if (function_exists('mb_strlen') && mb_strlen($s, 'UTF-8') > $maxLen) {
            return mb_substr($s, 0, $maxLen, 'UTF-8') . '...';
        }
        if (strlen($s) > $maxLen) {
            return substr($s, 0, $maxLen) . '...';
        }
        return $s;
    }
}