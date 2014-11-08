<?php

/**
 * Queries wikidata items without label in a specified language.
 * Released under BSD license.
 */

$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : '';
$items = isset($_REQUEST['items']) ? trim($_REQUEST['items']) : '';
$language = isset($_REQUEST['language']) ? $_REQUEST['language'] : 'fr';
$languagesToPrint = isset($_REQUEST['languagesToPrint']) ? $_REQUEST['languagesToPrint'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Wikidata no labels | Tool Labs</title>
	<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="/wikidata-nolabels/style.css">
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script src="/wikidata-nolabels/js/sorttable.js"></script>
</head>
<body>
<nav class="navbar navbar-default" role="navigation">
  <!-- Brand and toggle get grouped for better mobile display -->
  <div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="/wikidata-nolabels/">Wikidata no labels</a>
  </div>

  <!-- Collect the nav links, forms, and other content for toggling -->
  <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
    <ul class="nav navbar-nav">
      <li class="active"><a href="/wikidata-nolabels/">Query</a></li>
      <li><a href="/wikidata-nolabels/help.html">Help</a></li>
    </ul>
    <ul class="nav navbar-nav navbar-right">
        <li class="navbar-logo"><a href="https://tools.wmflabs.org"><img title="Powered by Wikimedia Labs" src="//upload.wikimedia.org/wikipedia/commons/thumb/6/60/Wikimedia_labs_logo.svg/32px-Wikimedia_labs_logo.svg.png" /></a></li>
        <li class="navbar-logo"><a href="http://www.dereckson.be/"><img title="Developed by Dereckson" src="img/LoupDereckson-32.png" /></a></li>
    </ul>
  </div><!-- /.navbar-collapse -->
</nav>
<div id="main_content" class="container"><div class="row">
<?php
if (isset($_REQUEST['query']) || isset($_REQUEST['items'])) {
	echo "<h3>Your query</h3>\n";

	require('../lib/WikidataNoLabelsQuery.php');
	$languages = explode(" ", $languagesToPrint);
	try {
		$noLabelsQuery = new WikidataNoLabelsQuery($language, $languages);
		if ($query) {
			$noLabelsQuery->fillItemsFromWDQ($query);
		} elseif (WikidataNoLabelsQuery::isValidURL($items)) {
			$noLabelsQuery->fillItemsFromURL($items);
		} else {
			$itemsArray = explode("\n", $items);
			$noLabelsQuery->fillItems($itemsArray);
		}
		$noLabelsQuery->run();

		echo "<table class=\"sortable\">
<thead>
    <tr><th>Item</th>";
		foreach ($languages as $labelLanguage) {
			echo "<th>Label $labelLanguage</th>";
		}
		echo "</tr>
</thead>
<tbody>";
		foreach ($noLabelsQuery->results as $item) {
			echo '<tr><td><a href="https://www.wikidata.org/wiki/Q', $item['id'], "\">Q$item[id]</a></td>";
			foreach ($languages as $labelLanguage) {
				echo "<td>", $item["label$labelLanguage"], "</td>";
			}
			echo '</tr>';
		}
		echo "
</tbody>
</table>";

	} catch (Exception $ex) {
		echo "<p>", $ex->getMessage(), "</p>";
	}
}
?>
<h3>New query</h3>
<form role="form" method="POST">
  <h4>I. Get Wikidata items</h4>
  <div class="tabbable-panel">
	<div class="tabbable-line">
		<ul class="nav nav-tabs ">
			<li class="active">
				<a href="#tab_getitems_query" data-toggle="tab">
				WDQ API search</a>
			</li>
			<li>
			<a href="#tab_getitems_list" data-toggle="tab">
				Items list</a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="tab_getitems_query">
  <div class="form-group">
    <label for="query">Get Wikidata items with this WDQ query</label>
    <input id="query" name="query" value="<?= $query ?>" size="48" placeholder="A WDQ query, like claim[1080:81738]" class="form-control" />
  </div>
			</div>
			<div class="tab-pane" id="tab_getitems_list">
  <div class="form-group">
    <label for="items">Encode Wikidata items</label><br />
    <textarea id="items" name="items" placeholder="Q..." class="form-control" rows="8"><?= $items ?></textarea>
  </div>
			</div>
		</div>
	</div>
  </div>

  <h4>II. Define labels</h4>
  <div class="tabbable-panel">
  <div class="form-group">
    <label for="language">Without label in the following language</label>
    <input id="language" name="language" value="<?= $language ?>" size="4" class="form-control" />
  </div>
  <div class="form-group">
    <label for="languagesToPrint">Add columns to print labels in the following languages</label>
    <input id="languagesToPrint" name="languagesToPrint" value="<?= $languagesToPrint ?>" class="form-control" placeholder="Pick one or several languages with space as separator." />
  </div>
  </div>

  <div class="form-group">
    <button type="submit" class="btn btn-default">Submit</button>
  </div>
</form>
<hr />
<p>Please limit to small queries. The tool is not yet optimized for heavy queries.</p>
</div></div>
</body></html>
