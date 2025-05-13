<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_YouTube;

class AutoCommentYouTube extends Command
{
    protected $signature = 'app:auto-comment';
    protected $description = 'Auto comment';

    public function handle()
    {
        $articles = $this->getRecentArticles();
        $client = $this->initGoogleClient();
        $youtube = new Google_Service_YouTube($client);

        foreach ($articles as $article) {
            $keywords = $this->extractKeywords($article);
            $video = $this->searchRelatedVideo($youtube, $keywords);

            if ($video) {
                $comment = $this->generateComment($article['url']);
                $this->postComment($youtube, $video['id']['videoId'], $comment);
                sleep(rand(3600, 7200));
            }
        }
    }

    private function getRecentArticles(): array
    {
        return \App\Models\Article::whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get(['title', 'url'])
            ->toArray();
    }

    private function extractKeywords(array $article): array
    {
        return explode(' ', $article['title']);
    }

    private function initGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('YouTube Auto Commenter');
        $client->setScopes(['https://www.googleapis.com/auth/youtube.force-ssl']);
        $client->setAuthConfig(storage_path('app/google_oauth.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if (file_exists(storage_path('app/google_token.json'))) {
            $accessToken = json_decode(file_get_contents(storage_path('app/google_token.json')), true);
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents(storage_path('app/google_token.json'), json_encode($client->getAccessToken()));
            }
        }

        return $client;
    }

    private function searchRelatedVideo(Google_Service_YouTube $youtube, array $keywords)
    {
        $query = implode(' ', array_slice($keywords, 0, 5));
        $params = [
            'q' => $query,
            'type' => 'video',
            'maxResults' => 1,
            'order' => 'relevance',
            'publishedAfter' => now()->subDays(30)->toRfc3339String()
        ];

        $response = $youtube->search->listSearch('snippet', $params);
        return $response->items[0] ?? null;
    }

    private function generateComment(string $url): string
    {
        $templates = [
            "Bài viết bên mình có phân tích thêm về game này, mời anh em đọc: %s",
            "Xem thêm bài review chi tiết tại đây: %s",
            "Mình có bài viết khá tâm huyết về game này, link: %s",
        ];
        
        return sprintf($templates[array_rand($templates)], $url);
    }

    private function postComment(Google_Service_YouTube $youtube, string $videoId, string $commentText)
    {
        $commentSnippet = new \Google_Service_YouTube_CommentSnippet();
        $commentSnippet->setTextOriginal($commentText);

        $topLevelComment = new \Google_Service_YouTube_Comment();
        $topLevelComment->setSnippet($commentSnippet);

        $commentThreadSnippet = new \Google_Service_YouTube_CommentThreadSnippet();
        $commentThreadSnippet->setTopLevelComment($topLevelComment);
        $commentThreadSnippet->setVideoId($videoId);

        $commentThread = new \Google_Service_YouTube_CommentThread();
        $commentThread->setSnippet($commentThreadSnippet);

        $youtube->commentThreads->insert('snippet', $commentThread);
    }
}
