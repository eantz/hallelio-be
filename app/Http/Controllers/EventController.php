<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventOccurence;
use App\Models\EventRecurrence;
use App\Queries\EventQueries;
use Arr;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{

    private function getBaseValidationRules(Request $request)
    {
        return [
            'event_type' => [
                'required',
                'max:100',
                Rule::enum(EventType::class)
            ],
            'title' => 'required|max:255',
            'description' => 'required|min:3',
            'location' => 'required|max:255',
            'start_time' => 'nullable|string|date_format:Y-m-d H:i:s,Y-m-d H:i',
            'end_time' => 'nullable|string|date_format:Y-m-d H:i:s,Y-m-d H:i|after:start_time',
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
        ];
    }



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
            'end_time' => 'required|date_format:Y-m-d H:i:s|after:start_date',
            'include_recurrence_info' => 'nullable|boolean'
        ]);

        $events = EventQueries::getEvents($validated['start_time'], $validated['end_time'], $id);

        $event = Arr::first($events);

        if (!$event) {
            return response(['message' => 'Event not found'], 404);
        }

        if (array_key_exists('include_recurrence_info', $validated) && $validated['include_recurrence_info'] && !$event['is_exception']) {
            $recurrence = EventRecurrence::where('event_id', $event['is_exception'] ? $event['exception_event_id'] : $event['id'])
                ->first();

            if ($recurrence) {
                $event['recurrence'] = [
                    'recurrence_type' => $recurrence->recurrence_type,
                    'start_date' => $recurrence->start_date,
                    'end_date' => $recurrence->end_date,
                    'interval' => $recurrence->interval
                ];
            }
        }

        return response()->json($event);
    }

    public function create(Request $request)
    {
        $validated = $request->validate($this->getBaseValidationRules($request));

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

        $validation_rules = $this->getBaseValidationRules($request);
        $validation_rules['mode'] = [
            Rule::requiredIf($event->is_recurring),
            Rule::in(['this', 'this_and_following'])
        ];
        $validation_rules['selected_start_time'] = [
            Rule::requiredIf($request->input('mode') !== null),
            'date_format:Y-m-d H:i:s,Y-m-d H:i',
        ];
        $validation_rules['selected_end_time'] = [
            Rule::requiredIf($request->input('mode') !== null),
            'date_format:Y-m-d H:i:s,Y-m-d H:i',
            'after:selected_start_time'
        ];

        $validated = $request->validate($validation_rules);

        if (array_key_exists('mode', $validated)) {
            $recurring_events = EventQueries::getEvents($validated['selected_start_time'], $validated['selected_end_time'], $eventID);

            if (count($recurring_events) == 0) {
                throw ValidationException::withMessages(['id' => 'Event not found']);
            }

            $event = $recurring_events[0];

            // handle event exception
            if ($validated['mode'] == 'this') {
                // using this mode, new data's recurrence will not be read
                // since exception will always not be recurring

                if ($event['is_exception']) {
                    // if the original data is already an exception
                    // update the exception event

                    $updated_event = DB::table('events')
                        ->where('id', $event['exception_id'])
                        ->update([
                            'title' => $validated['title'],
                            'description' => $validated['description'],
                            'location' => $validated['location'],
                            'start_time' => $validated['start_time'],
                            'end_time' => $validated['end_time'],
                            'updated_at' => Carbon::now()
                        ]);

                    return $updated_event;
                } else {
                    // make an exception
                    $new_event = Event::create([
                        'event_type' => $validated['event_type'],
                        'title' => $validated['title'],
                        'description' => $validated['description'],
                        'location' => $validated['location'],
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'is_recurring' => false,
                        'is_exception' => true,
                        'exception_event_id' => $event['id'],
                        'exception_time' => $validated['selected_start_time']
                    ]);

                    return $new_event;
                }
            } else if ($validated['mode'] == 'this_and_following') {

                if ($event['is_exception']) {
                    // if original data is arlready an exception, just update the exception (without recurrence)
                    // since the exception will always not be recurring

                    $updated_event = DB::table('events')
                        ->where('id', $event['exception_id'])
                        ->update([
                            'title' => $validated['title'],
                            'description' => $validated['description'],
                            'location' => $validated['location'],
                            'start_time' => $validated['start_time'],
                            'end_time' => $validated['end_time'],
                            'updated_at' => Carbon::now()
                        ]);

                    return $updated_event;
                } else {

                    try {
                        DB::beginTransaction();

                        if ($event['num'] > 0) {
                            // if event is not the first event, 
                            // stop current event to the date before this new event
                            $new_end_time = new Carbon($event['start_time'])->subDay();

                            DB::table('event_recurrences')
                                ->where('event_id', $event['id'])
                                ->update([
                                    'end_date' => $new_end_time,
                                    'updated_at' => Carbon::now()
                                ]);

                            // create a new recurring event, so it will not connected to the previous event
                            // thus, exception will always not be recurring
                            $new_event = Event::create([
                                'event_type' => $validated['event_type'],
                                'title' => $validated['title'],
                                'description' => $validated['description'],
                                'location' => $validated['location'],
                                'start_time' => $validated['start_time'],
                                'end_time' => $validated['end_time'],
                                'is_recurring' => $validated['is_recurring'],
                            ]);

                            if ($new_event->is_recurring) {
                                $recurrence = new EventRecurrence([
                                    'recurrence_type' => $validated['recurrence']['recurrence_type'],
                                    'start_date' => $validated['recurrence']['start_date'],
                                    'end_date' => $validated['recurrence']['end_date'],
                                    'interval' => $validated['recurrence']['interval'],
                                ]);

                                $new_event->recurrence()->save($recurrence);
                            }

                            $original_start_time = new Carbon($event['start_time']);
                            $original_exceptions = Event::where('exception_event_id', $eventID)->get();

                            if ($original_exceptions->count() > 0) {

                                $day_diff = $original_start_time->diffInDays($validated['start_time']);

                                if ($day_diff != 0) {
                                    foreach ($original_exceptions as $event_exception) {
                                        $original_exception_time = new Carbon($event_exception->exception_time);
                                        if ($original_exception_time->gt($new_end_time)) {

                                            $event_exception->exception_time = $original_exception_time->addDays($day_diff);
                                            $event_exception->exception_event_id = $new_event->id;
                                            $event_exception->save();
                                        }
                                    }
                                }
                            }


                            DB::commit();

                            $new_event->load('recurrence');

                            return $new_event;
                        } else {
                            // if event is the first event

                            $event_to_update = Event::where('id', $eventID)->first();

                            $original_start_time = new Carbon($event_to_update->getOriginal('start_time'));

                            $event_to_update->title = $validated['title'];
                            $event_to_update->description = $validated['description'];
                            $event_to_update->location = $validated['location'];
                            $event_to_update->start_time = $validated['start_time'];
                            $event_to_update->end_time = $validated['end_time'];
                            $event_to_update->updated_at = Carbon::now();

                            $event_to_update->save();

                            $event_to_update->recurrence()->update([
                                'recurrence_type' => $validated['recurrence']['recurrence_type'],
                                'start_date' => $validated['recurrence']['start_date'],
                                'end_date' => $validated['recurrence']['end_date'],
                                'interval' => $validated['recurrence']['interval'],
                            ]);

                            $exceptions = Event::where('exception_event_id', $eventID)->get();

                            if ($exceptions->count() > 0 && $event_to_update->wasChanged('start_time')) {
                                $day_diff = $original_start_time->diffInDays($event_to_update->start_time);

                                if ($day_diff != 0) {
                                    foreach ($exceptions as $event_exception) {
                                        $original_exception_time = new Carbon($event_exception->exception_time);
                                        $event_exception->exception_time = $original_exception_time->addDays($day_diff);
                                        $event_exception->save();
                                    }
                                }
                            }


                            DB::commit();

                            return $event_to_update;
                        }
                    } catch (Exception $e) {
                        \Log::error($e->__toString());
                        DB::rollBack();

                        throw $e;
                    }
                }
            }
        } else {
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
                if ($validated['is_recurring']) {
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

    public function delete(Request $request, string $eventID)
    {
        $event = Event::with(['recurrence'])->find($eventID);

        if (!$event) {
            throw ValidationException::withMessages(['id' => 'Event not found']);
        }

        $validated = $request->validate([
            'mode' => [
                Rule::requiredIf($event->is_recurring),
                Rule::in(['this', 'this_and_following'])
            ],
            'selected_start_time' => [
                Rule::requiredIf($request->input('mode') !== null),
                'date_format:Y-m-d H:i:s,Y-m-d H:i',
            ],
            'selected_end_time' => [
                Rule::requiredIf($request->input('mode') !== null),
                'date_format:Y-m-d H:i:s,Y-m-d H:i',
                'after:selected_start_time'
            ]
        ]);

        if (array_key_exists('mode', $validated)) {

            if ($validated['mode'] == 'this') {

                if ($event['is_exception']) {
                    // if already exception, just update the exception

                    DB::table('events')
                        ->where('id', $event['exception_id'])
                        ->update([
                            'exception_is_removed' => true
                        ]);

                    return response([
                        'message' => 'Success deleting event'
                    ]);
                } else {
                    // if only delete 1 event, create an exception

                    Event::create([
                        'event_type' => $event['event_type'],
                        'title' => '',
                        'description' => '',
                        'location' => '',
                        'start_time' => null,
                        'end_time' => null,
                        'is_recurring' => false,
                        'is_exception' => true,
                        'exception_event_id' => $event['id'],
                        'exception_time' => $validated['selected_start_time'],
                        'exception_is_removed' => true,
                    ]);

                    return response([
                        'message' => 'Success deleting event'
                    ]);
                }
            } else if ($validated['mode'] == 'this_and_following') {
                // if delete more than one event

                if ($event['num'] == 0) {
                    // if this is the first event, then delete all event, including the recurring

                    DB::beginTransaction();

                    DB::table('event_recurrences')
                        ->where('event_id', $event['id'])
                        ->delete();

                    DB::table('events')
                        ->where('exception_event_id', $event['id'])
                        ->delete();

                    DB::table('events')
                        ->where('id', $event['id'])
                        ->delete();

                    DB::commit();

                    return response([
                        'message' => 'Success deleting event'
                    ]);
                } else {
                    // if not the first event


                    DB::beginTransaction();

                    // update recurring end date to D-1
                    $new_end_time = new Carbon($event['start_time'])->subDay();

                    DB::table('event_recurrences')
                        ->where('event_id', $event['id'])
                        ->update([
                            'end_date' => $new_end_time,
                            'updated_at' => Carbon::now()
                        ]);


                    // delete exceptions dated after selected date
                    $original_start_time = new Carbon($event['start_time']);
                    $original_exceptions = Event::where('exception_event_id', $eventID)
                        ->where('exception_start_time', '>', $original_start_time)
                        ->delete();

                    DB::commit();

                    return response([
                        'message' => 'Success deleting event'
                    ]);
                }
            }
        } else {
            $event->delete();

            return response([
                'message' => 'Success deleting event'
            ]);
        }
    }

    public function registerEventOccurence(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|numeric',
            'occurence_time' => 'required|date_format:Y-m-d H:i:s,Y-m-d H:i',
        ]);

        $existing_event_occurence = EventOccurence::where('event_id', $validated['event_id'])
            ->where('occurence_time', $validated['occurence_time'])
            ->first();

        if ($existing_event_occurence) {
            return $existing_event_occurence;
        }

        $event_occurence = EventOccurence::create([
            'event_id' => $validated['event_id'],
            'occurence_time' => $validated['occurence_time'],
        ]);

        return $event_occurence;
    }

    function getEventOccurence(Request $request, string $id)
    {

        $event_occurence = EventOccurence::where('id', $id)
            ->first();

        if (!$event_occurence) {
            return response(['message' => 'Event Occurence not found'], 404);
        }

        // assume minimum time is 60 mins
        $end_time = (new Carbon($event_occurence->occurence_time))->addMinutes(60);
        $events = EventQueries::getEvents($event_occurence->occurence_time, $end_time, $event_occurence->event_id);

        $event = Arr::first($events);

        $event_occurence->event = $event;

        return $event_occurence;
    }
}
