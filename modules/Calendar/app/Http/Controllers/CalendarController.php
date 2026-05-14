<?php

declare(strict_types=1);

namespace Modules\Calendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Library\Services\InstrumentService;
use Modules\Sanctkb\Services\SanctionService;

class CalendarController extends Controller
{
    public function __construct(
        private readonly InstrumentService $instrumentService,
        private readonly SanctionService $sanctionService,
    ) {}

    public function index(Request $request): Response
    {
        $from = now()->startOfDay();
        $to = now()->addDays(90)->endOfDay();

        $events = $this->instrumentService->obligationsForCalendar($from, $to);
        $sanctions = $this->sanctionService->forCalendar($from, $to);

        $allEvents = $events->concat($sanctions)
            ->sortBy('due_date')
            ->values()
            ->toArray();

        return Inertia::render('Calendar/Index', [
            'events'   => $allEvents,
            'month'    => (int) now()->format('n'),
            'year'     => (int) now()->format('Y'),
            'viewMode' => 'list',
        ]);
    }

    public function ics(Request $request): HttpResponse
    {
        $from = now()->startOfDay();
        $to = now()->addDays(90)->endOfDay();

        $events = $this->instrumentService->obligationsForCalendar($from, $to);

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Atheris Compliance//EN'];

        foreach ($events as $event) {
            $dtStart = str_replace('-', '', $event['due_date']).'T000000Z';
            $dtEnd = str_replace('-', '', $event['due_date']).'T235959Z';
            $uid = 'obligation-'.$event['id'].'@atheris.compliance';
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$uid;
            $lines[] = 'DTSTART:'.$dtStart;
            $lines[] = 'DTEND:'.$dtEnd;
            $lines[] = 'SUMMARY:'.str_replace(["\n", "\r"], ' ', $event['obligation']);
            $lines[] = 'DESCRIPTION:'.str_replace(["\n", "\r"], ' ', $event['instrument']);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="compliance-calendar.ics"',
        ]);
    }
}
