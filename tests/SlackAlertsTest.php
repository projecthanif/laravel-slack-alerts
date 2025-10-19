<?php

use Illuminate\Support\Facades\Bus;
use Spatie\SlackAlerts\Exceptions\JobClassDoesNotExist;
use Spatie\SlackAlerts\Exceptions\WebhookUrlNotValid;
use Spatie\SlackAlerts\Facades\SlackAlert;
use Spatie\SlackAlerts\Jobs\SendToSlackChannelJob;

beforeEach(function () {
    Bus::fake();
});

it('can dispatch a job to send a message to slack using the default webhook url', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('can dispatch a job to send a set of blocks to slack using the default webhook url', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::blocks([
        [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => "Hello!",
            ],
        ],
    ]);

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('can dispatch a job to send a message to slack using an alternative webhook url', function () {
    config()->set('slack-alerts.webhook_urls.marketing', 'https://test-domain.com');

    SlackAlert::to('marketing')->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('can dispatch a job to send a message to slack alternative channel', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::toChannel('random')->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('will throw an exception for a non existing job class', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.job', 'non-existing-job');

    SlackAlert::message('test-data');
})->throws(JobClassDoesNotExist::class);

it('will not throw an exception for an empty webhook url', function () {
    config()->set('slack-alerts.webhook_urls.default', '');

    SlackAlert::message('test-data');
})->expectNotToPerformAssertions();

it('will not send a message if the alerts are disabled', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.enabled', false);

    SlackAlert::message('test-data');
    SlackAlert::blocks([]);

    Bus::assertNotDispatched(SendToSlackChannelJob::class);
});

it('will throw an exception for an invalid webhook url', function () {
    config()->set('slack-alerts.webhook_urls.default', 'not-an-url');

    SlackAlert::message('test-data');
})->throws(WebhookUrlNotValid::class);

it('will throw an exception for an invalid job class', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.job', '');

    SlackAlert::message('test-data');
})->throws(JobClassDoesNotExist::class);

it('will throw an exception for a missing job class', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.job', null);

    SlackAlert::message('test-data');
})->throws(JobClassDoesNotExist::class);

it('can send a message via a queue set in config file ', function (string $queue) {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.queue', $queue);

    SlackAlert::message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
})->with([
            'default',
            'my-queue',
        ]);

it('can send a message via a queue set at runtime ', function (string $queue) {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    config()->set('slack-alerts.queue', 'custom-queue');

    SlackAlert::onQueue($queue)->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
})->with([
            'default',
            'my-queue',
        ]);

it('can send a message via a username set at runtime ', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::withUsername('My New Name #1')->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('can send a message via a icon_url set at runtime ', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::withIconURL('https://www.example.com/icon.jpg')->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});

it('can send a message via a icon_emoji set at runtime ', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');

    SlackAlert::withIconEmoji(':heart:')->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
});


it('can send message sync', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    SlackAlert::sync()->message('test-data');

    Bus::assertDispatchedSync(SendToSlackChannelJob::class);
});


it('can send a message async when sync is false', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    SlackAlert::sync(false)->message('test-data');

    Bus::assertDispatched(SendToSlackChannelJob::class);
    Bus::assertNotDispatchedSync(SendToSlackChannelJob::class);
});


it('works with blocks when sync is enabled', function () {
    config()->set('slack-alerts.webhook_urls.default', 'https://test-domain.com');
    SlackAlert::sync()->blocks([
        [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => "Hello!",
            ],
        ],
    ]);

    Bus::assertDispatchedSync(SendToSlackChannelJob::class);
});
