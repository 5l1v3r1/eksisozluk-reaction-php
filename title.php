<?php

include_once "constants.php";
include_once "entry.php";
include_once "page.php";

class Title
{
	public $URL;
	public $datatitle;
	public $noPage;
	public $noEntries;
	public $entries = array();

	public function __construct($URL)
	{
		$this->URL = $URL;

		$htmlString = file_get_contents($URL);

		libxml_use_internal_errors(true);
		$doc = DOMDocument::loadHTML($htmlString);
		$xpath = new DOMXPath($doc);

		// title
		$nodes = $xpath->query("//div[@id='container']/div[@id='main']/div[@id='content']/section[@id='content-body']/div[@id='topic']/h1");
		if ($nodes->length) {
		    $this->datatitle = $nodes->item(0)->getAttribute('data-title');
		}

		// noPage
		$nodes = $xpath->query("//div[@id='container']/div[@id='main']/div[@id='content']/section[@id='content-body']/div[@id='topic']/div[@class='pager']");
		if ($nodes->length) {
		    $this->noPage = $nodes->item(0)->getAttribute('data-pagecount');
		}

		// noEntries
		$lastPage = new Page($URL."?p=".$this->noPage);

		if ($this->noPage > 1)
		{
			$this->noEntries = 	((($this->noPage)-1) * NO_ENTRY_PER_PAGE ) + sizeof($lastPage->getPage());
		}else
		{
			$this->noEntries = 	sizeof($lastPage->getPage());
		}
		
		// entries
		$titleURLs = array();
		for($i=1; $i<=$this->noPage;$i++)
		{
			array_push($titleURLs,$URL."?p=$i");	
		}

		$pages = $this->runRequests($titleURLs);

		foreach ($pages as $page) {
			if (isset($page))
			{
				$tempPage = new Page($page["result"]);
				foreach ($tempPage->getPage() as $entry) {
					array_push($this->entries,$entry);	
				}	
			}
		
		}

		return $this;

	}

	public function process()
	{
		$result = array();
	
		if (count($this->entries)==0)
			return $result;

		date_default_timezone_set('Europe/Istanbul');

		$result = array();

		foreach ($this->entries as $entry) {
			$time = explode(":",$entry->first_time);
			if (0 <= $time[1] && $time[1] < 15)
			{
				$entry->first_time = $time[0] . ":00";
			}else if (15 <= $time[1] && $time[1] < 30)
			{
				$entry->first_time = $time[0] . ":15";
			}else if (30 <= $time[1] && $time[1] < 45)
			{
				$entry->first_time = $time[0] . ":30";
			}else if (45 <= $time[1] && $time[1] < 60)
			{
				$entry->first_time = $time[0] . ":45";
			}
		}

		
		$lastTime = strtotime($this->entries[count($this->entries)-1]->first_date . " " . $this->entries[count($this->entries)-1]->first_time);
		$timeStamp = strtotime($this->entries[0]->first_date . " " . "00:00");


		while (1)
		{
			$count = 0;
			foreach ($this->entries as $entry) {
				if (strtotime($entry->first_date . " " . $entry->first_time) == $timeStamp)
				{
					$count++;
				}

				if (strtotime($entry->modified_date . " " . $entry->modified_time) == $timeStamp)
				{
					$count++;
				}
			}

			array_push($result, array(date('d.m.Y H:i', $timeStamp),$count));

			$timeStamp = strtotime("+15 minutes", $timeStamp);

			if ($timeStamp >= $lastTime)
			{
				break;
			}
		}

		return $result;

		
	}

	public function runRequests($url_array, $thread_width = 500) {
	    $threads = 0;
	    $master = curl_multi_init();
	    $curl_opts = array(CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_MAXREDIRS => 5,
	        CURLOPT_CONNECTTIMEOUT => 15,
	        CURLOPT_TIMEOUT => 15,
	        CURLOPT_RETURNTRANSFER => TRUE);
	    $results = array();

	    $count = 0;
	    foreach($url_array as $url) {
	        $ch = curl_init();
	        $curl_opts[CURLOPT_URL] = $url;

	        curl_setopt_array($ch, $curl_opts);
	        curl_multi_add_handle($master, $ch); //push URL for single rec send into curl stack
	        $results[$count] = array("url" => $url, "handle" => $ch);
	        $threads++;
	        $count++;
	        if($threads >= $thread_width) { //start running when stack is full to width
	            while($threads >= $thread_width) {
	                usleep(100);
	                while(($execrun = curl_multi_exec($master, $running)) === -1){}
	                curl_multi_select($master);
	                // a request was just completed - find out which one and remove it from stack
	                while($done = curl_multi_info_read($master)) {
	                    foreach($results as &$res) {
	                        if($res['handle'] == $done['handle']) {
	                            $res['result'] = curl_multi_getcontent($done['handle']);
	                        }
	                    }
	                    curl_multi_remove_handle($master, $done['handle']);
	                    curl_close($done['handle']);
	                    $threads--;
	                }
	            }
	        }
	    }
	    do { //finish sending remaining queue items when all have been added to curl
	        usleep(100);
	        while(($execrun = curl_multi_exec($master, $running)) === -1){}
	        curl_multi_select($master);
	        while($done = curl_multi_info_read($master)) {
	            foreach($results as &$res) {
	                if($res['handle'] == $done['handle']) {
	                    $res['result'] = curl_multi_getcontent($done['handle']);
	                }
	            }
	            curl_multi_remove_handle($master, $done['handle']);
	            curl_close($done['handle']);
	            $threads--;
	        }
	    } while($running > 0);
	    curl_multi_close($master);
	    return $results;
	}



}


?>