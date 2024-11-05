<div id="review-badge" class="place-badge-outer">
    <div class="place-badge-inner">

        <div class="info-left">
            <img src="{$Image}" alt="{$Title}" width="100" height="100" title="{$Title}" class="badge-image">
        </div>

        <div class="info-right">
            <div class="info-place">
                <a href="{$Link}" target="_blank" rel="nofollow noopener">
                    <span>{$Title}</span>
                </a>
            </div>

            <div>
                <span class="rating-text"><b>{$Rating}</b></span>
                <span class="google-stars">
                    <% loop $Stars %>
                        <div class="rating-star rating-{$Value}-star"></div>
                    <% end_loop %>
                </span>
            </div>

            <div class="based-on">Based on {$Total} review<% if $Total != 1 %>s<% end_if %></div>

            <div class="review-link">
                <a href="https://search.google.com/local/writereview?placeid={$PlaceID}" target="_blank">
                    Review Us
                </a>
            </div>
        </div>

    </div>
</div>

<% require css("iliain/silverstripe-google-config: client/css/badge.css") %>
