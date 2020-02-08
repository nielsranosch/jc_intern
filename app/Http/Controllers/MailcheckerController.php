<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Webklex\IMAP;

class MailcheckerController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct() {
        $this->middleware('admin');
    }

    /**
     * @param IMAP\Message $message
     * @param IMAP\Folder|null $containing_folder
     * @return array
     */
    protected static function flattenMessage(IMAP\Message $message = null, IMAP\Folder $containing_folder = null) {
        if ($message === null) {
            return null;
        }

        $folder = $containing_folder === null ? "" : $containing_folder->full_name;

        // Get rid of leading topfolder name
        $topfolder = config("mailchecker.topfolder");
        if ($topfolder !== "NULL" && $topfolder !== ".") {
            $topfolder = $topfolder . '.';
            if (mb_strpos($folder, $topfolder) === 0) {
                $folder = mb_substr($folder, mb_strlen($topfolder));
            }
        }

        $result = ["date" => $message->getDate(), "from" => $message->getFrom()[0]->mail, "subject" => $message->getSubject(), "folder" => $folder];
        return $result;
    }

    /**
     * @param $current_count
     * @return mixed
     */
    protected static function flattenCountResult($current_count) {
        $current_count["newest_message"] = self::flattenMessage($current_count["newest_message"]["message"], $current_count["newest_message"]["folder"]);
        return $current_count;
    }

    /**
     * @param IMAP\Client $client
     * @param IMAP\Folder $folder
     * @param bool $recursive
     * @param $exclude_folders
     * @return array
     * @throws IMAP\Exceptions\ConnectionFailedException
     * @throws IMAP\Exceptions\GetMessagesFailedException
     */
    protected static function getFolderCount(IMAP\Client $client, IMAP\Folder $folder, $recursive = true, $exclude_folders = []) {
        if (in_array($folder->name, $exclude_folders)) {
            return ['total' => 0, 'unread' => 0, 'newest_message' => null];
        }
        $client->openFolder($folder);
        $total = $client->countMessages();
        $unread = count($folder->query()->unseen()->get());
        $newest_message = ["message" => $folder->query()->limit(1)->get()->first(), "folder" => $folder];

        // Handle subfolders recursively
        if ($recursive === true && $folder->has_children) {
            $subfolders = $folder->children->whereNotIn("name", $exclude_folders);
            foreach ($subfolders as $subfolder) {
                $subresult = self::getFolderCount($client, $subfolder, $recursive, $exclude_folders);

                $total += $subresult['total'];
                $unread += $subresult['unread'];

                if ($subresult['newest_message']['message'] === null) {
                    // No Action
                } else if ($newest_message['message'] === null) {
                    $newest_message = $subresult['newest_message'];
                } else if ($newest_message["message"]->getDate()->lt($subresult['newest_message']["message"]->getDate())) {
                    $newest_message = $subresult['newest_message'];
                }
            }
        }

        return ['total' => $total, 'unread' => $unread, 'newest_message' => $newest_message];
    }

    /**
     * @param null $folders
     * @param bool $recursive
     * @return array|string
     */
    public static function prepareMailboxOverview($folders = null, $recursive = true) {
        $topfolder = config("mailchecker.topfolder");
        $exclude_folders = config("mailchecker.exclude");

        $result = [];

        /** @var \Webklex\IMAP\Client $client */
        $client = IMAP\Facades\Client::account('default');

        try {
            $client->connect();

            $all_folders = $client->getFolders(true);

            if ($folders === null) {
                /*
                 * Two cases are most common:
                 * 1) Everything is under the "INBOX" folder.
                 * 2) Everything is on the same level as the "INBOX" folder.
                 *
                 * We handle both of them here.
                 */
                if ($topfolder === "NULL" || $topfolder === ".") {
                    $topfolder = null;
                    $folders = $all_folders;
                } else {
                    $topfolder = $all_folders->where('full_name', $topfolder)->first();

                    $current_counts = self::getFolderCount($client, $topfolder, false); //Recursion makes no sense here. hard-disabled
                    $current_counts = self::flattenCountResult($current_counts);
                    $result[$topfolder->name] = $current_counts;

                    $folders = $topfolder->children;
                    $exclude_folders[] = $topfolder;
                }

                $folders = $folders->whereNotIn("name", $exclude_folders);
            } else {
                $folders = $all_folders->whereIn('name', $folders);
            }

            foreach ($folders as $folder) {
                $current_counts = self::getFolderCount($client, $folder, $recursive, $exclude_folders);
                $current_counts = self::flattenCountResult($current_counts);
                $result[$folder->name] = $current_counts;
            }
        } catch (IMAP\Exceptions\ConnectionFailedException | IMAP\Exceptions\GetMessagesFailedException | IMAP\Exceptions\MailboxFetchingException $e) {
            $result = "NO_IMAP_CONNECTION";
        } finally {
            $client->disconnect();
            unset($client);
        }

        return $result;
    }

    public function overview() {
        $mailbox_folders = self::prepareMailboxOverview();

        return view('mailchecker.overview', [
            'mailbox_name' => config('imap.accounts.default.username'),
            'mailbox_folders' => $mailbox_folders]);
    }
}
