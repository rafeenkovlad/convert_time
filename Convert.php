<?php

Class Convert
{
    public function checkTime(&$time, string $default = 'h'): void
    {
        preg_match_all('/(\d{1,6}[w|d|h|m])/isx', $time, $result);

        $sort = [
            'w' => null,
            'd' => null,
            'h' => null, //default
            'm' => null
        ];

        if (!empty($result[0])) {
            $checkFormatTemp = null;
            $formatTime = [];
            foreach ($result[0] as $item) {
                $format = preg_replace('/\d+(\w)/isx', '$1', $item);
                if ($checkFormatTemp === $format) {
                    throw new RuntimeException('Неверный формат времени, повторяющееся значение: ' . $format);
                }
                $formatTime[$format] = $item;
                $checkFormatTemp = $format;
            }

            foreach ($formatTime as $key => $item) {
                $sort[$key] = $item;
            }
        }

        $sort = array_filter($sort, static fn(?string $item) => $item !== null);

        if (empty($sort)) {
            $sort[$default] = $time . $default;
        }

        $this->convertFn($sort);

        foreach ($sort as $f => $val) {
            if ((int)$val === 0) {
                unset($sort[$f]);
                continue;
            }
            $sort[$f] = $val . $f;
        }

        $time = $sort;
    }

    public function convertFn(array &$sort): void
    {
        $convert = [
            'w' => ['month' => 99999], //недели не переводим в месяцы
            'd' => ['w' => 7],
            'h' => ['d' => 24, 'w' => 7],
            'm' => ['h' => 60, 'd' => 24, 'w' => 7],
        ];

        $result = [];

        foreach ($sort as $format => $val) {
            $val = (int)$val;
            $time = [];

            $how = ['h' => 'm', 'd' => 'h', 'w' => 'd'];
            $i = 0;
            foreach ($convert[$format] as $f => $denominator) {
                $module = null;

                if ($val >= $denominator) {
                    $i++;
                    $valTemp = $val;
                    $val = intdiv($val, $denominator);
                    $time [$f] = $val;
                    $module = $valTemp % $denominator;
                    $time [$how[$f]] = $module;
                    continue;
                }


                if ($module || $module === 0) {
                    $time[$f] = $module;
                    break;
                }

                if ($i !== 0) {
                    break;
                }

                $time[$format] = $val;
                break;
            }

            $result [] = $time;
        }


        $sort = array_reduce($result, fn($init, array $val) => $init = ['w' => $init['w'] + $val['w'], 'd' => $init['d'] + $val['d'], 'h' => $init['h'] + $val['h'], 'm' => $init['m'] + $val['m']], ['w' => null, 'd' => null, 'h' => null, 'm' => null]);

        $format = ['h' => 24, 'd' => 7, 'm' => 60];
        foreach ($sort as $f => $val) {
            if (isset($format[$f]) && $format[$f] < $val) {
                $this->convertFn($sort);
            }
        }
    }

    private function convertTimeOnExecute(&$timeOnExecute): void
    {
        /*--- Convert to time format "m" ---*/
        preg_match_all('/\d{1,6}[w|d|h|m]/isx', $timeOnExecute, $match);


        if ($match[0]) {
            $timeOnExecute = $match[0];
            $timeOnExecute = array_reduce($timeOnExecute, fn($init, $f) => $init += $this->strToTime($f), 0);
        } else {
            $timeOnExecute *= 60;
        }
        /*--- end ---*/
    }

    public function formatUnicodeTime(&$diff, $isShortDateName = false)
    {
        $str = [];
        foreach ($diff as $f => $v) {
            if ($f === 'w') {
                $v = (int)$v;
                if ($isShortDateName === true) {
                    $str[] = $v . 'н';
                    continue;
                }
                if ($v === 1) {
                    $str[] = $v . ' неделю';
                }
                if ($v > 1 && $v < 5) {
                    $str[] = $v . ' недели';
                }
                if ($v > 4) {
                    $str[] = $v . ' недель';
                }
            }

            if ($f === 'd') {
                $v = (int)$v;
                if ($isShortDateName === true) {
                    $str[] = $v . 'д';
                    continue;
                }
                if ($v === 1) {
                    $str[] = $v . ' день';
                }
                if ($v > 1 && $v < 5) {
                    $str[] = $v . ' дня';
                }
                if ($v > 4) {
                    $str[] = $v . ' дней';
                }
            }

            if ($f === 'h') {
                $v = (int)$v;
                if ($isShortDateName === true) {
                    $str[] = $v . 'ч';
                    continue;
                }
                if ($v === 1) {
                    $str[] = $v . ' час';
                }
                if ($v > 1 && $v < 5) {
                    $str[] = $v . ' часа';
                }
                if ($v > 4) {
                    $str[] = $v . ' часов';
                }
            }

            if ($f === 'm') {
                $v = (int)$v;
                if ($isShortDateName === true) {
                    $str[] = $v . 'м';
                    continue;
                }
                if ($v === 1) {
                    $str[] = $v . ' минута';
                }
                if ($v > 1 && $v < 5) {
                    $str[] = $v . ' минуты';
                }
                if ($v > 4) {
                    $str[] = $v . ' минут';
                }
            }
        }
        $diff = implode(' ', $str);
    }
}