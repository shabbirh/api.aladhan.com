<?php

namespace Api\Controllers\v1;

use Api\Models\HijriCalendar;
use Mamluk\Kipchak\Components\Controllers\Slim;
use Mamluk\Kipchak\Components\Http;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Hijri extends Slim
{
    public HijriCalendar $h;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->h = new HijriCalendar();
    }

    public function greogorianToHijriCalendar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $y = (int)Http\Request::getAttribute($request, 'year');
        $m = (int)Http\Request::getAttribute($request, 'month');
        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;

        return Http\Response::json($response,
            $this->h->getGToHCalendar($m, $y, $adjustment),
            200,
            true,
            604800
        );
    }

    public function hijriToGregorianCalendar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $y = (int)Http\Request::getAttribute($request, 'year');
        $m = (int)Http\Request::getAttribute($request, 'month');
        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;

        return Http\Response::json($response,
            $this->h->getHtoGCalendar($m, $y, $adjustment),
            200,
            true,
            604800
        );
    }

    public function gregorianToHijriDate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $d = Http\Request::getAttribute($request, 'date');
        if ($d === null) {
            $d = Http\Request::getQueryParam($request, 'date');
            if ($d === null) {
                $date = date('d-m-Y', time());

                return Http\Response::redirect($response, '/v1/gToH/' . $date . '?' . http_build_query($request->getQueryParams()), 302);
            }

            return Http\Response::redirect($response, '/v1/gToH/' . $d . '?' . http_build_query($request->getQueryParams()), 301);
        }

        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;
        $result = $this->h->gToH($d, $adjustment);

        if ($result) {
            return Http\Response::json($response,
                $result,
                200,
                true,
                604800
            );
        }

        return Http\Response::json($response,
            'Invalid date or unable to convert it.',
            404
        );

    }

    public function hijriToGregorianDate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $d = Http\Request::getAttribute($request, 'date');
        if ($d === null) {
            $d = Http\Request::getQueryParam($request, 'date');
            if ($d === null) {
                $date = date('d-m-Y', time());
                $fs = $this->h->gToH($date);
                $date = $fs['hijri']['date'];

                return Http\Response::redirect($response, '/v1/hToG/' . $date . '?' . http_build_query($request->getQueryParams()), 302);
            }

            return Http\Response::redirect($response, '/v1/hToG/' . $d . '?' . http_build_query($request->getQueryParams()), 301);
        }

        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;
        $result = $this->h->hToG($d, $adjustment);

        if ($result) {
            return Http\Response::json($response,
                $result,
                200,
                true,
                604800
            );
        }

        return Http\Response::json($response,
            'Invalid date or unable to convert it.',
            404
        );
    }

    public function nextHijriHoliday(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;
        $result = $this->h->nextHijriHoliday(360, $adjustment);

        if ($result) {
            return Http\Response::json($response,
                $result,
                200
            );
        }

        return Http\Response::json($response,
            'Unable to compute next holiday.',
            400
        );
    }

    public function currentIslamicYear(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Http\Response::json($response,
            $this->h->getCurrentIslamicYear(),
            200
        );
    }

    public function currentIslamicMonth(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;

        return Http\Response::json($response,
            $this->h->getCurrentIslamicMonth($adjustment),
            200
        );
    }

    public function islamicYearFromGregorianForRamadan(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $y = Http\Request::getAttribute($request, 'year');

        if ($y !== null && (int)$y > 622) {
            return Http\Response::json($response,
                $this->h->getIslamicYearFromGregorianForRamadan((int)$y),
                200
            );
        }

        return Http\Response::json($response,
            'Please specify a valid year',
            400
        );
    }

    public function hijriHolidays(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $d = Http\Request::getAttribute($request, 'day');
        $m = Http\Request::getAttribute($request, 'month');

        if ($d === null && (int)$m === null && (int)$m > 12 && (int)$m < 1 && (int)$d < 1 && (int)$d > 30) {
            return Http\Response::json($response,
                'Please specify a valid day and month',
                400
            );
        }
        $result = $this->h->getHijriHolidays((int)$d, (int)$m);
        if (!empty($result)) {
            return Http\Response::json($response,
                $result,
                200
            );
        }

        return Http\Response::json($response,
            'No holidays found.',
            404
        );
    }

    public function specialDays(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Http\Response::json($response,
            $this->h->specialDays(),
            200
        );
    }

    public function islamicMonths(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Http\Response::json($response,
            $this->h->getIslamicMonths(),
            200
        );
    }

    public function islamicHolidaysByHijriYear(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $y = Http\Request::getAttribute($request, 'year');
        $a = Http\Request::getQueryParam($request, 'adjustment');
        $adjustment = $a === null ? 0 : $a;
        $result = $this->h->getIslamicHolidaysByHijriYear($y, $adjustment);
        if (!empty($result)) {
            return Http\Response::json($response,
                $result,
                200
            );
        }

        return Http\Response::json($response,
            'No holidays found.',
            404
        );

    }

}