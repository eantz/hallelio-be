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
        if (!str_contains($start_date, ':')) {
            $start_date .= ' 00:00:00';
        }

        if (!str_contains($end_date, ':')) {
            $end_date .= ' 23:59:59';
        }

        $q_single_events = Event::from('events AS e')
            ->select(
                'e.id',
                'e.event_type',
                'e.title',
                'e.description',
                'e.location',
                'e.start_time',
                'e.end_time',
                'e.is_recurring',
                'e.is_exception',
                'e.exception_event_id',
                'eo.id AS event_occurence_id'
            )
            ->leftJoin('event_occurences AS eo', function ($join) {
                $join->on('e.id', '=', 'eo.event_id')
                    ->whereRaw('DATE(e.start_time) = DATE(eo.occurence_time)');
            })
            ->where('is_recurring', false)
            ->where('is_exception', false)
            ->where('start_time', '>=', $start_date)
            ->where('end_time', '<=', $end_date);

        if ($id != 0) {
            $q_single_events = $q_single_events->where('e.id', $id);
        }

        $single_events = $q_single_events->get();

        $add_id_filter = $id != 0 ? 'AND e.id=' . $id : '';

        DB::statement('SET @start_date = ?', [$start_date]);
        DB::statement('SET @end_date = ?', [$end_date]);

        $recurring_events = DB::select(sprintf('
            SELECT COALESCE(ee.id, rd.id) AS id,
                rd.event_type,
                COALESCE(ee.title, rd.title) AS title,
                COALESCE(ee.description, rd.description) AS description,
                COALESCE(ee.location, rd.location) AS location,
                COALESCE(ee.start_time, rd.start_time) AS start_time,
                COALESCE(ee.end_time, rd.end_time) AS end_time,
                COALESCE(ee.is_recurring, rd.is_recurring) AS is_recurring,
                COALESCE(ee.is_exception, rd.is_exception) AS is_exception,
                COALESCE(ee.exception_event_id, rd.exception_event_id) AS exception_event_id,
                eo.id AS event_occurence_id,
                rd.num AS num
            FROM (
                SELECT e.id,
                    e.event_type,
                    e.title,
                    e.description,
                    e.location,
                    DATE_ADD(
                        e.start_time,
                        INTERVAL n.num *
                            CASE
                                WHEN r.recurrence_type = "daily" THEN 1
                                WHEN r.recurrence_type = "weekly" THEN 7
                                WHEN r.recurrence_type = "monthly" THEN 30
                            END DAY
                    ) AS start_time,
                    DATE_ADD(
                        e.end_time,
                        INTERVAL n.num *
                            CASE
                                WHEN r.recurrence_type = "daily" THEN 1
                                WHEN r.recurrence_type = "weekly" THEN 7
                                WHEN r.recurrence_type = "monthly" THEN 30
                            END DAY
                    ) AS end_time,
                    e.is_recurring,
                    e.is_exception,
                    e.exception_event_id,
                    n.num AS num
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
                WHERE e.is_recurring = TRUE
                    AND e.is_exception = FALSE
                    %s
            ) AS rd
            LEFT JOIN events AS ee
                ON rd.id = ee.exception_event_id
                AND (
                    DATE(rd.start_time) = DATE(ee.exception_time)
                )
            LEFT JOIN event_occurences AS eo
                ON COALESCE(ee.exception_event_id, rd.id) = eo.event_id
                AND DATE(COALESCE(ee.start_time, rd.start_time)) = DATE(eo.occurence_time)
            WHERE rd.start_time BETWEEN @start_date AND @end_date
                AND (
                    ee.id IS NULL 
                    OR (
                        (
                            ee.exception_is_removed IS NULL
                            OR ee.exception_is_removed = FALSE
                        )
                        AND 
                        ee.start_time between @start_date AND @end_date
                    )
                    
                )
            UNION
            SELECT 
                e.id,
                e.event_type,
                ee.title,
                ee.description,
                ee.location,
                ee.start_time,
                ee.end_time,
                e.is_recurring,
                ee.is_exception,
                ee.exception_event_id,
                eo.id AS event_occurence_id,
                0 as num
            FROM events e
            JOIN events ee ON e.id = ee.exception_event_id 
            LEFT JOIN event_occurences AS eo
                ON ee.exception_event_id = eo.event_id
                AND DATE(ee.start_time) = DATE(eo.occurence_time)
            WHERE 
                ee.exception_time NOT BETWEEN @start_date AND @end_date
                AND ee.start_time BETWEEN @start_date AND @end_date
                AND (
                    ee.exception_is_removed IS NULL
                    OR ee.exception_is_removed = FALSE
                )
                %s
        ', $add_id_filter, $add_id_filter));

        $events = [];

        foreach ($single_events as $e) {
            $events[] = [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'is_recurring' => $e->is_recurring,
                'is_exception' => $e->is_exception,
                'exception_id' => null,
                'event_occurence_id' => $e->event_occurence_id,
                'num' => 0
            ];
        }

        foreach ($recurring_events as $e) {
            $events[] = [
                'id' => $e->exception_event_id != null ? $e->exception_event_id : $e->id,
                'event_type' => $e->event_type,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'is_recurring' => $e->is_recurring,
                'is_exception' => $e->is_exception,
                'exception_id' => $e->exception_event_id !== null ? $e->id : null,
                'event_occurence_id' => $e->event_occurence_id,
                'num' => $e->num
            ];
        }

        usort($events, array(EventQueries::class, 'sortEvents'));

        return $events;
    }
}
