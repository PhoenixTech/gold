<?php

namespace App\Enums;

/**
 * Role of a product inside a campaign.
 *
 * `Include` pins a product to the front of the campaign in a hand chosen order.
 * `Exclude` removes a product even when its occasions match the campaign.
 *
 * A product holds exactly one role per campaign — the unique index on
 * (campaign_id, product_id) enforces it — so there is no precedence rule to
 * remember.
 */
enum CampaignProductRole: string
{
    case Include = 'include';
    case Exclude = 'exclude';
}
