<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\AbuseLog;
use App\Models\Group;
use App\Models\Message;
use App\Models\Rule;
use App\Models\User;
use App\Services\AbuseDetector;
use App\Services\RuleEngine;

final class ComposeController
{
    public function form(): void
    {
        Session::requireLogin();
        $me = (int) Session::userId();

        $replyTo = isset($_GET['reply_to']) ? (int) $_GET['reply_to'] : null;
        $replyTarget = null;
        $parentSubject = null;
        if ($replyTo !== null) {
            $orig = Message::findById($replyTo);
            if ($orig !== null && Message::userCanRead($orig, $me)) {
                $replyTarget = (int) $orig['sender_id'];
                $parentSubject = (string) $orig['subject'];
            } else {
                $replyTo = null;
            }
        }

        View::render('compose/form', [
            'title'         => 'New message',
            'users'         => User::listForCompose($me),
            'groups'        => Group::all(),
            'visibleRules'  => Rule::visible(),
            'replyTo'       => $replyTo,
            'replyTarget'   => $replyTarget,
            'parentSubject' => $parentSubject,
        ]);
    }

    public function send(): void
    {
        Session::requireLogin();
        Csrf::check();

        $me = (int) Session::userId();
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body    = trim((string) ($_POST['body'] ?? ''));
        $isReview = !empty($_POST['is_review']);
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $target   = (string) ($_POST['target'] ?? '');

        if ($subject === '' || $body === '') {
            Session::flash('error', 'Subject and body are required.');
            header('Location: /compose');
            return;
        }

        [$kind, $idStr] = array_pad(explode(':', $target, 2), 2, '');
        $targetId = (int) $idStr;
        $recipientUser  = $kind === 'user'  && $targetId > 0 ? $targetId : null;
        $recipientGroup = $kind === 'group' && $targetId > 0 ? $targetId : null;

        if ($recipientUser === null && $recipientGroup === null) {
            Session::flash('error', 'Please select a recipient.');
            header('Location: /compose');
            return;
        }

        $ruleResult = RuleEngine::canSend($me, $recipientUser, $recipientGroup);
        if (!$ruleResult['allowed']) {
            Session::flash('error', 'You cannot send this message: ' . $ruleResult['reason']);
            header('Location: /compose');
            return;
        }

        $findings = AbuseDetector::scan($subject . "\n" . $body);
        if (AbuseDetector::shouldBlock($findings)) {
            foreach ($findings as $f) {
                AbuseLog::record(null, $me, $f['pattern'], $f['snippet'], $f['severity']);
            }
            Session::flash('error',
                'Message blocked: an attempt to disclose personal information was detected ('
                . implode(', ', array_unique(array_column($findings, 'pattern'))) . ').'
            );
            header('Location: /compose');
            return;
        }

        $messageId = Message::send(
            $me,
            $recipientUser,
            $recipientGroup,
            $subject,
            $body,
            $isReview,
            $parentId,
        );

        foreach ($findings as $f) {
            AbuseLog::record($messageId, $me, $f['pattern'], $f['snippet'], $f['severity']);
        }

        $msg = $findings === []
            ? 'Message sent.'
            : 'Message sent, but flagged for admin review.';
        Session::flash('success', $msg);
        header('Location: /sent');
    }
}
