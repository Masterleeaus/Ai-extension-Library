<?php

namespace App\Extensions\SocialMedia\System\Services\Publisher;

use App\Extensions\SocialMedia\System\Models\SocialMediaPost;
use App\Extensions\SocialMedia\System\Services\Publisher\Contracts\BasePublisherService;
use App\Extensions\SocialMedia\System\Services\SocialMediaChannelEntitlementService;
use DomainException;

class PublisherDriver
{
    public SocialMediaPost $post;

    public function setPost(SocialMediaPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getPost(): SocialMediaPost
    {
        return $this->post;
    }

    public function getDriver(): ?BasePublisherService
    {
        if ($this->post->platform) {
            $platform = $this->post->platform;
            $user = $platform->user;

            if (! $user || ! app(SocialMediaChannelEntitlementService::class)->canPublish($user, $platform)) {
                throw new DomainException(trans('This channel is paused because it is outside your Titan Reach channel allowance.'));
            }

            $driver = match ($platform->platform ?: 'default') {
                'instagram'      => app(InstagramService::class),
                'facebook'       => app(FacebookService::class),
                'linkedin'       => app(LinkedinService::class),
                'x'              => app(XService::class),
                'tiktok'         => app(TiktokService::class),
                'youtube'        => app(YoutubeService::class),
                'youtube-shorts' => app(YoutubeService::class),
                default          => null,
            };

            if ($driver instanceof BasePublisherService) {
                $driver
                    ->setPost($this->post)
                    ->setPlatform($platform);
            }

            return $driver;
        }

        return null;
    }
}
