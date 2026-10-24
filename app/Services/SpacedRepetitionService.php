<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SpacedRepetitionItem;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;

class SpacedRepetitionService
{
    /**
     * Record a review for a topic and compute next interval using SM-2.
     *
     * Rating scale 1 to 10 mapped to SM-2 quality 0 to 5:
     * 9-10 -> 5
     * 7-8  -> 4
     * 5-6  -> 3
     * 3-4  -> 2
     * 1-2  -> 1
     */
    public function recordTopicReview(Topic $topic, int $rating1To10): SpacedRepetitionItem
    {
        $quality = (int) min(5, max(0, round(($rating1To10 / 10) * 5)));

        $item = SpacedRepetitionItem::firstOrNew(['topic_id' => $topic->id]);

        $repetition = (int) ($item->repetition_number ?? 0);
        $easeFactor = (float) ($item->ease_factor ?? 2.5);
        $intervalDays = (int) ($item->interval_days ?? 1);

        if ($quality >= 3) {
            if ($repetition === 0) {
                $intervalDays = 1;
            } elseif ($repetition === 1) {
                $intervalDays = 6;
            } else {
                $intervalDays = (int) round($intervalDays * $easeFactor);
            }
            $repetition++;
        } else {
            // Reset repetition if user struggled
            $repetition = 0;
            $intervalDays = 1;
        }

        // Adjust ease factor: EF' = EF + (0.1 - (5 - q) * (0.08 + (5 - q) * 0.02))
        $easeFactor = $easeFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        if ($easeFactor < 1.3) {
            $easeFactor = 1.3;
        }

        $nextReview = now()->addDays($intervalDays);

        $item->repetition_number = $repetition;
        $item->ease_factor = $easeFactor;
        $item->interval_days = $intervalDays;
        $item->last_reviewed_at_utc = now()->toDateTimeString();
        $item->next_review_at_utc = $nextReview->toDateTimeString();
        $item->status = $intervalDays > 30 ? 'mastered' : 'active';
        $item->save();

        return $item;
    }

    /**
     * Get items due for review today or overdue.
     *
     * @return Collection<int, SpacedRepetitionItem>
     */
    public function getDueItems(): Collection
    {
        return SpacedRepetitionItem::with(['topic.phase.roadmap'])
            ->where('status', 'active')
            ->where('next_review_at_utc', '<=', now()->toDateTimeString())
            ->orderBy('next_review_at_utc')
            ->get();
    }
}
