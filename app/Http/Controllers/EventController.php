<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventRecurrence;
use App\Queries\EventQueries;
use Arr;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function list(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date'
        ]);

        $events = EventQueries::getEvents($validated['start_date'], $validated['end_date']);

        return response()->json(['data' => $events]);
    }

    public function detail(Request $request, string $id)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_date'
        ]);

        $events = EventQueries::getEvents($validated['start_time'], $validated['end_time'], $id);

        $event = Arr::first($events);

        return response()->json($event);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'event_type' => [
                'required',
                'max:100',
                Rule::enum(EventType::class)
            ],
            'title' => 'required|max:255',
            'description' => 'required|min:3',
            'location' => 'required|max:255',
            'start_time' => 'nullable|string|date_format:Y-m-d H:i:s',
            'end_time' => 'nullable|string|date_format:Y-m-d H:i:s|after:start_time',
            'is_recurring' => 'boolean',
            'recurrence' => 'required_if_accepted:is_recurring',
            'recurrence.recurrence_type' => [
                Rule::requiredIf($request->input('recurrence') != null),
                Rule::in(['daily', 'weekly', 'monthly'])
            ],
            'recurrence.start_date' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'string',
                'date_format:Y-m-d'
            ],
            'recurrence.end_date' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'string',
                'date_format:Y-m-d',
                'after:recurrences.end_date'
            ],
            'recurrence.interval' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'integer'
            ]
        ]);

        try {
            DB::beginTransaction();

            $event = Event::create([
                'event_type' => $validated['event_type'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'location' => $validated['location'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_recurring' => $validated['is_recurring'],
            ]);



            if ($event->is_recurring) {
                $recurrence = new EventRecurrence([
                    'recurrence_type' => $validated['recurrence']['recurrence_type'],
                    'start_date' => $validated['recurrence']['start_date'],
                    'end_date' => $validated['recurrence']['end_date'],
                    'interval' => $validated['recurrence']['interval'],
                ]);

                $event->recurrence()->save($recurrence);
            }

            DB::commit();
        } catch (Exception $e) {
            \Log::error($e->__toString());
            DB::rollBack();

            throw $e;
        }

        $event->load('recurrence');

        return $event;
    }

    public function update(Request $request, string $eventID)
    {
        $event = Event::with(['recurrence'])->find($eventID);

        if (!$event) {
            throw ValidationException::withMessages(['id' => 'Event not found']);
        }

        $validated = $request->validate([
            'event_type' => [
                'required',
                'max:100',
                Rule::enum(EventType::class)
            ],
            'title' => 'required|max:255',
            'description' => 'required|min:3',
            'location' => 'required|max:255',
            'start_time' => 'nullable|string|date_format:Y-m-d H:i:s',
            'end_time' => 'nullable|string|date_format:Y-m-d H:i:s|after:start_time',
            'is_recurring' => 'boolean',
            'recurrence' => 'required_if_accepted:is_recurring',
            'recurrence.recurrence_type' => [
                Rule::requiredIf($request->input('recurrence') != null),
                Rule::in(['daily', 'weekly', 'monthly'])
            ],
            'recurrence.start_date' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'string',
                'date_format:Y-m-d'
            ],
            'recurrence.end_date' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'string',
                'date_format:Y-m-d',
                'after:recurrences.end_date'
            ],
            'recurrence.interval' => [
                Rule::requiredIf($request->input('recurrence') != null),
                'integer'
            ]
        ]);

        try {
            DB::beginTransaction();

            $event->event_type = $validated['event_type'];
            $event->title = $validated['title'];
            $event->description = $validated['description'];
            $event->location = $validated['location'];
            $event->start_time = $validated['start_time'];
            $event->end_time = $validated['end_time'];
            $event->is_recurring = $validated['is_recurring'];

            $event->save();

            $recurrence = $event->recurrence;
            if (in_array('recurrence', $validated)) {
                if ($recurrence == null) {
                    $recurrence = new EventRecurrence();
                }

                $recurrence->recurrence_type = $validated['recurrence']['recurrence_type'];
                $recurrence->start_date = $validated['recurrence']['start_date'];
                $recurrence->end_date = $validated['recurrence']['end_date'];
                $recurrence->interval = $validated['recurrence']['interval'];
            }

            if ($event->wasChanged('is_recurring') and $event->is_recurring) {
                $event->recurrence()->save($recurrence);
            } elseif ($event->wasChanged('is_recurring') and !$event->is_recurring) {
                $event->recurrence()->delete();
            } elseif ($event->is_recurring) {
                $event->recurrence()->save($recurrence);
            }

            DB::commit();
        } catch (Exception $e) {
            \Log::error($e->__toString());
            DB::rollBack();

            throw $e;
        }

        $event->load('recurrence');

        return $event;
    }
}
