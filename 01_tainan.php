<?php

$tmpPath = __DIR__ . '/tmp';
if (!file_exists($tmpPath)) {
    mkdir($tmpPath, 0777, true);
}
$targetPath = __DIR__ . '/schools/tainan';
if (!file_exists($targetPath)) {
    mkdir($targetPath, 0777, true);
}

exec("/usr/bin/iconv -futf16 -tutf8 " . __DIR__ . '/stats.moe.gov.tw/103_basec.txt', $lines);

$codes = array();

foreach ($lines AS $line) {
    $cols = explode("\t", $line);
    if (isset($cols[1]) && $cols[1] === '臺南市') {
        $codes[] = $cols[2];
    }
}

exec("/usr/bin/iconv -futf16 -tutf8 " . __DIR__ . '/stats.moe.gov.tw/103_basej.txt', $lines);

foreach ($lines AS $line) {
    $cols = explode("\t", $line);
    if (isset($cols[1]) && $cols[1] === '臺南市') {
        $codes[] = $cols[2];
    }
}

foreach ($codes AS $code) {
    $tmpFile = $tmpPath . '/' . $code;
    if (!file_exists($tmpFile)) {
        file_put_contents($tmpFile, file_get_contents('http://163.26.2.28/sch_data/sch_detail.aspx?sch_code=' . $code));
    }
    $page = file_get_contents($tmpFile);
    $pos = strpos($page, '<div style="text-align: center">');
    if (false !== $pos && false !== strpos($page, '學校代碼')) {
        $page = substr($page, $pos);
        $page = str_replace('&nbsp;', ' ', $page);
        $lineCount = 0;
        $school = array();
        $schoolFound = true;
        $lines = explode('</tr>', $page);
        $currentKey = '';
        $currentKeyLineCount = 0;
        foreach ($lines AS $line) {
            if ($schoolFound) {
                ++$lineCount;
                $cols = explode('</td>', $line);
                foreach ($cols AS $k => $v) {
                    $cols[$k] = trim(strip_tags($v));
                }
                switch ($lineCount) {
                    case 1:
                        $colKey = count($cols) - 2;
                        $school['更新日期'] = substr($cols[$colKey], strpos($cols[$colKey], '2')); //上次更新日期：2014/9/17 下午 09:33:00
                        break;
                    case 2:
                        if (empty($cols[1])) {
                            $schoolFound = false;
                        }
                        $school['學校代碼'] = $cols[1]; //學校代碼
                        break;
                    case 3:
                        $school['中文名稱'] = $cols[1]; //中文名稱
                        break;
                    case 4:
                        $school['英文名稱'] = $cols[1]; //英文名稱
                        break;
                    case 5:
                        $school['建校日期'] = $cols[1]; //建校日期
                        break;
                    case 6:
                        $school['註記'] = $cols[1]; //註 記
                        break;
                    case 7:
                        $school['學校地址'] = $cols[1]; //學校地址
                        break;
                    case 8:
                        $school['學校網址'] = $cols[1]; //學校網址
                        break;
                    case 9:
                        $school['校長'] = $cols[1]; //校 長
                        break;
                    default:
                        switch ($cols[0]) {
                            case '學校電話':
                            case '教職員數':
                            case '學生人數':
                            case '年級班數':
                            case '特殊班級數':
                            case '學 區':
                            case '[校舍校地面積]':
                            case '[歲出結算數]':
                            case '[校長辦學理念]':
                            case '[學校特色]':
                            case '[大事紀]':
                            case '[校務未來發展重點]':
                                $currentKey = $cols[0];
                                $currentKeyLineCount = 0;
                                break;
                        }
                        ++$currentKeyLineCount;
                        switch ($currentKey) {
                            case '學校電話':
                                switch ($currentKeyLineCount) {
                                    case 1:
                                        if (!empty($cols[1])) {
                                            $school[$currentKey] = array();
                                            $school[$currentKey][$currentKey] = $cols[1];
                                        }
                                        break;
                                    case 2:
                                        $school[$currentKey][$cols[1]] = $cols[2];
                                        break;
                                    default:
                                        if (empty($cols[0]) && !empty($cols[1])) {
                                            $school[$currentKey][$cols[1]] = $cols[2];
                                        } elseif (!empty($cols[1])) {
                                            $school[$currentKey][$cols[0]] = $cols[1];
                                        }
                                }
                                break;
                            case '教職員數':
                                switch ($currentKeyLineCount) {
                                    case 1:
                                        if (!empty($cols[1])) {
                                            $school[$currentKey] = array(
                                                'total' => 0,
                                            );
                                            $school[$currentKey][$cols[1]] = intval($cols[2]);
                                            $school[$currentKey]['total'] += $school[$currentKey][$cols[1]];
                                        }
                                        break;
                                    default:
                                        if (!empty($cols[1])) {
                                            $school[$currentKey][$cols[0]] = intval($cols[1]);
                                            $school[$currentKey]['total'] += $school[$currentKey][$cols[0]];
                                        }
                                }

                                break;
                            case '學生人數':
                                $parts = explode('人，', $cols[1]);
                                $partsM = explode('生', $parts[0]);
                                $partsF = explode('生', $parts[1]);
                                $school[$currentKey] = array(
                                    'total' => $partsM[1] + $partsF[1],
                                    $partsM[0] => $partsM[1],
                                    $partsF[0] => $partsF[1],
                                );
                                break;
                            case '年級班數':
                            case '特殊班級數':
                                switch ($currentKeyLineCount) {
                                    case 1:
                                        $school[$currentKey] = array(
                                            'total' => 0,
                                        );
                                        $school[$currentKey][$cols[1]] = intval($cols[2]);
                                        $school[$currentKey]['total'] += $school[$currentKey][$cols[1]];
                                        break;
                                    default:
                                        if (!empty($cols[1])) {
                                            $school[$currentKey][$cols[0]] = intval($cols[1]);
                                            $school[$currentKey]['total'] += $school[$currentKey][$cols[0]];
                                        }
                                }
                                break;
                            case '學 區':
                                $school[$currentKey] = $cols[1];
                                break;
                            case '[校舍校地面積]':
                            case '[歲出結算數]':
                                if ($currentKeyLineCount === 2) {
                                    $sKey = substr($currentKey, 1, -1);
                                    $school[$sKey] = array();
                                    $parts = explode('、', $cols[0]);
                                    foreach ($parts AS $part) {
                                        $a = explode('：', $part);
                                        $school[$sKey][trim($a[0])] = trim($a[1]);
                                    }
                                }
                                break;
                            case '[校長辦學理念]':
                            case '[學校特色]':
                            case '[大事紀]':
                            case '[校務未來發展重點]':
                                if ($currentKeyLineCount === 2) {
                                    $sKey = substr($currentKey, 1, -1);
                                    $school[$sKey] = $cols[0];
                                }
                                break;
                        }
                }
            }
        }
        if ($schoolFound) {
            file_put_contents("{$targetPath}/{$code}.json", json_encode($school, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}