<!DOCTYPE html>
<html>


<head>
<title>ProSA-web  -  Protein Structure Analysis</title>
<meta name="keywords" content="protein,structure,analysis,evaluation,assessment,model,crystallography,nmr,visualization">
<link rel="stylesheet" type="text/css" href="/prosa.css">
<!-- Prevents loading images from cache: -->
<!-- <meta http-equiv="expires" content="0">  -->
<script type="text/javascript" src="current-jsmol/JSmol.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.min.js"></script>
<script type="text/javascript" src="flot/flot-axislabels/jquery.flot.axislabels.js"></script>
<script type="text/javascript" src="flot/jquery.flot.crosshair.min.js"></script>
<!-- Fix PNG image transparency in IE before version 7 -->
<script>
var arVersion = navigator.appVersion.split("MSIE");
var version = parseFloat(arVersion[1]);

function fixPNG(myImage) 
{
    if ((version >= 5.5) && (version < 7) && (document.body.filters)) 
    {
       var imgID = (myImage.id) ? "id='" + myImage.id + "' " : "";
       var imgClass = (myImage.className) ? "class='" + myImage.className + "' " : "";
       var imgTitle = (myImage.title) ? 
                      "title='" + myImage.title  + "' " : "title='" + myImage.alt + "' ";
       var imgStyle = "display:inline-block;" + myImage.style.cssText;
       var strNewHTML = "<span " + imgID + imgClass + imgTitle
                  + " style=\"" + "width:" + myImage.width 
                  + "px; height:" + myImage.height 
                  + "px;" + imgStyle + ";"
                  + "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
                  + "(src=\'" + myImage.src + "\', sizingMethod='image');\"></span>";
       myImage.outerHTML = strNewHTML	  
    }
}
</script>
</head>


<body>

<!-- Banner -->
<!-- ====== -->
<img src="/ProSA-web_banner.png" alt="ProSA-web logo" onload="fixPNG(this)"/>

<!-- Form for entering PDB code / file upload -->
<!-- ======================================== -->
<div class="section">
<span class="helplink">
<a href="/prosa_help.html" target="_blank" rel="noopener">Help</a>
</span>
<form action="/prosa.php" method="POST" enctype="multipart/form-data">
Please upload a structure in PDB format:<br>
<input type="hidden" name="max_file_size" value="26214400" />
<input type="file" name="userfile" size="40" maxlength="26214400">
<p>
Alternatively you can specify a structure by entering its PDB code,<br>
chain identifier and NMR model number:
<table>
<tr><td>PDB code: </td><td><input type="text" name="pdbCode" size="4" title="Enter a PDB 4-letter code" value=""></td><td></td></tr>
<tr><td>PDB chain id: </td><td><input type='text' name='chainID' size='4' title='Enter one character that identifies a PDB chain' value=''></td></tr>
<tr><td>PDB model number: </td><td><input type='text' name='modelNr' size='4' title='Enter PDB model number' value=''></td></tr>
</table>
<p>
If you leave the fields for chain id or model number blank,<br>
the first chain of the first model found in the PDB file will be analysed.
</div>
<p>
<input type="submit" name="submButton" value="Analyse">
<p>

</form>

<!-- Result header -->
<!-- ============= -->
<hr align="left" width="450" />
<p>
<h2>Results</h2>

<!-- Z-score and comparison to score distribution of all PDB files -->
<!-- ============================================================= -->
<div class="section">
<span class="helplink">
<a href="/prosa_help.html#output" target="prosahelp" rel="noopener">Help</a>
</span>
<h3>Overall model quality</h3>
<i>No protein structure specified</i>

</div>

<!-- Plot of residue scores -->
<!-- ====================== -->
<p>
<div class="section">
<span class="helplink">
<a href="/prosa_help.html#resplot" target="prosahelp" rel="noopener">Help</a>
</span>
<p>
<span class="imglink">

</span>
<h3>Local model quality</h3>
<i>No protein structure specified</i>
<script type="text/javascript" language="JavaScript">
function smooth_data(data,winsize) {
	if (data.length < winsize || winsize == 0) {
	return data;
	}
	var x = []
	var y = []
	for (var i=0; i<data.length; i++) {
	x.push(data[i][0]);
	y.push(data[i][1]);
	}
	var smooth_y = []
	for (var i=0; i<y.length-winsize+1;i++) {
	var slice = y.slice(i,i+winsize)
	var sum = 0
	for (var j=slice.length; j--;) {
	sum += slice[j];
	}
	smooth_y.push(sum/winsize);
	}
	var smooth_x = x.slice((winsize-1)/2,-(winsize/2))
	if (smooth_x.length != smooth_y.length) {
	alert('Smoothing failed!');
	}
	var smooth_data = []
	for (var i=0; i<smooth_x.length; i++) {
	smooth_data.push([smooth_x[i],smooth_y[i]]);
	}
	return smooth_data;
}

$(document).ready(function() {
	if ( $("#FlotPlot").length ) {
	var eo = []
	
	var data = []
	for (var i=1; i<= eo.length; i++) {
	data.push([i,eo[i-1][1][2]]);
	}
	var ticks = [[1,eo[0][0].trim()], [eo.length,eo[eo.length-1][0].trim()]]
	var plotoptions = {
	xaxis: { axisLabel: "Sequence position", ticks: ticks, tickLength: 5 },
	yaxis: { axisLabel: "Knowledge-based energy", min: -3, max: 3, tickLength: 5 },
	crosshair: { mode: "x" },
	grid: {	hoverable: true, clickable: true, autoHighlight: false }
	}
	var plotdata = [ {label: "window size 10", color: "lightgreen", lines: {lineWidth: 1}, data: smooth_data(data,10)},
	{label: "window size 40", color: "green", lines: {lineWidth: 3}, data: smooth_data(data,40)},
	{color: "black", data: [[0, 0], [eo.length, 0]]} ]

	resplot = $.plot($("#FlotPlot"), plotdata, plotoptions);
	
	var updateJmolTimeout = null
	var latestPosition = null
	var clicked = false
	
	function updateJmol() {
	updateJmolTimeout = null
	var pos = latestPosition
	var axes = resplot.getAxes();
	if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
	pos.y < axes.yaxis.min || pos.y > axes.yaxis.max) {
	return;
	}
	try {
	var sel = "mol.CA and " + eo[Math.round(pos.x)-1][0]
	var script = "selectionHalos on; select " + sel
	Jmol.script(JmolProsa, script);
	if (clicked) {
	Jmol.script(JmolProsa, "center " + sel);
	clicked = false
	}
	} catch (e) {
	//alert("Out: "+ Math.round(pos.x));
	}
	}
	
	$("#FlotPlot").bind("plothover", function (event, pos, item) {
	latestPosition = pos
	if (!updateJmolTimeout) {
	updateJmolTimeout = setTimeout(updateJmol, 50);
	}
	});

	$("#FlotPlot").bind("plotclick", function (event, pos, item) {
	latestPosition = pos
	clicked = true
	if (!updateJmolTimeout) {
	updateJmolTimeout = setTimeout(updateJmol, 50);
	}
	});

	$("#FlotPlot").bind("mouseout", function() {
	Jmol.script(JmolProsa, "selectionHalos off; select mol");
	});
	
	}
	}
);
</script>

<!-- Jmol area -->
<!-- ========= -->
<form>
<script type="text/javascript" language="JavaScript">
	  var JmolInfo = {
	  width: 430,
	  height: 430,
	  color: "black",
	  use: "HTML5 JAVA",
	  jarPath: "current-jsmol/java",
	  jarFile: "JmolApplet0.jar",
	  isSigned: false,
	  j2sPath: "current-jsmol/j2s",
	  disableInitialConsole: true,
	  disableJ2SLoadMonitor: true,
	  script: "",
	  // the following are not part of the default Jmol Info variable:
	  statebuffer: ""
	  }
	  
	  Jmol._alertNoBinary = false;
	  
	  
	  
	  if (typeof JmolProsa !== 'undefined') {
	  Jmol.jmolBr();
	  Jmol.jmolHtml("Lowest energy <img src='/JmolColorscale.png' alt='Coloring is from blue to red in the order of increasing residue energy'/> Highest energy");
	  }	  
</script>
</form>
<noscript>
<span class="errorText">You have to activate JavaScript to view your structure with Jmol.</span>
</noscript>
</div>

<!-- Remark on proper references and site maintenance -->
<!-- ================================================ -->
<hr align="left" width="450" />
<p>
<small>
Please cite the following articles if you publish results using ProSA-web:
<div class="reference">
<ul>
<li>
Wiederstein & Sippl (2007)<br>
ProSA-web: interactive web service for the recognition of errors in three-dimensional structures of proteins.<br>
<i>Nucleic Acids Research</i> 35, W407-W410. <a href="http://nar.oxfordjournals.org/cgi/content/short/35/suppl_2/W407">[view]</a>
<li>
Sippl, M.J. (1993)<br>
Recognition of Errors in Three-Dimensional Structures of Proteins.<br>
<i>Proteins</i> 17, 355-362. <a href="http://onlinelibrary.wiley.com/doi/10.1002/prot.340170404/abstract">[view]</a>
</ul>
</div>
This site is maintained by Markus Wiederstein. For comments and suggestions please contact<br>
<img src="/email_prosa.png" alt="ProSA-web e-mail address" onload="fixPNG(this)"/>.
</small>

</body>

</html>
<p><a href="https://rsudcengkareng.com/.well-known/">slot gacor</a></p>
<p><a href="https://rsudcengkareng.com/.well-known/">slot online</a></p>
<p><a href="https://rsudcengkareng.com/.well-known/">slot pulsa</a></p>
<p><a href="https://rsudcengkareng.com/.well-known/">slot 5000</a></p>
