<style type="text/css">
.month_calendar {
    width:100%;
    z-index:1;
    padding:5px;
    background-color:white;
}
table.calendar {
    width: 100%;
    border-collapse: collapse;
}
table.calendar th {
    background: #EEEEEE;
}
table.calendar th,
table.calendar td {
    font-size:9pt;
    border: 1px solid #CCCCCC;
    text-align: center;
    padding: 8px 0;
}
.today {
    background-color:#ffcfb0;
}
</style>
<?php
 
// 現在の年月を取得
$year = date('Y');
$month = date('n');
$weekheader = ["日","月","火","水","木","金","土"];
 // 月末日を取得
$last_day = date('j', mktime(0, 0, 0, $month + 1, 0, $year));
$first_week = date('w',mktime(0, 0, 0, $month, 1, $year));
$last_week = $last_day + $first_week;
$column = 0;
$date = 1;
echo "<div class='month_calendar'>";
echo "{$year}年{$month}月のカレンダー";
echo "<table class='calendar'>";
echo "<tr>";
foreach($weekheader as $wk) echo "<th>{$wk}</th>";
echo "</tr>";
for($row = 1; $row < 6; $row++) {
    echo "<tr>";
    for($col = 1; $col <= 7; $col++) {
        $lnk = date('Y/m/d',mktime(0, 0, 0, $month, $date, $year));
        $cls = (date('Y/m/d')===$lnk) ? ' class="today"':'';
        echo "<td{$cls}>";
        if($column >= $first_week && $column <$last_week) {
            if(array_key_exists($lnk,$MyModel->Monthly)) {
                echo "<a href='#{$date}'>{$date}</a>";
                $date++;
            } else
            echo $date++;
        }
        $column++;
        echo "</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";