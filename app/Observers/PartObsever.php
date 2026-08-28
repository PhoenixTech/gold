<?php

namespace App\Observers;

use App\Models\Part;

class PartObsever
{
    /**
     * Handle the Part "created" event.
     */
    public function created(Part $part): void
    {
        $handle = Part::segmentClass((string) $part->segment, (string) $part->part);
        if ($handle && method_exists($handle, 'onAdd')) {
            $handle::onAdd($part);
        }
    }

    /**
     * Handle the Part "updated" event.
     */
    public function updated(Part $part): void
    {
        if (! $part->isDirty('part') && ! $part->isDirty('segment')) {
            return;
        }

        $oldSegment = (string) ($part->getOriginal('segment') ?? $part->segment);
        $oldPart = (string) ($part->getOriginal('part') ?? $part->part);
        $old = clone $part;
        $old->segment = $oldSegment;
        $old->part = $oldPart;

        $handleOld = Part::segmentClass($oldSegment, $oldPart);
        if ($handleOld && method_exists($handleOld, 'onRemove')) {
            $handleOld::onRemove($old);
        }

        $handle = Part::segmentClass((string) $part->segment, (string) $part->part);
        if ($handle && method_exists($handle, 'onAdd')) {
            $handle::onAdd($part);
        }
    }

    /**
     * Handle the Part "deleted" event.
     */
    public function deleted(Part $part): void
    {
        $handle = Part::segmentClass((string) $part->segment, (string) $part->part);
        if ($handle && method_exists($handle, 'onRemove')) {
            $handle::onRemove($part);
        }
    }

    /**
     * Handle the Part "restored" event.
     */
    public function restored(Part $part): void
    {
        //
    }

    /**
     * Handle the Part "force deleted" event.
     */
    public function forceDeleted(Part $part): void
    {
        //
    }
}
