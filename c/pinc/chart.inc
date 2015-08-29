<?PHP

/*
 *   include this file in theme header to link in google charts
 *   should be located in html  header
 *   Somewhere else include the code to define the callback function named drawChart
 */

echo "
        <script type='text/javascript' src='https://www.google.com/jsapi'></script>
        <script type='text/javascript'>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawChart);
        </script>\n";


