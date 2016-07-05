<head>
<title>Reaction</title>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>
<body>
<br><br><br>
	<div align="center">
	<h1>Reaction</h1>
<?php

include_once "title.php";

$url = $_POST["url"];
$url = str_replace(" ","",$url);

if (!isset($url) || $url == "")
{
	echo "<h4>URL is missing</h4>";
}else if (filter_var($url, FILTER_VALIDATE_URL) === false)
{
	echo "<h4>URL is not valid</h4>";
}else
{
	$parse = parse_url($url);
	if ($parse['host'] == "eksisozluk.com")
	{
		$file_headers = @get_headers($url);
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') 
			echo "<h4>Page not found!</h4>";
		else
		{
			$start = microtime(true);
			$title = new Title($url);
			echo "<div align=\"center\"><h2><i>".$title->datatitle."</i></h2></div>";
			/*
			foreach ($title->entries as $entry) {
				echo "<div align=\"left\">
					<h4>
						entry-id: ".$entry->entry_id." <br>
						writer: ".$entry->writer." <br>
						first-date: ".$entry->first_date." <br>
						first-time: ".$entry->first_time." <br>
						modified-date: ".$entry->modified_date." <br>
						modified-time: ".$entry->modified_time." <br>
						entry: ".$entry->entry." <br>

					</h4>
				</div><br><br>";
			}*/

			$plotData = $title->process();

			$plotX = "";
			$plotY = "";
			
			for ($i=0;$i<count($plotData)-1;$i++)
			{
				$data = $plotData[$i];
				$plotX .= "'".$data[0]."',";
				$plotY .= $data[1].",";
			}
			if (count($plotData)>0)
			{
				$data = $plotData[count($plotData)-1];
				$plotX .= "'".$data[0]."'";
				$plotY .= $data[1];	
			}
			

			
			echo "
			<div id=\"myDiv\" style=\"width: 880px; height: 400px;\"></div>
			  <script>
			    var data = [{
				  x: [".$plotX."],
				  y: [".$plotY."],
				  type: 'bar'
				}];

				Plotly.newPlot('myDiv', data);
			  </script>


			";

			$time_elapsed_secs = microtime(true) - $start;
			echo "<div align=\"center\">
				<h4>
					Total entry: ".count($title->entries)."<br>
					Time: ".$time_elapsed_secs." seconds
				</h4>";
		}

		
	}else
	{
		echo "<h4>Domain name should be eksisozluk.com</h4>";
	}
	
}


?>
	</div>
</body>
</html>