<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\View;
use App\Models\Message;

final class InboxController
{
    public function index(): void
    {
        Session::requireLogin();
        $messages = Message::inboxFor((int) Session::userId());
        View::render('inbox/list', [
            'title'    => 'Inbox',
            'messages' => $messages,
            'mode'     => 'inbox',
        ]);
    }

    public function sent(): void
    {
        Session::requireLogin();
        $messages = Message::sentBy((int) Session::userId());
        View::render('inbox/sent', [
            'title'    => 'Sent',
            'messages' => $messages,
        ]);
    }

    /** @param array<string, string> $params */
    public function read(array $params): void
    {
        Session::requireLogin();
        $id = (int) $params['id'];
        $msg = Message::findById($id);
        if ($msg === null) {
            http_response_code(404);
            echo 'Message not found.';
            return;
        }
        $me = (int) Session::userId();
        if (!Message::userCanRead($msg, $me) && !Session::isAdmin()) {
            http_response_code(403);
            echo 'You don\'t have access to this message.';
            return;
        }

        $rootId = $msg['parent_id'] !== null ? (int) $msg['parent_id'] : (int) $msg['id'];
        $thread = Message::thread($rootId);

        // Mark as read for everyone in the thread who is the current user.
        foreach ($thread as $m) {
            if ((int) $m['sender_id'] !== $me) {
                Message::markRead((int) $m['id'], $me);
            }
        }

        View::render('inbox/read', [
            'title'  => $msg['subject'],
            'thread' => $thread,
            'rootId' => $rootId,
        ]);
    }
}
