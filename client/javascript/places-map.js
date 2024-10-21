function initMap() {
    const position = { lat: -33.8688, lng: 151.2195 };
    const existingPlace = document.getElementById("place-id");

    const map = new google.maps.Map(document.getElementById("map-field"), {
        center: position,
        zoom: 13,
        mapId: "DEMO_MAP_ID"
    });

    const input = document.getElementById("pac-input");
    const autocomplete = new google.maps.places.Autocomplete(input);

    autocomplete.bindTo("bounds", map);

    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    const infowindow = new google.maps.InfoWindow({content: null, ariaLabel: 'Test'});
    const infowindowContent = document.getElementById("infowindow-content");

    infowindow.setContent(infowindowContent);

    const marker = new google.maps.marker.AdvancedMarkerElement({ 
        map: map,
        position: null,
    });

    if (existingPlace.value) {
        const geocoder = new google.maps.Geocoder();
        geocoder
            .geocode({ placeId: existingPlace.value })
            .then(({ results }) => {
                const existingCoords = results[0].geometry.location;
                marker.position = existingCoords;
                map.setCenter(existingCoords);
            });
    }

    autocomplete.addListener("place_changed", function() {
        marker.position = null;
        infowindow.close();
    
        const place = autocomplete.getPlace();
    
        if (!place.geometry || !place.geometry.location) {
            return;
        }
    
        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        marker.position = place.geometry.location;
    
        infowindowContent.children.namedItem("place-id").textContent = place.place_id;
        // set href
        infowindowContent.children.namedItem("place-id").href = `javascript:setPlaceValue('${place.place_id}')`;
        infowindowContent.children.namedItem("place-address").textContent = place.formatted_address;
        infowindow.open(map, marker);

        let heading = document.createElement('div');
        heading.textContent = place.name;
        heading.style.fontWeight = 'bold';

        infowindow.setHeaderContent(heading);
    });
}

function setPlaceValue(placeId) {
    document.getElementById("Form_ItemEditForm_PlaceID").value = placeId;
}
