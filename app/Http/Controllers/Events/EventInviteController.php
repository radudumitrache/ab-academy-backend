<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponder;
use App\Models\Event;
use App\Models\Group;
use Illuminate\Http\Request;

class EventInviteController extends Controller
{
    use ApiResponder;

    /**
     * Invite people to an event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function invite(Request $request, $id)
    {
        $user = $request->user();
        $event = Event::find($id);

        if (!$event) {
            return $this->notFound('Event not found.');
        }

        $isAdmin = $user && $user->role === 'admin';
        $isOrganizer = $user && (int) $event->event_organizer === (int) $user->id;

        if (!$isAdmin && !$isOrganizer) {
            return $this->forbidden('Only the event organizer or admin can invite people.');
        }

        $validated = $request->validate([
            'people_ids' => 'nullable|array',
            'people_ids.*' => 'exists:users,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,group_id',
        ]);

        $peopleIds = collect($validated['people_ids'] ?? [])->map(fn ($id) => (int) $id);
        $groupIds = collect($validated['group_ids'] ?? [])->map(fn ($groupId) => (int) $groupId);

        if ($peopleIds->isEmpty() && $groupIds->isEmpty()) {
            return $this->error('Provide at least one person or group to invite.', 422);
        }

        $groupMemberIds = Group::with('students:id')
            ->whereIn('group_id', $groupIds->all())
            ->get()
            ->flatMap(fn ($group) => $group->students->pluck('id'))
            ->map(fn ($memberId) => (int) $memberId);

        $existingGuestIds = collect($event->guests ?? [])
            ->map(function ($guest) {
                if (is_array($guest)) {
                    return isset($guest['id']) ? (int) $guest['id'] : null;
                }

                return is_numeric($guest) ? (int) $guest : null;
            })
            ->filter();

        $updatedGuests = $existingGuestIds
            ->merge($peopleIds)
            ->merge($groupMemberIds)
            ->unique()
            ->values()
            ->all();

        $event->update([
            'guests' => $updatedGuests,
        ]);

        return $this->success($event->load('organizer'), 'People invited successfully.');
    }
}
