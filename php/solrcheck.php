<?php

header("Content-type: text/xml");

require(__DIR__.'/init.php');

//replaces certain characters and strings with XML-sanatized versions
function parseToXML($htmlStr)
{
	$xmlStr=str_replace('<','&lt;',$htmlStr);
	$xmlStr=str_replace('>','&gt;',$xmlStr);
	$xmlStr=str_replace('"','&quot;',$xmlStr);
	$xmlStr=str_replace("'",'&#39;',$xmlStr);
	$xmlStr=str_replace("&",'&amp;',$xmlStr);
	return utf8_encode($xmlStr);
}




// create a client instance
$client = new Solarium\Client($config);

// get a select query instance
$query = $client->createQuery($client::QUERY_SELECT);

$fuzzyLimit = 1; //range of fuzzy search (# of operations)- DON'T PUT ABOVE 2 FOR SAKE OF PERFORMANCE


        //example data
/*
$_POST['rows'] = 3;
$_POST['rowStart'] = 0;
$_POST['normalData'] = "mod,help,OP,size";
$_POST['postType'] = "normal";
$_POST['searchBy'] = "all";
*/

//recieve definite post data
$rows = $_POST['rows']; //number of rows (changes depending on version called, normal or advanced)

$helper = $query->getHelper(); //initializes sanatize funciton

$input = ""; //our eventual input

if ($_POST['postType'] == "normal") //for normal version of site
{
  $normalData = $_POST['normalData']; //data from normal version
  $query->setStart($_POST['rowStart']); //starting index
  $normalDataArray = array(); //temp array to redistribute query
  $limiter = substr_count($normalData, ","); //holds # of ","

  for ($i = 0; $i < $limiter; $i++) //for each comma
  {
    $inputer = substr($normalData,0,strpos($normalData, ",")); //first word
    $normalData = substr($normalData,strpos($normalData, ",")+1); //remove first word + comma from the rest (so that we can loop)
    array_push($normalDataArray, $helper->escapePhrase($inputer)."~".$fuzzyLimit); //push first word to array
  }
  $normalDataArray[$limiter+1] = $helper->escapePhrase($normalData)."~".$fuzzyLimit; //clean up- fit the last one in

  $newData = ""; //our new query
  for ($i = 0; $i < $limiter; $i++) //limiter now represents # of words -1
  {
    $newData = $newData . $normalDataArray[$i] . ","; //add each element in data array to new data
  }
  $newData = $newData . $normalDataArray[$limiter + 1]; //clean up- fit last one in without comma at end

  switch($_POST['searchBy']){
    case 'all': //search both title and description
      $input = "title:" . $newData . " OR description:". $newData;
      break;
    case 'title':
      $input = "title:" . $newData;
      break;
    case 'description':
      $input = "description:" . $newData;
      break;
  }

}
else if ($_POST['postType'] == "advanced"){
  //TODO: put everything here
}


//query settings
$query->setRows($rows); //output length
$query->setFields(array('user','date','title', 'description', 'url', 'type', 'score', 'postID')); //fields to return, don't return id
$query->addSort('date', $query::SORT_DESC); //sort- note that SCORE IS NOT PERFECT (not even close lol)

//start building $input, our search

//set the query
$query->setQuery($input);


// this executes the query and returns the result
$resultset = $client->execute($query);

// display the total number of documents found by solr
//echo 'NumFound: '.$resultset->getNumFound();


//construct xml doc

echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<devposts>";

// show documents using the resultset iterator
foreach ($resultset as $document) {

    echo '<post ';

    // the documents are also iterable, to get all fields
    foreach ($document as $field => $value) {
        // this converts multivalue fields to a comma-separated string
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        echo $field . '="' . parseToXML($value) . '" ';
    }

    echo '/>';
}
echo "</devposts>";
?>
