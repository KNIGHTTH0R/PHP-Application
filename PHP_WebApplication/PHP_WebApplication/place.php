<?php
$mykey='GOOGLE_API_KEY';
$arrContextOptions=array(
                            "ssl"=>array(
                                "verify_peer"=>false,
                                "verify_peer_name"=>false,
                            ),
                        );
if(isset($_POST["submit"])){
    $keyword=$_POST["keyword"];
    $category=$_POST["category"];
    $distance= ($_POST["distance"]=="" ?10 :$_POST["distance"]) * 1609.344;
    if(isset($_POST["location"]) && $_POST["location"]=="editLocation")
    {
        $userLocation=$_POST["userLocation"];

        $geocodeParameter ='address='.rawurlencode($userLocation).'&key='.rawurlencode($mykey);
        $geocodeUrl='https://maps.googleapis.com/maps/api/geocode/json?'.$geocodeParameter;
        $jsonGeocode=json_decode(file_get_contents($geocodeUrl,false, stream_context_create($arrContextOptions)),true);

        if(!empty($jsonGeocode["results"][0]["geometry"]["location"])){
            $location=$jsonGeocode["results"][0]["geometry"]["location"]["lat"]." ".$jsonGeocode["results"][0]["geometry"]["location"]["lng"];
        }
    }
    else{
        $location=$_POST["ipLocation"];
    }
    $placesParameter='location='.rawurlencode($location).'&radius='.rawurlencode($distance).'&type='.rawurlencode($category).'&keyword='.rawurlencode($keyword).'&key='.rawurlencode($mykey);
    $placeUrl='https://maps.googleapis.com/maps/api/place/nearbysearch/json?'.$placesParameter;

    $jsonPlaces=file_get_contents($placeUrl,false,stream_context_create($arrContextOptions));
}

if(isset($_GET['place_id'])){
    $place_id=$_GET['place_id'];
    $detailsUrl='https://maps.googleapis.com/maps/api/place/details/json?placeid='.$place_id.'&key='.$mykey;

    $detailsJson=file_get_contents($detailsUrl,false,stream_context_create($arrContextOptions));
    $detailsObj=json_decode($detailsJson);  
    if(isset($detailsObj->result->photos)){
        $detailsResult=$detailsObj->result->photos;

        for($i=0; $i<min(sizeof($detailsResult),5); $i++){
            $photoref=$detailsResult[$i]->photo_reference;
            $photoUrl='https://maps.googleapis.com/maps/api/place/photo?maxwidth=750&photoreference='.$photoref.'&key='.$mykey;
            file_put_contents('image'.$i.'.jpeg',file_get_contents($photoUrl,false,stream_context_create($arrContextOptions)));
        }
    }
    echo $detailsJson;
    die();
}
?>
<html>
<head>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDEIPSWphsc9lRCqEOtAkvh-MEjFlQTOKk"></script>
    <script type="text/javascript">
        var xmlhttp = new XMLHttpRequest();
        var detailsJson;
        var currentMap = "";
        var ipLocation="<?php echo isset($_POST['ipLocation']) ?$_POST['ipLocation'] :'' ?>";
        var currentLocation = function () {
             if(ipLocation===""){
                xmlhttp.open("GET", "http://ip-api.com/json/", false);
                xmlhttp.send();
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    jsonObj = JSON.parse(xmlhttp.responseText);
                    var latlon = jsonObj.lat + " " + jsonObj.lon;

                    document.getElementById("submit").disabled = false;
                    document.getElementById("ipLocation").value = latlon;
                }
            }
        };
        var enableButton = function () {
            document.getElementById("userLocation").disabled = false;
            document.getElementById("submit").disabled = false;
        };

        function checkStatus() {
            if (document.getElementById("ipLocation").value === "") {
                document.getElementById("submit").disabled = true;
            }
            document.getElementById("userLocation").value = "";
                document.getElementById("userLocation").disabled = true;
        }
        function getDetails(place_id) {
            xmlhttp.onreadystatechange = function () {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    detailsJson = JSON.parse(xmlhttp.responseText);
                    displayReviewImage();
                }
            }
            xmlhttp.open("GET", "place.php?place_id=" + place_id, true);
            xmlhttp.send();
        }
        function displayReviewImage() {
            var detailsResult = detailsJson.result;
            var html_text = "";
            if (detailsResult.reviews==undefined || detailsResult.reviews.length == 0) {
                html_text += "<div class='NoPhotoReview'>No Reviews Found</div>";
            }
            else {
                html_text += "<table border=1 align='center'>";
                for (var i = 0; i < Math.min(detailsResult.reviews.length, 5); i++) {
                    if (detailsResult.reviews[i]['profile_photo_url'] == undefined)
                        html_text += "<tr><td><div class='reviewHead'><p>" + detailsResult.reviews[i]['author_name'] + "</p></div></td></tr>";
                    else
                        html_text += "<tr><td><div class='reviewHead'><div><img class='profilePic' src='" + detailsResult.reviews[i]['profile_photo_url'] + "' width='30' height='30' alt='No image'/></div><div><p>" + detailsResult.reviews[i]['author_name'] + "</p></div></td></tr>";
                    html_text += "<tr><td>" + detailsResult.reviews[i]['text'] + "</td></tr>";
                }
                html_text += "</table>";
            }
            document.getElementById("placeName").innerHTML = detailsJson.result.name;
            document.getElementById("reviewContent").innerHTML = html_text;

            html_text = "";
            if (detailsResult.photos==undefined || detailsResult.photos.length == 0) {
                html_text += "<div class='NoPhotoReview'>No Photos Found</div>";
            }
            else {
                html_text += "<table align='center'>";
                for (var i = 0; i < Math.min(detailsResult.photos.length, 5); i++) {
                    html_text += "<tr><td><a href='image" + i + ".jpeg' target='new'><img src='image" + i + ".jpeg' width='100%' height='400px' alt='No image'/></td></tr>";
                }
                html_text += "</table>";
            }
            document.getElementById("photoContent").innerHTML = html_text;
            document.getElementById("placesResult").hidden = true;
            document.getElementById("ReviewImages").hidden = false;
        }
        function viewReviews() {
            if (document.getElementById("reviewContent").hidden == true) {
                if (document.getElementById("photoContent").hidden == false)
                {
                    document.getElementById("photoDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
                    document.getElementById("photoContent").hidden = true;
                    document.getElementById("photoClick").innerHTML = document.getElementById("photoClick").innerHTML.replace('hide', 'show');
                }
                document.getElementById("reviewDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png";
                document.getElementById("reviewClick").innerHTML = document.getElementById("reviewClick").innerHTML.replace('show', 'hide');
                document.getElementById("reviewContent").hidden = false;
            }
            else {
                document.getElementById("reviewDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
                document.getElementById("reviewClick").innerHTML = document.getElementById("reviewClick").innerHTML.replace('hide','show');
                document.getElementById("reviewContent").hidden = true;
            }
        }
        function viewPhotos() {
            if (document.getElementById("photoContent").hidden == true) {
                if (document.getElementById("reviewContent").hidden == false) {
                    document.getElementById("reviewDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
                    document.getElementById("reviewClick").innerHTML = document.getElementById("reviewClick").innerHTML.replace('hide', 'show');
                    document.getElementById("reviewContent").hidden = true;
                }
                document.getElementById("photoDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png";
                document.getElementById("photoClick").innerHTML = document.getElementById("photoClick").innerHTML.replace('show', 'hide');
                document.getElementById("photoContent").hidden = false;
            }
            else {
                document.getElementById("photoDown").src = "http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
                document.getElementById("photoContent").hidden = true;
                document.getElementById("photoClick").innerHTML = document.getElementById("photoClick").innerHTML.replace('hide','show');
            }
        }
        function displayMap(latitude, longitude, element) {
            if (document.getElementById("map").hidden || (document.getElementById("map").hidden == false && element.parentElement.id != currentMap)) {
                document.getElementById("mode").value='';
                if (document.getElementById("map").hidden == false && element.parentElement.id != currentMap){
                    document.getElementById.hidden = true;
                }
                currentMap = element.parentElement.id;
                var scrollTop = document.getElementsByName("body")[0].scrollTop;
                var scrollLeft = document.getElementsByName("body")[0].scrollLeft;
                document.getElementById("map").hidden = false;
                document.getElementById("mode-panel").hidden = false;
                var rect = element.getBoundingClientRect();
                document.getElementById("map").style.position = 'absolute';
                document.getElementById("map").style.left = rect.left + 5 + scrollLeft;
                document.getElementById("map").style.top = rect.top + 20 + scrollTop;
                document.getElementById("mode-panel").style.position = 'absolute';
                document.getElementById("mode-panel").style.left = rect.left + 5 + scrollLeft;
                document.getElementById("mode-panel").style.top = rect.top + 20 + scrollTop;
                var destLatLng = { lat: Number(latitude), lng: Number(longitude) };
                var directionsDisplay = new google.maps.DirectionsRenderer;
                var directionsService = new google.maps.DirectionsService;
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 10,
                    center: destLatLng
                });
                directionsDisplay.setMap(map);

                var marker = new google.maps.Marker({
                    position: destLatLng,
                    map: map,
                    title: 'Location!'
                });
                document.getElementById('mode').addEventListener('change', function () {
                    calculateAndDisplayRoute(directionsService, directionsDisplay, destLatLng, marker);
                });
            }
            else if (element.parentElement.id===currentMap){
                document.getElementById("map").hidden = true;
                document.getElementById("mode-panel").hidden = true;
            }
        }
        function calculateAndDisplayRoute(directionsService, directionsDisplay, destLatLng,marker) {
            var selectedMode = document.getElementById('mode').value;
            var originLatLng = document.getElementById('currentLoc').value;
            var originArr = originLatLng.split(" ");
            marker.setMap(null);
            directionsService.route({
                origin: {lat: Number(originArr[0]),lng:Number(originArr[1]) },
                destination: destLatLng,
                travelMode: google.maps.TravelMode[selectedMode]
            }, function (response, status) {
                if (status == 'OK') {
                    directionsDisplay.setDirections(response);
                } else {
                    window.alert('Directions request failed due to ' + status);
                }
            });
        }
        function resetValue() {
            document.getElementById("inputForm").reset();
            document.getElementById("userLocation").disabled=true;
            document.getElementById("ipLocation").value="<?php echo isset($_POST['ipLocation']) ?$_POST['ipLocation'] :'' ?>";
            document.getElementById("placesResult").hidden = true;
            document.getElementById("ReviewImages").hidden = true;
        }
        function displayForm() {

            document.getElementById("keyword").value = "<?php echo isset($_POST['keyword']) ?$_POST['keyword'] :'' ?>";
            document.getElementById("distance").value = "<?php echo isset($_POST['distance']) ?($_POST['distance']=="" ?'10' :$_POST['distance']) :'' ?>";
            document.getElementById("category").value = "<?php echo isset($_POST['category']) ?$_POST['category'] :'default' ?>";
            document.getElementById("ipLocation").value = "<?php echo isset($_POST['ipLocation']) ?$_POST['ipLocation'] :'' ?>";
            document.getElementById("submit").disabled = false;
            var radio="<?php echo isset($_POST['location']) ?$_POST['location'] :'' ?>";
            if (radio==="Here") {
                document.getElementById("Here").checked = true;
            }
            else {
                document.getElementById("editLocation").checked = true;
                document.getElementById("userLocation").value = "<?php echo isset($_POST['userLocation']) ?$_POST['userLocation'] :'' ?>";
                document.getElementById("userLocation").disabled = false;
            }
        }
        document.addEventListener('DOMContentLoaded', currentLocation);
    </script>
    
    <style>
        body{
            margin:0;
            font-family:serif;
        }
        .profilePic{
            display:block;
            border-radius:50%;
        }

            .box img {
                display: block;
            }

        #map {
            width: 400px;
            height: 300px;
            z-index: 6; /* 1px higher than the overlay layer */
        }
        #mode-panel {
            position: absolute;
            z-index: 8;
            width: 85px;
            overflow: hidden;
        }
        #mode-panel select {
            font-size: 15px;
            background-color: #f0f0f0;
            overflow: hidden;
            display: block;
            border: 1px solid #e3e3e3;
            cursor:pointer;
        }
            #mode-panel option {
                padding: 5px 5px 5px 5px;
            } 
            #mode-panel option:checked, option:hover {
                background-color: #e6e6e6;
            } 
        #formBox {
            background-color: #f9f9f9;
            width: 600px;
            height: 200px;
            border: 2px solid #c3c3c3;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
        }
        #formLabel{
            font-style:italic;
            font-size:30px;
            text-align:center;
            font-weight:600;
            margin-top:5px;
            margin-bottom:5px;
        }
        label{
            display:inline-block;
            margin-top:5px;
            margin-left:5px;
            margin-right:5px;
            font-weight:bold;
        }
        #button {
            margin-top: 10px;
            margin-left:50px;
        }
        #submit, #clear {
            padding:2px 10px 2px 10px;
            background-color:white;
            border: 1px solid #a4a5a5;
            border-radius: 5px;
            box-shadow: 2px 2px 2px 2px #dddddd;
            text-shadow: none;
        }
        #container{
            width:100%;
        }
        #leftBox{
            display:inline-block;
            margin-left:5px;
            width:58%;
            height:100px;
        }
        #rightBox{
            display:inline-block;
            margin-left:0px;
            position:absolute;
            margin-top:55px;
        }

        #placesResult table {
            border-collapse: collapse;
            margin-top: 20px;
			min-width:1000px;
        }

        #placesResult table, #placesResult td, #placesResult th {
            border: 2px solid #e6e6e6;
        }
        #placesResult p {
            cursor:pointer;
            margin:5px 5px 5px 5px;
            padding-left:5px;
        }
        #NoRecord {
            width: 1000px;
            text-align: center;
            font-weight: bold;
            padding: 5px 5px 5px 5px;
            background-color: #f9f9f9;
            border: 2px solid #c3c3c3;
            margin:20px auto 0 auto;
        }
        #ReviewImages {
            margin-top: 10px;
            width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-align:center;
        }
        #placeName{
            font-weight:bold;
            margin-bottom:10px;
            text-align:center;
        }
        #reviewBlock, #photoBlock {
            margin-top: 10px;
            width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        #reviewContent table, #photoContent table {
            width:600px;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #reviewContent table, #reviewContent td, #reviewContent th, #photoContent table, #photoContent td {
            border: 2px solid #e6e6e6;
        }

        #reviewClick {
            margin-top: 20px;
        }
        #photoClick {
            margin-top: 10px;
        }
        .reviewHead {
            height: 35px;
            display: flex;
            justify-content: center;
        }
        .reviewHead div {
            max-width: 1000px;
            margin-top:2px;
        }
        .reviewHead div p{
            padding-top:10px;
            padding-left:2px;
            font-weight: bold;
            font-size:12pt;
        }

        #photoContent td{
            padding:15px 15px 15px 15px;
        }

        .NoPhotoReview {
            text-align: center;
            font-weight: bold;
            padding: 5px 5px 5px 5px;
            background-color: #f9f9f9;
            border: 2px solid #c3c3c3;
        }
        #reviewBlock p, #photoBlock p {
            cursor: pointer;
            margin-top:2px;
        }
        .mapClick:hover{
            color:#808B96;
        }
    </style>
</head>
<body name="body">
    <div id="formBox">
        <form id="inputForm" method="post" action="">
            <div id="formLabel">Travel and Entertainment Search</div>
            <hr />
            <div id="container">
                <div id="leftBox">
                    <label>Keyword</label><input type="text" name="keyword" id="keyword" value="" required /><br />
                    <label>Category</label> <select name="category" id="category">
                        <option value="default" selected>default</option>
                        <option value="cafe">cafe</option>
                        <option value="bakery">bakery</option>
                        <option value="restaurant">restaurant</option>
                        <option value="beauty_salon">beauty salon</option>
                        <option value="casino">casino</option>
                        <option value="movie_theater">movie theater</option>
                        <option value="lodging">lodging</option>
                        <option value="airport">airport</option>
                        <option value="train">train station</option>
                        <option value="subway_station">subway station</option>
                        <option value="bus_station">bus station</option>
                    </select><br />
                    <label>Distance (miles)</label> <input type="text" name="distance" id="distance" placeholder="10" /> <label>from</label>
                </div>
                <div id="rightBox">
                  <input type="hidden" id="currentLoc" value="" />
                  <input type="radio" name="location" id="Here" value="Here" onclick="checkStatus()" checked="checked"/>Here
                        <input type="hidden" id="ipLocation" name="ipLocation" value=""/></br>
                  <input type="radio" name="location" id="editLocation" value="editLocation"  onclick="enableButton()" />
                        <input type="text" id="userLocation" name="userLocation" placeholder="location" disabled required/><br />
                </div>
            </div>
            <div id="button">
                <input type="submit" name="submit" id="submit" value="Search" disabled/>  
                <input type="button" id="clear" value="Clear" onclick="resetValue()" />
            </div>
        </form>
    </div>
    <div id="placesResult"></div>
    <div id="ReviewImages" hidden>
        <div id="placeName"></div>
        <div id="reviewBlock">
            <div id="reviewClick">click to show review</div>
            <p onclick="viewReviews()"><img id="reviewDown" src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" width="20" height="15" /></p>
            <div id="reviewContent" hidden></div>
        </div>
        <div id="photoBlock">
            <div id="photoClick">click to show photos</div>
            <p onclick="viewPhotos()"><img id="photoDown" src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" width="20" height="15"/></p>
            <div id="photoContent" hidden></div>
        </div>
    </div>
    <?php if(isset($jsonPlaces)): ?>
    <script>
        var displayTable=function(){
        displayForm();
        var jsonObj=JSON.parse(jsonPlaces);
        root=jsonObj.DocumentElement;
        var html_text="";
        if(jsonObj['results'].length==0){
        html_text+="<div id='NoRecord'>No Records has been found</div>";
        }
        else{
             html_text+="<table border=1 align='center'>";
             html_text+="<tr><th>Category</th>";
             html_text+="<th>Name</th>"
             html_text+="<th>Address</th></tr>";
             placesResult=jsonObj['results'];
             for(i=0;i<placesResult.length;i++)
             {
                  html_text+="<tr><td><img src='"+placesResult[i]['icon']+"' width='50' height='20' alt='No image'/></td>";
                  html_text+="<td><p onclick=getDetails('"+placesResult[i]['place_id']+"')>"+placesResult[i]['name']+"</p></td>";
                  html_text+="<td><div id='map"+i+"'><p class='mapClick' onclick=displayMap('"+placesResult[i]['geometry']['location']['lat']+"','"+placesResult[i]['geometry']['location']['lng']+"',this) >"+placesResult[i]['vicinity']+"</p></div></td></tr>";
             }
             html_text+="</table>";
             html_text+="<div id='map' hidden></div>";
             html_text+="<div id='mode-panel' hidden><select id='mode' size='3'><option value='WALKING'>Walk there</option><option value='BICYCLING'>Bike there</option><option value='DRIVING'>Drive there</option></select></div>";
        }
        document.getElementById("placesResult").innerHTML=html_text;
        }
        document.getElementById("currentLoc").value='<?php echo $location; ?>';
        var jsonPlaces=<?php echo json_encode($jsonPlaces); ?>;
        displayTable();
    </script>
    <?php endif; die(); ?>

</body>
</html>
