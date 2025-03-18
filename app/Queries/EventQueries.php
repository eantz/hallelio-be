<?php

namespace App\Queries;

use App\Models\Event;
use Carbon\Carbon;
use DB;

class EventQueries
{
    private static function sortEvents($event_a, $event_b)
    {
        $event_a_start = Carbon::createFromFormat('Y-m-d H:i:s', $event_a['start_time']);
        $event_b_start = Carbon::createFromFormat('Y-m-d H:i:s', $event_b['end_time']);

        if ($event_a_start->equalTo($event_b_start)) {
            return 0;
        }

        return $event_a_start->greaterThan($event_b_start) ? 1 :  -1;
    }

    public static function getEvents($start_date, $end_date, $id = 0)
    {
        $start_date .= ' 00:00:00';
        $end_date .= ' 23:59:59';

        $q_single_events = Event::from('events as e')
            ->select(
                'e.id',
                'e.event_type',
                'e.title',
                'e.description',
                'e.location',
                'e.start_time',
                'e.end_time'
            )
            ->where('is_recurring', false)
            ->where('is_exception', false)
            ->where('start_time', '>=', $start_date)
            ->where('end_time', '<=', $end_date);

        if ($id != 0) {
            $q_single_events = $q_single_events->where('id', $id);
        }

        $single_events = $q_single_events->get();

        $recurring_events = DB::select(sprintf('
            SELECT e.id, e.event_type, 
                COALESCE(ee.title, e.title) AS title, 
                COALESCE(ee.description, e.description) AS description, 
                COALESCE(ee.location, e.location) AS location,
                DATE_ADD(
                    COALESCE(ee.start_time, e.start_time),
                    INTERVAL n.num *
                        CASE
                            WHEN r.recurrence_type = "daily" THEN 1
                            WHEN r.recurrence_type = "weekly" THEN 7
                            WHEN r.recurrence_type = "monthly" THEN 30
                        END DAY
                ) AS start_time, 
                DATE_ADD(
                    COALESCE(ee.start_time, e.end_time),
                    INTERVAL n.num *
                        CASE
                            WHEN r.recurrence_type = "daily" THEN 1
                            WHEN r.recurrence_type = "weekly" THEN 7
                            WHEN r.recurrence_type = "monthly" THEN 30
                        END DAY
                ) AS end_time 
            FROM events AS e 
            INNER JOIN event_recurrences AS r 
                ON e.id = r.event_id 
            INNER JOIN numbers AS n 
                ON n.num *
                    CASE
                        WHEN r.recurrence_type = "daily" THEN 1
                        WHEN r.recurrence_type = "weekly" THEN 7
                        WHEN r.recurrence_type = "monthly" THEN 30
                    END <= DATEDIFF(r.end_date, r.start_date) 
            LEFT JOIN events AS ee 
                ON e.id = ee.exception_event_id 
                    AND DATE_ADD(e.start_time,
                        INTERVAL n.num *
                        CASE
                            WHEN r.recurrence_type = "daily" THEN 1
                            WHEN r.recurrence_type = "weekly" THEN 7
                            WHEN r.recurrence_type = "monthly" THEN 30
                        END DAY
                        ) = ee.start_time 
            WHERE e.is_recurring = 1 
                %s 
                AND DATE_ADD(e.start_time,
                    INTERVAL n.num *	
                    CASE
                        WHEN r.recurrence_type = "daily" THEN 1
                        WHEN r.recurrence_type = "weekly" THEN 7
                        WHEN r.recurrence_type = "monthly" THEN 30
                    END DAY
                ) BETWEEN "%s" AND "%s" 
                AND (
                    ee.exception_is_removed is null or
                    ee.exception_is_removed = 0
                )
        ', $id != 0 ? 'AND e.id=' . $id : '', $start_date, $end_date));

        $events = [];

        foreach ($single_events as $e) {
            $events[] = [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time
            ];
        }

        foreach ($recurring_events as $e) {
            $events[] = [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time
            ];
        }

        usort($events, array(EventQueries::class, 'sortEvents'));

        return $events;
    }
}
