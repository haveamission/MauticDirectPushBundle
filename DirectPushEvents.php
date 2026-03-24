<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle;

final class DirectPushEvents
{
    public const SEND_PUSH = 'mautic.direct_push.send';
    public const PUSH_CLICKED = 'mautic.direct_push.clicked';
    public const EXECUTE_CAMPAIGN_ACTION = 'mautic.direct_push.campaign.action';
    public const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.direct_push.campaign.decision';
}
