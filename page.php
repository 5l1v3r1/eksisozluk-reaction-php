<?php

include_once "constants.php";
include_once "entry.php";

class Page
{
	private $arrEntries;

	public function __construct($htmlString)
	{	
		$this->arrEntries = array();
		libxml_use_internal_errors(true);
		$doc = DOMDocument::loadHTML($htmlString);
		$xpath = new DOMXPath($doc);

		$nodes = $xpath->query("//div[@id='container']/div[@id='main']/div[@id='content']/section[@id='content-body']/div[@id='topic']/ul[@id='entry-list']/li");

		if ($nodes->length) 
		{
			for($i=0;$i<$nodes->length;$i++)
			{
				$tempEntry = new Entry;

				$tempEntry->entry_id = $nodes->item($i)->getAttribute('data-id');
				$tempEntry->writer = $nodes->item($i)->getAttribute('data-author');

				if (STORE_ENTRIES)
				{
					$entryNodes = $xpath->query("//div[@id='container']/div[@id='main']/div[@id='content']/section[@id='content-body']/div[@id='topic']/ul[@id='entry-list']/li/div[@class='content']");
					$tempEntry->entry = $entryNodes->item($i)->nodeValue;	
				}

				$datesNodes = $xpath->query("//div[@id='container']/div[@id='main']/div[@id='content']/section[@id='content-body']/div[@id='topic']/ul[@id='entry-list']/li/footer/div[@class='info']/a[@class='entry-date permalink']");
				$dates = explode(" ~ ", $datesNodes->item($i)->nodeValue);

				$first = explode(" ", $dates[0]);
				$tempEntry->first_date = $first[0];
				$tempEntry->first_time = $first[1];

				if (sizeof($dates)>1)
				{
					$second = explode(" ", $dates[1]);
					if (strlen($dates[1])>5)
					{
						$tempEntry->modified_date = $second[0];
						$tempEntry->modified_time = $second[1];
					}else if ($dates[1] == "")
					{
						// leave empty
					}else
					{
						$tempEntry->modified_date = strval($first[0]);
						$tempEntry->modified_time = strval($second[0]);
					}	
				}

				array_push($this->arrEntries, $tempEntry);
			}
		}

		



	}

	public function getPage()
	{
		return $this->arrEntries;
	}

	public function getEntry($index)
	{
		return $this->arrEntries[$index]->entry;
	}





}



?>