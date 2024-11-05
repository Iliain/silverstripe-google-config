<div id="map-holder">
    <% if $MapType == 'places' %>
        <div style="display: none">
            <input
                id="pac-input"
                class="controls"
                type="text"
                placeholder="Enter a location"
            />
        </div>
    <% end_if %>

    <% if $PlaceID %>
        <div style="display: none">
            <input type="hidden" 
                id="place-id" 
                value="{$PlaceID}" 
            />
        </div>
    <% end_if %>

    <div id="map-field"><a href="javascript:window.location.href=window.location.href">Click to reload map</a></div>

    <% if $MapType == 'places' %>
        <div style="display: none;">
            <div id="infowindow-content">
                <span id="place-address"></span>
                <br /><br />
                <strong>Place ID:</strong> <a href="" id="place-id"></a>
            </div>
        </div>
    <% end_if %>
</div>